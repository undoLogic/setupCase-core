<div class="card shadow-sm mb-4">

    <!-- Header -->
    <div class="card-header">
        <div class="d-flex align-items-start gap-3">

            <!-- Back icon + title -->
            <div class="d-flex align-items-center gap-2">

                <?= $this->Html->link(
                    '←',
                    ['action' => 'index'],
                    [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'escape' => false,
                        'title' => 'Back to list',
                    ]
                ) ?>

                <div>
                    <h5 class="mb-1">
                        <?= h($entity->name) ?>
                    </h5>
                    <p class="mb-0 text-muted small">
                        Record #<?= $entity->id ?>
                    </p>
                </div>

            </div>

            <!-- Actions -->
            <div class="ms-auto d-flex gap-2 flex-wrap">

                <?php if ($canManage): ?>
                    <?= $this->Html->link(
                        'Edit',
                        ['prefix' => 'Manager', 'action' => 'edit', $entity->id],
                        ['class' => 'btn btn-sm btn-primary']
                    ) ?>

                    <?= $this->Form->postLink(
                        'Delete',
                        ['prefix' => 'Manager', 'action' => 'delete', $entity->id],
                        [
                            'class' => 'btn btn-sm btn-danger',
                            'confirm' => 'Are you sure you want to delete this record?'
                        ]
                    ) ?>
                <?php endif; ?>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-info"
                    data-bs-toggle="modal"
                    data-bs-target="#exampleModalLive">
                    Help
                </button>

            </div>

        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Left column: the record's fields -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Details</h6>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">
                        <?php if ($typeViewUrl): ?>
                            <?= $this->Html->link($typeName, $typeViewUrl) ?>
                        <?php else: ?>
                            <?= h($typeName) ?>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8"><?= h($entity->created) ?></dd>

                    <dt class="col-sm-4">Modified</dt>
                    <dd class="col-sm-8"><?= h($entity->modified) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Right column: long text (records under this one and change history go here when the record has them) -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Description</h6>
            </div>

            <div class="card-body">
                <?= $this->Text->autoParagraph(h($entity->description)) ?>
            </div>
        </div>
    </div>

</div>

<!-- Help Modal -->
<div class="modal fade" id="exampleModalLive" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Popup
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>
