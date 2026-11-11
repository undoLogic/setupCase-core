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

class CodeBlock extends Entity
{
    // Only these fields can be set from form data (id, created, modified cannot)
    protected array $_accessible = [
        'code_block_type_id' => true,
        'name' => true,
        'description' => true,
    ];
}
