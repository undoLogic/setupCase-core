<?php
/**
 * PDF.js viewer: toolbar + lazily rendered pages, opening on $docPage.
 * Logic lives in webroot/js/documentation-viewer.js.
 *
 * @var \App\View\AppView $this
 * @var string $webroot
 * @var string $docTitle
 * @var int $docPage
 * @var bool $docEmbed
 * @var string $docFileUrl
 * @var string $docDownloadUrl
 * @var string $docOwnTabUrl
 * @var string $docPageBaseUrl
 * @var bool $docUpdateAddressBar
 */
$pdfJs = $webroot . 'modules/pdfjs-3.11.174/';
?>
<style>
    .documentation-pages {
        height: <?= $docEmbed ? 'calc(100vh - 4.5rem)' : '75vh'; ?>;
        overflow-y: auto;
        position: relative;
        background: #525659;
        padding: .5rem;
    }
    .documentation-page {
        margin: 0 auto .5rem;
        background: #fff;
        max-width: min(100%, 960px);
    }
    .documentation-page canvas {
        display: block;
        width: 100%;
    }
</style>

<div class="documentation-viewer"
     data-file-url="<?= h($docFileUrl); ?>"
     data-page="<?= (int)$docPage; ?>"
     data-worker-url="<?= h($pdfJs . 'pdf.worker.min.js'); ?>"
     data-page-base-url="<?= h($docPageBaseUrl); ?>"
     data-update-address-bar="<?= $docUpdateAddressBar ? '1' : '0'; ?>">

    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <h2 class="h5 mb-0 me-auto"><?= h($docTitle); ?></h2>

        <div class="input-group input-group-sm w-auto">
            <button type="button" class="btn btn-outline-secondary" data-doc-prev aria-label="Previous page">&lsaquo;</button>
            <input type="number" min="1" class="form-control text-center" style="width: 4.5rem;"
                   value="<?= (int)$docPage; ?>" data-doc-page-input aria-label="Page">
            <span class="input-group-text">of&nbsp;<span data-doc-page-count>-</span></span>
            <button type="button" class="btn btn-outline-secondary" data-doc-next aria-label="Next page">&rsaquo;</button>
        </div>

        <button type="button" class="btn btn-sm btn-outline-primary" data-doc-copy-link>
            Copy link to this page
        </button>

        <a class="btn btn-sm btn-outline-primary" href="<?= h($docDownloadUrl); ?>" download>
            Download PDF
        </a>

        <a class="btn btn-sm btn-outline-primary" href="<?= h($docOwnTabUrl); ?>" target="_blank" rel="noopener" data-doc-own-tab>
            View in its own tab
        </a>
    </div>

    <div class="alert alert-danger d-none" data-doc-error></div>

    <div class="documentation-pages" data-doc-pages></div>
</div>

<script src="<?= $pdfJs; ?>pdf.min.js"></script>
<script src="<?= $webroot; ?>js/documentation-viewer.js"></script>
