# AGENTS.md

## Purpose
Repository-specific instructions for development, code reviews, refactoring, and collaboration.

---

# Tech Stack

- PHP (`CakePHP`)
- Frontend templates located under:
  - `sourceFiles/templates`

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
- Public functions should remain under approximately `35` lines whenever possible.
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

## Base Template Philosophy

Base templates should:

- Keep a short Bootstrap overview structure
- Show only high-level layout flow
- Remain under one screen whenever possible

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

# Database Schema Changes

- Record all new database SQL changes in:

```text
sourceFiles/config/schema/YYYY-MM-DD.sql
```

- Use the date the change is introduced for the filename.
- Append related changes to that day's file so the database history remains clear over time.
- Do not add new SQL changes only to an older consolidated schema file.

---

# Formatting and Cleanup Rules

- Keep ASCII unless Unicode is already required.
- Run cleanup/formatting only when no staged changes exist.

Pre-check:

```bash
git diff --cached --quiet
```

If staged changes exist:

- do not run broad cleanup
- keep cleanup isolated in separate commits

---

## Formatting Consistency

- Normalize method indentation first
- Top-level class methods must align consistently
- Prefer minimal formatting-only changes when the task is formatting-related

---

# Pre-Commit Hook Policy

Pre-commit blocks when:

- public PHP methods exceed:
  - default: `45` lines
- base templates exceed:
  - default: `45` lines

Exemptions:

- `private`
- `protected`
- template elements under:
  - `sourceFiles/templates/element/`
  - `sourceFiles/templates/elements/`

---

## Hook Overrides

Override public function limit:

```bash
PRECOMMIT_PUBLIC_FUNCTION_MAX_LINES=<n>
```

Override template limit:

```bash
PRECOMMIT_TEMPLATE_MAX_LINES=<n>
```

Temporarily disable soft checks:

```bash
SOFT_PRECOMMIT_DISABLE=1
```

Skip all hooks:

```bash
git commit --no-verify
```

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

- Never run `git add` or `git commit` unless the user explicitly asks
  for it in that turn.
- This applies even after a large multi-file change, and even if the
  user approved staging/committing earlier in the session — a prior
  approval does not carry forward to new changes.
- Leave changes in the working tree. The user reviews the diff and
  stages/commits manually themselves, in their own single commit.
- Reason: the user sometimes works against pending/remote servers
  (not local), and manually staging is how they control exactly which
  files get uploaded there. Do not shortcut this by staging on their
  behalf "to save time."

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

# Future Project Rules

Add additional project-specific rules here over time:

- linters
- deployment rules
- CI/CD requirements
- formatting standards
- infrastructure constraints

---

# UpdateCase Conversion System

## Purpose

UpdateCase separates website presentation/programming from editable content.

The intended workflow is:

1. Build the website normally with realistic temporary/default content.
2. Run **UpdateCase Convert** in Codex.
3. Codex identifies editable content and replaces it with UpdateCase PHP calls.
4. Codex preserves the original clean content in a deterministic Markdown import definition.
5. Paste or upload that Markdown into UpdateCase.
6. UpdateCase parses, validates, and shows a complete preview/review screen.
7. The user confirms the proposed Site, Pages, Locations, Elements, Groups, and content.
8. Only after confirmation does UpdateCase create the real database records.
9. Connect/synchronize the website with UpdateCase and verify every field.
10. Run **UpdateCase Cleanup** to remove temporary fallback/debug content.

The Markdown import format must be deterministic and machine-parseable. Do not rely on AI inside UpdateCase to interpret the import file.

---

## Hierarchy

UpdateCase content follows this hierarchy:

```text
Site
└── Page
    ├── Single Location
    │   └── Element
    └── Grouped Location
        └── Group
            └── Element
```

- A **Site** is the website being imported, such as `VendorGrid`.
- A **Page** represents the UpdateCase page used by a website template.
- A **Single Location** is a non-repeating logical section of editable content.
- A **Grouped Location** is a repeating structure, such as slides, cards, testimonials, team members, products, authors, FAQs, or repeated image/text blocks.
- An **Element** is one editable content value.
- A **Group** is one item/row inside a Grouped Location.

