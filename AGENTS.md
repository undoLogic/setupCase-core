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

# Git and Change Hygiene

- Never revert unrelated user changes
- Keep diffs minimal
- Make one logical change per commit whenever requested

---

# Review Priorities

When reviewing code, prioritize:

1. Bugs and regressions
2. Security risks
3. Data handling issues
4. Validation and error handling
5. Missing test coverage

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
