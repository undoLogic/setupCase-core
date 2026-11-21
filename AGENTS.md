# AGENTS.md From SetupCase-CORE an undoLogic technology

## Purpose
Repository-specific instructions for development, code reviews, refactoring, and collaboration.

In this file, "the agent" means whatever AI coding tool is working in this repo (a CLI agent, an IDE assistant, or a cloud agent).

## This File Is Synced

AGENTS.md is synced from SetupCase-CORE and gets overwritten on every sync.

- Never edit AGENTS.md inside a project.
- Project-specific rules go in the project's README, under "Project Rules for Agents" (see "Project README Format").
- Where the README's project rules and this file differ, the README wins for that project.

---

# Tech Stack

- PHP (`CakePHP`)
- Frontend templates located under:
  - `sourceFiles/templates`

---

# Directory Map

Know which folder a change belongs in before editing.

```text
codeBlocks/<framework>/<version>/   reusable baseline, copied into sourceFiles/ by the build
                                    (e.g. codeBlocks/cakePHP/4.x/; 5.x/ is a sibling, never an edit of 4.x/)
init-web/<version>/                 build scripts for that version (e.g. init-web/4.x/1-Install.php)
sourceFiles/                        the generated app; only one version is built at a time
docs/features/                      feature files (source of truth for behaviour)
```

- A change to the app goes in `sourceFiles/`. A change meant for every future project is promoted into `codeBlocks/` through "Update CodeBlocks".
- A change to framework files the build edits (routes, `Application.php`, `AppController`, `config/app.php`) belongs in an `init-web/<version>/9-*` transform.
- Keep each version's scripts and code independent. Never share code between versions.
- Full workflow: `docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md`. CakePHP 5 plan: `docs/features/CakePHP-5-Upgrade-Plan.md`.

---

# SetupCase Shared Code Block References

These references are considered canonical implementation examples and reusable architectural foundations.

| Reference Name | URL |
|---|---|
| Application Foundation | https://testflight.setupcase.com/en/code-blocks/application-foundation |
| Responsive Table | https://testflight.setupcase.com/en/code-blocks/responsive-table |

## Usage Rules

- Reuse these structures whenever applicable before creating new implementations.
- Maintain compatibility with existing SetupCase architectural conventions.
- Prefer extending shared foundations over duplicating functionality.
- Keep implementations copy/paste friendly and modular.

---

# Installed Modules

Some projects include optional modules (for example UpdateCase). A module documents itself: its rules, workflows, and any exceptions to the core rules in this file live inside the module's own main file, not here.

- Before working on a module's code, or code that calls it, read the module's main file first.
  - UpdateCase: `sourceFiles/src/Util/UpdateCaseUtil_*.php` (use whichever version is installed).
- A module may document accepted exceptions to core rules (for example a GET endpoint or a committed low-security key). Treat a documented module exception as intentional. Do not "fix" it back to the core rule.
- An exception counts only if it is written in the module's own file. Never assume one.

---

# Core Development Philosophy

- Keep changes small, targeted, and task-focused.
- Preserve existing conventions in touched files.
- Avoid unrelated refactors unless explicitly requested.
- Prefer readable and maintainable code over clever shortcuts.
- Keep controllers slim.
- Push business logic and data handling into models whenever possible ("fat models").
- Keep public functions and base templates short enough to fit on one screen whenever possible.
- Move complexity into:
  - private helper methods
  - template elements

---

# Authorization & Data Scoping

Two separate rules below — never conflate them.

## Role Authorization: Prefix RBAC Only

- Route prefix RBAC (`config/app.php` `rbac` array + `RbacMiddleware`) is the single source of truth for "can this role reach this action." Do not duplicate role checks inside controllers — no re-asking in the action what the middleware already answered.
- Prefix access is all-or-nothing by design: granting a prefix to a role grants every current and future action under it. Role inheritance (e.g. Admin reaching everything under `Owner/*`) is intentional, not an oversight.
- **Consequence to hold onto:** a new action added under an existing prefix is immediately reachable by every role already holding that prefix. If an action genuinely needs to be stricter than its prefix, either split it into a narrower prefix or add an explicit in-action check (e.g. `AppController::isOwner()`) — don't rely on the action just not being linked to anywhere.

