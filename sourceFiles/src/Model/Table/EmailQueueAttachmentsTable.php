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

use Cake\ORM\Table;

class EmailQueueAttachmentsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('email_queue_attachments');
        $this->addBehavior('Timestamp');

        $this->belongsTo('EmailQueues', [
            'foreignKey' => 'email_queue_id',
        ]);
    }

    /**
     * Moves an uploaded file into tmp/ (not webroot — attachments are never
     * directly downloadable by URL) and records a path reference.
     * No filename randomising or hashing for this MVP — collisions are accepted.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $upload
     */
    public function saveUpload(int $emailQueueId, $upload): array
    {
        if ($upload->getError() !== UPLOAD_ERR_OK) {
            return ['STATUS' => 400, 'MSG' => 'Upload failed'];
        }

        $originalFilename = $upload->getClientFilename();
        $relativePath = $this->saveUpload_targetPath($originalFilename);

        $upload->moveTo(TMP . $relativePath);

        $entity = $this->newEmptyEntity();
        $entity->email_queue_id = $emailQueueId;
        $entity->original_filename = $originalFilename;
        $entity->path = $relativePath;
        $entity->mime_type = $upload->getClientMediaType();
        $entity->size = $upload->getSize();
        $entity->removed = false;

        if (!$this->save($entity)) {
            return ['STATUS' => 400, 'MSG' => 'Attachment could not be recorded'];
        }

        return ['STATUS' => 200, 'MSG' => 'Attachment saved', 'id' => (int)$entity->id];
    }

    private function saveUpload_targetPath(string $originalFilename): string
    {
        $folder = 'uploads' . DS . 'email-queue-attachments';
        $absoluteFolder = TMP . $folder;

        if (!is_dir($absoluteFolder)) {
            mkdir($absoluteFolder, 0755, true);
        }

        return $folder . DS . $originalFilename;
    }
}
