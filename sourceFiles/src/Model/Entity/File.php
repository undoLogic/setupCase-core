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

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * One uploaded file. See docs/features/MVP-Files-S3-Offload.md.
 *
 * Always import by full namespace (App\Model\Entity\File) so it is not
 * confused with Cake\Filesystem\File or PHP's filesystem functions.
 */
class File extends Entity
{
    protected $_accessible = [
        '*' => false,
    ];

    protected $_hidden = [
        'local_path',
        'storage_key',
        'thumb_path',
        'thumb_key',
    ];
}
