<?php
declare(strict_types=1);

/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */

namespace App\Model\Table;

use App\Model\Entity\File;
use Aws\S3\ObjectUploader;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Table;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Files block: local-first uploads, pushed to an S3-compatible bucket by cron.
 * See docs/features/MVP-Files-S3-Offload.md.
 *
 * - FILES_LOCAL_PATH (required) is the private folder files live in. Each file
 *   gets its own folder named by its id: {id}/{safe name} and {id}/_thumb.jpg.
 * - S3 is off until FILES_S3_KEY is set. pushToS3() is the only code that
 *   writes to the bucket; it never deletes a local file before the S3 copy
 *   is confirmed (object exists, size matches).
 * - Every user-facing lookup filters on user_id (owner only).
 *
 * Settings use the ENV Vars pattern: server php.ini first, then Docker env.
 */
class FilesTable extends Table
{
    public const MAX_UPLOAD_BYTES = 200 * 1024 * 1024;

    // Check what finfo really reports when adding a type: CSV often comes back
    // as text/plain, and Office files sometimes as application/zip
    public const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'video/mp4',
        'application/zip',
        'text/csv',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // Types that get a generated thumbnail; everything else gets an icon
    public const THUMB_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public const THUMB_SIZE = 300;

    // Bigger images get the generic image icon, so decoding cannot exhaust memory
    public const THUMB_MAX_PIXELS = 40000000;

    public const THUMB_NAME = '_thumb.jpg';

    // Extension => icon in webroot/img/files-s3-offload/
    public const THUMB_ICONS = [
        'csv' => 'thumb-csv.jpg',
        'xlsx' => 'thumb-xlsx.jpg',
        'xls' => 'thumb-xlsx.jpg',
        'docx' => 'thumb-docx.jpg',
        'doc' => 'thumb-docx.jpg',
        'pdf' => 'thumb-pdf.jpg',
        'zip' => 'thumb-zip.jpg',
        'mp4' => 'thumb-video.jpg',
    ];

    public const ICON_IMAGE = 'thumb-image.jpg';

    public const ICON_FILE = 'thumb-file.jpg';

    public const DELETE_LOCAL_AFTER_PUSH = true;

    public const MAX_ATTEMPTS = 5;

    public const PUSH_LIMIT = 10;

    public const PUSH_SECONDS = 50;

    public const STUCK_MINUTES = 60;

    private const STATUS_BADGES = [
        'local' => 'bg-secondary',
        'uploading' => 'bg-info',
        'remote' => 'bg-success',
        'failed' => 'bg-danger',
    ];

    private string $root = '';

    private string $mime = '';

    private int $folderId = 0;

    private ?S3Client $s3 = null;

    public function initialize(array $config): void
    {
        $this->setTable('files');
        $this->setEntityClass(File::class);
        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
    }

    /**
     * Read-only overview for the Files page: ready-to-print setup checks and
     * how many of this user's files are in each status.
     */
    public function setupStatus(int $userId): array
    {
        $localOk = $this->localRoot() !== '';
        $s3On = $this->s3Configured();
        $bucket = self::setting('FILES_S3_BUCKET');
        $checks = [
            'FILES_LOCAL_PATH' => $localOk
                ? ['Ready', 'bg-success']
                : ['Not set, missing or not writable: uploads are refused', 'bg-danger'],
            'S3 (FILES_S3_KEY)' => $s3On
                ? ['On, bucket ' . ($bucket !== '' ? $bucket : '(FILES_S3_BUCKET not set)'), 'bg-success']
                : ['Off: files stay on the server', 'bg-secondary'],
        ];

        $counts = array_fill_keys(array_keys(self::STATUS_BADGES), 0);
        $rows = $this->find()
            ->select(['status', 'total' => $this->find()->func()->count('*')])
            ->where(['user_id' => $userId])
            ->group('status')
            ->disableHydration()
            ->all();
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['total'];
        }

