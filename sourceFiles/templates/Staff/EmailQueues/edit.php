<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h5 class="mb-0">Edit Email #<?= h($entity->id) ?></h5>
        <p class="mb-0 text-muted small">Queued by <?= h($entity->user->email ?? 'unknown') ?></p>
    </div>

    <div class="card-body">
        <?php if (!empty($entity->error)): ?>
            <div class="alert alert-danger">
                <strong>Last attempt failed:</strong> <?= h($entity->error) ?>
            </div>
        <?php endif; ?>

        <?= $this->Form->create($entity, ['type' => 'file']) ?>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <?= $this->Form->control('email_to', ['label' => 'To', 'help' => 'Bare addresses only, comma delimited']) ?>
            </div>
            <div class="col-12 col-md-2">
                <?= $this->Form->control('email_from', [
                    'label' => 'From',
                    'options' => $fromOptions,
                    'default' => $defaultFrom,
                ]) ?>
            </div>
            <div class="col-12 col-md-3">
                <?= $this->Form->control('cc', ['label' => 'CC']) ?>
            </div>
            <div class="col-12 col-md-3">
                <?= $this->Form->control('bcc', ['label' => 'BCC']) ?>
            </div>

            <div class="col-12">
                <?= $this->Form->control('subject') ?>
            </div>

            <div class="col-12">
                <?= $this->Form->control('body', ['type' => 'textarea', 'rows' => 8]) ?>
            </div>

            <div class="col-12 col-md-3">
                <?= $this->Form->control('language', ['label' => 'Language', 'maxlength' => 2]) ?>
            </div>

            <div class="col-12">
                <?= $this->Form->control('attachments', [
                    'type' => 'file',
                    'multiple' => true,
                    'name' => 'attachments[]',
                    'label' => 'Add Attachments',
                ]) ?>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <?= $this->Form->button('Save', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link('Cancel', ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<?php if (!empty($entity->email_queue_attachments)): ?>
    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="mb-0">Attachments</h6>
        </div>
        <ul class="list-group list-group-flush">
            <?php foreach ($entity->email_queue_attachments as $attachment): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= h($attachment->original_filename) ?></span>
                    <span class="text-muted small"><?= h($attachment->mime_type) ?>, <?= h($attachment->size) ?> bytes</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
