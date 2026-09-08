<?php if (!isset($activeTab)) $activeTab = 'waiting'; ?>
<ul class="nav nav-tabs">
    <li class="nav-item">
        <?= $this->Html->link(
            'Waiting to Send',
            ['action' => 'index'],
            ['class' => 'nav-link' . ($activeTab === 'waiting' ? ' active' : '')]
        ) ?>
    </li>

    <li class="nav-item">
        <?= $this->Html->link(
            'Sent',
            ['action' => 'index', '?' => ['tab' => 'sent']],
            ['class' => 'nav-link' . ($activeTab === 'sent' ? ' active' : '')]
        ) ?>
    </li>
</ul>
<div class="card shadow-sm">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">Email Queues</h5>
                <p class="mb-0 text-muted small">
                    <?= $activeTab === 'sent' ? 'Emails that have already been sent.' : 'Emails waiting to be sent.' ?>
                </p>
            </div>

            <div class="d-flex gap-2">
                <?= $this->Html->link('Create Email', ['action' => 'create'], ['class' => 'btn btn-sm btn-primary']) ?>

                <?php if ($activeTab === 'waiting'): ?>
                    <?= $this->Form->postLink(
                        'Send All',
                        ['action' => 'sendAll'],
                        ['class' => 'btn btn-sm btn-warning', 'confirm' => 'Send every waiting email now?']
                    ) ?>
                    <?= $this->Form->postLink(
                        'Send All Test',
                        ['action' => 'sendAllTest'],
                        ['class' => 'btn btn-sm btn-outline-warning']
                    ) ?>
                    <?= $this->Form->postLink(
                        'Remove All',
                        ['action' => 'removeAll'],
                        ['class' => 'btn btn-sm btn-danger', 'confirm' => 'Remove every waiting email? This cannot be undone from the UI.']
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th style="width:1%;">Actions</th>
                <th>ID</th>
                <th>Created By</th>
                <th>To</th>
                <th>Subject</th>
                <th>Lang</th>
                <th><?= $activeTab === 'sent' ? 'Sent At' : 'Last Attempt' ?></th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="text-nowrap">
                        <?= $this->Html->link('Edit', ['action' => 'edit', $row->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>

                        <?php if (!$row->sent): ?>
                            <?= $this->Form->postLink('Send', ['action' => 'send', $row->id], ['class' => 'btn btn-sm btn-warning']) ?>
                            <?= $this->Form->postLink('Send Test', ['action' => 'sendTest', $row->id], ['class' => 'btn btn-sm btn-outline-warning']) ?>
                        <?php endif; ?>

                        <?= $this->Form->postLink(
                            'Remove',
                            ['action' => 'remove', $row->id],
                            ['class' => 'btn btn-sm btn-danger', 'confirm' => 'Remove this email?']
                        ) ?>
                    </td>

                    <td><?= h($row->id) ?></td>
                    <td><?= h($row->user->email ?? '-') ?></td>
                    <td>
                        <?= h($row->email_to) ?>
                        <?php if (!empty($row->cc)): ?>
                            <br/><span class="badge bg-secondary">CC: <?= h($row->cc) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($row->bcc)): ?>
                            <br/><span class="badge bg-secondary">BCC: <?= h($row->bcc) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($row->subject) ?></td>
                    <td><?= h($row->language) ?></td>
                    <td>
                        <?php $timestamp = $activeTab === 'sent' ? $row->sent_at : $row->last_attempt_at; ?>
                        <?= $timestamp ? h($timestamp->format('Y-m-d H:i')) : '-' ?>
                    </td>
                    <td>
                        <span
                            class="badge <?= h($row->status_badge_class) ?>"
                            <?php if (!empty($row->error)): ?>title="<?= h($row->error) ?>"<?php endif; ?>
                        ><?= h($row->status_label) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
