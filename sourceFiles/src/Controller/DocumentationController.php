<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\DocumentationService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Routing\Router;

/**
 * Documentation (SetupCase core, starter shape)
 *
 * Mirrors link-shared Google Docs to PDFs on this domain and serves them
 * through a PDF.js viewer with optional page jumps.
 * See docs/features/MVP-documentation.md.
 *
 * WARNING - PRIVACY:
 *  In SetupCase core this controller has NO prefix and is PUBLIC on purpose
 *  (demo only, non-sensitive demo doc). On client projects it MUST be moved
 *  under a logged-in prefix (e.g. src/Controller/Staff/, namespace
 *  App\Controller\Staff). Without a prefix, anyone with the URL can read
 *  the client's documentation.
 *
 * This is the deliberately minimal form:
 *  - regenerate is a GET link with ?code= (MVP exception to the AGENTS.md
 *    POST rule; code from DOCUMENTATION_REGENERATE_CODE, deterrence only)
 *  - config lives in DocumentationService, no database
 *  - only link-shared ("anyone with the link") docs are supported
 *
 * On larger projects, move regenerate into the admin area and split this
 * into multiple controllers as needed.
 */
class DocumentationController extends AppController
{
    private DocumentationService $Documentation;

    public function initialize(): void
    {
        parent::initialize();
        $this->Documentation = new DocumentationService();
    }

    public function beforeFilter(EventInterface $event)
    {
        $this->set('webroot', Router::url('/'));
        $this->viewBuilder()->setLayout('code_blocks');
        parent::beforeFilter($event);
    }

    public function view($key = null, $page = null)
    {
        $result = $this->Documentation->getDocument((int)$key);
        if ($result['STATUS'] !== 200) {
            throw new NotFoundException($result['MSG']);
        }
        $page = max(1, (int)$page);
        // ?embed=1 is used by the modal iframe (element view_modal): bare layout, no sidebar
        $embed = (bool)$this->request->getQuery('embed');
        if ($embed) {
            $this->viewBuilder()->setLayout('documentation_embed');
        }

        $this->set('docTitle', $result['title']);
        $this->set('docExists', $result['exists']);
        $this->set('docMsg', $result['MSG']);
        $this->set('docPage', $page);
        $this->set('docEmbed', $embed);
        $this->set('docFileUrl', $this->documentationUrl('file', [(int)$key], ['v' => $result['version']]));
        $this->set('docDownloadUrl', $this->documentationUrl('file', [(int)$key], ['v' => $result['version'], 'download' => 1]));
        $this->set('docOwnTabUrl', $this->documentationUrl('view', [(int)$key, $page]));
        // Shareable link per page: base + '/' + page. Full URL so it can be pasted anywhere.
        $this->set('docPageBaseUrl', $this->documentationUrl('view', [(int)$key], [], true));
        // The modal iframe must not rewrite its own (embed) URL.
        $this->set('docUpdateAddressBar', !$embed);
    }

    public function file($key = null)
    {
        $result = $this->Documentation->getFile((int)$key);
        if ($result['STATUS'] !== 200) {
            throw new NotFoundException($result['MSG']);
        }

        // ?download=1 saves the file instead of showing it in the browser
        $disposition = $this->request->getQuery('download') ? 'attachment' : 'inline';

        return $this->response
            ->withFile($result['path'])
            ->withType('pdf')
            ->withHeader('Content-Disposition', $disposition . '; filename="' . $result['filename'] . '"')
            ->withHeader('Cache-Control', 'private, max-age=86400');
    }

    /**
     * MVP exception: state change over GET, gated by ?code= (deterrence only).
     * Documented in docs/features/MVP-documentation.md. Move to POST or a
     * console command when this graduates past MVP.
     */
    public function regenerate($key = null)
    {
        if (!$this->regenerate_codeIsValid()) {
            throw new NotFoundException();
        }
        $result = $this->Documentation->regenerate((int)$key);
        if ($result['STATUS'] === 404) {
            throw new NotFoundException($result['MSG']);
        }
        if ($result['STATUS'] === 200) {
            $this->Flash->success($result['MSG']);
        } else {
            $this->Flash->error($result['MSG']);
        }

        return $this->redirect($this->documentationUrl('view', [(int)$key]));
    }

    /**
     * Server value first, then Docker env (ENV Vars block). No default: unset disables regenerate.
     */
    private function regenerate_codeIsValid(): bool
    {
        $expected = (string)(get_cfg_var('DOCUMENTATION_REGENERATE_CODE') ?: env('DOCUMENTATION_REGENERATE_CODE') ?: '');
        $given = $this->request->getQuery('code');

        return $expected !== '' && is_string($given) && hash_equals($expected, $given);
    }

    /**
     * Same controller, current prefix and language, so the views need no
     * changes when this controller is moved under a prefix.
     */
    private function documentationUrl(string $action, array $pass = [], array $query = [], bool $full = false): string
    {
        $url = ['action' => $action, 'language' => $this->request->getParam('language') ?: 'en'];
        if ($query) {
            $url['?'] = $query;
        }

        return Router::url(array_merge($url, $pass), $full);
    }
}
