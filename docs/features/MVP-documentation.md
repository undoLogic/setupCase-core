# SetupCase Core Block: Documentation

## Purpose

Some clients sit behind locked-down corporate networks that block every non-whitelisted domain, so Google Docs links are unreachable for them. This block mirrors a Google Doc as a PDF onto the project's own (whitelisted) domain and serves it through a viewer that supports jumping to a specific page.

Goals:

- Replace the manual "File > Download > PDF > upload to private directory" routine with one regenerate action.
- Serve documentation from the product's own domain so it looks like part of the product.
- On client projects, keep the PDF private: only logged-in users can view it.
- Preserve the "check out page 22" workflow with page-addressable links.
- Be simple enough to point an AI at SetupCase core and say "set up documentation."

This work has two deliverables:

1. The working Documentation feature in SetupCase core (the reference copy).
2. A **Blocks** page that is the setup guide for installing this feature on client projects. See `## CodeBlocks Setup Page`.

## MVP Status

This is MVP work (AGENTS.md "MVP Work"):

- No automated tests are created or scaffolded.
- The `## Testing` section below lists expected behaviour to be checked manually; nothing is marked covered until it has actually been run.
- Lightweight checks only (PHP lint, page renders).

## Access: Public in Core, Prefixed on Client Projects

| Where | Controller location | Example URL | Who can reach it |
|---|---|---|---|
| SetupCase core (reference / demo) | `src/Controller/DocumentationController.php` (no prefix) | `/en/documentation/view/1` | Anyone (public) |
| Client project | `src/Controller/<Prefix>/DocumentationController.php` (e.g. `Staff`) | `/staff/en/documentation/view/1` | Logged-in users holding that prefix |

- In SetupCase core the block is deliberately public, so the demo can be opened from the Blocks page without logging in. The core demo must only point at a non-sensitive, demo Google Doc.
- On client projects the controller **must** be moved under a logged-in prefix. That is what makes the PDF private. Without it, anyone with the URL can read the client's documentation.
- This is stated in three places so it cannot be missed:
  1. A `WARNING` block in the controller source (see `## Disclaimer comment`).
  2. A matching warning in the model-layer class next to `$documents`.
  3. A required step on the CodeBlocks setup page.
- Public in core also means anyone who knows the regenerate code can trigger regenerate there. It only re-fetches the demo doc, so this is acceptable for the reference copy.

## Scope: starter shape

This is deliberately the minimal form, not a half-finished one.

- **One controller**, `DocumentationController` (public in core, prefixed on client projects).
- **One model-layer class** holding the config and the fetch/save/stream logic (fat model, slim controller).
- **No database.** Configuration lives in a property on the model-layer class.
- Bigger projects can later graduate it to multiple controllers and move regenerate into the admin area. That is out of scope here.
- The controller carries a disclaimer comment stating that it is the starter shape, that it must be prefixed on client projects, and when to graduate it.

## Conventions (AGENTS.md)

- **Role authorization:** on client projects, handled by the prefix RBAC only (e.g. `Staff`, held by every role). No login or role checks are duplicated inside the actions. In core there is no prefix, by design (see above).
- **HTTP method by effect:** `view` and `file` are GET (read-only). `regenerate` is a **documented MVP exception**: it is a GET link with a `?code=` check, chosen for simplicity (one link, bookmarkable). AGENTS.md requires state changes to be POST; this exception is accepted because regenerate only refreshes from the configured source and never overwrites with anything but a validated PDF. The exception must be stated in a comment on the action. Moving it to POST (or a console command) is the first step when this graduates past MVP.
- **Data scoping:** documentation is project-wide, not tenant data, so there is no `group_id` / `user_id` scoping. This is a deliberate exception and must be stated in a comment in the model-layer class.
- **Precondition on the target (destructive overwrite):** regenerate only overwrites the stored PDF when the key exists in config and the fetched response is a valid PDF. Otherwise it refuses and the existing file is left untouched. This check lives in the model layer, not the controller.
- **Public model methods** return at minimum `['STATUS' => ..., 'MSG' => ...]`. Helpers are private and named `<publicFunction>_<helper>()`.
- **Passive view:** the controller hands the template ready-to-use vars (title, PDF URL with version query, start page, own-tab URL). Templates do not compute any of them, so moving the controller under a prefix needs no template changes.
- **Elements:** `sourceFiles/templates/element/2026/documentation/view_<location>.php`.
- **Vendor assets:** PDF.js is added as an untouched vendor asset. Any overrides go under `sourceFiles/webroot/js` / `sourceFiles/webroot/css`.

