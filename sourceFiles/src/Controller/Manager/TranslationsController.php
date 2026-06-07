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

namespace App\Controller\Manager;

//share the appcontroller between all the views
use App\Controller\AppController;

use App\Util\Assets;
use App\Util\SetupCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Router;
use Cake\View\Exception\MissingTemplateException;
use Composer\Factory;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class TranslationsController extends AppController
{


    function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        //  $this->set('menu_manager', true);



    }

    // src/Controller/TranslationsController.php


    function index() {
        $this->set('menuActive', ['Settings' => 'Settings']);
        $count = 0;
        $entity = $this->Translations->newEmptyEntity();
        $session = $this->request->getSession();
        $postData = $this->request->getData();

        if (isset($_GET['reset'])) {
            $session->delete('sessionData');
        }

        if (!empty($postData)) {
            $session->write('sessionData', $postData);
        } else {
            $postData = $session->read('sessionData');
        }


        if ($postData) {
            $this->Translations->patchEntity($entity, $postData);
        }

        $this->set('entity', $entity);

        //dd($entity);

        $translations = $this->Translations->getAllTranslations($postData);
        if($translations){
            $count = count($translations->toArray());
        }

        $this->set(compact('translations', 'count'));
    }//index




    function edit($id=false) {

        $cols = $this->Translations->getSchema()->columns();
        $this->set('cols', $cols);

        $row = $this->Translations->newEmptyEntity();
        $postData = $this->request->getData();

        if (!empty($postData)) {
            $row = $this->Translations->patchEntity($row, $postData);
            if ($this->Translations->save($row)) {
                $this->Flash->success('Saved');
                if (in_array($row['cameFrom'], ['/', ''])) {
                    $this->redirect(['action' => 'index']);
                } else {
                    $this->redirect($row['cameFrom']);
                }
            } else {
                $this->Flash->error('Error saving');
            }
        } else {// end of  post
            if ($id === 'new') {
                //New - row entity will be used
            } else {
                $row = $this->Translations->get($id);
            }
            $row->cameFrom = $this->referer();
        }
        $this->set('row', $row);
    }//edit

    function delete($id = NULL) {
        if (!$id) {
            $this->Flash->error('Specify ID');
            $this->redirect(array('prefix'=>'Manager', 'action' => 'index'));
        }else {
            $entity = $this->Translations->get($id);
            if ($this->Translations->delete($entity)) {
                $this->Flash->success('Deleted '.$entity->name);
            } else {
                $this->Flash->error('Error cannot delete');
            }
            $this->redirect($this->referer());
        }
    }//delete


}//END
