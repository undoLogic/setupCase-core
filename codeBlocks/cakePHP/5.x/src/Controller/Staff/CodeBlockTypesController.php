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

namespace App\Controller\Staff;

use App\Controller\AppController;
use App\Util\SetupCase;
use Cake\Event\EventInterface;
use Cake\Routing\Router;

/**
 * Application Foundation demo: read-only view of a related (parent) record (Staff access).
 * Shows how a cell for a related record on another page links here, and how the
 * records under it link back to their own view.
 */
class CodeBlockTypesController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        $this->set('webroot', Router::url('/'));
        $this->viewBuilder()->setLayout('code_blocks');
        parent::beforeFilter($event);
    }

    public function view($id)
    {
        $result = $this->CodeBlockTypes->getForView((int)$id);

        if ($result['STATUS'] !== 200) {
            $this->Flash->error(__('Record not found.'));

            return $this->redirect(['controller' => 'CodeBlocks', 'action' => 'index']);
        }

        $this->set([
            'type' => $result['type'],
            'childRows' => $this->view_shapeChildRows($result['type']->code_blocks),
        ]);

        // IGNORE
        $this->set('codeBlocks_title', 'View a related record');
        $this->set('codeBlocks_subTitle', 'The parent record, with each record under it linking to its own view');
        $this->set('codeBlocks_renderFiles', [
            'Template' => APP . '../templates/Staff/CodeBlockTypes/view.php',
        ]);
        $this->set('codeBlocks_renderVar', [
            'Controller Action' => SetupCase::extractFunction(self::class, 'view'),
        ]);
        // IGNORE-END
    }

    /**
     * Records under this one, each with its ready-to-use view URL.
     */
    private function view_shapeChildRows(array $codeBlocks): array
    {
        $rows = [];
        foreach ($codeBlocks as $codeBlock) {
            $rows[] = [
                'name' => $codeBlock->name,
                'viewUrl' => ['prefix' => 'Staff', 'controller' => 'CodeBlocks', 'action' => 'view', $codeBlock->id],
            ];
        }

        return $rows;
    }
}
