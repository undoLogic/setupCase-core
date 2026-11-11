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

namespace App\View\Helper;

use Cake\View\Helper;

/**
 * Auth helper
 */
class AuthHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    public function isLoggedIn(): bool
    {
        return $this->getView()->getRequest()->getAttribute('identity') !== null;
    }
}
