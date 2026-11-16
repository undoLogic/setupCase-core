<div class="card shadow-sm mb-4">
    <div class="card-header">
        <div class="d-flex align-items-center gap-2">
            <?= $this->Html->link(
                '←',
                ['controller' => 'CodeBlocks', 'action' => 'index'],
                ['class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Back to list']
            ) ?>
            <div>
                <h5 class="mb-1"><?= h($type->name) ?></h5>
                <p class="mb-0 text-muted small">Type #<?= $type->id ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Code blocks of this type</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Name</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($childRows as $row): ?>
                <tr>
                    <td><?= $this->Html->link($row['name'], $row['viewUrl']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($childRows)): ?>
                <tr>
                    <td class="text-muted">No code blocks of this type yet.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