Single Locations and Grouped Locations may share the same name. The location type keeps them distinct, so `Single Location: Catalog` and `Grouped Location: Catalog` may coexist.

---

## Import Heading Format

Use these Markdown headings exactly:

```md
# UpdateCase-Site: Site.name
## UpdateCase-Page: Page.name
### Source Templates
### Page Metadata
### Assets
### Single Location: Location.name
#### Grouped Location: Location.name
```

Rules:

- Use the single top-level `#` heading for the website.
- Use `##` headings for pages.
- Use plain `###` headings only for export metadata sections such as source templates, page metadata, or assets.
- Use `### Single Location: Name` for normal, non-group content areas.
- Use `#### Grouped Location: Name` for grouped content areas.
- Keep similar locations together, including related single and grouped locations.
- Preserve visible copy, CTA labels, hrefs, image paths, alt text, form labels, and placeholder visual notes.

Example of related locations staying together:

```md
### Single Location: Catalog
#### Grouped Location: Catalog
```

---

## Forms

Forms are never grouped locations.

Form inputs are manually wired in programming and should be exported as single elements under a separate single location:

```md
### Single Location: Demo
### Single Location: Demo Form Fields
```

Keep form field locations separate from the related form intro/copy location, even when the content is visually adjacent or semantically similar.

---

## Element Types

Valid element types are strictly:

```text
text
paragraph
general
image
```

- Use `text` for short strings such as headings, labels, buttons, short descriptions, addresses, and other single-line values.
- Use `paragraph` for plain multiline text without rich formatting.
- Use `general` for rich text that requires formatting such as bold, italic, links, lists, or HTML.
- Use `image` for editable website images.

Do not invent new element types during conversion.

Content rendering call:

```php
<?= $updateCase->getContentBy('LOCATION.NAME', 'ELEMENT.NAME'); ?>
```

Image rendering call:

```php
<?= $webroot.$updateCase->getImageBy('LOCATION.NAME', 'ELEMENT.NAME'); ?>
```

Grouped content rendering call:

```php
<?= $updateCase->getContentBy('LOCATION.NAME', 'ELEMENT.NAME', $group); ?>
```

Grouped image rendering call:

```php
<?= $webroot.$updateCase->getImageBy('LOCATION.NAME', 'ELEMENT.NAME', $group); ?>
```

The distinction between `text`, `paragraph`, and `general` matters to the UpdateCase editor/importer even though all three use `getContentBy()` when rendered.

---

## Page Selection

Every converted template must define its UpdateCase page near the top of the template:

```php
<?php $updateCase->changePage('PAGE.NAME'); ?>
```

`PAGE.NAME` must correspond exactly with the Page name generated in the UpdateCase import definition.

---

## Group Loops

Repeated adjacent structures should use Grouped Locations.

Do not create independent Single Locations for every repeated item when they share the same structure. Instead, use one Grouped Location with multiple Groups.

Example PHP:

```php
<?php $groups = $updateCase->getGroupNamesByLocation('Authors'); ?>
<?php if ($groups): foreach ($groups as $group): ?>
    <?= $updateCase->getContentBy('Authors', 'Name', $group); ?>
    <?= $updateCase->getContentBy('Authors', 'Biography', $group); ?>
    <?= $webroot.$updateCase->getImageBy('Authors', 'Photo', $group); ?>
<?php endforeach; endif; ?>
```

The same Element names should normally be reused within each Group because each Group represents another instance of the same content structure.

---

## UpdateCase Convert

When instructed to perform **UpdateCase Convert**, Codex must:

1. Identify the applicable Page for each converted template.
2. Add `<?php $updateCase->changePage('PAGE.NAME'); ?>` near the top of each applicable template.
3. Divide the page into logical Single Locations and Grouped Locations.
4. Identify editable Elements and classify each as `text`, `paragraph`, `general`, or `image`.
5. Replace hard-coded editable content with UpdateCase calls.
6. Preserve the original clean content in the generated import definition.
7. Add temporary fallback/debug content in the website when needed.