        return ['STATUS' => 200, 'MSG' => 'Setup status', 'checks' => $checks, 'counts' => $counts];
    }

    /**
     * Saves one upload to FILES_LOCAL_PATH and writes its row (status local).
     * The row is inserted first to get its id (the folder name), inside a
     * transaction, so the push job never sees a row without its file.
     */
    public function saveUpload(int $userId, $upload): array
    {
        $this->root = $this->localRoot();
        if ($this->root === '') {
            Log::error('[Files] FILES_LOCAL_PATH is not set, missing or not writable; upload refused');

            return ['STATUS' => 500, 'MSG' => 'File storage is not configured (FILES_LOCAL_PATH). Nothing was saved.'];
        }
        $error = $this->saveUpload_validate($upload);
        if ($error !== '') {
            return ['STATUS' => 400, 'MSG' => $error];
        }

        $this->folderId = 0;
        try {
            $file = $this->getConnection()->transactional(function () use ($userId, $upload) {
                return $this->saveUpload_store($userId, $upload);
            });
        } catch (Throwable $e) {
            Log::error('[Files] Upload failed: ' . $e->getMessage());
            $file = false;
        }
        if (!$file) {
            $this->saveUpload_removeFolder();

            return ['STATUS' => 500, 'MSG' => 'The file could not be saved'];
        }

        return ['STATUS' => 200, 'MSG' => 'File uploaded', 'id' => (int)$file->id];
    }

    private function saveUpload_validate($upload): string
    {
        if ($upload === null || $upload->getError() !== UPLOAD_ERR_OK) {
            return 'Upload failed. Please choose a file and try again.';
        }
        if ($upload->getSize() > self::MAX_UPLOAD_BYTES) {
            return 'The file is too large (max ' . (int)(self::MAX_UPLOAD_BYTES / 1048576) . ' MB).';
        }
        $tmp = (string)$upload->getStream()->getMetadata('uri');
        $this->mime = (string)(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($this->mime, self::ALLOWED_MIME, true)) {
            return 'This file type is not allowed (' . $this->mime . ').';
        }

        return '';
    }

    /**
     * Runs inside the transaction. Returning false (or throwing) rolls back.
     */
    private function saveUpload_store(int $userId, $upload)
    {
        $file = $this->newEmptyEntity();
        $file->user_id = $userId;
        $file->filename = $this->saveUpload_originalName((string)$upload->getClientFilename());
        $file->mime_type = $this->mime;
        $file->size_bytes = (int)$upload->getSize();
        $file->status = 'local';
        $file->attempts = 0;
        if (!$this->save($file)) {
            return false;
        }

        $this->folderId = (int)$file->id;
        $folder = $this->root . '/' . $file->id;
        if (!is_dir($folder) && !mkdir($folder, 0775, true)) {
            throw new RuntimeException('Folder could not be created: ' . $folder);
        }
        $file->local_path = $file->id . '/' . $this->saveUpload_safeName($file->filename);
        $absolute = $this->root . '/' . $file->local_path;
        $upload->moveTo($absolute);
        $file->size_bytes = (int)filesize($absolute);
        $file->checksum = hash_file('sha256', $absolute);

        if (in_array($this->mime, self::THUMB_MIME, true)) {
            $thumb = $file->id . '/' . self::THUMB_NAME;
            if ($this->saveUpload_thumb($absolute, $this->root . '/' . $thumb)) {
                $file->thumb_path = $thumb;
            } else {
                Log::warning('[Files] ' . $file->id . ' thumbnail could not be generated; using the image icon');
            }
        }

        return $this->save($file) ? $file : false;
    }

    /**
     * Original name for display and download: basename only, no control characters.
     */
    private function saveUpload_originalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = trim((string)preg_replace('/[\x00-\x1F\x7F]/u', '', $name));

        return $name === '' ? 'file' : mb_substr($name, 0, 255);
    }

    /**
     * Name on disk and in the bucket: safe characters only, never starts with
     * "_" or "." (so it can never collide with _thumb.jpg).
     */
    private function saveUpload_safeName(string $name): string
    {
        $extension = substr((string)preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($name, PATHINFO_EXTENSION))), 0, 10);
        $base = (string)preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($name, PATHINFO_FILENAME));
        $base = substr(trim((string)preg_replace('/-{2,}/', '-', $base), '._- '), 0, 100);
        if ($base === '') {
            $base = 'file';
        }

        return $extension === '' ? $base : $base . '.' . $extension;
    }

    /**
     * Square THUMB_SIZE JPEG, centre-cropped. EXIF orientation is applied and
     * transparency is flattened onto white. Returns false if it cannot be made.
     */
    private function saveUpload_thumb(string $source, string $target): bool
    {
        $info = @getimagesize($source);
        if ($info === false || $info[0] * $info[1] > self::THUMB_MAX_PIXELS) {
            return false;
        }
        $loaders = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/gif' => 'imagecreatefromgif',
            'image/webp' => 'imagecreatefromwebp',
        ];
        $loader = $loaders[$this->mime] ?? null;
        $image = $loader !== null && function_exists($loader) ? @$loader($source) : false;
        if ($image === false) {
            return false;
        }
        $image = $this->saveUpload_orient($image, $source);

        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);
        $thumb = imagecreatetruecolor(self::THUMB_SIZE, self::THUMB_SIZE);
        imagefill($thumb, 0, 0, (int)imagecolorallocate($thumb, 255, 255, 255));
        imagecopyresampled(
            $thumb,
            $image,
            0,
            0,
            (int)(($width - $side) / 2),
            (int)(($height - $side) / 2),
            self::THUMB_SIZE,
            self::THUMB_SIZE,
            $side,
            $side
        );

        return imagejpeg($thumb, $target, 82);
    }

    /**
     * Phone photos store their rotation in EXIF; apply it so thumbnails are upright.
     */
    private function saveUpload_orient($image, string $source)
    {
        if ($this->mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($source);
        $angles = [3 => 180, 6 => 270, 8 => 90];
        $angle = $angles[(int)($exif['Orientation'] ?? 1)] ?? 0;

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }

    /**
     * Only ever removes the folder of the row this request just tried to create.
     */
    private function saveUpload_removeFolder(): void
    {
        if ($this->folderId === 0 || $this->root === '') {
            return;
        }
        $folder = $this->root . '/' . $this->folderId;
        foreach (glob($folder . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($folder);
    }

    /**
     * This user's files, newest first, with ready-to-render thumbnail and
     * status values so the template stays a passive view.
     */
    public function findAllForUser(int $userId): array
    {
        $rows = $this->find()
            ->where(['Files.user_id' => $userId])
            ->orderDesc('Files.id')
            ->all();

        $s3On = $this->s3Configured();
        $files = [];
        foreach ($rows as $file) {
            $file->thumb_icon = $this->findAllForUser_icon($file);
            // The "push-s3" link: only while S3 is on and the file is not in S3 yet
            $file->can_push = $s3On && in_array($file->status, ['local', 'failed'], true);
            // Not while a push is copying it
            $file->can_delete = $file->status !== 'uploading';
            $file->status_badge_class = self::STATUS_BADGES[$file->status] ?? 'bg-secondary';
            $file->size_label = $file->size_bytes >= 1048576
                ? number_format($file->size_bytes / 1048576, 1) . ' MB'
                : number_format($file->size_bytes / 1024, 0) . ' KB';
            $files[] = $file;
        }

        return ['STATUS' => 200, 'MSG' => count($files) . ' file(s)', 'files' => $files];
    }

    /**
     * Null means a generated thumbnail exists; otherwise the icon file to show.
     */
    private function findAllForUser_icon(File $file): ?string
    {
        if ($file->thumb_path !== null) {
            return null;
        }
        if (in_array($file->mime_type, self::THUMB_MIME, true)) {
            return self::ICON_IMAGE;
        }
        $extension = strtolower(pathinfo((string)$file->filename, PATHINFO_EXTENSION));

        return self::THUMB_ICONS[$extension] ?? self::ICON_FILE;
    }

    /**
     * Where to serve a file (or its thumbnail) from: local disk first, then S3.
     * Filters on id AND user_id in one query; anything else is a 404.
     *
     * @param string $which 'file' or 'thumb'
     */
    public function serveInfo(int $id, int $userId, string $which): array
    {
        $file = $this->find()->where(['Files.id' => $id, 'Files.user_id' => $userId])->first();
        if ($file === null) {
            return ['STATUS' => 404, 'MSG' => 'Not found'];
        }
        $isThumb = $which === 'thumb';
        $relative = $isThumb ? $file->thumb_path : $file->local_path;
        $key = $isThumb ? $file->thumb_key : $file->storage_key;
        $response = [
            'STATUS' => 200,
            'MSG' => 'Found',
            'mime' => $isThumb ? 'image/jpeg' : $file->mime_type,
            // Header-safe name for Content-Disposition
            'name' => str_replace(['"', '\\'], '', $file->filename),
            'local' => null,
            'key' => null,
        ];

        $root = $this->localRoot();
        if ($relative !== null && $root !== '' && is_file($root . '/' . $relative)) {
            return ['local' => $root . '/' . $relative] + $response;
        }
        if ($key !== null && $file->status === 'remote') {
            return ['key' => $key] + $response;
        }
        if ($relative !== null || $key !== null) {
            Log::warning('[Files] ' . $id . ' ' . $which . ': row and storage disagree');
        }

        return ['STATUS' => 404, 'MSG' => 'Not found'];
    }

    /**
     * Opens an object for streaming. $range is the browser's Range header, passed on as is.
     */
    public function openRemote(string $key, string $range = ''): array
    {
        if (!$this->s3Configured()) {
            return ['STATUS' => 503, 'MSG' => 'S3 is not configured'];
        }
        $args = [
            'Bucket' => self::setting('FILES_S3_BUCKET'),
            'Key' => $key,
            '@http' => ['stream' => true],
        ];
        if (preg_match('/^bytes=\d*-\d*$/', $range)) {
            $args['Range'] = $range;
        }

        try {
            $result = $this->s3Client()->getObject($args);
        } catch (Throwable $e) {
            Log::warning('[Files] S3 read failed for ' . $key . ': ' . $e->getMessage());

            return ['STATUS' => 502, 'MSG' => 'File storage is not reachable'];
        }

        return [
            'STATUS' => 200,
            'MSG' => 'Opened',
            'body' => $result['Body'],
            'length' => (int)$result['ContentLength'],
            'contentRange' => (string)($result['ContentRange'] ?? ''),
        ];
    }

    /**
     * Cron job `files_push` (config/cron.php). Pushes local files to S3.
     * Unscoped on purpose: cron runs for the whole install, and it only ever
     * copies files and updates their own rows; it never deletes a row.
     */
    public function pushToS3(): array
    {
        if (!$this->s3Configured()) {
            return ['STATUS' => 200, 'MSG' => 'S3 disabled (FILES_S3_KEY is not set)'];
        }
        $this->root = $this->localRoot();
        if ($this->root === '') {
            Log::error('[FilesPush] FILES_LOCAL_PATH is not set, missing or not writable');

            return ['STATUS' => 500, 'MSG' => 'FILES_LOCAL_PATH is not set, missing or not writable'];
        }
        $lock = $this->pushLock();
        if ($lock === null) {
            return ['STATUS' => 200, 'MSG' => 'Already running'];
        }

        $this->pushToS3_resetStuck();
        $started = time();
        $pushed = 0;
        $failed = 0;
        foreach ($this->pushToS3_rows() as $file) {
            if (time() - $started >= self::PUSH_SECONDS) {
                break;
            }
            $this->pushFile($file) ? $pushed++ : $failed++;
        }
        $this->pushUnlock($lock);

        return [
            'STATUS' => $failed > 0 ? 500 : 200,
            'MSG' => 'Pushed ' . $pushed . ', failed ' . $failed,
            'pushed' => $pushed,
            'failed' => $failed,
        ];
    }

    private function pushToS3_resetStuck(): void
    {
        $this->updateAll(
            ['status' => 'failed', 'last_error' => 'Reset: stuck in uploading'],
            ['status' => 'uploading', 'modified <' => FrozenTime::now()->subMinutes(self::STUCK_MINUTES)]
        );
    }

    private function pushToS3_rows(): iterable
    {
        return $this->find()
            ->where(['OR' => [
                'status' => 'local',
                ['status' => 'failed', 'attempts <' => self::MAX_ATTEMPTS],
            ]])
            ->orderAsc('id')
            ->limit(self::PUSH_LIMIT)
            ->all();
    }

    /**
     * Deletes one of this user's files: the S3 copies first, then the local
     * files, then the row (AGENTS "Destructive Operations"):
     * - Scope is required and never inferred: no id or user_id = exception.
     * - The row is found and deleted by id AND user_id in the same query.
     * - S3 deletes are idempotent: an object already removed by hand counts as
     *   deleted. If S3 cannot be reached nothing is deleted and the row stays,
     *   so a bucket copy is never left with no row pointing at it.
     * - Takes the push lock, so it never races the cron job or the push-s3 link.
     */
    public function deleteForUser(int $id, int $userId): array
    {
        if ($id <= 0 || $userId <= 0) {
            throw new InvalidArgumentException('deleteForUser() needs an id and a user_id');
        }
        $file = $this->find()->where(['Files.id' => $id, 'Files.user_id' => $userId])->first();
        if ($file === null) {
            return ['STATUS' => 404, 'MSG' => 'File not found'];
        }
        $refusal = $this->deleteForUser_refusal($file);
        if ($refusal !== '') {
            return ['STATUS' => 409, 'MSG' => $refusal];
        }
        $lock = $this->pushLock();
        if ($lock === null) {
            return ['STATUS' => 409, 'MSG' => 'A push is running. Try again in a minute.'];
        }

        try {
            $error = $this->deleteForUser_remote($file);
            if ($error !== '') {
                return ['STATUS' => 502, 'MSG' => $error];
            }
            $this->deleteForUser_local($file);
            $deleted = $this->deleteAll(['id' => $id, 'user_id' => $userId]);
        } finally {
            $this->pushUnlock($lock);
        }
        if ($deleted !== 1) {
            Log::warning('[Files] ' . $id . ': storage deleted but the row was not');

            return ['STATUS' => 500, 'MSG' => 'The file could not be deleted'];
        }
        Log::notice('[Files] user ' . $userId . ' deleted file ' . $id . ' (' . $file->filename . ')');

        return ['STATUS' => 200, 'MSG' => 'Deleted: ' . $file->filename];
    }

    /**
     * Preconditions on the target. Refuse rather than half-delete.
     */
    private function deleteForUser_refusal(File $file): string
    {
        if ($file->status === 'uploading') {
            return 'This file is being pushed to S3 right now. Try again in a minute.';
        }
        if (($file->storage_key !== null || $file->thumb_key !== null) && !$this->s3Configured()) {
            return 'This file has a copy in S3, but S3 is off (FILES_S3_KEY is not set). Nothing was deleted.';
        }
        $this->root = $this->localRoot();
        if (($file->local_path !== null || $file->thumb_path !== null) && $this->root === '') {
            return 'FILES_LOCAL_PATH is not set, missing or not writable. Nothing was deleted.';
        }

        return '';
    }

    /**
     * Returns '' when every S3 copy is gone (including ones already deleted by hand).
     */
    private function deleteForUser_remote(File $file): string
    {
        foreach (array_filter([$file->storage_key, $file->thumb_key]) as $key) {
            try {
                $this->s3Client()->deleteObject(['Bucket' => self::setting('FILES_S3_BUCKET'), 'Key' => $key]);
            } catch (AwsException $e) {
                if ($e->getStatusCode() === 404) {
                    continue; // already gone
                }
                Log::warning('[Files] ' . $file->id . ' S3 delete failed for ' . $key . ': ' . $e->getMessage());

                return 'File storage (S3) could not be reached. Nothing was deleted; try again later.';
            }
        }

        return '';
    }

    /**
     * Local original and thumbnail, then the {id}/ folder if it is now empty.
     * A file that is already missing is fine.
     */
    private function deleteForUser_local(File $file): void
    {
        foreach (array_filter([$file->local_path, $file->thumb_path]) as $relative) {
            if (is_file($this->root . '/' . $relative)) {
                unlink($this->root . '/' . $relative);
            }
        }
        if ($this->root !== '') {
            @rmdir($this->root . '/' . $file->id); // only succeeds when empty
        }
    }

    /**
     * The "push-s3" link on the Files page: pushes one of this user's files now.
     * Unlike the cron job it is scoped (id AND user_id) and touches one row; the
     * two share only pushFile(). It takes the same lock as cron, so a file is
     * never pushed twice at once. A manual push also retries a file cron gave up on.
     */
    public function pushOne(int $id, int $userId): array
    {
        if (!$this->s3Configured()) {
            return ['STATUS' => 400, 'MSG' => 'S3 is off (FILES_S3_KEY is not set)'];
        }
        $this->root = $this->localRoot();
        if ($this->root === '') {
            Log::error('[FilesPush] FILES_LOCAL_PATH is not set, missing or not writable');

            return ['STATUS' => 500, 'MSG' => 'FILES_LOCAL_PATH is not set, missing or not writable'];
        }
        $file = $this->find()
            ->where(['Files.id' => $id, 'Files.user_id' => $userId, 'Files.status IN' => ['local', 'failed']])
            ->first();
        if ($file === null) {
            return ['STATUS' => 404, 'MSG' => 'File not found, or already in S3'];
        }
        $lock = $this->pushLock();
        if ($lock === null) {
            return ['STATUS' => 409, 'MSG' => 'A push is already running. Try again in a minute.'];
        }

        // Runs inside a web request: a large file can take longer than max_execution_time
        set_time_limit(0);
        $pushed = $this->pushFile($file);
        $this->pushUnlock($lock);

        return $pushed
            ? ['STATUS' => 200, 'MSG' => 'Pushed to S3: ' . $file->filename]
            : ['STATUS' => 500, 'MSG' => 'Push failed for ' . $file->filename . '. Hover its status for the error.'];
    }

    /**
     * One lock for the cron job and the "push-s3" link. In system temp, keyed
     * per site: never inside the project, so there is nothing to commit by accident.
     *
     * @return resource|null Null when another push holds it
     */
    private function pushLock()
    {
        $lock = fopen(sys_get_temp_dir() . '/files_push_' . md5(ROOT) . '.lock', 'c');
        if ($lock === false) {
            return null;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            return null;
        }

        return $lock;
    }

    private function pushUnlock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /**
     * Push one file (and its thumbnail), for both pushToS3() and pushOne().
     * The local original is deleted only after both S3 copies are confirmed;
     * on any failure it stays.
     */
    private function pushFile(File $file): bool
    {
        $file->status = 'uploading';
        $file->attempts = (int)$file->attempts + 1;
        $this->saveOrFail($file);

        try {
            $prefix = self::setting('FILES_S3_PREFIX', 'files/');
            $file->storage_key = $prefix . $file->local_path;
            $this->pushFile_upload($file->local_path, $file->storage_key, $file->mime_type, $file->size_bytes);
            if ($file->thumb_path !== null) {
                $file->thumb_key = $prefix . $file->thumb_path;
                $this->pushFile_upload($file->thumb_path, $file->thumb_key, 'image/jpeg', null);
            }
        } catch (Throwable $e) {
            $giveUp = $file->attempts >= self::MAX_ATTEMPTS ? ' (giving up)' : '';
            $file->status = 'failed';
            $file->last_error = $e->getMessage();
            $this->save($file);
            Log::error('[FilesPush] ' . $file->id . ' ' . $e->getMessage() . $giveUp);

            return false;
        }

        $file->status = 'remote';
        $file->uploaded_at = FrozenTime::now();
        $file->last_error = null;
        if (self::DELETE_LOCAL_AFTER_PUSH && @unlink($this->root . '/' . $file->local_path)) {
            $file->local_path = null;
        }
        $this->saveOrFail($file);

        return true;
    }

    /**
     * Upload one local file and confirm it: the object must exist with the same size.
     */
    private function pushFile_upload(string $relative, string $key, string $mime, ?int $expectedBytes): void
    {
        $path = $this->root . '/' . $relative;
        $handle = is_file($path) ? fopen($path, 'rb') : false;
        if ($handle === false) {
            throw new RuntimeException('Local file missing: ' . $relative);
        }
        $bucket = self::setting('FILES_S3_BUCKET');
        try {
            (new ObjectUploader($this->s3Client(), $bucket, $key, $handle, 'private', [
                'params' => ['ContentType' => $mime],
            ]))->upload();
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $head = $this->s3Client()->headObject(['Bucket' => $bucket, 'Key' => $key]);
        $expected = $expectedBytes ?? (int)filesize($path);
        if ((int)$head['ContentLength'] !== $expected) {
            throw new RuntimeException('Size mismatch after upload: ' . $key);
        }
    }

    /**
     * FILES_LOCAL_PATH without a trailing slash, or '' if it is not set, missing
     * or not writable. There is deliberately no fallback inside sourceFiles/.
     */
    private function localRoot(): string
    {
        $path = rtrim(self::setting('FILES_LOCAL_PATH'), '/');
        if ($path === '' || !is_dir($path) || !is_writable($path)) {
            return '';
        }

        return $path;
    }

    private function s3Configured(): bool
    {
        return self::setting('FILES_S3_KEY') !== '';
    }

    private function s3Client(): S3Client
    {
        if ($this->s3 === null) {
            $config = [
                'version' => 'latest',
                'region' => self::setting('FILES_S3_REGION', 'us-east-1'),
                'use_path_style_endpoint' => false,
                'credentials' => [
                    'key' => self::setting('FILES_S3_KEY'),
                    'secret' => self::setting('FILES_S3_SECRET'),
                ],
                'retries' => 1,
                'http' => ['connect_timeout' => 3], // connect only; transfers are not capped
            ];
            // Empty = AWS; DigitalOcean Spaces e.g. https://tor1.digitaloceanspaces.com
            $endpoint = self::setting('FILES_S3_ENDPOINT');
            if ($endpoint !== '') {
                $config['endpoint'] = $endpoint;
            }
            $this->s3 = new S3Client($config);
        }

        return $this->s3;
    }

    private static function setting(string $name, string $default = ''): string
    {
        return (string)(get_cfg_var($name) ?: env($name) ?: $default);
    }
}
