<?php
/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */

namespace App\Util;

use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Datasource\FactoryLocator;

class Environments
{

    public static function getActive() {

        // dd($_SERVER);
        if (isset($_SERVER['SERVER_NAME'])) {
            $serverEnv = null;
            switch ($_SERVER['SERVER_NAME']) {
                case 'localhost':
                    return 'LOCAL';
                    break;
                case 'testboilerplate.undoweb.com':
                    return 'UNDOWEB';
                    break;
                default:
                    //default to the undoWEB env
                    return 'UNDOWEB';
                    //die('MISSING environment for '.$_SERVER['SERVER_NAME']);
            }
        } else {

            //maybe docker
            if (isset($_SERVER['DATABASE_DEFAULT_URL'])) {
                return 'DOCKER';
            } elseif (isset($_SERVER['HOME'])) {
                if (isset($_SERVER['LOGNAME'])) {
                    if ($_SERVER['LOGNAME'] == 'undoweb') {
                        return 'UNDOWEB';
                    }
                }
            } else {
                //default CLI is ?
                return 'LIVE';
            }
        }

    }



}
