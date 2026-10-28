<?php
/**
 * Files S3 Offload. Spec: docs/features/MVP-Files-S3-Offload.md
 *
 * One file on purpose (a copy-and-paste code block): upload form, setup checks,
 * the file grid and the guide. Once the block is in a project, split it into
 * elements following that project's AGENTS.md.
 *
 * Every thumbnail starts as the local loading GIF; files-s3-offload.js swaps
 * in data-src once it has loaded.
 *
 * @var \App\View\AppView $this
 * @var array $filesSetup
 * @var array $files
 * @var int $filesMaxBytes
 * @var int $filesMaxMb
 * @var array $filesUploadUrl
 */
?>
<?= $this->Html->css('files-s3-offload'); ?>
<div class="container-fluid px-0 files-s3-offload">
    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Upload a file</h5>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, [
                        'type' => 'file',
                        'url' => $filesUploadUrl,
                        'class' => 'files-s3-offload-upload',
                        'data-max-bytes' => $filesMaxBytes,
                    ]); ?>
                    <div class="mb-3">
                        <?= $this->Form->control('file', [
                            'type' => 'file',
                            'label' => false,
                            'class' => 'form-control',
                            'required' => true,
                        ]); ?>
                        <div class="form-text">
                            Max <?= h($filesMaxMb); ?> MB. Images, PDF, MP4, ZIP, CSV, Excel and Word.
                        </div>
                        <div class="text-danger small files-s3-offload-too-big" hidden>
                            This file is larger than <?= h($filesMaxMb); ?> MB.
                        </div>
                    </div>
                    <?= $this->Form->button('Upload', ['class' => 'btn btn-primary w-100']); ?>
                    <?= $this->Form->end(); ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Setup</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 files-s3-offload-setup">
                        <tbody>
                        <?php foreach ($filesSetup['checks'] as $name => [$text, $badgeClass]): ?>
                            <tr>
                                <th class="text-nowrap"><?= h($name); ?></th>
                                <td><span class="badge <?= h($badgeClass); ?>"><?= h($text); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($filesSetup['counts'] as $status => $count): ?>
                            <tr>
                                <th>Your files: <?= h($status); ?></th>
                                <td><?= h($count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Your files</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($files)): ?>
                        <p class="text-muted mb-0">No files yet. Upload one to see it here.</p>
                    <?php endif; ?>
                    <div class="files-s3-offload-grid">
                        <?php foreach ($files as $file): ?>
                            <div class="files-s3-offload-tile">
                                <a href="<?= h($file->view_url); ?>" target="_blank" rel="noopener">
                                    <?= $this->Html->image('files-s3-offload-loading.gif', [
                                        'class' => 'files-s3-offload-img',
                                        'data-src' => $file->thumb_src,
                                        'alt' => $file->filename,
                                    ]); ?>
                                </a>
                                <div class="files-s3-offload-name" title="<?= h($file->filename); ?>"><?= h($file->filename); ?></div>
                                <div class="small text-muted">
                                    <?= h($file->size_label); ?>
                                    <span class="badge <?= h($file->status_badge_class); ?>" title="<?= h($file->last_error); ?>"><?= h($file->status); ?></span>
                                </div>
                                <div class="mt-1">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= h($file->view_url); ?>" target="_blank" rel="noopener">View</a>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= h($file->download_url); ?>">Download</a>
                                    <?php if (!empty($file->push_url)): ?>
                                        <?= $this->Form->postLink('push-s3', $file->push_url, ['class' => 'btn btn-sm btn-outline-success']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($file->delete_url)): ?>
                                        <?= $this->Form->postLink('Delete', $file->delete_url, [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'confirm' => 'Delete ' . $file->filename . '? This removes it from the server and from S3.',
                                        ]); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php /* ===== CodeBlocks guide: only for the SetupCase CodeBlocks page. Remove this section on a project. ===== */ ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?= $this->element('2026/codeBlocks/files_s3_offload'); ?>
                </div>
            </div>
        </div>
        <?php /* ===== End of CodeBlocks guide ===== */ ?>
    </div>
</div>
<?= $this->Html->script('files-s3-offload'); ?>
