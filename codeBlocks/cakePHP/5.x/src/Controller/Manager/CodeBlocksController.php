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

namespace App\Controller\Manager;

use App\Controller\AppController;
use App\Util\SetupCase;
use Cake\Event\EventInterface;
use Cake\Routing\Router;

/**
 * Application Foundation demo: edit and delete (Manager access).
 */
class CodeBlocksController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        $this->set('webroot', Router::url('/'));
        $this->viewBuilder()->setLayout('code_blocks');
        parent::beforeFilter($event);
    }

    public function edit($id)
    {
        $entity = $this->CodeBlocks->get($id);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            $cameFrom = $data['cameFrom'] ?? null;
            unset($data['cameFrom']); // critical

            $entity = $this->CodeBlocks->patchEntity($entity, $data);

            if ($this->CodeBlocks->save($entity)) {
                $this->Flash->success('Saved successfully.');

                return $this->redirect($this->edit_safeRedirect($cameFrom));
            }

            $this->Flash->error(__('The record could not be updated. Please try again.'));
        } else {
            $cameFrom = $this->request->referer(true);
        }

        $this->set([
            'entity' => $entity,
            'cameFrom' => $cameFrom,
            'isCreate' => false,
            'pageTitle' => 'Edit Code Block',
            'pageSubTitle' => 'Update details and configuration',
        ]);

        // IGNORE
        $this->set('codeBlocks_title', 'Edit a file');
        $this->set('codeBlocks_subTitle', '');
        $this->set('codeBlocks_renderFiles', [
            'Template' => APP . '../templates/Manager/CodeBlocks/edit.php',
        ]);
        $this->set('codeBlocks_renderVar', [
            'Controller Action' => SetupCase::extractFunction(self::class, 'edit'),
        ]);
        // IGNORE-END
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $entity = $this->CodeBlocks->get($id);

        if ($this->CodeBlocks->delete($entity)) {
            $this->Flash->warning(__('The record has been deleted.'));
        } else {
            $this->Flash->error(__('The record could not be deleted. Please try again.'));
        }

        return $this->redirect(['prefix' => 'Staff', 'action' => 'index']);
    }

    /**
     * Only follow a local path from the form (no other hosts); otherwise go to the list.
     */
    private function edit_safeRedirect(?string $cameFrom): string|array
    {
        $isLocal = is_string($cameFrom)
            && str_starts_with($cameFrom, '/')
            && !str_starts_with($cameFrom, '//')
            && !str_contains($cameFrom, '\\');

        return $isLocal ? $cameFrom : ['prefix' => 'Staff', 'action' => 'index'];
    }
}
