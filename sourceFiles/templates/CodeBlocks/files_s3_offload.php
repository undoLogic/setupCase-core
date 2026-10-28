<?php
/**
 * Public, read-only Files S3 Offload page: the guide and every source file, no login.
 * The working page (upload, push-s3, delete) is Staff/Files/index.
 *
 * @var \App\View\AppView $this
 */
?>
<div class="alert alert-info">
    This page is read-only. The working version (upload, push-s3, delete) is
    <strong>Blocks with DB &rarr; Files S3 Offload</strong> and needs a Staff login.
</div>
<?= $this->element('2026/codeBlocks/files_s3_offload'); ?>
