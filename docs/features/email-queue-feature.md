# Feature: Email Queuing System (Open Source)

## Context

This is the open source version of an email queuing feature already running in
other projects. Build it using the existing application foundation — see the
AGENTS file for the foundation conventions, controller patterns, and table
rendering helpers.

**Core principle:** no project in this codebase sends email directly. Everything
is written to a queue, and a worker drains the queue.

**Portability requirement:** build this as one self-contained block (migrations,
controller, views, worker) that can be copied wholesale into future projects
without unpicking dependencies.

**Scope note:** this is an MVP / alpha proof of concept. Security is handled
later, deliberately. Do not add auth checks, upload sanitisation, rate limiting,
or CSRF beyond whatever the foundation gives for free.

**MVP testing rule (AGENTS.md):** since this is explicitly MVP work, do not
create or scaffold automated tests. The `## Testing` section below stays as
human-readable expected-behaviour scenarios only, checked off manually.

**Existing scaffold conflicts with this spec.** `EmailQueuesController.php` /
`EmailQueuesTable.php` and the templates under `Staff/EmailQueues/` and
`element/2026/email_queues/` already exist in the working tree from an earlier
pass, but their schema (`email_to`, `email_cc`, `lang`, no `error`/`removed`
columns) and behaviour (`deleteAll()` is an unscoped hard delete of every row)
do not match the schema and rules documented here. Treat those files as a
starting scaffold to be rewritten to this spec, not extended as-is — in
particular the hard-delete `deleteAll()` must become the soft `removed = true`
bulk update described under **Bulk actions** below.

---

## Conventions (AGENTS.md)

This feature must follow the repo-wide rules in `AGENTS.md`, notably:

