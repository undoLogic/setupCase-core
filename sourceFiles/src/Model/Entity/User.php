<?php

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
