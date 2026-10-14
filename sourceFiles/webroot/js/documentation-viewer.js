/**
 * Documentation viewer (SetupCase core). See docs/features/MVP-documentation.md.
 *
 * Loads the PDF from data-file-url with PDF.js, lays out one placeholder per
 * page, renders pages lazily as they scroll into view, and opens on
 * data-page (clamped to the last page). Requires modules/pdfjs-3.11.174/pdf.min.js.
 */
(function () {
    'use strict';

    function init(root) {
        var pagesEl = root.querySelector('[data-doc-pages]');
        var input = root.querySelector('[data-doc-page-input]');
        var countEl = root.querySelector('[data-doc-page-count]');
        var ownTab = root.querySelector('[data-doc-own-tab]');
        var errorEl = root.querySelector('[data-doc-error]');
        var ownTabBase = root.dataset.ownTabBaseUrl;
        var pageEls = [];
        var current = 1;

        pdfjsLib.GlobalWorkerOptions.workerSrc = root.dataset.workerUrl;

        pdfjsLib.getDocument(root.dataset.fileUrl).promise.then(function (pdf) {
            return pdf.getPage(1).then(function (first) {
                build(pdf, first.getViewport({scale: 1}));
                goTo(Math.min(parseInt(root.dataset.page, 10) || 1, pdf.numPages));
            });
        }).catch(function (err) {
            errorEl.textContent = 'The document could not be loaded (' + err.message + ').';
            errorEl.classList.remove('d-none');
        });

        // One sized placeholder per page so scroll positions are right before rendering.
        function build(pdf, firstViewport) {
            var ratio = firstViewport.height / firstViewport.width;
            countEl.textContent = pdf.numPages;
            input.max = pdf.numPages;

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        render(pdf, entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {root: pagesEl, rootMargin: '200% 0px'});

            for (var i = 1; i <= pdf.numPages; i++) {
                var el = document.createElement('div');
                el.className = 'documentation-page';
                el.dataset.page = i;
                el.style.aspectRatio = '1 / ' + ratio;
                pagesEl.appendChild(el);
                pageEls.push(el);
                observer.observe(el);
            }
            pagesEl.addEventListener('scroll', trackCurrent);
        }

        function render(pdf, el) {
            pdf.getPage(parseInt(el.dataset.page, 10)).then(function (page) {
                var base = page.getViewport({scale: 1});
                var scale = (el.clientWidth / base.width) * (window.devicePixelRatio || 1);
                var viewport = page.getViewport({scale: scale});
                var canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                el.style.aspectRatio = viewport.width + ' / ' + viewport.height;
                el.appendChild(canvas);
                page.render({canvasContext: canvas.getContext('2d'), viewport: viewport});
            });
        }

        function goTo(n) {
            n = Math.max(1, Math.min(n, pageEls.length));
            pagesEl.scrollTop = pageEls[n - 1].offsetTop;
            setCurrent(n);
        }

        function setCurrent(n) {
            current = n;
            input.value = n;
            ownTab.href = ownTabBase + '/' + n;
        }

        function trackCurrent() {
            var top = pagesEl.scrollTop + pagesEl.clientHeight / 3;
            for (var i = pageEls.length - 1; i >= 0; i--) {
                if (pageEls[i].offsetTop <= top) {
                    if (current !== i + 1) {
                        setCurrent(i + 1);
                    }
                    return;
                }
            }
        }

        root.querySelector('[data-doc-prev]').addEventListener('click', function () { goTo(current - 1); });
        root.querySelector('[data-doc-next]').addEventListener('click', function () { goTo(current + 1); });
        input.addEventListener('change', function () { goTo(parseInt(input.value, 10) || 1); });
    }

    document.querySelectorAll('.documentation-viewer').forEach(init);
})();