## Data Scoping: Separate From RBAC, Never Optional

"No duplicated role checks in controllers" does not mean no scoping. These answer different questions:

| Question | Answered by |
|---|---|
| What kind of user are you? | Prefix RBAC (middleware) |
| Is this row yours? | `group_id` / `user_id` scoping (model layer) |

Ownership/tenant scoping is mandatory everywhere it applies, independent of the RBAC rule above.

## HTTP Method by Effect

Prefix RBAC answers who can reach an action, scoping answers which rows it may touch, and the HTTP method answers how a request is allowed to arrive.

- GET is for read-only actions only: lists, detail views, and loading an edit form.
- Any new action that changes state must be POST and must never be reachable by GET. This includes create, update, delete, toggles, reordering, resets, and side effects such as sending mail.
- This holds regardless of prefix or portal access, and logged-in pages are where it matters most. Browsers, link previews, prefetchers, and scanners follow GET links automatically, and the user's own session cookie is what makes those requests succeed.
- To debug a state-changing action, submit the form once and refresh (the browser re-sends the POST), or call the Table method directly from a console command. Never convert the action to GET for convenience.
- Exceptions are allowed only where an installed module documents one in its own file (see "Installed Modules").

### Existing GET State Changes

When you find an existing action that changes state on GET:

- Flag it and offer to convert it to POST.
- Do not convert it during unrelated work unless the user agrees.
- If the user defers ("later"), add a short note to `docs/todos/` naming the action, then carry on without pushing further.

## Non-HTTP Entry Points

- Prefix RBAC runs in middleware, tied to routing — cron jobs, console commands, and queue workers run at full privilege by design and get **zero** role protection from it.
- Because of that: cron/console logic must never be reachable from a web route (no controller action shares a method with a cron task; no route triggers a scheduled job), and it must never infer scope from a session (none exists there) — scope must always be passed to it explicitly by the caller.

## Destructive Operations: Scope Is Required, Never Inferred

Applies to anything that deletes or overwrites data — regardless of caller, role, or how routine the record seems.

1. The `group_id` (and `user_id` where applicable) is a required argument at the model level. Throw if missing, null, or empty — never default to "all," never fall back to the session inside the model.
2. Filter on scope, don't check-then-delete: the delete query filters on record ID **and** group ID together in the same call. Never fetch a record, verify it in the controller, then delete by ID alone — a mismatched ID must affect zero rows.
3. A tenant-scoped row with a null/missing `group_id` is a data integrity failure — refuse the operation, don't guess or match broadly.
4. Enforce at the schema level where possible (`group_id NOT NULL`), so the invariant holds even when code forgets.
5. Since nearly every table here is group-scoped, this is the default posture for destructive model methods. Running unscoped is an explicitly-justified exception (say why, in a comment) — never the thing that happens when nobody was paying attention.

## Preconditions on the Target

Beyond "is this row in scope," some operations require the target itself to qualify for the operation. This is a data invariant, not authorization — it holds no matter who calls it.

- **Trigger:** any operation that deletes or overwrites data it doesn't own outright must declare a precondition on its target and refuse when unmet. Gate this on what the code actually does, not on whether someone remembered to label the feature "dangerous" — that's subjective and inconsistently applied.
- **Where it lives:** in the model, next to the scope guard — not the controller.
- **Where it doesn't live:** the model never checks *caller* permissions — it has no idea who's calling or whether a caller even exists in the usual sense. It checks the *target's* eligibility only. Deciding which scope is legitimate stays with the caller (a controller derives it from session; cron passes it deliberately).
- Worked example: Demo Mode reset refuses to touch anything unless the target group is flagged `is_demo` — see `GroupsTable::demoData_init()` / `demoData_deleteAll()`. This is what stops a hypothetical future "reset every group" cron job from wiping real client data.

---

# Model / Table Rules

## Public Table Methods

All public methods in `Table` classes must return a response array containing at minimum:

```php
[
    'STATUS' => 200,
    'MSG' => 'Short description'
]
```

Additional response data may be included as needed.

---

## Private Helper Functions

