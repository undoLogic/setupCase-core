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

namespace App\Model\Table;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public const PASSWORD_MIN_LENGTH = 12;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

    /**
     * Create a user. Only called from the console (bin/cake users add); there is
     * no public signup in 5.x. user_type must be a role in config 'rbac'.
     */
    public function addUser(string $email, string $userType, string $password): array
    {
        $user = $this->newEntity(
            ['email' => trim($email), 'user_type' => $userType, 'password' => $password],
            ['validate' => 'addUser', 'accessibleFields' => ['user_type' => true, 'password' => true]]
        );

        if ($user->hasErrors()) {
            return ['STATUS' => 400, 'MSG' => 'User not created', 'ERRORS' => $user->getErrors()];
        }

        $user->set('password', (new DefaultPasswordHasher())->hash($password));

        if (!$this->save($user)) {
            return ['STATUS' => 400, 'MSG' => 'User not created', 'ERRORS' => $user->getErrors()];
        }

        return ['STATUS' => 200, 'MSG' => 'User created', 'id' => $user->id];
    }

    public function validationAddUser(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['email', 'user_type', 'password'])
            ->email('email')
            ->maxLength('email', 99)
            ->inList('user_type', array_keys((array)Configure::read('rbac')), 'Unknown user type')
            ->minLength('password', self::PASSWORD_MIN_LENGTH, 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email'], 'This email is already used'));

        return $rules;
    }
}
