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

use App\Util\SetupCase;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;
use Cake\Datasource\ConnectionManager;
use Cake\Routing\Router;
use Authentication\PasswordHasher\DefaultPasswordHasher;

// Add this line


/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class UsersController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        # test inside codeblocks

    }


//    function testing()
//    {
//
//        $email = 'sachalewis@undologic.com';
//        $pass = '1234';
//
//        $passObj = new DefaultPasswordHasher;
//
//        $hash = ($passObj)->hash($pass);
//
//        $this->writeToLog('debug', 'pass is: ' . $pass, true);
//        $this->writeToLog('debug', 'hash is: ' . $hash, true);
//        $isCorrect = $passObj->check($pass, $hash);
//        $this->writeToLog('debug', 'isCorrect: ' . $isCorrect, true);
//
//
//        $didSave = $this->Users->saveUserPassword($email, $hash);
//        $this->writeToLog('debug', 'didSave: '.$didSave, true);
//
//
//        $userArray = $this->Users->getUserByEmail($email);
//        pr ($userArray);
//
//
//        $session = $this->request->getSession();
//        $session->write('User', $userArray);
//
//        $sessionUser = $session->read('User');
//
//        pr ($sessionUser);
//
//
//        exit;
//
//    }


//    function signup() {
//
//        if ($this->request->is('post')) {
//
//            $this->writeToLog('debug', 'Signup', true);
//
//            $emailSubmitted = $this->request->getData()['email'];
//            $passSubmitted = $this->request->getData()['password'];
//
//            $passObj = new DefaultPasswordHasher;
//            $didCreateUser = $this->Users->createUser(
//                $emailSubmitted,
//                $passObj->hash($passSubmitted)
//            );
//
//            if ($didCreateUser) {
//                $this->writeToLog('debug', 'User created user_id: '.$didCreateUser['id'], false);
//                $this->Flash->success('User has been CREATED');
//
//                $session = $this->request->getSession();
//                $session->write('User', $didCreateUser);
//
//                return $this->redirect(array('prefix' => 'Admin', 'controller' => 'SetupPages', 'action' => 'home'));
//            } else {
//                $this->Flash->error('Could NOT create user');
//                return $this->redirect('/');
//            }
//
//        }
//
//    }
    /*
     * @author: latha nov-08-2022
     * @summary: addUser has the following features
     * cake4 form create in template. form submit. flash message. logActivities, patchEntity, tableLocator,
     */
    public function addUser()
    {
        $user = $this->Users->newEmptyEntity();


        if ($this->request->is('post')) {
            $userData = $this->Users->patchEntity($user, $this->request->getData());

            // Edit: some of the entity properties are manually set at this point, e.g.
            $userData->group_id = 1;

            if ($this->Users->save($userData)) {
                $userData = json_encode($userData->toArray());
                $this->writeToLog('debug', "user added.$userData");
                //  writeToLog log created in appController

                // pr($userData); exit;
                $this->Flash->success(__('User Saved'));
            }
        }// end of  post

        // add find first and find all , contain
        // find first
        $query = $this->Users
            ->find()
            ->where(['Users.id' => 1])
            ->contain(['Groups'])
            ->first();

        if (!empty($query)) {
            $user = $query->toArray();
        }

        $query1 = $this->Users
            ->find('all', [
                'conditions' => [
                    'Users.group_id' => 1
                ],
                'contain' => ['Groups']
            ]);
        if (!empty($query1)) {
            $users = $query1->toArray();
            $this->set(compact('user', 'users'));
        }



    }//login

    public function add()
    {
        //die('hi');
        //dd($this->request);

        $this->request->allowMethod(['get', 'post']);

        if ($this->request->is('post')) {


            $this->writeToLog('debug', 'Signup', true);

            $emailSubmitted = $this->request->getData()['email'];
            $passSubmitted = $this->request->getData()['password'];

            //dd($this->request);
            $userType = $this->request->getData()['user_type'];

            $passObj = new DefaultPasswordHasher;
            $didCreateUser = $this->Users->createUser(
                $emailSubmitted,
                $passObj->hash($passSubmitted),
                $userType
            );

            if ($didCreateUser) {
                $this->writeToLog('debug', 'User created user_id: ' . $didCreateUser['id'], false);
                $this->Flash->success('User has been CREATED');

                $session = $this->request->getSession();
                $session->write('User', $didCreateUser);

                return $this->redirect(array('prefix' => 'Admin', 'controller' => 'SetupPages', 'action' => 'home'));
            } else {
                $this->Flash->error('Could NOT create user');
                return $this->redirect('/');
            }
        }

    }//login

    //        $pass = '1234';