- Private functions may return simple values.
- Public functions should remain under approximately `35` lines whenever possible. This is a soft target, not a hard block.
- If a public function becomes too large:
  - split logic into private helper functions
  - keep the public method as an orchestration layer

Name helper methods using:

```php
<publicFunctionName>_<helperName>()
```

Example:

```php
public function processInvoice()
private function processInvoice_validate()
private function processInvoice_save()
```

---

## Helper Function Data Flow Pattern

Preferred pattern:

- Private helpers set class properties
- Public methods consume those properties afterward

Avoid excessive chaining of return values between private helper functions whenever possible.

---


# PHP Traits

- Do not create new PHP traits, and do not add methods to existing project traits.
- Framework and library traits (e.g. CakePHP's `LocatorAwareTrait`) may be used as normal.
- Existing project traits are legacy. Leave them as they are unless the spec says to convert them.

## Class Structure Decisions Happen at Spec Review

- During planning, if the spec adds substantial code to an already large class, or needs logic shared between classes, add a **Class structure** section to the spec.
- In that section, propose the options and let the user choose:
  1. Keep the code in the existing class.
  2. Create a new service class that the class calls.
  3. Create a shared class used by several classes.
- For each option, state which classes would use it and what it depends on (constants, config, tables, other services).

## Explicit Dependencies

- Shared code must receive what it needs explicitly, through constructor or method arguments.
- Never assume the calling or host class defines a constant, property or method.

## During Implementation

- If a structure question comes up that the spec didn't cover, stop and return to the user. Do not improvise a trait or a new class structure.

---

# Template / View Rules

## Passive View Principle

Templates and elements must stay "dumb" (a Passive View / Humble View).
They render data they are handed; they do not decide, transform, or derive it.

- No branching on business state (status checks, permission logic, workflow
  rules) beyond simple `if (!empty($rows))` / `foreach` over data that is
  already shaped for display.
- No data transformation (formatting aside) — grouping, filtering,
  aggregating, or deriving one value from another belongs in a Table method,
  not a template.
- Computed display strings (e.g. a label built from two fields, a link
  target chosen by entity state) should arrive from the controller or model
  as a ready-to-use view var, not be assembled inline in the template.
- A template needing more than trivial `if`/`foreach` logic is a sign the
  data preparation belongs one layer down (Table method, or a controller
  helper if it is purely view-shaping, e.g. building a `$sections` array
  from data the model already returned).

This is the same "fat model, skinny controller" idea extended one layer
further: fat model, skinny controller, dumb view. Keeping the view passive
is what keeps it trivially correct — there is nothing in it to get wrong.

---

## Index, View, Edit: Every Record Is Clickable

Every project starts from this structure. Before any client-specific screen is built, every kind of record gets these three pages, so anyone can click around the data and follow related records from day one:

| Page | Shows | Changes data |
|---|---|---|
| index | many records | no (GET) |
| view | one record, everything about it | no (GET) |
| edit | one record, its form | yes (POST) |

This is the minimum, not the finished product. Screens built for a client's specific needs (dashboards, workflows, combined pages) are added on top later. They add to the baseline pages and never replace them, so every record stays reachable through its own view.

- Every kind of record a person can see gets a read-only **view** page, so they can open one record and check its data. A record you can reach only through its edit form, or see only as a row in a list, does not have a view yet.
- Every row, and every cell that stands for a record, links to that record's view. This includes cells for a related record: the customer or category shown on an order row links to that customer's or category's view.
- A cell for a record that does not exist yet (e.g. a period not entered yet) links to the view of the parent record it would belong to.
- Totals and other aggregates are not records and are not linked.
- A view shows the record's fields, its status where it has one (e.g. active or removed, and who, when, why), the records under it (each linking to its own view), and its change history where one is kept.
- Views are GET and read only (see "HTTP Method by Effect"). Edit and other action buttons on a view appear only for roles that can already reach those actions (prefix RBAC); the view itself never grants more access. Data scoping applies to views exactly as it does to lists.
- The link target arrives from the model or controller as a ready-to-use URL in the row data (see "Passive View Principle"); the template does not choose it.
- Name the action `view` (e.g. `/staff/en/orders/view/12`) and its elements `view_<location>` (see "Element Naming Convention").
- The Application Foundation code block is the worked example: its index links each name to its view and each type to the type's view, and the type's view lists its records, each linking back.

---

## Sticky Filters (Index Pages)

Every index page with filters or options at the top keeps them reachable while the list scrolls. Once the filter form's place at the top has scrolled under the fixed header, the form docks to the bottom of the screen, with the same left edge and width as its card. Scrolled back up, it returns to its place.

- Copy, don't rebuild: the CSS, JS and element live in the Application Foundation code block (`webroot/css/sticky-filters-YYMMDD.css`, `webroot/js/sticky-filters-YYMMDD.js`, `templates/element/<year>/layout/sticky_filters.php`). The Staff CodeBlocks index is the worked example.
- Opt in per page: add `'data-sticky-filters' => true` to the filter form's `Form->create()` options, and load the element once on that page. One sticky form per page.
- Fixed header: the script uses Bootstrap's `.fixed-top`. If a project's theme uses another header, add `'data-sticky-filters-header' => '<selector>'` to the form. With no fixed header, the top of the screen is used.
- Docked, a spacer of the same height holds the form's place so the page does not jump, and the `body` gets bottom padding equal to the form's height so the last rows are not hidden. The docked form is at most `50vh` tall and scrolls, since filters stack on a phone.
- Undocked, the form looks exactly as before: the script clears every inline style it set.
- It only moves the form. Filters submit as they do at the top, and no data changes.
- Verify on desktop and phone widths: in place at the top, docked at the bottom when scrolled down, back in place when scrolled up, and changing a filter while docked reloads the list with that filter.

---

## Base Template Philosophy

Base templates should:

- Keep a short Bootstrap overview structure
- Show only high-level layout flow
- Remain under one screen whenever possible (soft target)

Typical structure:

```php
container
    row
        filters
        body
```

---

## Template Extraction Rules

If a template becomes too large:

- Extract detailed sections into elements
- Keep the base template as the high-level page shell

Elements may exceed one screen if needed.

---

# Element Naming Convention

## Folder Structure

All new or refactored elements should follow:

```text
YEAR/PREFIX/CONTROLLER/ACTION_location
```

Example route:

```text
/dealer/en/rebate-sales/detail/10/2024
```

Element examples:

```text
sourceFiles/templates/element/2026/dealer/rebateSales/detail_table.php
sourceFiles/templates/element/2026/dealer/rebateSales/detail_bottom.php
sourceFiles/templates/element/2026/dealer/rebateSales/detail_sidebar.php
```

---

## Naming Rules

- Use camelCase for controller folders
- Use lowercase with underscores for element filenames
- Keep action name first within the filename

Good:

```text
detail_table
detail_totals
detail_sidebar
```

Avoid:

```text
table_detail
sidebar_detail
```

---

## Year Folder Policy

- Keep older year folders intact
- Do not migrate older structures unless explicitly requested
- Year folders allow gradual refactoring and cleanup over time

---

# File Editing Rules

- Do not modify generated or vendor files unless explicitly requested.
- Treat anything under:

```text
sourceFiles/webroot/modules
```

as vendor/original assets.

Do not edit these files directly.

Instead:

- Put overrides under:
  - `sourceFiles/webroot/js`
  - `sourceFiles/webroot/css`

Prefer editing source files under:

```text
sourceFiles/
```

---

# CSS/JS File Organization

Use one CSS file and one JS file per feature, not one site-wide file.

- A new feature gets its own file, named in kebab-case to match its feature
  doc, e.g. `walkthroughs.css` and `walkthroughs.js`.
- Styles and scripts used by only one feature go in that feature's file.
- Load a feature file from that feature's template or element, not the global
  layout.
- Prefix selectors with the feature name (`.walkthroughs-*`) so styles cannot
  leak into other features.
- Do not split a feature file into sub-files. Large feature files are fine for
  now.
- Leave the legacy files (`css/2025/added-styles-V.css`,
  `css/2025new/added-styles-D.css`) as they are. Do not migrate them unless
  asked.

---

# Asset Cache Busting (Dated Filenames)

Browsers and CDNs cache CSS and JS aggressively. To guarantee users get the
current version after a release, a modified asset gets a new filename.

## Rule

When you modify any `.css` or `.js` file under `sourceFiles/webroot/css` or
`sourceFiles/webroot/js`, rename it so the filename ends in `-YYMMDD`, the
date of the change:

```text
walkthroughs.css         -> walkthroughs-261017.css
walkthroughs-260901.css  -> walkthroughs-261017.css
walkthroughs-261017.css  -> walkthroughs-261017.css   (already today: edit in place)
```

- Use today's date in `YYMMDD` format.
- If the file has no date suffix, add one.
- If the file has an older date suffix, replace it. Never stack suffixes
  (`name-260901-261017.css` is wrong).
- If the file already has today's suffix, edit it in place without renaming. This
  keeps the filename at one change per day no matter how many edits are made.
- Rename with `git mv` so history follows the file. Do not leave the old file
  behind.
- Only rename files you actually changed. Never rename untouched files
  in bulk.

## Same-Day Increment (Only When Requested)

If today's version is already live and another change ships the same day,
the user may ask to increment the filename. Only do this when the user explicitly
asks. Never increment on your own initiative.

When requested, add an uppercase letter after the date, or advance the existing one:

```text
walkthroughs-261017.css   -> walkthroughs-261017A.css
walkthroughs-261017A.css  -> walkthroughs-261017B.css
```

- Without a request, edit a file that has today's date (with or without a
  letter) in place.
