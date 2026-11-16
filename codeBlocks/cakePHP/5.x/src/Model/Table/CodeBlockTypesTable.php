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

/**
 * Demo table for the Application Foundation pages (no group_id: demo data only).
 * A type is the parent record of its code blocks.
 */
class CodeBlockTypesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('code_block_types');
        $this->addBehavior('Timestamp');

        $this->hasMany('CodeBlocks', [
            'foreignKey' => 'code_block_type_id',
        ]);
    }

    /**
     * One type with the code blocks under it, for its read-only view page.
     */
    public function getForView(int $id): array
    {
        $type = $this->find()
            ->where(['CodeBlockTypes.id' => $id])
            ->contain('CodeBlocks', fn ($q) => $q->orderBy(['CodeBlocks.name' => 'ASC']))
            ->first();

        if ($type === null) {
            return ['STATUS' => 404, 'MSG' => 'Type not found'];
        }

        return ['STATUS' => 200, 'MSG' => 'Type found', 'type' => $type];
    }
}
