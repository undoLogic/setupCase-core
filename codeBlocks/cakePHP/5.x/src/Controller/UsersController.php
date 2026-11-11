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

namespace App\Controller;

/**
 * Login and logout (CakePHP 5.x, authentication 4.x).
 *
 * Password checking is done by the Authentication Form authenticator configured
 * in Application::getAuthenticationService(). Signup and password reset are not
 * ported yet (see docs/features/CakePHP-5-Upgrade-Plan.md).
 */
class UsersController extends AppController
{
    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $this->Flash->success('You have been logged in');

            // getLoginRedirect() only returns local URLs (no open redirect)
            $redirect = $this->Authentication->getLoginRedirect()
                ?? ['controller' => 'CodeBlocks', 'action' => 'index', 'language' => $this->request->getAttribute('lang')];

            return $this->redirect($redirect);
        }

        if ($this->request->is('post')) {
            $this->Flash->error('Email or password is incorrect.');
        }
    }

    public function logout()
    {
        // GET allowed by owner decision (2026-11-11), same as 4.x. Note: AGENTS.md
        // "HTTP Method by Effect" does not list logout as an exception yet.
        $this->request->allowMethod(['get', 'post']);
        $this->Authentication->logout();
        $this->Flash->success('You have been logged out');

        return $this->redirect(['controller' => 'Users', 'action' => 'login', 'language' => $this->request->getAttribute('lang')]);
    }
}
