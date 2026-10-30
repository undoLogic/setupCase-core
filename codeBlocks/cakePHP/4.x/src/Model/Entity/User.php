<?php
/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */

namespace Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;

class User extends Entity
{
    protected $_accessible =[
        '*'=>true
    ];

    //entity accosor
    protected function _getIsAdmin():bool {
        $rbac = Configure::read('rbac');
        $roles = array_keys($rbac[$this->user_type]);
        return (in_array('ADMIN', $roles));
    }

}// end