- On a new day, drop the letter: `walkthroughs-261017B.css -> walkthroughs-261018.css`.
- The same `git mv` and reference-update rules apply.

## Update Every Reference

A rename is not complete until nothing points at the old name. In the same change:

- Search the whole repo for the old base name, e.g.
  `grep -rn "walkthroughs" sourceFiles/templates sourceFiles/src`.
- Update every reference, such as `$this->Html->css('walkthroughs')` and
  `$this->Html->script('walkthroughs')` calls, layouts, elements, and other
  CSS/JS files that import it.
- Report every updated reference in your summary.

## Exclusions

- Vendor assets under `sourceFiles/webroot/modules`: never edit or rename them
  (see File Editing Rules).
- Minified third-party libraries such as `*.min.js` and `*.min.css`, and files
  with a version already in the name, such as `vue.2.6.14.js`.
- Files outside `sourceFiles/webroot/css` and `sourceFiles/webroot/js`.

---

# Database Schema Changes

- Record all new database SQL changes in:

```text
sourceFiles/config/schema/YYYY-MM-DD.sql
```

- Use the date the change is introduced for the filename.
- Append related changes to that day's file so the database history remains clear over time.
- Do not add new SQL changes only to an older consolidated schema file.

---

## Column Naming: Internal vs External IDs

