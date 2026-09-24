# TODO: Fix 8-Save to Include All CodeBlocks Files

## Status

TODO - not started. Captured 2026-09-24 after the first `Update CodeBlocks` run, to be reviewed and done in a dedicated session.

Parent workflow: `docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md` (Known Gap 3, Decision 1).

## Purpose

`init-web/8-Save-CodeBlocks.php` copies SetupCase-owned files from `sourceFiles/` back into `codeBlocks/cakePHP/4.x/`. Its list is explicit and maintained by AI during `Update CodeBlocks` (Decision 1). Today 47 files that exist in CodeBlocks have no entry, so edits to them on TestFlight are never copied back by 8-Save.

This matters most for the core foundation - especially RBAC (`RbacMiddleware`, `AccessMiddleware`) and authentication - which must be tracked.

The drift report still catches these files (it compares the whole CodeBlocks tree, so an edit shows as `DIFFERS`), but 8-Save should cover every synced file on its own.

## Current State (2026-09-24)

All 47 files are identical in `codeBlocks/cakePHP/4.x/` and `sourceFiles/` - nothing is lost today.

`codeBlocks/import_new_changes.sh` (the old reverse-sync script) covers about 8 of them; the rest were never in either list.

### Uncovered Files

Core foundation (auth, RBAC, language):

- `src/Middleware/AccessMiddleware.php`
- `src/Middleware/RbacMiddleware.php`
- `src/Middleware/LangMiddleware.php`
- `src/Middleware/FormLoginAttemptsAuthenticator.php`
- `src/View/Helper/AuthHelper.php`
- `src/View/Helper/LangHelper.php`
- `src/View/Helper/HtmlHelper.php`
- `src/Model/Table/UsersTable.php`
- `src/Model/Entity/User.php`
- `config/bootstrap-setupCase.php`
- `src/Util/Environments.php`
- `src/Util/OfflineBox.php`

Users pages:

- `templates/Users/` - `login.php`, `reset.php`, `begin_reset.php`, `signup.php`, `add.php`, `add_user.php`

SetupPages:

- `src/Controller/Admin/SetupPagesController.php`
- `src/Controller/Staff/SetupPagesController.php`
- `templates/SetupPages/` - `digital_signage.php`, `form_validation.php`, `google_analytics.php`, `home.php`, `increase_limit.php`, `read_more.php`, `responsive_table.php`, `set_timer.php`, `sticky.php`, `vue_test.php`
- `templates/Admin/SetupPages/` - `home.php`, `activity_logs.php`
- `templates/Staff/SetupPages/home.php`

Translations:

- `src/Controller/Manager/TranslationsController.php`
- `src/Model/Table/TranslationsTable.php`
- `templates/Manager/Translations/` - `edit.php`, `index.php`

Other tables:

- `src/Model/Table/CodeBlockTypesTable.php`
- `src/Model/Table/FormAttemptsTable.php`

Email and element snippets:

- `templates/email/html/default.php`
- `templates/email/html/email_reset.php`
- `templates/email/text/default.php`
- `templates/element/timer.php`
- `templates/element/increase_limit_script.php`
- `templates/element/bootstrap_current_size.ctp`

Clutter (candidates for removal from CodeBlocks instead):

- `src/Util/deprecated-OfflineBox.php`
- `src/View/Helper/.gitkeep`

## Rules

- **Folder entries only where SetupCase owns the whole folder.** A folder entry (`templates/Users/.`) copies back everything in that `sourceFiles/` folder, including files a project added for itself.
- **File entries for shared folders.** `src/Middleware/`, `src/View/Helper/`, `src/Model/Table/`, `src/Model/Entity/`, `src/Controller/*`, `src/Util/`, `config/`, `templates/email/` and `templates/element/` also hold project files, so each SetupCase file gets its own entry.
- 8-Save never creates or deletes files outside `codeBlocks/cakePHP/4.x/`.

## Planned Work

1. Add folder entries for SetupCase-owned template folders:
   - `templates/Users/.`
   - `templates/SetupPages/.`
   - `templates/Admin/SetupPages/.`
   - `templates/Staff/SetupPages/.`
   - `templates/Manager/Translations/.`
2. Add file entries for everything else in the list above (core foundation first, RBAC included).
3. Compare with `codeBlocks/import_new_changes.sh`; keep anything still valid, then delete that script.
4. Decide on the clutter: remove `deprecated-OfflineBox.php` and `View/Helper/.gitkeep` from CodeBlocks, or keep them with an entry.
5. Run the drift report `SAVE-LIST-MISSING` / `SAVE-LIST-DEAD` checks; both must be empty.
6. Update the parent workflow feature file (Known Gap 3, Decision 1) and `codeBlocks/cakePHP/4.x/changeLog.md`.

## Testing

### 8-Save Coverage

**Intent:** Every file SetupCase owns in CodeBlocks can be copied back from TestFlight by 8-Save, so foundation changes such as RBAC are never silently left behind.

**Surfaces:**
- `init-web/8-Save-CodeBlocks.php`
- `codeBlocks/cakePHP/4.x/`
- drift report (`SAVE-LIST-MISSING`, `SAVE-LIST-DEAD`)

#### Scenarios

- [ ] Every file under `codeBlocks/cakePHP/4.x/` (except `README.md` and `changeLog.md`) is covered by an 8-Save entry.
- [ ] Every 8-Save entry points at a file or folder that exists in `sourceFiles/`.
- [ ] An edit to `RbacMiddleware.php` on TestFlight is copied into CodeBlocks by 8-Save.
- [ ] A project-specific file added to a shared folder (e.g. a new middleware) is not copied into CodeBlocks.
- [ ] A new file added to a SetupCase-owned template folder (e.g. `templates/Users/`) is copied into CodeBlocks.
- [ ] `codeBlocks/import_new_changes.sh` no longer exists.
