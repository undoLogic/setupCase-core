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

namespace App\Controller;

use App\Util\SetupFiles;
use Cake\Controller\Controller;


use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\Http\Session;
use Cake\View\Exception\MissingTemplateException;

use Cake\Datasource\ConnectionManager;

use Cake\ORM\TableRegistry;
use Laminas\Diactoros\StreamFactory;


/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class SetupPagesController extends AppController
{
    function beforeFilter(EventInterface $event  )
    {
        parent::beforeFilter($event);
        $this->objectStorages = TableRegistry::getTableLocator()->get('ObjectStorages');

    }

    var $objectStorages;

    function getFields(){
        $fields = [
            'name' => ['rule' => 'notBlank'],
            'email' => ['rule' => 'notBlank'],
            'movie' => ['rule' => 'notBlank', 'string' => 'Start Wars']
        ];
        return $fields;

    }

    function jsonGetFields(){
        $fields = $this->getFields();
        //pr($fields); exit;
        echo  json_encode($fields);
        exit;
    }

    function dashboard()
    {
        //let's redirect to the prefix admin (I was not able to do this in the prefix not sure why)
        $this->redirect(array(
            'prefix' => 'Admin',
            'controller' => 'SetupPages',
            'action' => 'dashboard'
        ));
    }

    function index()
    {
        $this->redirect([
            'language' => $this->request->getAttribute('lang'),
            'action' => 'home'
        ]);
    }

    function home()
    {
        $setupFiles = new SetupFiles();
        $allFiles = $setupFiles->getAll();
        //dd($allFiles);
        $this->set('allFiles', $allFiles);
    }

    function readMore() {

        $this->viewBuilder()->disableAutoLayout();
    }
    function moved() {

        $oldPath = $this->request->getUri()->getPath();
        $newPath = str_replace('eng', 'en', $oldPath);
        $newPath = str_replace('spa', 'es', $newPath);
        $newPath = str_replace('fre', 'fr', $newPath);
        $this->redirect($newPath, 301); //301 Moved Permanently redirect

    }

    ///// newest to oldest /////

    function digitalSignage() {

        $this->viewBuilder()->disableAutoLayout(); // to disable layout

    }




    function increaseLimit(){
        $token = $this->request->getAttribute('csrfToken');
        $this->set('csrf', $token);


        $this->viewBuilder()->disableAutoLayout(); // to disable layout
    }

    function jsonIncreaseLimit(){
        $objData=file_get_contents('php://input');
        if(empty($objData)){
            $objData = '{"limit":20}';
        }

        $data = json_decode($objData, true);
        $limit = $data['limit'];
        $names = [
            ['id' => 0, 'name' => 'Lorem ipsum dolor sit amet'],
            ['id' => 1, 'name' => 'Lorem ipsum dolor sit amet'],
            [ 'id' => 2, 'name' => 'Lorem ipsum dolor sit amet']
        ];

        $increase_limit = [
            'Lorem ipsum dolor sit amet','Lorem ipsum dolor sit amet',
            'consectetur adipiscing elit', 'sed do eiusmod tempor incididunt ',
            'Lorem ipsum dolor sit amet','consectetur adipiscing elit',
            'sed do eiusmod tempor incididunt ','Lorem ipsum dolor sit amet',
            'consectetur adipiscing elit', 'sed do eiusmod tempor incididunt ',
            'Lorem ipsum dolor sit amet', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit','consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',
            'consectetur adipiscing elit', 'consectetur adipiscing elit',

        ];
        $initial = 0;
        //$increase = 21;
        //$limit = 21 + 21;
        $original_count = count($increase_limit);

        $result = array_slice($increase_limit, $initial, $limit, true);
        $count = count($result);
        // pr($result); exit;
        $data1 = array('result' => $result, 'limit' => $limit, 'count' => $count, 'originalCount' => $original_count);
        echo  json_encode($data1);


        exit;

    }

    function sticky() {

    }
    function responsiveTable(){
        $this->viewBuilder()->disableAutoLayout();
    }

    function formValidation(){

        $token = $this->request->getAttribute('csrfToken');
        $this->set('csrf', $token);

        $this->viewBuilder()->disableAutoLayout(); // to disable layout
        //  $this->viewBuilder()->setLayout("vue_layout"); // assign layout
    }

    function setTimer(){
        $this->viewBuilder()->disableAutoLayout(); // to disable layout
    }

    function submitForm(){

        $objData=file_get_contents('php://input');
        if(empty($objData)){
            $objData = '{"name":"nametest","movie":"Vanilla Sky","email":"test999@undologic.com"}';
        }

        $response = [];
        $response['STATUS'] = 200;
        $response['MSG'] = 'data: '.$objData;
        //echo json_encode($result);
        echo  $this->jsonHeaders(json_encode($response));
        exit;
    }

    function fileAdd()
    {

        if ($this->request->is('post')) {
            $submittedData = $this->request->getData();

            // Here is all the info about the uploaded file
            $attachment = $this->request->getData('fileToUpload');
            $filename = $attachment->getClientFilename();
            $typeOfUpload = $attachment->getClientMediaType();
            $size = $attachment->getSize();
            $uploadedFile = $attachment->getStream()->getMetadata('uri');
            $error = $attachment->getError();
            // end of info about uploaded file

            $setupFiles = new SetupFiles();
            $res = $setupFiles->put($submittedData['key_name'], file_get_contents($uploadedFile), $filename);

            if ($res['STATUS'] == 200) {
                $this->Flash->success('File added');
            } else {
                $this->Flash->error('Problem adding');
            }
        }
        $this->redirect($this->referer());
    }

    public function fileView(string $key_name): Response
    {

        $setupFiles = new SetupFiles();
        $file = $setupFiles->get($key_name);

        if ($file['STATUS'] == 200) {
            $streamFactory = new StreamFactory();
            return $this->response
                ->withType($file['extension'])
                ->withBody($streamFactory->createStreamFromFile($file['file']));
        } else {
            //show a placeholder image
//            return $this->response
//                ->withType($file['extension'])
//                ->withBody($streamFactory->createStreamFromFile($file['file']));
        }


    }

    function fileDownload($key_name)
    {
        $setupFiles = new SetupFiles();
        $file = $setupFiles->get($key_name);

        $streamFactory = new StreamFactory();
        return $this->response
            //->withType('image/jpeg')
            //->withType('application/zip')
            ->withLength($file['size'])
            ->withType($file['extension'])
            ->withDownload($file['key_name'].'.'.$file['extension'])
            ->withBody($streamFactory->createStreamFromFile($file['file']));
    }

    function fileDelete($key_name)
    {

        $setupFiles = new SetupFiles();
        $file = $setupFiles->delete($key_name);

        $this->redirect($this->referer());
    }

    //object storage
    function objAdd()
    {
        if ($this->request->is('post')) {

            dd($this->request->getData());

            $attachment = $this->request->getData('fileToUpload');
            $filename = $attachment->getClientFilename();
            $typeOfUpload = $attachment->getClientMediaType();
            $size = $attachment->getSize();
            $uploadedFile = $attachment->getStream()->getMetadata('uri');
            $error = $attachment->getError();

            $mime = mime_content_type($uploadedFile);

            $key_name = 'key_name_' .$filename;
            $res = $this->objectStorages->putObject($key_name, file_get_contents($uploadedFile), $mime, $filename);
            if ($res['status'] == 200) {
                $this->Flash->success('File added to object storage');
            } else {
                $this->Flash->error('Problem adding to object storage');
            }
        }
        $this->redirect($this->referer());
    }



    public function getUserId() {

        if (isset($this->request->getAttribute('identity')['id'])) {
            return $this->request->getAttribute('identity')['id'];
        } else {
            return false;
        }

    }
    function activityLogAddToLog() {

        $res = FactoryLocator::get('Table')->get('ActivityLogs')->addLog(
            $this->getUserId(),
            'PublicAddToLogPage',
            'On test undoweb website OR json_encode($this->request->getData())');
        $this->Flash->warning('Added log id: '.$res);
        $this->redirect($this->referer());


    }

    function googleAnalytics() {

        $this->viewBuilder()->disableAutoLayout();



    }

}
