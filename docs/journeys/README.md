# Journeys

A **journey** is a named, ordered flow that a real person walks through the product - "submit a quote", "edit my profile", "reset a password".

Features describe what the software *can* do. Journeys describe the *path* a person takes through it.

Each journey file is both:

1. **The spec we discuss** - readable markdown you can paste into a conversation, rework, and paste back.
2. **The executable source** - the runner walks it to capture screenshots, and the PDF builder renders it into a client-facing walkthrough.

One file, two outputs. Change the file, regenerate, send the client the new PDF. They review the flow without having to test it themselves.

The file format is deterministic. The runner never uses AI to interpret a journey - every field is either literal data or plain text for the client.

---

## Status

- **This README (the spec):** in place.
- **Browser method:** proven by hand in InternalTesting - headless Chromium driven over the DevTools Protocol (CDP) by a small Python script. See [Runner](#runner).
- **Journey runner and PDF builder:** built, in `docs/journeys/runner/`. One command runs a journey and builds its PDF - see [Running a journey](#running-a-journey). No AI is involved in a run.
- **`aiAccess` login:** proven in InternalTesting. The code to copy into each project is in [aiAccess reference code](#aiaccess-reference-code).

---

## Folder layout

```
docs/journeys/
|-- README.md            <- this file (the spec)
|-- logo.png             <- your company logo, shown on the PDF cover (optional)
|-- files/               <- fixture files used by `upload` steps
|-- runner/              <- the runner: run.sh, journey_runner.py, requirements.txt
|-- edit-profile.md      <- one file per journey
|-- submit-quote.md
`-- ...
```

- One journey per file.
- File names are kebab-case and describe the flow: `submit-quote.md`, not `journey1.md`.

---

## Where journeys run

Journeys run **only against the local Docker environment**. Never against pending, staging, or live.

- Journeys create and change real records (a "submit a quote" journey submits a quote). The Docker database is disposable; reset it with `dockerLinux/9resetDatabase.sh`.
- Login uses `aiAccess`, which refuses to run anywhere except Docker (see below). That guard is also what keeps the runner off a real server.
- The runner's base URL is runner config (default `http://localhost`). Journey files only ever contain local paths, never a host.

### Starting data

- Each journey must run on its own from a freshly reset database plus the project's seed data. Never depend on records created by another journey.
- If a journey needs specific data to exist (a product, a customer, an open order), document it in the linked feature file and seed it - don't create it with extra journey steps that the client would then see in the PDF.
- If the database can't be reset (it holds dev data you want to keep), prefix every record a journey creates with `[TEST]` so it can be found and deleted afterwards. The prefix shows up in the screenshots, so reset instead for client-facing PDFs.

---

## Login: aiAccess

The runner logs in through `UsersController::aiAccess()` - a Docker-only action that signs in a dedicated AI user without a password. No credentials are ever written into a journey file.

Flow for a journey with `login: ai`:

1. Runner opens `/users/ai-access?redirect=<start_url>`. The route has **no language segment**, e.g. `http://localhost/users/ai-access?redirect=/staff/en/pages/dashboard`.
2. `aiAccess` confirms `Environments::getActive() === 'DOCKER'`, otherwise 404.
3. It loads the AI user via `UsersTable::getAiAccessUser()` (matched on `AI_ACCESS_EMAIL`, active, not removed), otherwise 404.
4. It sets the identity and redirects to `start_url`. Only local paths are accepted: it must start with `/`; `//` and `\` are rejected; anything invalid goes to `/`.
5. No screenshot is taken during login. This also keeps the "Logged in as the AI user" flash message out of the PDF. The runner then starts step 1.

What the AI user can reach is decided by its role and the normal prefix RBAC (`config/app.php` `rbac`) - journeys get no special access. In InternalTesting the AI user is `ai_user@example.com` with role OWNER. The default SetupCase `rbac` only defines ADMIN, MANAGER and STAFF, so each project must give the AI user a role that exists in its own `rbac` and reaches every prefix its journeys use.

### Prerequisites

Before journeys work in a project, it needs:

- [ ] `UsersController::aiAccess()` and `aiAccess_redirectTarget()` - see [aiAccess reference code](#aiaccess-reference-code).
- [ ] `UsersTable::getAiAccessUser()` and the `AI_ACCESS_EMAIL` constant - same section.
- [ ] The AI user row in the Docker database, active, with the role the journeys need.
- [ ] A route for `/users/ai-access` (no language segment).
- [ ] `aiAccess` allowed without authentication (`addUnauthenticatedActions`), or the login redirect catches it.
- [ ] `Environments::getActive()` returning `DOCKER` for **web** requests inside Docker. Older SetupCase versions return `LOCAL` for any request to `localhost` and only reach `DOCKER` from the CLI, so `aiAccess` would always 404. Check what your project returns for a web request to `localhost` before relying on it.
- [ ] The Docker stack running (`dockerLinux/1reStartDocker.sh`).
- [ ] The runner tooling on the host - see [Runner](#runner).


### aiAccess reference code

Copy these into each project. They are the versions proven in InternalTesting.

**`src/Model/Table/UsersTable.php`** - the constant and the lookup:

```php
    // The one account UsersController::aiAccess() may sign in as (DOCKER only)
    public const AI_ACCESS_EMAIL = 'ai_user@example.com';

    /**
     * The one user UsersController::aiAccess() may log in as (DOCKER only).
     * Must be active and not removed.
     */
    public function getAiAccessUser(): array
    {
        $user = $this->find()
            ->where([
                'Users.email' => self::AI_ACCESS_EMAIL,
                'Users.is_active' => 1,
                'Users.removed' => 0,
            ])
            ->first();
        if (!$user) {
            return ['STATUS' => 404, 'MSG' => 'AI user ' . self::AI_ACCESS_EMAIL . ' not found or inactive'];
        }

        return ['STATUS' => 200, 'MSG' => 'AI user found', 'user' => $user];
    }//getAiAccessUser
```

**`src/Controller/UsersController.php`** - the action and its redirect guard. Needs `use App\Util\Environments;` and `use Cake\Http\Exception\NotFoundException;`.

```php
    /**
     * DOCKER-only password-less login as the AI user (see docs/journeys/README.md) so an
     * AI agent can click around the local build. The AI user only has the access its
     * own group/roles give it. Any other environment gets a plain 404, so the action
     * looks like it doesn't exist.
     */
    public function aiAccess()
    {
        $activeEnv = Environments::getActive();
        if ($activeEnv != 'DOCKER') {
            throw new NotFoundException();
        }

        $response = $this->Users->getAiAccessUser();
        if ($response['STATUS'] !== 200) {
            throw new NotFoundException($response['MSG']);
        }

        $this->request->getSession()->write('temp_group_id', false);
        $this->Authentication->setIdentity($response['user']);
        $this->Flash->success('Logged in as the AI user (DOCKER only).');

        return $this->redirect($this->aiAccess_redirectTarget());
    }//aiAccess

    /**
     * Optional ?redirect=/some/local/path so a single (headless) browser visit can log
     * in and land on the page to inspect. Local paths only - never another host.
     */
    private function aiAccess_redirectTarget(): string
    {
        $redirect = (string)$this->request->getQuery('redirect', '');
        if (strncmp($redirect, '/', 1) !== 0 || strncmp($redirect, '//', 2) === 0 || strpos($redirect, '\\') !== false) {
            return '/';
        }

        return $redirect;
    }//aiAccess_redirectTarget
```

Notes when copying:

- `temp_group_id` clears a group a previous session may have switched to. Drop the line if the project has no group switching.
- `is_active` and `removed` must match the project's `users` columns. Adjust the `where` if they differ.
- The route and the unauthenticated-action entry are listed in [Prerequisites](#prerequisites). Add the route before `fallbacks()`, e.g. `$builder->connect('/users/ai-access', ['controller' => 'Users', 'action' => 'aiAccess']);`.
- Never loosen the `DOCKER` check or the redirect guard. They are what keep this off real servers and stop it being used as an open redirect.

---

## File format

A journey file has YAML front matter, followed by one `##` heading per step.

```markdown
---
client: CLIENT NAME
project: BIG PROJECT
name: Edit your profile
description: How a staff member updates their own contact details.
feature: docs/features/user-profile.md
login: ai
start_url: /staff/en/users/profile
---

## Open your profile

- action: goto
- target: /staff/en/users/profile
- expect: [data-testid="profile-form"]
- caption: Your profile holds the details other staff see on orders.

## Change your phone number

- action: fill
- target: #phone
- value: 514-555-0100
- expect: #phone
- caption: Type the new number. Nothing is saved until you press Save.

## Save the changes

- action: click
- target: [data-testid="profile-save"]
- expect: [data-testid="profile-form"]
- expect_text: Your profile has been saved
- caption: A green message confirms the change straight away.
```

### Front matter

| Field         | Required | Notes                                                                                                 |
|---------------|----------|-------------------------------------------------------------------------------------------------------|
| `client`      | yes      | The company the journey is for, e.g. `CLIENT NAME`. Shown at the top of the PDF cover.                |
| `project`     | yes      | The project or app name, e.g. `Big Project`. Shown next to `client` on the cover.      |
| `name`        | yes      | Human-readable journey name. Used as the PDF title.                                                   |
| `description` | yes      | One sentence. Appears on the PDF cover.                                                               |
| `feature`     | no       | Path to the feature file this journey walks through.                                                  |
| `login`       | yes      | `ai` (log in through `aiAccess`) or `none` (public pages).                                            |
| `start_url`   | yes      | Local path where the runner lands before step 1. Full route including language and prefix (see URLs). |

### Step fields

Each step is a `##` heading followed by `- key: value` lines, in this order:

| Field         | When | Purpose |
|---------------|------|---------|
| heading       | always | The step **title**, shown above the screenshot. |
| `action`      | always | What the runner does. See the action table. |
| `target`      | if the action needs it | A local path (for `goto`) or a selector. |
| `value`       | if the action needs it | The text, option, or file. |
| `expect`      | always | A selector that must be **visible** after the action. |
| `dialog`      | optional | `accept` or `dismiss`. The action opens a browser `confirm()`/`alert()` box; the runner answers it. |
| `expect_text` | optional | Text that must appear on the page after the action. |
| `caption`     | always | The explanation shown below the screenshot. |

### Actions

| Action   | `target`        | `value` | What the runner does |
|----------|-----------------|---------|----------------------|
| `goto`   | local path      | -       | Opens `<base URL><target>`. |
| `click`  | selector        | -       | Scrolls the element to the centre of the screen and clicks it. |
| `fill`   | selector        | text    | Sets the field's value, then fires `input` and `change` so page scripts and validation react. |
| `select` | selector        | option label | Chooses the option with that visible label and fires `change`. If jQuery is on the page, uses `$(el).val(x).trigger('change')` so select2 widgets update too. |
| `editor` | textarea id     | text    | Sets a TinyMCE editor's content with `tinymce.get(<target>).setContent(<value>)`. `target` is the bare id, no `#`. |
| `upload` | selector        | file name | Attaches `docs/journeys/files/<value>` to the file input (CDP `DOM.setFileInputFiles`). |
| `wait`   | -               | -       | Does nothing; the runner just waits for `expect`. Use for slow screens. |
| `email_code` | selector    | email address | Reads the newest `email_queues` row whose `email_to` is `value` (from the Docker database), takes the first 6-digit number in its text, and fills it into `target` like `fill`. |
| `email_preview` | - | email address | Shows the newest `email_queues` row whose `email_to` is `value` as an email-client page: From, To, Subject, attachment names, then the message HTML exactly as queued. Its `expect` is `[data-testid="email-preview"]`. |
| `email_attachments` | - | email address | Shows every page of every PDF attached to that same newest email, side by side, each labelled with its file name and page. Its `expect` is `[data-testid="email-attachments"]`. |

Browser dialogs (`confirm()`, `alert()`) block the page until answered. A step whose action opens one must say `dialog: accept` or `dialog: dismiss`; the runner answers it with CDP `Page.handleJavaScriptDialog`. An unexpected dialog stops the run. Dialogs are native browser boxes, so they never appear in screenshots - mention them in the caption if the client should know.

`email_code` exists because verification codes are only ever delivered by email. Every email goes through the queue first, so the runner can read the code there instead of from an inbox. It only works where the runner can reach the Docker database.

`email_preview` and `email_attachments` let the client see what the customer receives, without anyone opening an inbox. The runner builds these pages itself from the queue (it is not a page of the software), so they leave the site: put them after the last step that clicks through the software. Attachment paths are stored as the container sees them; the runner maps the container web root (`/var/www/vhosts/website.com/www`) to the project folder on the host and renders PDF pages with `pdftoppm` (poppler-utils).

There is deliberately no "run this JavaScript" action. If a widget needs one, add a named action here so every journey drives it the same way.

A field the action doesn't use must not be written. A missing required field, an unknown field, or an unknown action stops the run with an error naming the journey and step.

### How a step runs

1. Perform the action. If the target selector matches nothing, the step fails - it is never skipped. If the step has `dialog`, answer the dialog the action opens.
2. Wait for `document.readyState === 'complete'` (this covers the page load after a click submits a form).
3. Wait for `expect` to be visible (timeout: 10 seconds). This replaces fixed sleeps - the step goes on as soon as the screen is ready.
4. If `expect_text` is set, wait for that text to appear.
5. Capture **one screenshot**.

If any part fails, the run stops. The runner reports the failing step and saves a debug screenshot outside the PDF output. A PDF is never built from a partial run.

### Parsing rules

- Front matter is YAML.
- Everything between one `##` heading and the next is one step.
- Step lines are `- key: value`. The key is everything before the **first** `": "`; the value is everything after it, to the end of the line, taken literally. No quoting, no escaping - so selectors like `[data-testid="x"]` and `input[type=submit][value="Save"]` need nothing special.
- Selectors are standard CSS, exactly as `document.querySelector()` accepts them. Tool-specific extensions such as Playwright's `:has-text()` are not supported.
- Values are single-line.
- Blank lines are ignored. Any other line inside a step is an error.

---

## URLs

Every SetupCase route carries a language segment, and prefixed routes put the prefix first:

| Kind      | Pattern                                      | Example |
|-----------|----------------------------------------------|---------|
| Public    | `/<language>/<controller>/<action>`          | `/en/users/login` |
| Prefixed  | `/<prefix>/<language>/<controller>/<action>` | `/staff/en/email-queues` |

Controller and action names are dashed (`EmailQueues::index` -> `/staff/en/email-queues`). Always write the full path - the runner never adds a language or prefix.

---

## Selectors

In order of preference:

1. `[data-testid="..."]` - added to the template for this purpose. Names describe the thing: `profile-save`, `quote-submit`.
2. `#id` - the ids CakePHP's `FormHelper` already generates (`#email`, `#password`, `#phone`).
3. Stable attributes - a link's route or a field's name: `a[href="/staff/en/project-tasks/create"]`, `[name="email"]`.
4. Nothing else. No Bootstrap classes, no positional selectors (`nth-child`, `:first-child`), no matching on button labels - they break on the next layout or copy change.

To find selectors on a page, use the CDP driver's `js` command on the running page, e.g. `Array.from(document.querySelectorAll('a')).map(a=>a.innerText.trim()+' => '+a.getAttribute('href'))`.

SetupCase templates don't have `data-testid` attributes yet. Adding them to the templates a journey touches is part of writing that journey.

---

## Writing rules

These apply to humans and AI agents alike.

**Step numbers are never stored.** Order in the file *is* the order. The PDF builder counts steps at render time and prints "Step 3 of 12". Inserting or removing a step renumbers everything automatically - do not write numbers into headings or captions.

**Titles are short.** Four or five words, 40 characters max. Say *where you are*, not what happens: "Review the quote", not "The customer now reviews their quote before sending".

**Captions are hard-capped at 140 characters.** That is two lines on the page and no more. If you can't explain a step in 140 characters, the step is doing too much - split it into two steps.

**Write for the client, not the developer.** Plain language, present tense, no selectors, no component names, no jargon. The PDF goes to people who will never see the code. Selectors live in `target` and `expect`, never in titles or captions.

**AI drafts, humans refine.** Agents may generate titles and captions. Once a human has edited one, treat the wording as intentional and don't rewrite it unless asked.

---

## Journeys and feature files

A journey is a **client walkthrough, not a test**.

- The feature file (`docs/features/`) stays the single source of truth for behaviour and its `## Testing` scenarios (see `AGENTS.md`).
- A journey never replaces a `## Testing` scenario and is never counted as test coverage.
- Link the journey to its feature with `feature:`. When the feature's behaviour changes, update the journey in the same change.

---

## Screenshots

- Browser viewport: **1920 x 1080**, device scale factor 1, desktop (set with CDP `Emulation.setDeviceMetricsOverride`). The InternalTesting driver hard-codes 1440 x 1000 - the journey runner must use 1920 x 1080.
- Captured at **16:9**, uncropped and unannotated. Capture the viewport, not the full page.
- Files are written as `<output>/<run-name>/NN-<title-slug>.png`, e.g. `screenshots/2026-09-29_14-05-32-journey-customize-to-submit/02-change-your-phone-number.png`. `<run-name>` is the PDF's file name without `.pdf` (see [PDF layout](#pdf-layout)), so each run's PDF and screenshot folder sit side by side with the same name - delete both together when a run is no longer needed. The number comes from the step's position at run time; it is never written in the journey file.
- Nothing is drawn over the screenshot. The image stays clean so nothing important is ever covered.

---

## PDF layout

- **File name:** `<output>/<run-name>.pdf`, where `<run-name>` is `YYYY-MM-DD_HH-II-SS-<journey-file-name>`, stamped with the local date and time the run started, e.g. `2026-09-29_14-05-32-journey-customize-to-submit.pdf`. The run's screenshots go in a folder with the same `<run-name>`. Every run makes a new PDF and folder, so earlier runs are never overwritten and sort by date. The time uses `-`, not `:`, because Windows and macOS don't allow `:` in file names and the PDF is sent to clients.
- **Landscape A4**, one step per page, after a cover page laid out top to bottom as: `Client: <client> / Project: <project>`, the `name`, the `description`, the Automated Build banner, and the logo at the bottom.
- **Automated Build banner:** the cover always carries, between the description and the logo, a large "AUTOMATED BUILD" title, a line explaining the walkthrough was generated automatically from the application and that, on request, the software can be promptly updated and an updated build delivered, and the date and time it was generated. It tells the client the PDF is a by-product of the tooling, not billable manual work. The wording is fixed in the runner so every project says the same thing.
- **Cover logo:** if `docs/journeys/logo.png` exists, the cover shows it centred at the bottom of the page (max 30 mm tall, 60 mm wide, aspect ratio kept). One logo per project, used by every journey. Use a PNG with a transparent or white background. No file, no logo - the run doesn't fail.
- **Above** the image: `Step N of M` and the step title.
- **Middle**: the 16:9 screenshot.
- **Below** the image: the caption, as a band beneath the picture - never overlaid on it.

Titles and captions are real PDF text, not pixels. They stay sharp at any zoom, are searchable, and can be reworded and re-rendered **without re-running the browser**.

Page numbers match step numbers, so "page 4 looks wrong" means the same thing to you and the client.

The PDF is built by the same headless Chromium: the builder writes an HTML page (cover plus one page per step, with the screenshots and text) and prints it with CDP `Page.printToPDF` (`landscape: true`, A4 = 11.69 x 8.27 in, `printBackground: true`). No PDF library is needed.

---

## Runner

The browser method below was proven by hand in InternalTesting. The journey runner automates it: it reads a journey file and sends the matching CDP commands for each step.

### Running a journey

From the project root, with the Docker stack running:

```bash
docs/journeys/runner/run.sh docs/journeys/<journey>.md
```

That is the whole manual process - no AI, no tokens. `run.sh`:

1. On first use, creates a Python venv in `docs/journeys/runner/.venv` and installs `requirements.txt` (`websocket-client`, `pyyaml`). The venv is git-ignored.
2. Starts headless Chromium (Flatpak) on port 9222 with a fresh, temporary profile, so no cookies or cart carry over between runs.
3. Runs `journey_runner.py`, which walks every step, prints `[N/M] <title>` as it goes, and ends with `PDF: screenshots/<run-name>.pdf`.
4. Stops Chromium, even if the run fails.

Options:

- Second argument: the output folder (default `screenshots`), e.g. `run.sh docs/journeys/x.md /tmp/out`.
- The database container for `email_*` actions is `<PROJECT_NAME>-db-1`, read from `dockerLinux/.env`. Pass `--db <container>` to `journey_runner.py` directly if a project differs.

If a step fails, the run stops with `FAILED at step N "<title>": <reason>` and the path of a debug screenshot. No PDF is built. The usual causes: a selector changed in a template, the Docker stack isn't running, or port 9222 is still held by another Chromium (`pkill -f "remote-debugging-por[t]=9222"`).

Host requirements: Linux with Flatpak Chromium (`org.chromium.Chromium`), Python 3, `curl`, `docker`, and `pdftoppm` (poppler-utils) for `email_attachments`. Other hosts need their own Chromium launch line in `run.sh`; the rest is the same.

The sections below describe how the runner drives Chromium, for anyone changing it.

### Host setup (Linux, Flatpak Chromium)

The runner drives Chromium on the **host**, not inside the Docker container, and loads the site from `http://localhost`.

- Chromium runs as a Flatpak (`org.chromium.Chromium`). The Flatpak sandbox blocks file access, so pass `--filesystem=<dir>` for every directory Chromium must read or write: its profile dir, the output dir, and `docs/journeys/files/` for uploads. If one is missing, the file is **silently** not written or read.
- Python 3 with a venv holding `websocket-client`:

  ```bash
  python3 -m venv $S/venv && $S/venv/bin/pip install websocket-client
  ```

- Other hosts (Windows/WSL, Mac) need their own Chromium command. Only the launch command changes; the CDP calls are the same.

### Start Chromium with remote debugging

```bash
mkdir -p $S/chrome-cdp
nohup flatpak run --filesystem=$S org.chromium.Chromium --headless=new --no-first-run \
  --disable-gpu --remote-debugging-port=9222 --remote-allow-origins='*' \
  --user-data-dir=$S/chrome-cdp --window-size=1920,1080 about:blank > $S/chrome.log 2>&1 &
curl -s http://127.0.0.1:9222/json/version   # verify it's up
```

`$S` is a temporary working dir. Use a fresh profile dir per run, so no login or cookies carry over from a previous run.

### Driver

The InternalTesting session used a small driver, `cdp.py`: `python cdp.py <cmd> [args]`, where `<cmd>` is one of `nav URL | js EXPR | click CSS | shot PATH | wait SECONDS | url`. It connects to the first page tab on port 9222 and sends CDP commands over its WebSocket. The journey runner uses the same calls:

| Journey need     | CDP call |
|------------------|----------|
| `goto`           | `Page.navigate` |
| `click`, `fill`, `select`, `editor`, `expect`, `expect_text` | `Runtime.evaluate` (with `returnByValue`, `awaitPromise`) |
| `upload`         | `DOM.setFileInputFiles` |
| `dialog`         | `Page.javascriptDialogOpening` event, then `Page.handleJavaScriptDialog` |
| `email_code`     | `docker exec <project>-db-1 mysql ...` to read the code, then the same call as `fill` |
| Viewport         | `Emulation.setDeviceMetricsOverride` |
| Screenshot       | `Page.captureScreenshot` (PNG) |
| PDF              | `Page.printToPDF` |

Differences from `cdp.py` that the runner must fix:

- `fill` fires `input`/`change`. Setting `.value` directly, as the hand-run session did, skips page scripts and validation.
- Waiting uses `expect`, not fixed `sleep`s.
- A selector that matches nothing fails the run. `cdp.py` printed `NOT FOUND` and kept going.
- The viewport is 1920 x 1080.

### Gotchas

- Wrap every Chromium launch in `timeout` - headless Chromium can hang.
- Kill the debugging Chromium when done: `pkill -f remote-debugging-port=9222`.
- `/users/ai-access` returns 404 unless the Docker stack is running with the active environment `DOCKER`.
- To check `[TEST]` records in the database: `docker exec <project>-db-1 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" LIVE_database -e "SELECT ... WHERE name LIKE \"[TEST]%\""'`.

---

## Out of scope (for now)

- Arrows, highlight boxes, or any drawing on the screenshot. The target is already known per step, so this is easy to add later - but captions come first.
- Multiple screenshots per step.
- Branching journeys. If a flow branches, write two journeys.
- More than one login role. There is one AI user; if journeys need different roles, that is a future `login:` value.
- Other languages. A French walkthrough is a separate journey file with `/fr/` paths.

---

## Open decisions

To settle before the runner is built:

1. **Shipping the runner.** Decided: Python + CDP + headless Chromium, living in `docs/journeys/runner/`. Still open: whether it ships to client projects as its own installer module.
2. **Output location and commit policy.** InternalTesting used `screenshots/<name>/` at the project root. Decide whether screenshots and PDFs are committed or git-ignored.
3. **Distribution.** `docs/` is not in `install_setupCaseCore_modules.sh` `MODULES`, so existing client projects don't receive this spec through the installer - only new projects created from the template.
4. **Ship `aiAccess` with the SetupCase template.** Today it is copied by hand from [aiAccess reference code](#aiaccess-reference-code), including the `Environments` check.

---

## Checklist for creating a journey

1. Check the [Prerequisites](#prerequisites) are in place for this project.
2. Create `docs/journeys/<flow-name>.md`.
3. Add front matter: `name`, `description`, `login`, `start_url`, and `feature` if one exists.
4. Add one `##` step per screen the person sees, with the fields its action requires.
5. Give every `expect` a selector that proves the screen is ready; add `data-testid` to templates where needed.
6. Keep titles <= 40 characters and captions <= 140 characters.
7. Don't write step numbers anywhere.
8. Reset the Docker database if the PDF goes to a client (see [Starting data](#starting-data)), run `docs/journeys/runner/run.sh docs/journeys/<flow-name>.md`, then review the PDF page by page.
