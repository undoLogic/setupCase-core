# SetupCase Core Block: Documentation

## Purpose

Some clients sit behind locked-down corporate networks that block every non-whitelisted domain, so Google Docs links are unreachable for them. This block mirrors a Google Doc as a PDF onto the project's own (whitelisted) domain and serves it through a gated viewer that supports jumping to a specific page.

Goals:

- Replace the manual "File → Download → PDF → upload to private directory" routine with one regenerate call.
- Serve documentation from the product's own domain so it looks like part of the product.
- Keep the PDF private: only logged-in users can view it.
- Preserve the "check out page 22" workflow with page-addressable links.
- Be simple enough to point an AI at SetupCase core and say "set up documentation."

## Scope: starter shape

This is deliberately the minimal form, not a half-finished one.

- **One controller**, named `documentation`.
- **No database.** Configuration lives in a variable on the controller.
- Bigger projects can later graduate it to multiple controllers and move regenerate into the admin area. That is out of scope here.
- The controller carries a disclaimer comment stating that it is the starter shape and when to graduate it.

## Assumptions

- Source docs live on Sacha-Lewis's own Google Workspace and are link-shared as "anyone with the link can view."
- Because the docs are link-shared, the server fetches the export **without any Google credentials**. No API key or service account is needed for this version.
- The server does **not** borrow the browser's Google login. Browser cookies never reach a server-to-Google request. If private (non-link-shared) docs are ever needed, the server will need its own credential (service account or stored token), which is a later concern.

## Configuration

A single array on the controller, keyed by number. Most projects will only use key `1`, but starting with an array avoids a refactor when a project has more than one doc.

```php
// Starter shape. See the disclaimer at the top of the controller.
protected $documents = [
    1 => [
        'title'    => 'Project Documentation',
        'source'   => 'https://docs.google.com/document/d/<DOC_ID>/edit', // or just the Doc ID
        'filename' => 'project-documentation.pdf', // fixed name, never changes
    ],
];

protected $regenerateToken = '<random-string>';
protected $storagePath     = '<internal/private directory, outside the web root>';
```

### Fixed filename

Each document is always saved under the same hard-coded name. Regenerate overwrites the file in place, so every link in the software keeps working and only the content underneath changes.

## Functions

### `documentation/regenerate/{key}/{token}`

Fetches the latest PDF from Google and saves it to the internal directory.

1. Check that `{token}` matches `$regenerateToken`. If not, return 403/404.
2. Look up `{key}` in `$documents`. If missing, return 404.
3. Extract the Doc ID from `source`.
4. Fetch the public PDF export:
   `https://docs.google.com/document/d/<DOC_ID>/export?format=pdf`
5. Confirm the response is a PDF (content type and `%PDF` header). If Google returns an HTML page instead, the doc is probably not link-shared; fail loudly and do not overwrite the existing file.
6. Write to a temp file, then rename it over `storagePath/filename` so a failed fetch never leaves a broken PDF.
7. Return a simple confirmation, including the timestamp.

**Security note.** The token in the URL is deterrence, not security. It keeps random visitors and bots from hammering Google through your server. It will appear in server logs and browser history. That is acceptable for a regenerate button, but do not reuse this pattern for anything sensitive.

### `documentation/file/{key}`

Streams the raw PDF bytes. Used internally by the viewer.

1. Require a logged-in user. If not, deny.
2. Look up `{key}`. If missing, return 404.
3. Read `storagePath/filename` and stream it with `Content-Type: application/pdf` and `Content-Disposition: inline`.
4. Never expose the internal path or redirect to it.

### `documentation/view/{key}/{page?}`

The gated viewer page.

1. Require a logged-in user.
2. Look up `{key}`. If missing, return 404.
3. Render an HTML page that embeds **PDF.js**, loads the PDF from `documentation/file/{key}`, and opens on `{page}` (default 1).

Example links:

- `documentation/view/1` opens doc 1 at page 1.
- `documentation/view/1/22` opens doc 1 at page 22.

## Why streaming plus PDF.js (and not a redirect with `#page=22`)

- Browsers' built-in PDF viewers support `#page=N`, but the hash is never sent to the server.
- A redirect to the file with the hash attached would work, but it would require the PDF to sit at a publicly reachable URL.
- The PDF must stay gated: "public on Google via an unguessable link" is not the same as "anyone can hit this file on my server."
- So the controller streams the bytes behind the login, and the page number becomes a real route argument that PDF.js reads to open on the right page.

## Front-end usage

Two framings, same viewer, same gate:

- **Modal.** Inside the app, render the PDF.js view in a modal so the doc opens in context.
- **Own tab.** A "View in its own tab" button opens `documentation/view/{key}/{page}` as a full page. This is the link to paste into emails ("Here it is on page 22").

## Caching

Because the file name and URL never change, browsers or a CDN may serve a stale copy after regeneration. Append a version query to the file URL the viewer loads, for example:

```
documentation/file/1?v=<file modified timestamp>
```

The file stays one name on disk; the link changes whenever the content does.

## Known limitations

- **Page numbers drift** when the doc is edited and content reflows. Acceptable here: page links are sent in emails and are not meant to persist long term.
- **Link-shared docs only.** Private docs need a server-side Google credential.
- **Token-gated regenerate** is deterrence only.
- **Single controller.** Larger projects should move regenerate into admin and split responsibilities.

## Disclaimer comment (for the top of the controller)

```php
/**
 * Documentation (SetupCase core, starter shape)
 *
 * Mirrors link-shared Google Docs to PDFs on this domain and serves them
 * through a login-gated PDF.js viewer with optional page jumps.
 *
 * This is the deliberately minimal form:
 *  - regenerate is protected by a URL token (deterrence, not security)
 *  - config lives in this controller, no database
 *  - only link-shared ("anyone with the link") docs are supported
 *
 * On larger projects, move regenerate into the admin area and split this
 * into multiple controllers as needed.
 */
```

## Build checklist

- [ ] Create the `documentation` controller with the disclaimer comment.
- [ ] Add the `$documents` array, `$regenerateToken`, and `$storagePath`.
- [ ] Implement `regenerate` with token check, PDF validation, and atomic write.
- [ ] Implement `file` with login check and inline streaming.
- [ ] Add PDF.js to the project assets.
- [ ] Implement `view` with login check and page argument.
- [ ] Add the cache-busting version query to the file URL.
- [ ] Add the modal and the "View in its own tab" button.
- [ ] Test: regenerate, view page 1, view page 22, logged-out access denied, wrong token denied, non-shared doc fails without overwriting.