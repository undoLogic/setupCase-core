<?php
/**
 * Documentation viewer. See docs/features/MVP-documentation.md.
 *
 * @var \App\View\AppView $this
 * @var string $docTitle
 * @var bool $docExists
 * @var string $docMsg
 */
?>
<div class="container-fluid px-0">
    <div class="row">
        <div class="col-12">
            <?php if ($docExists): ?>
                <?= $this->element('2026/documentation/view_viewer'); ?>
            <?php else: ?>
                <h2 class="h4"><?= h($docTitle); ?></h2>
                <div class="alert alert-warning"><?= h($docMsg); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
