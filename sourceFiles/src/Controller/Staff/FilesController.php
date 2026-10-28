<?php
declare(strict_types=1);

namespace App\Controller\Staff;

use App\Controller\AppController;
use App\Model\Table\FilesTable;
use Cake\Event\EventInterface;
use Cake\Http\CallbackStream;
use Cake\Http\Exception\NotFoundException;
use Cake\Routing\Router;

/**
 * Files block: upload, list, view, download and thumbnails, owner only.
 * Pushing to S3 runs from cron (config/cron.php `files_push`); the "push-s3" link
 * pushes one of the user's own files through the scoped FilesTable::pushOne().
 * See docs/features/MVP-Files-S3-Offload.md.
 *
 * @property \App\Model\Table\FilesTable $Files
 */
class FilesController extends AppController
{
    // Shown on this page and on the public CodeBlocks page (CodeBlocksController::filesS3Offload)
    public const CODE_BLOCK_SUBTITLE = 'Upload a file: it is saved to the private path and works at once. A cron job then pushes it to S3 (AWS or DigitalOcean Spaces). Images get a thumbnail, other files an icon.';

    public const CODE_BLOCK_FILES = [
        'SQL: config/schema/2026-10-27.sql' => CONFIG . 'schema/2026-10-27.sql',
        'Model: src/Model/Table/FilesTable.php' => APP . 'Model/Table/FilesTable.php',
        'Entity: src/Model/Entity/File.php' => APP . 'Model/Entity/File.php',
        'Controller: src/Controller/Staff/FilesController.php' => APP . 'Controller/Staff/FilesController.php',
        'Template (one file; split into elements once in a project): templates/Staff/Files/index.php' => APP . '../templates/Staff/Files/index.php',
        'JS: webroot/js/files-s3-offload.js' => WWW_ROOT . 'js/files-s3-offload.js',
        'CSS: webroot/css/files-s3-offload.css' => WWW_ROOT . 'css/files-s3-offload.css',
        'Cron: config/cron.php' => CONFIG . 'cron.php',
    ];

    public function beforeFilter(EventInterface $event)
    {
        $this->set('webroot', Router::url('/'));
        $this->viewBuilder()->setLayout('code_blocks');
        parent::beforeFilter($event);
    }

    public function index()
    {
        $userId = (int)$this->getUserId();
        $this->set('filesSetup', $this->Files->setupStatus($userId));
        $this->set('files', $this->index_decorate($this->Files->findAllForUser($userId)['files']));
        $this->set('filesMaxBytes', FilesTable::MAX_UPLOAD_BYTES);
        $this->set('filesMaxMb', (int)(FilesTable::MAX_UPLOAD_BYTES / 1048576));
        // With the language in it: a URL without one is redirected, and a redirect turns the POST into a GET
        $this->set('filesUploadUrl', ['action' => 'add', 'language' => $this->request->getParam('language')]);
        $this->index_codeBlocks();
    }

    /**
     * View-shaping only: turn the Table's thumbnail info into ready-to-print URLs.
     */
    private function index_decorate(array $files): array
    {
        $language = $this->request->getParam('language');
        foreach ($files as $file) {
            $file->thumb_src = $file->thumb_icon === null
                ? Router::url(['action' => 'thumb', $file->id, 'language' => $language])
                : Router::url('/img/files-s3-offload/' . $file->thumb_icon);
            $file->view_url = Router::url(['action' => 'view', $file->id, 'language' => $language]);
            $file->download_url = Router::url(['action' => 'download', $file->id, 'language' => $language]);
            // Array, not a string: Form->postLink() adds the base path itself
            $file->push_url = $file->can_push ? ['action' => 'push', $file->id, 'language' => $language] : null;
            $file->delete_url = $file->can_delete ? ['action' => 'delete', $file->id, 'language' => $language] : null;
        }

        return $files;
    }

    private function index_codeBlocks(): void
    {
        $this->set('codeBlocks_title', 'Files S3 Offload');
        $this->set(
            'codeBlocks_subTitle',
            self::CODE_BLOCK_SUBTITLE
        );
        $this->set('codeBlocks_renderFiles', self::CODE_BLOCK_FILES);
    }

    public function add()
    {
        $this->request->allowMethod(['post']);
        $upload = $this->request->getUploadedFiles()['file'] ?? null;
        $result = $this->Files->saveUpload((int)$this->getUserId(), $upload);
        $result['STATUS'] === 200 ? $this->Flash->success($result['MSG']) : $this->Flash->error($result['MSG']);

        return $this->redirect(['action' => 'index', 'language' => $this->request->getParam('language')]);
    }

    /**
     * The "push-s3" link: push this one file to S3 now (POST only, owner only).
     */
    public function push($id = null)
    {
        $this->request->allowMethod(['post']);
        $result = $this->Files->pushOne((int)$id, (int)$this->getUserId());
        $result['STATUS'] === 200 ? $this->Flash->success($result['MSG']) : $this->Flash->error($result['MSG']);

        return $this->redirect(['action' => 'index', 'language' => $this->request->getParam('language')]);
    }

    /**
     * Delete one of the user's own files: S3 copies, local files and the row (POST only).
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        if ((int)$id <= 0) {
            throw new NotFoundException(); // the model throws on a missing id; a bad URL is just a 404
        }
        $result = $this->Files->deleteForUser((int)$id, (int)$this->getUserId());
        $result['STATUS'] === 200 ? $this->Flash->success($result['MSG']) : $this->Flash->error($result['MSG']);

        return $this->redirect(['action' => 'index', 'language' => $this->request->getParam('language')]);
    }

    public function view($id = null)
    {
        return $this->serve((int)$id, 'file', false);
    }

    public function download($id = null)
    {
        return $this->serve((int)$id, 'file', true);
    }

    public function thumb($id = null)
    {
        return $this->serve((int)$id, 'thumb', false);
    }

    /**
     * Local disk first, then S3. Another user's file, an unknown id and a row
     * whose storage is missing all give the same 404.
     */
    private function serve(int $id, string $which, bool $download)
    {
        $info = $this->Files->serveInfo($id, (int)$this->getUserId(), $which);
        if ($info['STATUS'] !== 200) {
            throw new NotFoundException();
        }
        // File sessions lock: release it so a long download does not block other tabs
        $this->request->getSession()->close();

        $disposition = ($download ? 'attachment' : 'inline') . '; filename="' . $info['name'] . '"';
        $response = $this->response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', $which === 'thumb' ? 'private, max-age=86400' : 'private');

        if ($info['local'] !== null) {
            // withFile() handles Range / 206 itself
            return $response->withFile($info['local'])
                ->withType($info['mime'])
                ->withHeader('Content-Disposition', $disposition);
        }

        return $this->serve_remote($response, $info, $disposition);
    }

    private function serve_remote($response, array $info, string $disposition)
    {
        $remote = $this->Files->openRemote($info['key'], $this->request->getHeaderLine('Range'));
        if ($remote['STATUS'] !== 200) {
            throw new NotFoundException();
        }
        $body = $remote['body'];
        // Stream in chunks instead of loading the whole object into memory
        $stream = new CallbackStream(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(1048576);
                flush();
            }

            return '';
        });

        $response = $response->withBody($stream)
            ->withType($info['mime'])
            ->withHeader('Content-Disposition', $disposition)
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Content-Length', (string)$remote['length']);
        if ($remote['contentRange'] !== '') {
            $response = $response->withStatus(206)->withHeader('Content-Range', $remote['contentRange']);
        }

        return $response;
    }
}
