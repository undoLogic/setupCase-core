<?php
/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */
// src/Model/Table/ArticlesTable.php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Utility\Text;
//use Cake\Validation\Validator;

class CodeBlockTypesTable extends Table
{
    public function initialize(array $config):void
    {
        parent::initialize($config); // REQUIRED

        $this->setTable('code_block_types');

        $this->addBehavior('Timestamp');
        $this->addBehavior('AuditLog');

       // belongsTo
//        $this->hasMany('CodeBlocks', [
//            'foreignKey' => 'code_block_type_id'
//        ]);
    }



}// end
