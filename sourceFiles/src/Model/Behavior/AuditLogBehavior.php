<?php
// src/Model/Behavior/AuditLogBehavior.php

namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Util\AuditContext;

class AuditLogBehavior extends Behavior
{
    protected $_defaultConfig = [
        'actions' => ['insert', 'update', 'delete'],
        'ignoreContain' => [
            'ActivityLogs',
            'AuditLogs',
        ],
    ];

    /**
     * BEFORE SAVE (UPDATE ONLY)
     * Capture TRUE database state (with contain + joinData)
     */
    public function beforeSave(
        EventInterface $event,
        EntityInterface $entity,
        $options
    ): void {
        if ($entity->isNew() || !$entity->id) {
            return;
        }

        $table   = $this->table();
        $locator = FactoryLocator::get('Table');

        $original = $locator->get($table->getAlias())->get(
            $entity->id,
            ['contain' => $this->buildContainList($table)]
        );

        $entity->set('_audit_before', $original->toArray(), ['guard' => false]);
    }

    /**
     * BEFORE DELETE
     * Capture state before deletion
     */
    public function beforeDelete(
        EventInterface $event,
        EntityInterface $entity,
        $options
    ): void {
        $entity->set('_audit_before', $entity->toArray(), ['guard' => false]);
    }

    /**
     * AFTER SAVE COMMIT (INSERT / UPDATE)
     * Audit only once transaction is committed
     */
    public function afterSaveCommit(
        EventInterface $event,
        EntityInterface $entity,
        $options
    ): void {
        $action = $entity->isNew() ? 'insert' : 'update';

        if (!in_array($action, $this->getConfig('actions'), true)) {
            return;
        }

        $before = $entity->get('_audit_before') ?? null;

        $this->logChange($entity, $action, $before);
    }

    /**
     * AFTER DELETE COMMIT
     */
    public function afterDeleteCommit(
        EventInterface $event,
        EntityInterface $entity,
        $options
    ): void {
        if (!in_array('delete', $this->getConfig('actions'), true)) {
            return;
        }

        $before = $entity->get('_audit_before') ?? null;

        $this->logChange($entity, 'delete', $before);
    }

    /**
     * Central audit writer (transaction-safe, guarded)
     */
    protected function logChange(
        EntityInterface $entity,
        string $action,
        ?array $before = null
    ): void {
        $table = $this->table();

        // Prevent recursion
        if ($table->getAlias() === 'AuditLogs') {
            return;
        }

        $locator = FactoryLocator::get('Table');

        // Try to reload AFTER from DB (preferred, includes joinData)
        try {
            $afterEntity = $locator->get($table->getAlias())->get(
                $entity->id,
                ['contain' => $this->buildContainList($table)]
            );

            $after = $afterEntity->toArray();

        } catch (RecordNotFoundException $e) {
            // Fallback: never let audit logging break the operation
            $after = $entity->toArray();
        }

        unset($after['_audit_before']);

        $auditLogs = $locator->get('AuditLogs');

        $auditLogs->saveOrFail(
            $auditLogs->newEntity([
                'table_name'      => $table->getTable(),
                'entity_id'       => $entity->id ?? null,
                'action'          => $action,
                'original_fields' => $before ? json_encode($before) : null,
                'changed_fields'  => json_encode($after),
                'user_id'         => AuditContext::userId(),
                'ip_address'      => AuditContext::ip(),
            ])
        );
    }

    /**
     * Build safe contain list (shared by BEFORE and AFTER)
     */
    protected function buildContainList($table): array
    {
        $locator = FactoryLocator::get('Table');

        $ignore = array_map(
            'strtolower',
            (array)$this->getConfig('ignoreContain')
        );

        $contain = [];

        foreach ($table->associations() as $association) {
            $alias = $association->getName();

            if (in_array(strtolower($alias), $ignore, true)) {
                continue;
            }

            try {
                $locator->get($alias);
                $contain[] = $alias;
            } catch (\Throwable $e) {
                // skip unresolved / invalid associations
            }
        }

        return $contain;
    }

}
