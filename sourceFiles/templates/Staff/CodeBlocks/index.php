<div class="card shadow-sm">

    <!-- Card Header -->
    <div class="card-header">

        <!-- Title + Actions -->
        <div class="d-flex align-items-start gap-3">

            <div>
                <h5 class="mb-1">Title</h5>
                <p class="mb-0 text-muted small">Sub-title</p>
            </div>

            <div class="ms-auto d-flex gap-2 flex-wrap">
                <?= $this->Html->link('Create', ['action' => 'create'], ['class' => 'btn btn-sm btn-primary']) ?>


                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#exampleModalLive">
                    Popup
                </button>
            </div>

        </div>

        <!-- Filters -->
        <div class="row g-2 mt-3 pt-3 border-top">
            <div class="col-12 col-lg-3">
                <?= $this->Form->control('dropdown', [
                    'options' => ['1' => '1', '2' => '2'],
                    'empty' => 'Choose …',
                    'label' => false,
                    'class' => 'form-select form-select-sm'
                ]) ?>
            </div>

            <div class="col-12 col-lg-3">
                <?= $this->Form->control('dropdown2', [
                    'options' => ['1' => '1', '2' => '2'],
                    'empty' => 'Choose …',
                    'label' => false,
                    'class' => 'form-select form-select-sm'
                ]) ?>
            </div>

            <div class="col-12 col-lg-3">
                <?= $this->Form->control('dropdown3', [
                    'options' => ['1' => '1', '2' => '2'],
                    'empty' => 'Choose …',
                    'label' => false,
                    'class' => 'form-select form-select-sm'
                ]) ?>
            </div>

            <div class="col-12 col-lg-3">
                <?= $this->Form->control('dropdown4', [
                    'options' => ['1' => '1', '2' => '2'],
                    'empty' => 'Choose …',
                    'label' => false,
                    'class' => 'form-select form-select-sm'
                ]) ?>
            </div>
        </div>

    </div>

    <!-- Card Body -->
    <div class="card-body p-0">

        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 1%;">Actions</th>
                <th>Name</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($rows as $each): ?>
                <tr>
                    <td class="text-nowrap">
                        <?= $this->Html->link('View', ['action' => 'view', $each['id']], ['class' => 'btn btn-sm btn-primary']) ?>
                        <?= $this->Html->link('Edit', ['prefix' => 'Manager', 'action' => 'edit', $each['id']], ['class' => 'btn btn-sm btn-warning']) ?>
                        <?= $this->Form->postLink(
                            'X',
                            ['prefix' => 'Manager', 'action' => 'delete', $each['id']],
                            ['class' => 'btn btn-sm btn-danger', 'confirm' => 'Are you sure?']
                        ) ?>
                    </td>
                    <td><?= h($each['name']) ?></td>
                    <td><?= h($each['description']) ?></td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>





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

