/*
 * Files S3 Offload. Spec: docs/features/MVP-Files-S3-Offload.md
 *
 * 1. Loading placeholder: every img.files-s3-offload-img starts as the local
 *    loading GIF. The real URL (data-src) loads in the background and is
 *    swapped in when ready; on failure the tile shows "not available".
 * 2. Upload size check: refuse a too-large file before sending it. Over PHP's
 *    post_max_size the request would arrive empty and fail the CSRF check.
 */
(function () {
    document.querySelectorAll('img.files-s3-offload-img[data-src]').forEach(function (img) {
        var loader = new Image();
        loader.onload = function () {
            img.src = loader.src;
            img.classList.add('files-s3-offload-loaded');
        };
        loader.onerror = function () {
            img.classList.add('files-s3-offload-failed');
            img.removeAttribute('src');
            img.alt = 'Not available';
        };
        loader.src = img.getAttribute('data-src');
    });

    var form = document.querySelector('form.files-s3-offload-upload');
    if (!form) {
        return;
    }
    var input = form.querySelector('input[type="file"]');
    var warning = form.querySelector('.files-s3-offload-too-big');
    var maxBytes = parseInt(form.getAttribute('data-max-bytes'), 10);

    function tooBig() {
        return input.files.length > 0 && input.files[0].size > maxBytes;
    }

    input.addEventListener('change', function () {
        warning.hidden = !tooBig();
    });
    form.addEventListener('submit', function (event) {
        if (tooBig()) {
            event.preventDefault();
            warning.hidden = false;
        }
    });
})();
