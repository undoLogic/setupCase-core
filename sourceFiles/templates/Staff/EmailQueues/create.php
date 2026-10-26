<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Create Email</h5>
    </div>

    <div class="card-body">
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
                <?= $this->Form->control('language', ['label' => 'Language', 'default' => 'en', 'maxlength' => 2]) ?>
            </div>

            <div class="col-12">
                <?= $this->Form->control('attachments', [
                    'type' => 'file',
                    'multiple' => true,
                    'name' => 'attachments[]',
                    'label' => 'Attachments',
                ]) ?>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <?= $this->Form->button('Queue Email', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link('Cancel', ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
