<?php
// src/Model/Table/ArticlesTable.php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Utility\Text;
//use Cake\Validation\Validator;

class CodeBlocksTable extends Table
{
    public function initialize(array $config):void
    {
        parent::initialize($config); // REQUIRED

        $this->setTable('code_blocks');

        $this->addBehavior('Timestamp');
        $this->addBehavior('AuditLog');

       // belongsTo
        $this->belongsTo('CodeBlockTypes', [
            'foreignKey' => 'code_block_type_id'
        ]);
    }



}// end