## Assumptions

- Source docs live on Sacha-Lewis's own Google Workspace and are link-shared as "anyone with the link can view."
- Because the docs are link-shared, the server fetches the export **without any Google credentials**. No API key or service account is needed for this version.
- The server does **not** borrow the browser's Google login. Browser cookies never reach a server-to-Google request. If private (non-link-shared) docs are ever needed, the server will need its own credential (service account or stored token), which is a later concern.
- Unprefixed actions in core are reachable without login (`AppController` marks them unauthenticated). On client projects, prefixed routes require login. To be confirmed per client project during setup.
- The server can make outbound HTTPS requests to `docs.google.com` (and follow its redirect to `googleusercontent.com`).

## Configuration

A single array keyed by number, on the model-layer class. Most projects will only use key `1`, but starting with an array avoids a refactor when a project has more than one doc.

```php
// Starter shape. See the disclaimer at the top of DocumentationController.
// WARNING: on client projects the controller must live under a logged-in
// prefix, otherwise these documents are public.
protected $documents = [
    1 => [
        'title'    => 'Project Documentation',
        'source'   => 'https://docs.google.com/document/d/<DOC_ID>/edit', // or just the Doc ID
        'filename' => 'project-documentation.pdf', // fixed name, never changes
    ],
];

// Starting default: CakePHP tmp (sourceFiles/tmp/documentation/), private and not web-served.
protected $storagePath = TMP . 'documentation' . DS;
```

- `storagePath` must never be under `sourceFiles/webroot`, not even for testing: the file would be served directly and skip the controller entirely.
- Starting default is `TMP . 'documentation' . DS`. The directory is created on first regenerate if missing, and `/sourceFiles/tmp/documentation/` is added to `.gitignore`.
- tmp may be wiped by a deploy or cleanup. If that happens, the viewer shows "not generated yet" until regenerate is run again. Move to a dedicated private storage path later if this becomes a problem.

### Regenerate code

- Read using the ENV Vars block pattern: server value (`get_cfg_var`) first, then Docker env (`env`), name `DOCUMENTATION_REGENERATE_CODE`.
- No default and never committed in source. If it is not set, regenerate is disabled (404).
- The code is deterrence, not security: it stops crawlers, link previews, and random visitors from triggering fetches. It will appear in server logs and browser history. Do not reuse this pattern for anything sensitive.

### Fixed filename

Each document is always saved under the same hard-coded name. Regenerate overwrites the file in place, so every link in the software keeps working and only the content underneath changes.

## Functions

URLs below use the core (unprefixed) shape `/{language}/documentation/{action}/...`. On client projects the prefix is added in front, e.g. `/staff/en/documentation/...`.

### `GET /en/documentation/regenerate/{key}?code=<code>`

Fetches the latest PDF from Google and saves it to the private directory. GET is a documented MVP exception (see Conventions).

1. If `DOCUMENTATION_REGENERATE_CODE` is not set, or `?code=` does not match it (compared with `hash_equals`), return 404 and do nothing.
2. Look up `{key}` in `$documents`. If missing, 404.
3. Extract the Doc ID from `source` and validate it (`[A-Za-z0-9_-]+`). If invalid, refuse.
4. Fetch the public PDF export (with a timeout, following redirects):
   `https://docs.google.com/document/d/<DOC_ID>/export?format=pdf`
