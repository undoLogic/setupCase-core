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
- **Runner and PDF builder:** not built yet. See [Open decisions](#open-decisions).
- **`aiAccess` login:** exists in a client project, not yet promoted into CodeBlocks. See [Prerequisites](#prerequisites).

---

## Folder layout

```
docs/journeys/
|-- README.md            <- this file (the spec)
|-- files/               <- fixture files used by `upload` steps
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

---

## Login: aiAccess

The runner logs in through `UsersController::aiAccess()` - a Docker-only action that signs in a dedicated AI user without a password. No credentials are ever written into a journey file.

Flow for a journey with `login: ai`:

1. Runner opens `/<language>/users/ai-access?redirect=<start_url>`.
2. `aiAccess` confirms `Environments::getActive() === 'DOCKER'`, otherwise 404.
3. It loads the AI user via `UsersTable::getAiAccessUser()` (active, not removed), otherwise 404.
4. It sets the identity and redirects to `start_url` (local paths only - anything else falls back to `/`).
5. No screenshot is taken during login. The runner then starts step 1.

What the AI user can reach is decided by its role and the normal prefix RBAC (`config/app.php` `rbac`) - journeys get no special access. A journey that goes into `/admin/...` needs the AI user to be an ADMIN.

### Prerequisites

Before journeys work in a project, it needs:

- [ ] `UsersController::aiAccess()` and `aiAccess_redirectTarget()`.
- [ ] `UsersTable::getAiAccessUser()` and the `AI_ACCESS_EMAIL` constant.
- [ ] The AI user row in the Docker database, active, with the role the journeys need.
- [ ] `aiAccess` allowed without authentication (`addUnauthenticatedActions`), or the login redirect catches it.
- [ ] `Environments::getActive()` returning `DOCKER` for **web** requests inside Docker. The current CodeBlocks version returns `LOCAL` for any request to `localhost` and only reaches `DOCKER` from the CLI, so `aiAccess` would always 404 until this is fixed.

---

## File format

A journey file has YAML front matter, followed by one `##` heading per step.

```markdown
---
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

| Field         | Required | Notes |
|---------------|----------|-------|
| `name`        | yes      | Human-readable journey name. Used as the PDF title. |
| `description` | yes      | One sentence. Appears on the PDF cover. |
| `feature`     | no       | Path to the feature file this journey walks through. |
| `login`       | yes      | `ai` (log in through `aiAccess`) or `none` (public pages). |
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
| `expect_text` | optional | Text that must appear on the page after the action. |
| `caption`     | always | The explanation shown below the screenshot. |

### Actions

| Action   | `target`        | `value` | What the runner does |
|----------|-----------------|---------|----------------------|
| `goto`   | local path      | -       | Opens the page. |
| `click`  | selector        | -       | Clicks the element. |
| `fill`   | selector        | text    | Clears the field and types the text. |
| `select` | selector        | option label | Chooses the option with that visible label. |
| `upload` | selector        | file name | Uploads `docs/journeys/files/<value>`. |
| `wait`   | -               | -       | Does nothing; the runner just waits for `expect`. Use for slow screens. |

A field the action doesn't use must not be written. A missing required field, an unknown field, or an unknown action stops the run with an error naming the journey and step.

### How a step runs

1. Perform the action.
2. Wait for `expect` to be visible (timeout: 10 seconds).
3. If `expect_text` is set, wait for that text to appear.
4. Capture **one screenshot**.

If any part fails, the run stops. The runner reports the failing step and saves a debug screenshot outside the PDF output. A PDF is never built from a partial run.

### Parsing rules

- Front matter is YAML.
- Everything between one `##` heading and the next is one step.
- Step lines are `- key: value`. The key is everything before the **first** `": "`; the value is everything after it, to the end of the line, taken literally. No quoting, no escaping - so selectors like `[data-testid="x"]` and `a:has-text("Save")` need nothing special.
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
3. Nothing else. No Bootstrap classes, no positional selectors (`nth-child`, `tr:first`), no text-only matches - they break on the next layout or copy change.

CodeBlocks templates don't have `data-testid` attributes yet. Adding them to the templates a journey touches is part of writing that journey.

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

- Browser viewport: **1920 x 1080**.
- Captured at **16:9**, uncropped and unannotated.
- Nothing is drawn over the screenshot. The image stays clean so nothing important is ever covered.

---

## PDF layout

- **Landscape A4**, one step per page, after a cover page with `name` and `description`.
- **Above** the image: `Step N of M` and the step title.
- **Middle**: the 16:9 screenshot.
- **Below** the image: the caption, as a band beneath the picture - never overlaid on it.

Titles and captions are real PDF text, not pixels. They stay sharp at any zoom, are searchable, and can be reworded and re-rendered **without re-running the browser**.

Page numbers match step numbers, so "page 4 looks wrong" means the same thing to you and the client.

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

1. **Runner location and tool.** Proposed: Playwright (Node 20 is already in the Docker image; it needs a headless browser plus its system libraries added). Playwright can also render the PDF from an HTML template, so one tool covers both outputs.
2. **Output location and commit policy.** Where screenshots and PDFs go, and whether they are committed or ignored.
3. **Distribution.** `docs/` is not in `install_setupCaseCore_modules.sh` `MODULES`, so existing client projects don't receive this spec through the installer - only new projects created from the template.
4. **Promote `aiAccess` into CodeBlocks**, including the `Environments` fix.

---

## Checklist for creating a journey

1. Check the [Prerequisites](#prerequisites) are in place for this project.
2. Create `docs/journeys/<flow-name>.md`.
3. Add front matter: `name`, `description`, `login`, `start_url`, and `feature` if one exists.
4. Add one `##` step per screen the person sees, with the fields its action requires.
5. Give every `expect` a selector that proves the screen is ready; add `data-testid` to templates where needed.
6. Keep titles <= 40 characters and captions <= 140 characters.
7. Don't write step numbers anywhere.
8. Reset the Docker database, regenerate screenshots and the PDF, then review the PDF page by page.
