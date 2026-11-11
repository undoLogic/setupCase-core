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
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Util\SetupCase;
use Cake\Event\EventInterface;
use Cake\Routing\Router;

/**
 * CodeBlocks demo pages (no prefix, no database).
 *
 * CakePHP 5.x port: only the pages listed in docs/features/CakePHP-5-Upgrade-Plan.md
 * are brought over. Other pages are ported one at a time.
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
        //Intro page
    }

    public function applicationFoundation()
    {
        // IGNORE
        $this->set('codeBlocks_title', 'Application Foundation');
        $this->set('codeBlocks_subTitle', 'The initial fully functional controller logic and working UI using that starts your new great application - including sessions, creating, editing and deleting database rows');
        $this->set('codeBlocks_renderFiles', [
            'Visual INDEX template (Staff access)' => APP . '../templates/Staff/CodeBlocks/index.php',
            'Visual VIEW template (Staff access)' => APP . '../templates/Staff/CodeBlocks/view.php',
            'Visual EDIT template (Manager access)' => APP . '../templates/Manager/CodeBlocks/edit.php',
        ]);
        $this->set('codeBlocks_renderVar', [
            'Controller INDEX action (Staff access)' => SetupCase::extractFunction(\App\Controller\Staff\CodeBlocksController::class, 'index'),
            'Controller CREATE action (Staff access)' => SetupCase::extractFunction(\App\Controller\Staff\CodeBlocksController::class, 'create'),
            'Controller VIEW action (Staff access)' => SetupCase::extractFunction(\App\Controller\Staff\CodeBlocksController::class, 'view'),
            'Controller DUPLICATE action (Staff access)' => SetupCase::extractFunction(\App\Controller\Staff\CodeBlocksController::class, 'duplicate'),
            'Controller EDIT action (Manager access)' => SetupCase::extractFunction(\App\Controller\Manager\CodeBlocksController::class, 'edit'),
            'Controller DELETE action (Manager access)' => SetupCase::extractFunction(\App\Controller\Manager\CodeBlocksController::class, 'delete'),
        ]);
        // IGNORE-END
    }
}
