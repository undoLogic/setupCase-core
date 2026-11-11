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

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Demo table for the Application Foundation pages (no group_id: demo data only).
 * AuditLog behavior not ported yet.
 */
class CodeBlocksTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('code_blocks');
        $this->addBehavior('Timestamp');

        $this->belongsTo('CodeBlockTypes', [
            'foreignKey' => 'code_block_type_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 99)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        return $validator;
    }
}