Do not convert structural HTML, CSS classes, JavaScript, layout logic, or technical configuration into editable content unless explicitly instructed.

---

## Temporary Fallback Content

During conversion, the website must make it visually obvious when UpdateCase content is not yet being returned.

Use a consistent marker such as:

```text
[UC-IMPORT] Original fallback content here
```

Rules:

- Clean original content goes into the import definition.
- Temporary website fallback/debug content is visibly marked.
- If `[UC-IMPORT]` is visible after connecting UpdateCase, that field has not been successfully populated/connected.
- The marker is temporary and must be removed during UpdateCase Cleanup.
- Do not add the marker to the actual content imported into UpdateCase.

---

## Markdown Import Definition

Codex must generate a Markdown file describing the UpdateCase structure and initial content.

This file is data, not prose documentation. Do not add commentary, explanations, conversational text, or undocumented fields inside the import data.

Use the required Markdown headings plus fenced YAML blocks under each metadata/location heading. YAML provides deterministic structure while keeping the file inspectable.

Example:

````md
# UpdateCase-Site: VendorGrid

## UpdateCase-Page: Home

### Source Templates

```yaml
templates:
  - sourceFiles/templates/Pages/home.php
```

### Single Location: Hero

```yaml
elements:
  - name: Heading
    type: text
    value: "Turn Custom Orders Into a Visual Experience."
  - name: Body
    type: paragraph
    value: "Let customers choose products, colours, quantities, logos and customization online."
```

#### Grouped Location: Catalog

```yaml
groups:
  - name: "1"
    elements:
      - name: Heading
        type: text
        value: "Your Catalog"
      - name: Body
        type: paragraph
        value: "Build your own products, colours, sizing, variants and customization options."
```
````

### Import Schema

Metadata blocks may contain keys such as:

```text
source_url
captured_at
language
page_slug
templates
assets
```

Single Location blocks require:

```text
elements
```

Grouped Location blocks require:

```text
groups
```

Every Element requires:

```text
name
type
value
```

Every Group requires:

```text
name
elements
```

Group names must be stable and deterministic. Sequential names such as `1`, `2`, `3` are acceptable unless a meaningful existing identifier is available.

---

## UpdateCase SaaS Import Workflow

UpdateCase must not immediately create database records when this definition is pasted/uploaded.

The intended application workflow is:

```text
Paste / Upload
  -> Parse
  -> Validate
  -> Preview
  -> User Review
  -> Confirm Import
  -> Create Records
```

At minimum, validation should confirm:

- Required fields exist.
- Element types are supported.
- Location names are valid.
- Element names are valid.
- Group structures are valid.
- Duplicate/conflicting names are detected according to UpdateCase rules.
- Empty or malformed structures are reported.

Only after explicit confirmation should UpdateCase create the actual Site, Page, Locations, Elements, Groups, and initial content. The importer should ideally perform creation transactionally so a failed import does not leave a partially created structure.

---

## UpdateCase Cleanup

Run **UpdateCase Cleanup** only after:

1. The Markdown definition has been imported into UpdateCase.
2. The website has been connected/synchronized.
3. The UpdateCase calls are returning the expected content.
4. The developer has reviewed the website.

Cleanup must:

- Remove `[UC-IMPORT]` fallback/debug content.
- Remove temporary fallback conditionals that are no longer required.
- Leave the UpdateCase PHP calls intact.
- Preserve all HTML structure and styling.
- Preserve group loops.
- Ensure no original hard-coded editable content remains accidentally duplicated.
- Report any UpdateCase calls that still appear empty or unresolved rather than silently deleting their fallback.

Do not remove fallback/debug content from an unresolved element merely to make the conversion appear complete.

---

## Core Principle

Codex is responsible for understanding the website and proposing the UpdateCase structure.

The generated Markdown is the deterministic contract between Codex and UpdateCase.

UpdateCase itself should not have to guess what Codex meant.

The human reviews the proposed structure before UpdateCase changes its database.