5. Confirm the response is a PDF (HTTP 200, PDF content type, body starts with `%PDF`). If Google returns an HTML page instead, the doc is probably not link-shared; fail loudly and do not overwrite the existing file.
6. Write to a temp file in the same directory, then rename it over `storagePath/filename` so a failed fetch never leaves a broken PDF.
7. Flash a confirmation (or the failure reason) including the timestamp, and redirect back to the view page.

Triggered by opening the link directly (e.g. a bookmark kept by whoever maintains the doc). The code is not shown on the view page; there is no public Regenerate button.

### `GET /en/documentation/file/{key}`

Streams the raw PDF bytes. Used internally by the viewer, and by the Download PDF button with `?download=1`.

1. Look up `{key}`. If missing, 404.
2. If the file has not been generated yet, 404 with a clear message.
3. Stream `storagePath/filename` with `Content-Type: application/pdf` and `Content-Disposition: inline` (or `attachment` when `?download=1`, so the file is saved instead of opened).
4. Never expose the internal path or redirect to it.

### `GET /en/documentation/view/{key}/{page?}`

The viewer page.

1. Look up `{key}`. If missing, 404.
2. `{page}` defaults to 1; non-numeric or < 1 falls back to 1. A page beyond the end opens the last page (handled by the viewer).
3. Render a page that embeds **PDF.js**, loads the PDF from `documentation/file/{key}?v=<mtime>`, and opens on `{page}`.
4. As the reader scrolls or uses the page controls, the address bar is updated to `documentation/view/{key}/{page}` without a reload (`history.replaceState`), so the URL always points at the current page. Not done inside the modal iframe (`?embed=1`), which must not rewrite its own URL.
5. Toolbar buttons: **Copy link to this page** (copies the full URL of the current page; falls back to a prompt when the clipboard API is unavailable, e.g. plain HTTP), **Download PDF** (`file/{key}?v=<mtime>&download=1`), and **View in its own tab**.
6. The controller provides `docPageBaseUrl` (full URL, without the page), `docUpdateAddressBar`, and `docDownloadUrl`; the template and JS do not build URLs.

Example links (core):

- `/en/documentation/view/1` opens doc 1 at page 1.
- `/en/documentation/view/1/22` opens doc 1 at page 22.

## Why streaming plus PDF.js (and not a redirect with `#page=22`)

- Browsers' built-in PDF viewers support `#page=N`, but the hash is never sent to the server.
- A redirect to the file with the hash attached would work, but it would require the PDF to sit at a publicly reachable URL.
- On client projects the PDF must stay gated: "public on Google via an unguessable link" is not the same as "anyone can hit this file on my server."
- So the controller streams the bytes (behind the prefix login on client projects), and the page number becomes a real route argument that PDF.js reads to open on the right page.
- Core uses the same streaming path even though it is public, so the reference copy is identical to what client projects run.

## Front-end usage

Two framings, same viewer:

- **Modal.** Inside the app, render element `2026/documentation/view_modal` so the doc opens in context. The modal holds an iframe to `documentation/view/{key}/{page}?embed=1`; `?embed=1` switches the view to the bare `documentation_embed` layout (no sidebar). The iframe only loads when the modal opens. The host page's controller sets `docModalUrl`, `docOwnTabUrl`, and `docModalTitle`.
- **Own tab.** A "View in its own tab" button opens `documentation/view/{key}/{page}` as a full page.
- **Sharing a page.** "Copy link to this page" copies the current page's full URL. This is the link to paste into emails ("Here it is on page 22").

## Caching

Because the file name and URL never change, browsers or a CDN may serve a stale copy after regeneration. Append a version query to the file URL the viewer loads, for example:

```
/en/documentation/file/1?v=<file modified timestamp>
```

The file stays one name on disk; the link changes whenever the content does. The controller builds this URL; the template only prints it.

## Known limitations

