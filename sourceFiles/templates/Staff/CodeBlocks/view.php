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

    <!-- Left column -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Details</h6>
            </div>

            <div class="card-body">
                <?= $this->Text->autoParagraph(h($entity->description)) ?>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Associated Data</h6>
            </div>

            <div class="card-body p-0">

                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Column 1</th>
                        <th>Column 2</th>
                        <th>Column 3</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach (range(1, 4) as $each): ?>
                        <tr>
                            <td class="text-nowrap">
                                Row #<?= $each ?>

                                <?= $this->Html->link(
                                    'Edit',
                                    ['action' => 'edit', $each],
                                    ['class' => 'btn btn-sm btn-outline-primary ms-2']
                                ) ?>

                                <?= $this->Form->postLink(
                                    'X',
                                    ['action' => 'delete', $each],
                                    [
                                        'class' => 'btn btn-sm btn-outline-danger ms-1',
                                        'confirm' => 'Really delete?'
                                    ]
                                ) ?>
                            </td>
                            <td>Info</td>
                            <td>Goes here…</td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>

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
