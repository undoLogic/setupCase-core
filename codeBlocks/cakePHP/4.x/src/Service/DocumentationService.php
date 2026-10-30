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

namespace App\Service;

use Cake\Http\Client;
use Throwable;

/**
 * Documentation block: mirrors link-shared Google Docs to PDFs stored on this
 * server. See docs/features/MVP-documentation.md.
 *
 * Scoping: documentation is project-wide, not tenant data, so nothing here is
 * scoped by group_id / user_id. This is a deliberate exception.
 *
 * Precondition (destructive overwrite): regenerate only replaces the stored
 * PDF when the key exists and Google returned a valid PDF. Otherwise the
 * existing file is left untouched.
 *
 * Caller checks (login, regenerate code) belong to the controller, not here.
 */
class DocumentationService
{
    private const EXPORT_URL = 'https://docs.google.com/document/d/%s/export?format=pdf';

    // Starter shape. See the disclaimer at the top of DocumentationController.
    // WARNING: on client projects the controller must live under a logged-in
    // prefix, otherwise these documents are public.
    protected array $documents = [
        1 => [
            'title' => 'Project Documentation',
            'source' => 'https://docs.google.com/document/d/<DOC_ID>/edit', // or just the Doc ID
            'filename' => 'project-documentation.pdf', // fixed name, never changes
        ],
    ];

    // Private, never under webroot. tmp may be wiped by a deploy; regenerate again if so.
    protected string $storagePath = TMP . 'documentation' . DS;

    private array $document = [];

    private string $error = '';

    /**
     * Title, existence and version (file mtime) of a document, for the viewer.
     */
    public function getDocument(int $key): array
    {
        if (!$this->loadDocument($key)) {
            return ['STATUS' => 404, 'MSG' => 'Unknown document'];
        }
        $path = $this->filePath();
        $exists = is_file($path);

        return [
            'STATUS' => 200,
            'MSG' => $exists ? 'Document found' : 'Document has not been generated yet',
            'title' => $this->document['title'],
            'exists' => $exists,
            'version' => $exists ? (int)filemtime($path) : 0,
        ];
    }

    /**
     * Internal path + public filename for streaming. The path must never be
     * sent to the browser.
     */
    public function getFile(int $key): array
    {
        if (!$this->loadDocument($key)) {
            return ['STATUS' => 404, 'MSG' => 'Unknown document'];
        }
        if (!is_file($this->filePath())) {
            return ['STATUS' => 404, 'MSG' => 'Document has not been generated yet'];
        }

        return [
            'STATUS' => 200,
            'MSG' => 'Document found',
            'path' => $this->filePath(),
            'filename' => $this->document['filename'],
        ];
    }

    /**
     * Fetch the latest PDF export from Google and atomically replace the stored file.
     */
    public function regenerate(int $key): array
    {
        if (!$this->loadDocument($key)) {
            return ['STATUS' => 404, 'MSG' => 'Unknown document'];
        }
        $docId = $this->regenerate_docId();
        if ($docId === null) {
            return ['STATUS' => 400, 'MSG' => 'Invalid Google Doc ID in the documentation config'];
        }
        $body = $this->regenerate_fetch($docId);
        if ($body === null) {
            return ['STATUS' => 502, 'MSG' => 'Regenerate failed, existing PDF kept: ' . $this->error];
        }
        if (!$this->regenerate_save($body)) {
            return ['STATUS' => 500, 'MSG' => 'Regenerate failed, existing PDF kept: ' . $this->error];
        }

        return [
            'STATUS' => 200,
            'MSG' => 'Regenerated "' . $this->document['title'] . '" at ' . date('Y-m-d H:i:s'),
        ];
    }

    private function loadDocument(int $key): bool
    {
        $this->document = $this->documents[$key] ?? [];

        return !empty($this->document);
    }

    private function filePath(): string
    {
        return $this->storagePath . $this->document['filename'];
    }

    /**
     * Accepts a full Google Docs URL or a bare Doc ID.
     */
    private function regenerate_docId(): ?string
    {
        $source = trim((string)$this->document['source']);
        if (preg_match('#/document/d/([^/?\#]+)#', $source, $match)) {
            $source = $match[1];
        }

        return preg_match('/^[A-Za-z0-9_-]+$/', $source) ? $source : null;
    }

    /**
     * Returns the PDF bytes, or null with $this->error set.
     */
    private function regenerate_fetch(string $docId): ?string
    {
        try {
            $response = (new Client(['timeout' => 30, 'redirect' => 5]))
                ->get(sprintf(self::EXPORT_URL, $docId));
        } catch (Throwable $e) {
            $this->error = 'could not reach Google (' . $e->getMessage() . ')';

            return null;
        }
        $body = (string)$response->getStringBody();
        $isPdf = $response->getStatusCode() === 200
            && stripos($response->getHeaderLine('Content-Type'), 'application/pdf') !== false
            && strncmp($body, '%PDF', 4) === 0;
        if (!$isPdf) {
            $this->error = 'Google did not return a PDF (HTTP ' . $response->getStatusCode()
                . '). Is the doc shared as "anyone with the link can view"?';

            return null;
        }

        return $body;
    }

    /**
     * Write to a temp file in the same directory, then rename over the target,
     * so a failure never leaves a broken PDF behind.
     */
    private function regenerate_save(string $body): bool
    {
        if (!is_dir($this->storagePath) && !mkdir($this->storagePath, 0775, true) && !is_dir($this->storagePath)) {
            $this->error = 'storage directory could not be created';

            return false;
        }
        $temp = tempnam($this->storagePath, 'doc');
        if ($temp === false || file_put_contents($temp, $body) !== strlen($body) || !rename($temp, $this->filePath())) {
            if ($temp !== false && is_file($temp)) {
                unlink($temp);
            }
            $this->error = 'the PDF could not be written to storage';

            return false;
        }
        chmod($this->filePath(), 0664);

        return true;
    }
}