- **Public in core.** Intentional for the demo; only a non-sensitive demo doc may be configured there.
- **Regenerate is GET + code (MVP exception).** Deterrence only; the code leaks into logs and history. Move to POST or a console command when graduating.
- **Page numbers drift** when the doc is edited and content reflows. Acceptable here: page links are sent in emails and are not meant to persist long term.
- **Link-shared docs only.** Private docs need a server-side Google credential.
- **Any logged-in role can regenerate** on client projects when placed under `Staff`, because every role holds `Staff`. Regenerate only refreshes from the source doc, so this is acceptable for the starter shape. If it must be stricter, move regenerate to a narrower prefix (e.g. `Admin`).
- **Single controller.** Larger projects should move regenerate into admin and split responsibilities.

## Disclaimer comment (for the top of the controller)

```php
/**
 * Documentation (SetupCase core, starter shape)
 *
 * Mirrors link-shared Google Docs to PDFs on this domain and serves them
 * through a PDF.js viewer with optional page jumps.
 *
 * WARNING - PRIVACY:
 *  In SetupCase core this controller has NO prefix and is PUBLIC on purpose
 *  (demo only, non-sensitive demo doc). On client projects it MUST be moved
 *  under a logged-in prefix (e.g. src/Controller/Staff/, namespace
 *  App\Controller\Staff). Without a prefix, anyone with the URL can read
 *  the client's documentation.
 *
 * This is the deliberately minimal form:
 *  - regenerate is a GET link with ?code= (MVP exception to the AGENTS.md
 *    POST rule; code from DOCUMENTATION_REGENERATE_CODE, deterrence only)
 *  - config lives in the model-layer class, no database
 *  - only link-shared ("anyone with the link") docs are supported
 *
 * On larger projects, move regenerate into the admin area and split this
 * into multiple controllers as needed.
 */
```

## CodeBlocks Setup Page

The Blocks page is the setup guide for installing this feature on client projects. The intended reader is an AI agent working in a client project ("set up documentation using the SetupCase Documentation block"), with a developer following along.

The setup page is the reusable recipe; the SetupCase core implementation is its reference copy. When the implementation changes, the setup page must be updated in the same change so they never drift.

### Menu entry

- Add a `Documentation` link to the **Blocks** group in `AppController::setupMenu()`, in alphabetical position (between `AGENTS.md` and `Email Queues`), pointing to `['prefix' => false, 'controller' => 'CodeBlocks', 'action' => 'documentation']`.
- Not added to **Blocks with DB**: this block has no database.

### Page structure

- `CodeBlocksController::documentation()` follows the `emailQueues()` pattern: sets `codeBlocks_title`, `codeBlocks_subTitle`, and `codeBlocks_renderFiles` so the real source files are shown, copy/paste ready, straight from SetupCase core (never a hand-maintained copy).
- `templates/CodeBlocks/documentation.php` renders only the element `2026/codeBlocks/documentation`.
- The page links to the working public demo at `/en/documentation/view/1`.
- A prominent warning at the top: "In core this is public for demo purposes. On client projects it must be placed under a logged-in prefix."

### Page content (the setup recipe)

Written as numbered, unambiguous steps an AI agent can follow without guessing:

1. **What it is:** one paragraph (purpose, link-shared Google Doc mirrored as a PDF with page links, private behind login on client projects).
2. **Prerequisites to confirm in the client project:**
   - CakePHP prefix routing with a logged-in prefix (e.g. `Staff`) whose routes require login.
   - Outbound HTTPS to `docs.google.com` is allowed.
   - The Google Doc is shared as "anyone with the link can view."
3. **Files to copy:** controller, model-layer class, base template, elements, viewer JS, PDF.js asset, each with its source path in SetupCase core and its target path in the client project.
4. **Move under a prefix (required):** place the controller in `src/Controller/<Prefix>/`, change its namespace to `App\Controller\<Prefix>`, and move the base template to `templates/<Prefix>/Documentation/`. Confirm a logged-out request to `view`, `file`, and `regenerate` is refused. Do not skip this step.
5. **Values to fill in:** `$documents` (title, source, filename) and `$storagePath` (default `TMP . 'documentation' . DS`). Never under webroot. Set `DOCUMENTATION_REGENERATE_CODE` on the server / Docker env to a random string (never in source).
6. **Project wiring:** add `/sourceFiles/tmp/documentation/` to `.gitignore`; add a menu link to the viewer in the client's own navigation.
7. **First run and verification:** open `documentation/regenerate/1?code=<code>`, confirm the PDF is saved, open `view/1` and `view/1/<page>`, then run the short checklist from this file's `## Testing` (Regenerate, File Streaming, Viewer, Client Project Privacy).
8. **Known limitations and when to graduate:** link-shared only, page drift, any logged-in role can regenerate, move regenerate to admin on larger projects.

