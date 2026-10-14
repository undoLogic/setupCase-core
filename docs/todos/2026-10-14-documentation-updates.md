# Documentation block: add "Copy link to this page" and "Download PDF"

## Description

Port two small features from a client project back into the SetupCase core Documentation block. Keep everything else in the block as is.

## Notes

Below is a change brief for the AI working on setupcase-core. I wrote it against the core pattern as published on testflight, so it covers only the two features you want to port. It leaves out the client-only work: the Staff prefix, Slides support, the layouts and the header modal.

### Goal

1. **Copy link to this page.** As the reader scrolls or uses the page controls,
   the address bar updates to `.../documentation/view/<key>/<page>` without a
   reload, so the URL always points at the current page. A button copies the
   full URL of the current page to the clipboard.
2. **Download PDF.** A button downloads the stored PDF instead of opening it.

The existing URL format (`view/<key>/<page>`) already opens the right page
server-side, so no new parameter is needed for deep links.

### Files to change

- `src/Controller/DocumentationController.php`
- `templates/element/2026/documentation/view_viewer.php`
- `webroot/js/documentation-viewer.js`
- `docs/features/MVP-documentation.md`

### 1. Controller

#### `view()`: add three view vars
