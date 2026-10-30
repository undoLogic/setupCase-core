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
use Cake\View\View;

/**
 * Lang helper
 */
class LangHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [];

    function get(): string {
        $req = $this->getView()->getRequest();
        return $req->getAttribute('lang');
    }

    function is($lang): bool {
        $current = $this->get();
        if ($lang == $current) {
            return true;
        }
        return false;
    }
    function getActiveClass($langToCheck, $classname = 'active'): string {
        if ($this->is($langToCheck)) {
            return $classname;
        } else {
            return '';
        }
    }

}