//        $passObj = new DefaultPasswordHasher;
//        $hash = ($passObj)->hash($pass);
//        $this->writeToLog('debug', 'pass is: ' . $pass, true);
//        $this->writeToLog('debug', 'hash is: ' . $hash, true);
//        $isCorrect = $passObj->check($pass, $hash);
//        $this->writeToLog('debug', 'is correct: '.$isCorrect, true);

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        // regardless of POST or GET, redirect if user is logged in
        if ($result && $result->isValid()) {
            // redirect to /articles after login success
            $this->Flash->success('You have been logged in');
            $redirect = $this->request->getQuery('redirect', [
                'controller' => 'SetupPages',
                'action' => 'home',
            ]);
            return $this->redirect($redirect);
        }
        // display error if user submitted and authentication failed
        if ($this->request->is('post') && !$result->isValid()) {
            $errors = ((array)$result);
            $this->Flash->error('Login error: ' . json_encode($errors));
        }
    }//login

// in src/Controller/UsersController.php
    public function logout()
    {
        $result = $this->Authentication->getResult();
        // regardless of POST or GET, redirect if user is logged in
        if ($result && $result->isValid()) {
            $this->Authentication->logout();
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $this->redirect('/');
    }


    function beginReset()
    {
        $this->writeToLog('debug', 'BeginReset', true);

        //accept an email from a post form
        if ($this->request->is('post')) {
            //if the email exists -> there should be a new column in the users table 'reset_token' get a random text string 8 alpha characters and add that into the users database
            $emailSubmitted = $this->request->getData()['email'];
            $userExists = $this->Users->getUserByEmail($emailSubmitted);
            if ($userExists) {
                //that user does exist
                $userToken = $this->Users->resetAddToken($emailSubmitted);
                //pr ($userToken);
                $this->writeToLog('debug', '- token: ' . $userToken['reset_token'], false);

                $vars = [
                    'emailSubmitted' => $emailSubmitted,
                    'resetToken' => $userToken['reset_token'],
                    'url' => Router::url('/', true) . 'Users' . DS . 'reset' . DS . base64_encode($emailSubmitted) . DS . base64_encode($userToken['reset_token'])
                ];

                $sent = SetupCase::sendEmail($emailSubmitted, 'email_reset','support@domain.com', 'Email Password Reset', $vars);

                if ($sent) {
                    $this->set('email_sent', true);
                    $this->writeToLog('debug', 'email sent !');
                } else {
                    $this->writeToLog('debug', 'email could NOT be sent');
                }
            } else {
                //does not exist
                $this->Flash->error('Email does not exist');
                $this->writeToLog('debug', 'That email does NOT exist: ' . $emailSubmitted, false);
            }
        } else {
            //start
        }
    }

    function reset($email = false, $resetToken = false)
    {
        $this->writeToLog('debug', 'Reset', true);
        if ($this->request->is('post')) {
            $this->writeToLog('debug', 'POST');
            $user = $this->Users->getUserByEmailAndResetToken(base64_decode($email), base64_decode($resetToken));
            if (!empty($user)) {
                //email and token are still ok
                $newPassword = $this->request->getData()['new_password'];
                $passObj = new DefaultPasswordHasher;
                $userUpdatedArray = $this->Users->saveUserPassword(base64_decode($email), $passObj->hash($newPassword));
                if ($userUpdatedArray) {
                    $this->Users->resetRemoveToken(base64_decode($email)); //prevent phishing / replay attack
                    $this->Flash->success('New password has been updated');
                    $this->redirect('/');
                } else {
                    $this->Flash->error('Password could NOT be updated');
                }
            } else {
                $this->Flash->error('Email and/or token is not correct - could NOT reset the password');
            }
        } elseif (!$email || !$resetToken) {
            $this->writeToLog('debug', 'Email and/or token are missing', true);
            $this->Flash->error('Email and/or token are missing');
            $this->redirect('/');
        } else {
            //show a form so they can manually reset the password
            $user = $this->Users->getUserByEmailAndResetToken(base64_decode($email), base64_decode($resetToken));
            if (!empty($user)) {
                //correct token
                $this->writeToLog('debug', 'Correct token, showing form');
                $this->set('email', base64_decode($email));
            } else {
                //token is not correct
                $this->writeToLog('debug', 'Email and/or token is NOT correct');

                $this->Flash->error('Email and/or token is not correct');
                $this->redirect('/');
            }
        }
    }
}