Rules for the page itself:

- No client names, Doc IDs, or real URLs; use placeholders like `<DOC_ID>`.
- Keep it copy/paste friendly and short per step (AGENTS.md "CodeBlocks examples simple and copy/paste friendly").
- Passive view: the page is static explanatory markup plus the rendered files the controller provides.

## Implementation Surfaces

- `sourceFiles/src/Controller/DocumentationController.php` (`view`, `file`, `regenerate`; no prefix in core)
- `sourceFiles/src/Service/DocumentationService.php` (config + logic, matching the existing `CronService`)
- `sourceFiles/templates/Documentation/view.php` (short base template)
- `sourceFiles/templates/element/2026/documentation/view_viewer.php`
- `sourceFiles/templates/element/2026/documentation/view_modal.php`
- `sourceFiles/templates/layout/documentation_embed.php` (bare layout for the modal iframe)
- `sourceFiles/webroot/js/documentation-viewer.js`
- PDF.js 3.11.174 vendor asset: `sourceFiles/webroot/modules/pdfjs-3.11.174/` (`pdf.min.js`, `pdf.worker.min.js`, `LICENSE`). Self-hosted on purpose: a CDN may be blocked on the same locked-down networks this block exists for. 3.x is used because it ships a classic (non-module) build.
- `sourceFiles/src/Controller/AppController.php` (`setupMenu()`, Blocks group)
- `sourceFiles/src/Controller/CodeBlocksController.php` (`documentation()`)
- `sourceFiles/templates/CodeBlocks/documentation.php`
- `sourceFiles/templates/element/2026/codeBlocks/documentation.php`

## Build checklist

- [x] Create the model-layer class with `$documents` (demo doc only), `$storagePath`, the privacy warning, and the no-group-scope comment.
- [x] Implement regenerate logic: Doc ID validation, fetch, PDF validation, atomic write.
- [x] Create the unprefixed `DocumentationController` with the disclaimer and privacy `WARNING`; `regenerate` as GET + `?code=` with the MVP-exception comment.
- [x] Read `DOCUMENTATION_REGENERATE_CODE` (ENV Vars pattern); regenerate disabled when unset.
- [x] Implement `file` with inline streaming.
- [x] Add PDF.js to the project assets.
- [x] Implement `view` with page argument; all URLs (file with version query, own tab, regenerate) built in the controller.
- [x] Add the modal and the "View in its own tab" button.
- [x] Create `TMP/documentation/` on first regenerate if missing; add `/sourceFiles/tmp/documentation/` to `.gitignore`.
- [x] Add the `Documentation` link to the Blocks menu and the `CodeBlocks::documentation()` setup page with the 8-step recipe and the privacy warning.
- [ ] Run the manual scenarios in `## Testing`.

## Testing

Manual only (MVP). Automated tests intentionally skipped.

### Regenerate

**Intent:** The stored PDF is refreshed from Google only when a valid PDF is fetched; a failure never damages the existing file.

**Verification notes (2026-10-14):** no real link-shared demo doc is configured yet, so the success scenarios are unverified. Failure handling was checked by calling the service directly: placeholder/invalid Doc ID gives 400, a non-existent Doc ID gives Google HTTP 404 and a clear message, and in both cases the existing PDF was unchanged. A private (not link-shared) doc was not tested.

**Surfaces:**
- `GET /en/documentation/regenerate/{key}?code=<code>`
- Documentation model-layer class

#### Scenarios

