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

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * Create a user from the server console. This is the only way to add a user in
 * 5.x (no public signup). Usage:
 *
 *     bin/cake users add someone@example.com STAFF
 *
 * The password is asked for without echo, or generated and printed when left
 * empty. It is never taken from the command line (shell history, process list).
 */
class UsersAddCommand extends Command
{
    public static function defaultName(): string
    {
        return 'users add';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription('Create a user (the only way to add users in 5.x).')
            ->addArgument('email', ['required' => true, 'help' => 'Login email'])
            ->addArgument('user_type', [
                'required' => true,
                'help' => 'Role from config rbac',
                'choices' => array_keys((array)Configure::read('rbac')),
            ]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $password = $this->execute_readPassword($io);
        $generated = $password === '';
        if ($generated) {
            $password = bin2hex(random_bytes(9));
        }

        $result = $this->fetchTable('Users')->addUser(
            (string)$args->getArgument('email'),
            (string)$args->getArgument('user_type'),
            $password
        );

        if ($result['STATUS'] !== 200) {
            $io->error($result['MSG']);
            foreach ($result['ERRORS'] as $field => $errors) {
                $io->error('  ' . $field . ': ' . implode(', ', $errors));
            }

            return static::CODE_ERROR;
        }

        $io->success($result['MSG'] . ' (id ' . $result['id'] . ')');
        if ($generated) {
            $io->out('Generated password (shown once): ' . $password);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Read the password without echoing it on an interactive terminal.
     * Returns '' when not interactive or left empty (a password is then generated).
     */
    private function execute_readPassword(ConsoleIo $io): string
    {
        if (!function_exists('posix_isatty') || !posix_isatty(STDIN)) {
            return '';
        }

        $io->out('Password (min ' . \App\Model\Table\UsersTable::PASSWORD_MIN_LENGTH . ', empty = generate): ', 0);
        shell_exec('stty -echo');
        $password = trim((string)fgets(STDIN));
        shell_exec('stty echo');
        $io->out('');

        return $password;
    }
}