A column ending in `_id` always means an internal relationship: an integer key pointing at another table in this database (e.g. `user_id`, `group_id`). Nothing else may use the `_id` suffix.

IDs that come from outside systems are named in camelCase ending in `ID`, with no underscore before it:

```text
stripeCustomerID     not  stripe_customer_id
googleID             not  google_id
paypalPayerID        not  paypal_payer_id
```

- Why: tools rely on `_id` meaning "internal key". OfflineBox anonymized backups keep integer `_id` columns real so relationships still work, and fake everything else. An external ID named `_id` could leak into dev copies. CakePHP's `bake` also guesses associations from `_id` columns.
- Apply this to new columns. Rename existing external `_id` columns only when asked, as a schema change (see above), and update every reference in the same change.
- Internal keys stay integers. Don't store an internal key in a text column.

---

# Formatting Rules

- Keep ASCII unless Unicode is already required.
- Normalize method indentation first.
- Top-level class methods must align consistently.
- Prefer minimal formatting-only changes when the task is formatting-related.
- Broad cleanup is only allowed when nothing is staged (see "Git and Change Hygiene").

---

# Testing and Verification

## MVP Work

When the user says they are creating or building an "MVP":

- Do not create, modify, or scaffold automated tests.
- Feature/spec files may still be created or updated when they are part of the normal workflow.
- Keep the feature file's `## Testing` section focused on expected behaviour that should eventually be verified.
- Do not mark unexecuted scenarios as covered.
- Focus on getting the feature running for manual verification.
- Still run lightweight syntax, lint, or build checks when useful to confirm the MVP starts or renders.
- Clearly state that automated tests were skipped because the work was requested as an MVP.

