<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller\Admin;

//share the appcontroller between all the views
use App\Controller\AppController;

use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class SetupPagesController extends AppController
{


    function home() {

    }
    function dashboard(){

        $this->writeToLog('debug' ,'Admin: dashboard', true);

        // pr('in admin pages dashboard');

       // pr ( $this->getLoggedInUser() );

        //pr ( $this->request->getAttributes());
    }

    function activityLogs() {

        $this->ActivityLogs = FactoryLocator::get('Table')->get('ActivityLogs');

        $cols = $this->ActivityLogs->getSchema()->columns();
        //unset here if you want to limit the view
        $this->set('cols', $cols);

        $logs = $this->ActivityLogs->find('all')->order('id DESC')->limit(10);
        $this->set('logs', $logs);
    }
}