- [x] A wrong or missing `code` returns 404 and changes nothing.
- [x] With `DOCUMENTATION_REGENERATE_CODE` unset, regenerate returns 404 even with a `code` given.
- [ ] Regenerating a link-shared doc saves the PDF under its fixed filename and shows a confirmation with timestamp.
- [ ] Regenerating again overwrites the same file; existing view links keep working and show the new content.
- [ ] A doc that is not link-shared (Google returns HTML) fails with a clear message and the existing PDF is unchanged.
- [ ] A network failure or timeout fails with a clear message and the existing PDF is unchanged.
- [x] An unknown key returns 404.
- [x] The view page does not show the code or a regenerate link.

### File Streaming

**Intent:** The PDF is only served through the controller, never from a direct path.

**Surfaces:**
- `GET /en/documentation/file/{key}`

#### Scenarios

- [x] The PDF is returned inline (`application/pdf`).
- [x] With `?download=1` the PDF is returned as an attachment (saved, not opened).
- [x] An unknown key returns 404.
- [x] A key whose PDF has not been generated yet returns 404 with a clear message.
- [x] The response never reveals the internal storage path.
- [x] The PDF is not reachable by any URL under the web root.

### Viewer

**Intent:** Users can open the doc in-app or in its own tab, on a specific page.

**Surfaces:**
- `GET /en/documentation/view/{key}/{page?}`
- `view_viewer.php`, `view_modal.php`

#### Scenarios

- [x] `view/1` opens doc 1 on page 1.
- [x] `view/1/22` opens doc 1 on page 22.
- [x] A non-numeric or zero page opens page 1.
- [x] A page beyond the end opens the last page.
- [ ] After regenerate, the viewer loads the new content (version query changed), not a cached copy.
- [ ] The modal and "View in its own tab" both open the same doc and page.
- [x] The viewer layout works at phone width.
- [ ] Scrolling or using the page controls updates the address bar to `view/{key}/{page}` without a reload.
- [x] Inside the modal iframe (`?embed=1`) the address bar is not rewritten (`data-update-address-bar="0"`).
- [ ] "Copy link to this page" copies the full URL of the current page and shows "Link copied"; without the clipboard API it shows a prompt with the link.
- [ ] "Download PDF" saves the file instead of opening it.

### Core Demo Access

**Intent:** In SetupCase core the block is public on purpose, and clearly marked as such.

**Surfaces:**
- `DocumentationController` (unprefixed)

#### Scenarios

- [x] A logged-out user can open `/en/documentation/view/1` in core.
- [x] The controller source contains the privacy `WARNING` block.
- [x] The model-layer class contains the privacy warning next to `$documents`.
- [ ] Core is configured with a non-sensitive demo doc only.

### Client Project Privacy

**Intent:** Once installed on a client project, the documentation is only reachable by logged-in users.

**Surfaces:**
- `/<prefix>/en/documentation/view|file|regenerate`

#### Scenarios

- [ ] The controller lives under a logged-in prefix with the matching namespace.
- [ ] A logged-out request to `view` redirects to login.
- [ ] A logged-out request to `file` is denied.
- [ ] A logged-out request to `regenerate` is denied and changes nothing, even with the correct code.
- [ ] No unprefixed `/en/documentation/...` route serves the documentation.

### CodeBlocks Setup Page

**Intent:** The block is discoverable in the CodeBlocks sidebar, and its setup page is enough on its own to install the feature on a client project.

**Surfaces:**
- `AppController::setupMenu()`
- `CodeBlocksController::documentation()`

#### Scenarios

- [x] The Blocks group shows a `Documentation` link.
- [x] The link opens the Documentation setup page with title, subtitle, and rendered source files.
- [x] The link is highlighted as active while on that page.
- [x] The privacy warning is visible at the top of the page.
- [x] The "move under a prefix" step is present and marked required.
- [x] The setup page links to the working public demo.
- [x] Every file the recipe tells you to copy is shown on the page from its real SetupCase core path.
- [x] The page contains no client names, real Doc IDs, or real URLs.
- [ ] Following only the setup page, the feature can be installed in a fresh client project and the Regenerate, File Streaming, Viewer, and Client Project Privacy scenarios pass there.
