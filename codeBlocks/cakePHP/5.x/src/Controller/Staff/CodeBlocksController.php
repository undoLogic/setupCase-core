<?php
declare(strict_types=1);

/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 *
 * Portions based on the CakePHP application skeleton:
 *
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

namespace App\Controller\Staff;

use App\Controller\AppController;
use App\Util\SetupCase;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\Routing\Router;

/**
 * Application Foundation demo with a database (Staff access).
 * Edit and delete live in Manager\CodeBlocksController (Manager access).
 */
class CodeBlocksController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        $this->set('webroot', Router::url('/'));
        $this->viewBuilder()->setLayout('code_blocks');
        parent::beforeFilter($event);
    }

    public function index()
    {
        $session = $this->request->getSession();

        // Namespaced session key (important for reuse)
        $sessionKey = 'CodeBlocks.index.filters';

        // Reset filters
        if ($this->request->getQuery('reset')) {
            $session->delete($sessionKey);

            return $this->redirect(['action' => 'index']);
        }

        // Read incoming data (POST preferred, fallback to session)
        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $session->write($sessionKey, $data);
        } else {
            $data = $session->read($sessionKey) ?? [];
        }

        $query = $this->CodeBlocks->find()
            ->contain('CodeBlockTypes')
            ->orderBy(['CodeBlocks.created' => 'DESC'])
            ->limit(20);

        // Example filter usage (expand later)
        if (!empty($data['name'])) {
            $query->where(['CodeBlocks.name LIKE' => '%' . $data['name'] . '%']);
        }

        $this->set([
            'filters' => $data,
            'rows' => $query->all(),
        ]);

        // IGNORE
        $this->set('codeBlocks_title', 'Application Foundation');
        $this->set('codeBlocks_subTitle', 'The initial fully functional controller logic and working UI using that starts your new great application - including sessions, creating, editing and deleting database rows');
        $this->set('codeBlocks_renderFiles', [
            'Visual INDEX template (Staff access)' => APP . '../templates/Staff/CodeBlocks/index.php',
            'Visual VIEW template (Staff access)' => APP . '../templates/Staff/CodeBlocks/view.php',
            'Visual CREATE / EDIT template (Manager template)' => APP . '../templates/Manager/CodeBlocks/edit.php',
        ]);
        $this->set('codeBlocks_renderVar', [
            'Controller INDEX action (Staff access)' => SetupCase::extractFunction(self::class, 'index'),
            'Controller CREATE action (Staff access)' => SetupCase::extractFunction(self::class, 'create'),
            'Controller VIEW action (Staff access)' => SetupCase::extractFunction(self::class, 'view'),
            'Controller DUPLICATE action (Staff access)' => SetupCase::extractFunction(self::class, 'duplicate'),
            'Controller EDIT action (Manager access)' => SetupCase::extractFunction(\App\Controller\Manager\CodeBlocksController::class, 'edit'),
            'Controller DELETE action (Manager access)' => SetupCase::extractFunction(\App\Controller\Manager\CodeBlocksController::class, 'delete'),
        ]);
        // IGNORE-END
    }

    public function duplicate($id)
    {
        $this->request->allowMethod(['post']);

        $entity = $this->CodeBlocks->get($id);

        // Copy the editable fields only (no id, created, modified)
        $newEntity = $this->CodeBlocks->newEntity($entity->extract(['code_block_type_id', 'name', 'description']));

        if ($this->CodeBlocks->save($newEntity)) {
            $this->Flash->success('Duplicated ' . $newEntity->name);

            return $this->redirect(['prefix' => 'Manager', 'action' => 'edit', $newEntity->id]);
        }

        $this->Flash->error('Error, cannot duplicate');

        return $this->redirect(['action' => 'index']);
    }

    public function create()
    {
        $entity = $this->CodeBlocks->newEmptyEntity();
        $cameFrom = null;

        if ($this->request->is('post')) {
            $entity = $this->CodeBlocks->patchEntity($entity, $this->request->getData());

            if ($this->CodeBlocks->save($entity)) {
                $this->Flash->success(__('The record has been created successfully.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The record could not be created. Please try again.'));
        } else {
            $cameFrom = $this->request->referer(true);
        }

        $this->set([
            'entity' => $entity,
            'cameFrom' => $cameFrom,
            'isCreate' => true,
            'pageTitle' => 'Create Code Block',
            'pageSubTitle' => 'Define the basic details',
        ]);

        // Reuse Manager template
        $this->viewBuilder()->setTemplate('/Manager/CodeBlocks/edit');

        // IGNORE
        $this->set('codeBlocks_title', 'Create a new Record');
        $this->set('codeBlocks_subTitle', 'Create new and share with the Manager EDIT template');
        $this->set('codeBlocks_renderFiles', [
            'Visual CREATE template (Staff access)' => APP . '../templates/Manager/CodeBlocks/edit.php',
        ]);
        $this->set('codeBlocks_renderVar', [
            'Controller CREATE action (Staff access)' => SetupCase::extractFunction(self::class, 'create'),
        ]);
        // IGNORE-END
    }

    public function view($id)
    {
        try {
            $entity = $this->CodeBlocks->get($id);
        } catch (RecordNotFoundException $e) {
            $this->Flash->error(__('Record not found.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->set([
            'entity' => $entity,
            'pageTitle' => 'View Code Block',
            'pageSubTitle' => 'Read-only details',
        ]);

        // IGNORE
        $this->set('codeBlocks_title', 'View');
        $this->set('codeBlocks_subTitle', 'View one record with standard layout');
        $this->set('codeBlocks_renderFiles', [
            'Template' => APP . '../templates/Staff/CodeBlocks/view.php',
        ]);
        $this->set('codeBlocks_renderVar', [
            'Controller Action' => SetupCase::extractFunction(self::class, 'view'),
        ]);
        // IGNORE-END
    }
}
