<?php
/**
 * Reusable modal that opens the Documentation viewer in context on any page.
 * The host controller passes ready-made URLs, e.g. for doc 1 page 22:
 *   docModalUrl   => /en/documentation/view/1/22?embed=1
 *   docOwnTabUrl  => /en/documentation/view/1/22
 * The iframe only loads when the modal is opened.
 *
 * @var \App\View\AppView $this
 * @var string $docModalUrl
 * @var string $docOwnTabUrl
 * @var string $docModalTitle
 */
?>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#documentationModal">
    Open in modal
</button>
<a class="btn btn-outline-primary" href="<?= h($docOwnTabUrl); ?>" target="_blank" rel="noopener">
    View in its own tab
</a>

<div class="modal fade" id="documentationModal" tabindex="-1" aria-labelledby="documentationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="documentationModalLabel"><?= h($docModalTitle); ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe data-src="<?= h($docModalUrl); ?>" title="<?= h($docModalTitle); ?>"
                        class="w-100 border-0 d-block" style="height: 80vh;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('documentationModal').addEventListener('show.bs.modal', function () {
        var frame = this.querySelector('iframe');
        if (!frame.src) {
            frame.src = frame.dataset.src;
        }
    });
</script>