---

## Template Changes

Verify:

- rendered markup
- Bootstrap structure consistency
- responsive layout integrity

---

## Controller / Model Changes

- Run project tests whenever possible
- If tests cannot be run:
  - clearly state what was manually verified

---

## Feature Documentation and Test Specifications

Each significant feature should have a dedicated Markdown feature file.

The feature file is the **single source of truth** for:

- feature intent
- business rules
- expected behaviour
- important implementation surfaces
- edge cases
- human-readable test scenarios
- automated test coverage

Do not create separate integration-test planning or tracking documents when the scenarios belong to an existing feature.

### Feature File Requirement

When creating or materially modifying a feature:

1. Locate the existing feature Markdown file.
2. Read it before making implementation changes.
3. Update it whenever feature behaviour, requirements, or expectations change.
4. Add or update the `## Testing` section as part of the same work.
5. Ensure automated tests remain aligned with the documented scenarios.

If no feature file exists for a significant new feature, create one under the project's established feature documentation location before or alongside implementation.

The feature file should describe **what the system must do**, not duplicate the PHP implementation.

---

## Testing Section

Every feature file should contain a:

```markdown
## Testing
```

section.

Tests should be grouped by behaviour or responsibility.

Use the following lightweight format:

```markdown
## Testing

### <Test Group>

**Intent:** <What behaviour or business rule this group protects>

**Surfaces:**
- `<important class, method, controller, URL, service, table, etc.>`

#### Scenarios

- [ ] <Human-readable behaviour or outcome>
- [ ] <Human-readable behaviour or outcome>
- [ ] <Human-readable edge case>
```

Example:

```markdown
## Testing

### Email Queue Processing

**Intent:** Ensure queued emails are processed once and successful execution is recorded.

**Surfaces:**
- `CronService`
- `EmailQueueTable`
- `cron.php?action=run&job=email_queue`

#### Scenarios

- [ ] An enabled email queue job processes pending messages.
- [ ] A successful run updates the job heartbeat.
- [ ] A failed run does not replace the previous successful heartbeat.
- [ ] A disabled job cannot be executed.
```

Keep scenarios:

- concise
- behaviour-focused
- implementation-independent where practical
- understandable by a developer without reading the test code

Do not include detailed PHPUnit syntax or implementation logic in the feature file.

---

## Automated Test Alignment

The Markdown scenarios define the expected behaviour.

Automated tests under:

```text
sourceFiles/tests/
```

implement and verify those scenarios.

When changing feature behaviour:

1. Update the feature file first or as part of the same change.
2. Review the `## Testing` scenarios.
3. Update affected automated tests to match.
4. Add tests for newly documented behaviour where appropriate.
5. Remove or update tests that represent behaviour which is no longer valid.

Never silently change automated tests to accommodate changed code when the feature specification still describes the old behaviour.

If the implementation, test, and feature document disagree, identify the conflict and use the feature file as the intended behavioural source of truth unless the user explicitly changes the requirement.

---

## Test Coverage References

When useful, a documented scenario may reference its automated test after coverage exists.

Example:

```markdown
- [x] A successful run updates the job heartbeat.
  - Test: `sourceFiles/tests/TestCase/Service/CronServiceTest.php::testSuccessfulRunUpdatesHeartbeat`
```

This reference is optional.

Do not require test paths for every scenario if doing so would create unnecessary maintenance overhead.

The human-readable scenario remains the authoritative requirement.

---

## Feature Work Completion

For non-MVP feature work, do not consider a feature change complete until:

- the implementation is complete
- the feature Markdown reflects the current behaviour
- the `## Testing` section reflects the expected behaviour
- relevant automated tests have been updated or added where practical
- executed verification is reported

If automated testing cannot be completed, leave the human-readable testing scenarios accurate and clearly state which scenarios remain unverified.

---

# Git and Change Hygiene

- Never revert unrelated user changes
- Keep diffs minimal
- Make one logical change per commit whenever requested

## Never Stage or Commit Automatically

- Never run `git commit` or `git push` unless the user explicitly asks
  for it in that same turn. These write history or send it to a
  pending/remote server, and that step is the user's alone.