- **Fat model, skinny controller, dumb view.** `EmailQueuesController` orchestrates only (load entity, call table method, set flash, redirect); all query building, send orchestration, and error recording live on `EmailQueuesTable`.
- **Table method response contract.** Every public `EmailQueuesTable` method returns at minimum `['STATUS' => 200, 'MSG' => '...']` (plus whatever data it needs to add), per the Model/Table Rules section.
- **Private helper naming.** Helpers split out of a public method are named `<publicMethodName>_<helperName>()` (e.g. `sendQueuedEmail()` / `sendQueuedEmail_loadAttachments()`), and public methods stay under ~35 lines.
- **Element naming convention.** New template elements live under `sourceFiles/templates/element/2026/staff/emailQueues/<action>_<location>.php` — camelCase controller folder, prefix included, action name first. The existing `element/2026/email_queues/tabs.php` (snake_case, no prefix segment) does not follow this and should be moved to `element/2026/staff/emailQueues/index_tabs.php` when rebuilt.
- **Schema changes recorded by date.** New/changed columns for `emailQueues` and `emailQueueAttachments` go in `sourceFiles/config/schema/YYYY-MM-DD.sql` (today's date), appended to if a file for that date already exists — not folded into an older consolidated schema file.
- **RBAC:** access control is prefix RBAC only (`staff` prefix) — no per-action role checks inside the controller. No `group_id`/`user_id` ownership scoping applies here since email queue rows are not tenant-owned data in this MVP.

---

## Routing

Everything lives under the staff prefix, which keeps it away from
non-signed-up users even in the open source build:

- `staff/email-queues` — listing (tabbed)
- `staff/email-queues/create`
- `staff/email-queues/edit/{id}`

---

## Schema

### `emailQueues`

| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `userId` | int, nullable | The staff user who **queued** this email (`getUserId()`), not a recipient. Null when queued by a non-logged-in process. |
| `emailTo` | varchar | Single or comma-delimited bare addresses. Named `email_to` in the DB/entity, not `to` — `TO` is a MySQL reserved word and breaks unquoted INSERT/UPDATE. |
| `emailFrom` | varchar, default `from@example.com` | Chosen on the create/edit form from a fixed PHP array of allowed senders (not free text). Passed to `SetupCase::sendEmail()` as `$from` at send time. |
| `cc` | text | Comma delimited, long enough for many addresses |
| `bcc` | text | Comma delimited, long enough for many addresses |
| `subject` | varchar | |
| `body` | text | Arrives already translated — see Language |
| `language` | char(2) | Two-letter code, defaults to `en` |
| `sent` | bool | Drives tab filtering |
| `sentAt` | datetime, nullable | |
| `lastAttemptAt` | datetime, nullable | Written on every send attempt, test or real |
| `error` | text, nullable | Last failure message, null on success |
| `removed` | bool, default false | Soft delete |
| `created` | datetime, nullable | Managed by CakePHP's `Timestamp` behavior |
| `modified` | datetime, nullable | Managed by CakePHP's `Timestamp` behavior |

### `emailQueueAttachments`

| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `emailQueueId` | FK → `emailQueues.id` | |
| `originalFilename` | varchar | As uploaded |
| `path` | varchar | Relative to `sourceFiles/tmp/` (not webroot — never directly downloadable) |
| `mimeType` | varchar | |
| `size` | int | Bytes |
| `removed` | bool, default false | |
| `created` | datetime, nullable | Managed by CakePHP's `Timestamp` behavior |
| `modified` | datetime, nullable | Managed by CakePHP's `Timestamp` behavior |

---

## Attachments

Files stay files. The database stores a **path reference**, not blob bytes —
this keeps the queue table light and lets a failed message be re-sent without
re-uploading.

- Uploads go to `sourceFiles/tmp/uploads/email-queue-attachments/`, not webroot —
  attachments are never directly downloadable by URL.
- **No filename randomising or hashing for this MVP.** Original filename is kept
  as-is. Collisions are accepted.
- The worker reads the file from disk at send time and hands the path to the
  send utility.

---

## Listing Page

Two tabs, both built on the foundation's standard table rendering:

1. **Waiting to Send** — `sent = false AND removed = false`
2. **Sent** — `sent = true AND removed = false`

Both tabs must exclude `removed` rows.

### Row actions

| Action | Behaviour |
|---|---|
| **Send** | Sends via the utility, marks `sent = true`, stamps `sentAt` |
| **Send Test** | Sends via the utility, **does not** mark as sent — row stays in Waiting so it can be fired repeatedly while debugging |
| **Edit** | Standard edit form |
| **Remove** | Sets `removed = true`. Never a hard delete. |

### Bulk actions (top of Waiting tab)

| Button | Behaviour |
|---|---|
| **Send All** | Loops the visible waiting rows and sends each |
| **Send All Test** | Same loop, but nothing gets marked sent |
| **Remove All** | Marks all visible waiting rows `removed = true` |

Note: "Delete All" is explicitly **not** a hard delete — it is a bulk soft
remove.

---

## Sending

Sending is already solved. Hook into the existing **SetupCase utility class** —
do not write fresh SMTP handling.

The worker's job is only:

1. Pull rows where `sent = false AND removed = false`
2. Load each row's attachments and resolve their paths
3. Hand subject, body, recipients, and attachment paths to the utility
4. Mark sent (unless in test mode)

### Error handling

Failures **park**, they do not retry.

- If the utility throws, write the exception message to `error`, stamp
  `lastAttemptAt`, and leave `sent = false`.
- The row stays in the Waiting tab with the failure text visible, so it can be
  inspected and re-sent manually.
- **Test sends also write to `error`** — otherwise debugging is blind. They just
  never set `sent`.
- No automatic retry logic in this alpha. Retries hide bugs at this stage.

---

## Language

The `language` column is **metadata, not a rendering instruction**. All
translation logic happens upstream, before anything reaches the queue — by the
time a row exists, the body is already in the correct language.

The column exists so that:

- The queue can be filtered by locale when debugging
- Any future per-language footer or unsubscribe text at the worker level has the
  information already available

Rules:

- Two-letter codes only (`en`, `fr`) — not full locales like `en-CA`
- Nullable, defaults to `en`, so existing projects can drop this in without
  changing their insert calls

---

## Recipients

- `emailTo` (`email_to` column), `cc`, `bcc` are all text columns
- Multiple addresses are **comma delimited**
- **Bare addresses only** — no display names. Display names containing commas
  (e.g. `Dmytruk, Sacha`) would break the split, and we're keeping it simple.
- The utility splits on comma

---

## Explicitly Out of Scope

- Cron scheduling — the worker is triggered manually via the buttons for now
- Retry logic
- Filename hashing or upload security
- Attachment soft-delete cascading (soft-deleted queue rows just hide their
  attachments; orphans are acceptable)
- Any auth beyond the staff prefix

---

## Testing

MVP work — no automated tests are being written for this pass. Scenarios below
are for manual verification only; check them off as they're confirmed by hand.

### Listing & Tabs

**Intent:** Waiting/Sent tabs show the right rows and never leak removed rows.

**Surfaces:**
- `EmailQueuesController::index()`
- `EmailQueuesTable`
- `staff/email-queues`

#### Scenarios

- [ ] Waiting tab shows only rows with `sent = false AND removed = false`.
- [ ] Sent tab shows only rows with `sent = true AND removed = false`.
- [ ] A removed row never appears in either tab.

### Sending

**Intent:** Sending goes through the SetupCase utility and records outcome correctly, without retrying on failure.

**Surfaces:**
- `EmailQueuesTable::sendQueuedEmail()` / `processQueue()`
- SetupCase send utility

#### Scenarios

- [ ] Send marks `sent = true` and stamps `sentAt` on success.
- [ ] Send Test sends the email but leaves `sent = false` and the row in Waiting.
- [ ] A failed send (real or test) writes the exception message to `error` and stamps `lastAttemptAt`, leaving `sent = false`.
- [ ] A failed row is never automatically retried.
- [ ] Send All / Send All Test loop every visible waiting row with the same per-row rules as single Send/Send Test.

### Removal

**Intent:** Remove is always a soft delete, never a hard delete, single or bulk.

**Surfaces:**
- `EmailQueuesTable`
- `EmailQueuesController::deleteAll()` (bulk "Remove All")

#### Scenarios

- [ ] Single-row Remove sets `removed = true` and never issues a hard delete.
- [ ] Remove All sets `removed = true` on every visible waiting row and never issues a hard delete.
- [ ] Soft-deleting a queue row leaves its attachment rows/files in place (orphans acceptable, no cascade).

### Attachments

**Intent:** Attachments are stored as path references and resolved from disk at send time.

**Surfaces:**
- `EmailQueueAttachmentsTable`
- `sourceFiles/tmp/uploads/email-queue-attachments/`

#### Scenarios

- [ ] Uploading a file stores the original filename and a tmp-relative path, with no renaming/hashing.
- [ ] Uploaded attachments are not reachable by any public URL.
- [ ] Two attachments with the same original filename can both be uploaded (collisions accepted).
- [ ] The worker reads the file from disk by its stored path at send time and passes it to the send utility.

### Recipients & Language

**Intent:** `emailTo`/`cc`/`bcc` are comma-delimited bare addresses; `language` is metadata only.

**Surfaces:**
- `EmailQueuesTable`

#### Scenarios

- [ ] A comma-delimited `emailTo` value sends to all listed addresses.
- [ ] `language` defaults to `en` when not supplied and accepts two-letter codes only.
- [ ] Changing `language` on a row does not alter how `body` is rendered.
