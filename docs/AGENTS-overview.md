# AGENTS.md Overview - SetupCase Core

As of 2026-11-11

## At a glance

AGENTS.md is the rulebook every AI assistant follows in SetupCase Core: a CakePHP app, templates under `sourceFiles/templates`, built on shared SetupCase code blocks (Application Foundation, Responsive Table).

Core philosophy in one line: **fat model, skinny controller, dumb view**, with small, targeted diffs.

- Keep changes task-focused; no unrelated refactors.
- Preserve conventions in files you touch.
- Public functions and base templates should fit on one screen.
- Push complexity into private helpers and template elements.
- Reuse SetupCase shared foundations before inventing new patterns.

## Directory structure: init-web and codeBlocks

`codeBlocks/` is the reusable code and `init-web/` is the scripts that build it into a working app in `sourceFiles/`. AGENTS.md itself doesn't describe these folders. The details live in the root README and `docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md`.

```text
codeBlocks/
  cakePHP/4.x/        files copied on top of a fresh CakePHP 4 app
    config/ src/ templates/ webroot/
    changeLog.md      last "Source commit" promoted
  import_new_changes.sh   legacy reverse sync
init-web/             browser-run build scripts
sourceFiles/          generated app (testFlight branch only)
```

**codeBlocks/cakePHP/4.x/**

- Pure payload: everything in it is rsync-ed into `sourceFiles/` (124 files today).
- Holds controllers, middleware (RBAC, Lang, Access), Tables, Util, demo pages, layouts, elements, email templates, cron.
- Version folder means tested on CakePHP 4.x. A future `5.x/` sits beside it as a sibling, not as an edit.
- `README.md` and `changeLog.md` are not synced.

**init-web/ build steps** (start at `localhost/init-web/1-Install.php`)

| Step | Script | Does |
| --- | --- | --- |
| 1 | `1-Install.php` | Runs 2a and 2b, stops on failure |
| 2a | `2-Install-Cake.php` | `composer create-project` CakePHP 4 + authentication |
| 2b | `2-Install-CodeBlocks.php` | rsyncs codeBlocks into `sourceFiles/`, runs the `9-*` transforms |
| 2b.x | `9-Install-CodeBlocks_*.php` | Edits framework files: layout, routes, bootstrap, application, middleware, AppController, helpers, app.php, CI |
| 3 | `3-View-CodeBlocks.php` | Opens `/sourceFiles/CodeBlocks` |
| reverse | `8-Save-CodeBlocks.php` | Copies listed `sourceFiles/` paths back into codeBlocks |

The transforms all follow one pattern: read the file, check for a marker, insert at a known anchor, and exit 1 if the anchor is missing. `5-Create-New-Project.php` is deprecated; new projects use GitHub "Use this template" from `main`.

**The cycle:** build on `main`, then develop features on `testFlight`. Run "Update CodeBlocks" to sort each changed file into sync, transform, schema or exclude. A rebuild check in `.rebuild-check/` then proves a fresh build reproduces the change, and `import_from_testflight.sh` brings it into `main`.

**Known open gaps** (from the feature doc):

- `8-Save-CodeBlocks.php` misses 47 synced files, including RBAC, and `import_new_changes.sh` keeps a second, different list.
- No step applies schema SQL during a build.
- Composer versions aren't pinned, so builds on different days differ.
- The menu lives inside the AppController transform, so re-running the build doesn't add new menu items.
- `/setup-pages/home` returns a 500 because of the missing `ObjectStorages` table, and `webroot/cron.php` calls a method that was removed.

## Security and data rules

Role access and row ownership are two separate checks, and both always apply.

| Question | Answered by |
| --- | --- |
| Can this role reach this action? | Prefix RBAC (`config/app.php` + `RbacMiddleware`) |
| Is this row yours? | `group_id` / `user_id` scoping in the model |
| How may the request arrive? | HTTP method: GET reads only, POST for any change |

- **RBAC is all-or-nothing per prefix.** A new action under a prefix is instantly reachable by every role holding it. Stricter actions need a narrower prefix or an explicit check like `isOwner()`.
- **No role checks duplicated in controllers**, but scoping is never optional.
- **GET never changes state.** Only exception: UpdateCase `PagesController::sync()`.
- **Cron, console and queue code** gets no RBAC. It must never be reachable from a web route, and scope is always passed in, never read from a session.
- **Destructive operations:** `group_id` is a required model argument (throw if empty); delete filters on ID and group together; a null `group_id` row is refused; enforce `NOT NULL` in the schema.
- **Target preconditions** live in the model, e.g. demo reset refuses unless the group is flagged `is_demo`.

## Code structure

Logic lives in Table classes; templates only render what they are handed.

**Tables (models)**

- Every public method returns at least `['STATUS' => 200, 'MSG' => '...']`.
- Public methods stay around 35 lines (hook blocks at 45).
- Helpers are named `publicName_helperName()` and set class properties the public method reads.

**Templates (passive view)**

- Only trivial `if (!empty(...))` / `foreach`; no business branching, grouping or derived values.
- Display strings and link targets arrive ready-made from the controller or model.
- Base templates are a short Bootstrap shell (container, row, filters, body); detail goes into elements.

**Element naming**

- Path: `element/YEAR/PREFIX/controllerCamel/action_location.php`
- Example: `element/2026/dealer/rebateSales/detail_table.php`
- Action name first (`detail_table`, not `table_detail`); old year folders left as they are.

## Assets and database

Every changed CSS/JS file gets today's date in its name, and every schema change goes into a dated SQL file.

**CSS / JS**

- One CSS and one JS file per feature, kebab-case (`walkthroughs.css`), selectors prefixed `.walkthroughs-*`, loaded from the feature's template.
- Never edit `webroot/modules` (vendor); overrides go in `webroot/css` or `webroot/js`.
- Legacy files `added-styles-V.css` / `added-styles-D.css` stay untouched.

**Cache busting**

- Rename changed files to `name-YYMMDD.css` with `git mv`; replace an older date, never stack.
- Same day = edit in place. Letter suffix (`261017A`) only when asked.
- Update every reference and list them in the summary. Skip `*.min.*` and versioned vendor files.

**Database**

- New SQL goes in `config/schema/YYYY-MM-DD.sql`, appended per day.
- `_id` means internal integer key only. External IDs are camelCase `...ID` (`stripeCustomerID`), so OfflineBox backups and CakePHP bake stay correct.

## Testing, docs and git

The feature Markdown file is the source of truth; the AI never stages, commits or pushes on its own.

**Feature files and tests**

- Each significant feature has a Markdown file with intent, rules, edge cases and a `## Testing` section.
- Testing format: group, **Intent**, **Surfaces**, then `- [ ]` scenarios (no PHPUnit detail).
- Code, tests and doc disagree: the feature file wins unless the user changes the requirement.
- "MVP" work: no automated tests, but still syntax/lint checks and say tests were skipped.

**Git hygiene**

- No `git add`, `commit` or `push` unless asked in that same turn; the user stages to review every file.
- Never revert unrelated user changes; one logical change per commit.
- Broad formatting only when nothing is staged (`git diff --cached --quiet`).

**Pre-commit hook**

- Blocks public PHP methods and base templates over 45 lines (private/protected and elements exempt).
- Overrides: `PRECOMMIT_PUBLIC_FUNCTION_MAX_LINES`, `PRECOMMIT_TEMPLATE_MAX_LINES`, `SOFT_PRECOMMIT_DISABLE=1`, `--no-verify`.

## UpdateCase

UpdateCase moves editable copy out of templates. The AI proposes the structure as a deterministic Markdown file, and a human approves it before anything is written to the database.

**Workflow**

1. Build the site with realistic placeholder content.
2. **Convert:** add `changePage()`, swap copy for `getContentBy()` / `getImageBy()`, mark fallbacks `[UC-IMPORT]`.
3. Generate the import Markdown (headings + YAML blocks).
4. Paste into UpdateCase: parse, validate, preview, confirm, then create the records in one transaction.
5. Sync the site and check that no `[UC-IMPORT]` markers remain visible.
6. **Cleanup:** remove markers and fallbacks, keep the calls and loops, and report any unresolved fields.

**Structure:** Site, then Page, then Single Location (elements) or Grouped Location (groups of elements).

- Element types are only `text`, `paragraph`, `general` (rich) or `image`.
- Repeated items use one Grouped Location (`getGroupNamesByLocation` loop); forms are never grouped and get their own "... Form Fields" location.
- The client is created once in `AppController::beforeFilter()`; the cache lives in `tmp/updateCase/`; the site key may be committed.

## Standard, journeys and ops

The SetupCase standard (last updated 2026-11-06) is a promise list checked only on request ("run the standards check").

| Profile | Key items |
| --- | --- |
| web | Responsive, fast, HTTPS, tap-to-call, form confirmations + owner notification, 404 page, dated assets, titles/descriptions, sitemap, share previews, bot check, error alerts |
| software | HTTPS, CSRF, secrets outside webroot, not indexed, error alerts, OfflineBox daily backup to S3 |

- The README top states the profile, last-checked date and an `[x]` / `[ ]` / `[n/a]` checklist.
- **Journeys** (`docs/journeys/`): step-by-step flows that generate screenshots and a client PDF.
- **aiAccess:** password-less login for the journey runner, Docker only, one fixed AI user, local redirects only; any change needs human review.
- **Bugs/todos** (`docs/bugs/`, `docs/todos/`): tidy into Title / Description / Notes on touch, never invent details.
- **Timeline:** `docs/timeline/`, per quarter.
- **Docker networking:** never `down -v` or volume prunes; ask before restarting Docker.
- **License:** Apache-2.0; keep headers, LICENSE and NOTICE when copying.

## Discussion points

Possible changes noticed while summarizing.

- [ ] **Move UpdateCase out.** It is about a third of the file. Moving it to its own README (like journeys and timeline) and linking it would keep AGENTS.md short.
- [ ] **"Codex" wording.** The UpdateCase sections say "Codex" while other tools read the file too. Should it say "the AI assistant"?
- [ ] **Two line limits.** Public methods are "about 35" lines in the guidelines but 45 in the hook. Keep both, or pick one number?
- [ ] **STATUS array vs CakePHP built-ins.** Public Table methods like `initialize()`, `validationDefault()` and `buildRules()` can't return the STATUS array. Should custom methods be exempted explicitly?
- [ ] **Responsive Table reference.** It is listed as a canonical code block, but the current preference is no `table-responsive` wrapper. Check that the block matches.
- [ ] **`element/` vs `elements/`.** The hook exempts both folders. Is `elements/` still used?
- [ ] **No date on AGENTS.md itself.** Only the standard has a "last updated" date. Add one for the whole file?
- [ ] **Add a short directory map to AGENTS.md.** It only mentions `sourceFiles/`. A few lines on `codeBlocks/` vs `init-web/` vs `sourceFiles/`, plus a link to the workflow doc, would tell an AI where a change belongs.
- [ ] **One reverse-sync script.** Keep `8-Save-CodeBlocks.php` and retire `codeBlocks/import_new_changes.sh`.