- A prior approval earlier in the session never carries forward to new
  changes, even after a large multi-file change.
- Do not run `git add` on your own initiative either. The user stages
  files themselves on purpose: watching what moves into staging forces
  a look at every new and modified file before anything is committed.
  That is the cheapest moment to catch a stray debug file, a temp
  artifact, or a file written to the wrong place. Staging on their
  behalf, even "to save time," removes that review.
- If the user names specific files to stage, staging those is fine.
  Choosing what to stage on their behalf is not.
- Staging is also how the user controls exactly which files get
  uploaded when working against pending/remote servers.
- Leave changes in the working tree so the user can review, stage, and
  commit themselves.

## No Broad Cleanup While Files Are Staged

Staged files mean the user is mid-review. Re-formatting files then mixes
whitespace noise into real changes, and the next `git add` makes them
impossible to tell apart in the diff.

Before any broad cleanup or formatting pass, check:

```bash
git diff --cached --quiet
```

If staged changes exist:

- do not run broad cleanup
- wait until the user has committed, or keep cleanup isolated in its own commit

---

# Review Priorities

When reviewing code, prioritize:

1. Bugs and regressions
2. Security risks
3. Data handling issues
4. Validation and error handling
5. Feature documentation and testing scenarios are out of sync with the implementation
6. Missing automated test coverage

---

# Communication Preferences

- Be concise and direct
- Include file paths for changes
- Clearly state:
  - assumptions
  - blockers
  - limitations

---

# Local Conventions

- Put reusable template snippets in:
  - `sourceFiles/templates/element/`
- Keep `CodeBlocks` examples simple and copy/paste friendly
- Reuse shared SetupCase foundations whenever practical before introducing new patterns

---

# Project README Format

Every project has a README at the repo root. AGENTS.md is shared and synced; the README is where everything specific to this project lives.

If no README exists, create one with these sections in this order. Leave sections blank (or with a short placeholder) rather than inventing content. Never guess a project's purpose, cron jobs, or services.

```markdown
Profile: web | software | web + software
SetupCase standard last checked: YYYY-MM-DD

Standard status:
- [ ] item

## Overview
<Two or three lines: who the client is, what the project does, who uses it.>

## Cron Jobs
- <what it does> | <how often> | `<command>`

## External Services
- <service> | <what it's used for> | config key: `<KEY_NAME>`

## Project Rules for Agents
- <project-specific rule, e.g. "Every table uses the sticky header element.">
```

Rules:

- **Header:** follows "The SetupCase standard" README format below.
- **Cron Jobs:** one line per active job. When adding, changing, or removing a cron job, update this list in the same change. This list is checked against production on deploy, so it must match reality.
- **External Services:** one line per outside service (payments, storage, mail, APIs). Name the config key only, never the value. When adding or removing a service, update this list in the same change.
- **Project Rules for Agents:** project-specific instructions. Where they differ from AGENTS.md, they win for this project.
- Setup instructions do not go here. Docker setup lives in the Docker folder's own README.

---

## Bug and Todo Files

If `docs/bugs/` exists, treat each file in it as a bug note. When you touch a bug file, rewrite it in place into a light structure: a `# Title` line, a short `## Description`, and the author's original notes kept under `## Notes`. If `docs/todos/` exists, treat its files the same way, with the same structure.

Only reorganize what the author wrote. Never invent a title, description, severity, reproduction steps, or any detail the author did not provide. This is a formatting-on-touch rule, not a tracking system: clean up the text, do not embellish it.

---

## Software Timeline

Per-quarter software history lives in `docs/timeline/`. Before creating or editing a timeline, read `docs/timeline/README.md` for the format and rules.

---

## Journeys

Journeys are step-by-step user flows (e.g. submitting a quote) that generate screenshots and a client-facing PDF.

They live in `docs/journeys/`, one markdown file per flow. Before creating or editing a journey, read `docs/journeys/README.md` for the file format and writing rules.

### aiAccess (Docker-only AI login)

`UsersController::aiAccess()` signs the journey runner in as one AI user without a password. This is a known, intentional pattern - not a security hole - only while these guardrails hold:

