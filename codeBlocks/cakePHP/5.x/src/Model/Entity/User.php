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
 * User entity. The password hash and reset token are hidden so they never
 * appear in toArray()/JSON output. Only email can be mass-assigned; user_type
 * and password are set explicitly by the code that changes them.
 */
class User extends Entity
{
    protected array $_accessible = [
        'email' => true,
    ];

    protected array $_hidden = [
        'password',
        'reset_token',
    ];
}