- It returns 404 unless `Environments::getActive() === 'DOCKER'`. Never loosen, bypass, or add other environments to this check.
- It signs in only `UsersTable::AI_ACCESS_EMAIL`. Never accept a user id or email from the request.
- The redirect accepts local paths only (`/...`; no `//`, `\`, or hosts). Never relax this guard.
- The AI user has no password and only the access its own role and group give it. It is created only by the Docker seed SQL, never on pending or live.
- Any change to `aiAccess`, its environment check, or the AI user needs explicit human review.

---

## The SetupCase standard

**Standard last updated:** 2026-11-06

The SetupCase standard is the minimum every project built on SetupCase should meet. It is written as promises to the owner, not design choices.

### Profiles

Every project's README states its profile at the very top. There are two profiles:

- **web**: a public website, found on Google and visited by the public.
- **software**: an app behind a login, used by staff or clients.

A project can be **web + software** (for example a public intro page plus an app behind the login). Then apply the web list to the public pages and the software list to everything behind the login.

If a project has no README yet, default to the **web** profile.

### Web checklist

Visitors:

- Works on phone, tablet and desktop.
- Loads fast.
- HTTPS (padlock) everywhere.
- The menu is always easy to reach.
- Phone numbers are tap-to-call.
- Forms confirm they were sent.
- A proper "page not found" page on LIVE. Each environment has its own debug setting: pending shows full errors for testing, and LIVE has `debug` off, so visitors never see error details.
- No broken links.

Owner:

- Visitors never have to clear their browser cache to see changes: changed CSS and JS files use dated filenames (see "Asset Cache Busting (Dated Filenames)").
- Every form submission notifies the owner.
- Proper page titles and descriptions.
- A sitemap.
- Only if the client gives us access to their **own** Google Search Console: connected, with the sitemap submitted to Google. Otherwise mark this n/a. The client always owns their Google accounts; we are added as a user, never the owner.
- A nice preview when a link is shared.

Under the hood:

- Keys and config outside the webroot; no secrets in the repo (installed modules may document accepted exceptions, see "Installed Modules").
- Form bot check in place.
- Error alerts set up.

With UpdateCase installed (the managed service). A basic SetupCase site may have hard-coded content, so mark these n/a until then:

- Content is no longer hard-coded; the owner can edit it.
- All form submissions are kept safely.

### Software checklist

- Works on the devices it's used on.
- HTTPS everywhere.
- CSRF protection on.
- Keys and config outside the webroot; no secrets in the repo (installed modules may document accepted exceptions, see "Installed Modules").
- Not indexed by Google (behind the login).
- Error alerts set up.
- Backups in place: [OfflineBox](https://www.OfflineBox.com) is connected to automate a daily data backup to S3. Install it from https://github.com/undoLogic/offlineBox (follow its README).

### README format

At the very top of the README:

```
Profile: web | software | web + software
SetupCase standard last checked: YYYY-MM-DD

Standard status:
- [x] item
- [ ] item (missing)
- [n/a] item (not applicable, with a short reason)
```

### Running the check

- Only run it when asked ("run the standards check"). Don't run it every session.
- Read the profile from the README and apply the matching list or lists.
- Check each item, mark it done, missing or n/a, and set the last-checked date to today.
- If the standard was updated after a project's last-checked date, the project is due a re-check. Mention it when working on the project, but don't run it unasked.

---

# Local Docker Network Troubleshooting

If a user reports slow uploads (downloads fine), sites timing out, or internet
dropouts on a machine running these Docker projects, suspect a Docker network
holding the router's address (often after moving between home and office). Follow
**"Runbook for AI assistants"** in `dockerLinux/README.md` → Troubleshooting before
changing anything.

- Never use `docker compose down -v`, `docker volume prune`, or `--volumes` prunes to fix network problems. They delete project databases.
- Ask before restarting Docker or recreating a running project.
- Keep `docker_pick_subnet` in `dockerLinux/docker-common.sh` and the `networks:` block in `dockerLinux/docker-compose.yml` when editing those files.

---

# Licensing

This codebase is Apache-2.0 licensed by undoLogic. When copying or adapting
files from it, keep the copyright header and carry the LICENSE and NOTICE
files into the destination project. Do not remove attribution.