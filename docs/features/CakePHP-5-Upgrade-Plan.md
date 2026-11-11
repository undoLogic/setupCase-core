# CakePHP 5 Upgrade Plan - SetupCase Core

As of 2026-11-11

## Intent

SetupCase Core uses an **immutable foundation**. We don't upgrade an existing install in place. We rebuild from scripts, prove the build works, and then bring features over one at a time.

The goal of this work is **not** a full migration. The goal is a proven CakePHP 5 base that CUSTOMER projects can start from, built up one feature at a time.

## Decisions

- **Target:** latest CakePHP 5.4.x on **PHP 8.3**. 8.3 is already proven on CakePHP 4.6 in production, and we are not going higher for now.
- **Locked to 5.4.x:** the 5.x build installs with `cakephp/app:~5.4.0` and then `cakephp/cakephp:~5.4.0` (the skeleton pin alone does not lock the framework), so it picks up 5.4 patches but never jumps to 5.5 on its own. Moving to 5.5 is a deliberate change to the build script.
- **codeBlocks:** add a new sibling folder `codeBlocks/cakePHP/5.x/`. `4.x/` is not edited. Features are ported into `5.x/` one at a time, not copied in bulk.
- **init-web:** split into `init-web/4.x/` (the existing scripts, which keep working) and `init-web/5.x/` (builds the new version).
- **Fully independent scripts:** `4.x` and `5.x` share no code, so `4.x` can later be deprecated and deleted outright. The 5.x rebuild is also a chance to clean up.
- **Authentication plugin 4.x:** use `cakephp/authentication` 4.x, not 3.x, so it won't need upgrading again later. Checked in the 2.11.3, 3.3.7 and 4.2.1 source code (2026-11-11):
    - **Shared database is safe.** All three hash with `password_hash()` / `PASSWORD_DEFAULT` (bcrypt) and check with `password_verify()`. A password saved by the 4.x app logs in on the 5.x app and the other way round.
    - **Remember-me tokens changed (corrected 2026-11-11).** Authentication 4.2 issues a new HMAC token format. It still accepts the old 2-part tokens from the 4.x app while `legacyTokens` is on (the default) and upgrades them at the next login, provided both apps use the same `Security.salt`. The 4.x app cannot read the new tokens. An earlier note here said the formats were the same; that check compared a function that 4.2 had renamed.
    - **The one real change:** `AuthenticationService::loadIdentifier()` (used by the 4.x app) is removed in 4.x (deprecated since 3.3). The identifier is passed to each authenticator instead.
- **One build at a time:** both versions build into the same `sourceFiles/`. Core only ever builds one version at a time. Running several versions side by side is advanced work done in CUSTOMER projects, not part of core.

## Target structure

```text
codeBlocks/
  cakePHP/
    4.x/            unchanged
    5.x/            new, filled feature by feature
init-web/
  4.x/              existing scripts, moved with git mv
  5.x/              new build scripts for CakePHP 5
sourceFiles/        generated app (one version at a time)
```

## Steps

### Step 0 - Move init-web into 4.x

- Move the current scripts into `init-web/4.x/` with `git mv` so history is kept.
- Fix the paths, since the scripts now sit one folder deeper:
    - Every `dirname(__DIR__)` becomes `dirname(__DIR__, 2)` (`2-Install-Cake.php`, `2-Install-CodeBlocks.php`, `8-Save-CodeBlocks.php` and the `9-*` transforms).
    - `3-View-CodeBlocks.php` builds its redirect from `SCRIPT_NAME`, so it needs one more level up to reach `/sourceFiles/CodeBlocks`.
    - `__DIR__` includes between scripts in the same folder keep working as they are.
- The entry point becomes `localhost/init-web/4.x/1-Install.php`. Update every reference:
    - `README.md`: the install URL, the "Update CodeBlocks" steps and the rebuild check command.
    - Feature docs that name `init-web/8-*` or `9-*` scripts (build workflow, MVP Status Endpoint, MVP Files S3 Offload, todo-fix-8-save, CI pipeline testing).
    - The demo text in `codeBlocks/cakePHP/4.x/templates/element/2026/codeBlocks/login_throttling.php`.
    - Old entries in `changeLog.md` stay as written (they record history).
    - `install_setupCaseCore_modules.sh` and `import_from_testflight.sh` copy the whole `init-web` folder, so they need no change.
- Add a short directory map to AGENTS.md: what `codeBlocks/<framework>/<version>/`, `init-web/<version>/` and `sourceFiles/` are for, and a link to this plan and the build workflow doc. This tells an AI which folder a change belongs in.
- Prove it with a full rebuild (see Verification pattern).

### Step 1 - CakePHP 5 loads

- Create `init-web/5.x/` and `codeBlocks/cakePHP/5.x/`.
- `init-web/5.x/2-Install-Cake.php` installs `cakephp/app:~5.4.0` and locks `cakephp/cakephp:~5.4.0` (see Decisions).
- Bring over only the basic layout and the Application Foundation demo page (the one with no database).
- Expect the `9-*` transforms to fail at first. The CakePHP 5 skeleton has different anchors in `Application.php`, the routes and `AppController`. Each failure shows exactly what to port next.
- Done when the foundation page loads and you can click around it.

**Done 2026-11-11.** What was learned:

- `cakephp/app:~5.4.0` only locks the skeleton. The framework is locked separately with `composer require cakephp/cakephp:~5.4.0`, otherwise it could drift to 5.5. The build resolved CakePHP 5.4.4.
- All 4.x transform anchors still exist in the 5.4 skeleton. The transforms were rewritten as independent 5.x copies that only add what Step 1 needs.
- The Application Foundation page needs no database, but it shows the source of the Staff/Manager `CodeBlocksController`, so those classes and templates are in `codeBlocks/cakePHP/5.x/`. No prefix routes exist yet, so they return 404 by URL until Step 2 adds prefixes and RBAC.
- CakePHP 5 fixes needed: typed `$_defaultConfig` in helpers, typed `HtmlHelper::link()` signature, and log `'scopes' => null` instead of `false`.
- `LangMiddleware` redirected plugin URLs to `/en/...`, which broke the DebugKit toolbar. 5.x now lets plugin routes through. 4.x likely has the same problem and was left unchanged.
- The full list is in `codeBlocks/cakePHP/5.x/changeLog.md`.
- Not ported yet: a 5.x `8-Save-CodeBlocks.php` (reverse sync). Add it before the first "Update CodeBlocks" on 5.x.

### Step 2 - Login, RBAC and Access

- Port login, `RbacMiddleware` and the Access middleware.
- Prefixes must work, because the security rules in AGENTS.md depend on them.
- Restore the parts Step 1 left out: login/logout and prefix routes, `rbac` in `app.php`, EncryptedCookie/Authentication/Rbac/Access middleware, the Authentication component, `getUserId()`, the RBAC redirect in `setupCase()`, the Auth helper and the login link in the sub-menu.
- Install `cakephp/authentication` 4.x (see Decisions), pinned to the minor version that passes this step.
- Before porting `getAuthenticationService()`, read both official upgrade guides: [2.x to 3.x](https://book.cakephp.org/authentication/3/en/upgrade-2-to-3.html) and the 3.x to 4.x guide (not included in the package source).
- Pass the Password identifier to each authenticator (Form and Cookie) instead of calling `loadIdentifier()` on the service. `ThrottledFormAuthenticator` comes with failed-login blocking (Later).

**Done 2026-11-11.** Built with authentication 4.2.1 (`~4.2.0`). What was learned and changed vs 4.x:

- **Prefix access is enforced in `AccessMiddleware`.** It returns the redirect itself (login page if logged out, home with "Requires X access" if the role lacks the prefix), so no controller code runs. The Authentication component is loaded with `requireIdentity => false`; prefix RBAC is the only gate.
- **Why in middleware:** CakePHP only stops an action when `beforeFilter()` returns the redirect response, so refusing in middleware is the reliable place.
- **Login URLs:** one per allowed language (`/en/login`, ...) without the base folder, checked with `'urlChecker' => 'Authentication.Multi'`. The 4.x style (base folder in the URL) would be doubled by the 4.x `DefaultUrlChecker`.
- **Login redirect** uses `getLoginRedirect()`, which only accepts local URLs.
- **Logout** stays GET (owner decision). AGENTS.md "HTTP Method by Effect" does not list it as an exception yet.
- **Not ported:** `EncryptedCookieMiddleware`.
- **No public signup (decided 2026-11-11).** 4.x `UsersController::add()` / `addUser()` are not ported. Users are created on the server with `bin/cake users add <email> <ADMIN|MANAGER|STAFF>`, which calls `UsersTable::addUser()`. The password is asked for without echo (or generated and shown once), never passed on the command line. The role must be a key of config `rbac`, the email must be unique, and the password at least 12 characters.
- **Not ported yet:** password reset (`beginReset`, `reset`).
- **Application Foundation with database** ("Blocks with DB" menu) ported the same day: Staff index/create/view/duplicate, Manager edit/delete. Delete and duplicate are POST only. A router URL filter keeps the language on every array URL; without it, delete buttons posted to a URL without a language, LangMiddleware redirected, and the browser re-sent it as GET (405). `AuditLog` and the CSV/PDF downloads are not ported.
- New in `codeBlocks/cakePHP/5.x/`: `UsersController` (login/logout), `UsersTable`, `User` entity (password and reset token hidden, only `email` mass-assignable), `RbacMiddleware`, `AccessMiddleware`, `AuthHelper`, login template.

### Later

- Failed-login blocking (brute-force protection), with `ThrottledFormAuthenticator`.
- `AuditLog` behavior, CSV/PDF downloads.
- Password reset.
- All other features, pulled in as CUSTOMER work needs them.

## Verification pattern

The build is disposable, so an AI or a CLI script can prove it:

1. Rename the existing `sourceFiles/` (for example `sourceFiles.old/`).
2. Rebuild from scratch with the new structure.
3. Diff the old and new folders. They should match.

Leave out of the diff anything that changes on every build, or you'll get false alarms:

- `vendor/` (Composer versions are not pinned yet)
- the generated salt in `config/app_local.php`
- `tmp/` and `logs/`

## Applying this to a CUSTOMER project

The same idea applies: don't upgrade in place.

- Build a brand new `sourceFiles/` from the 5.x base, then move CUSTOMER features over one by one. It's slower at first, but it beats debugging everything after the fact.
- Breakage after the move is in the CUSTOMER code, not the foundation, because the foundation is already proven.

### Database setup

- Run the database in Docker on the **same engine and version as production** (check this, don't assume it).
- Match the SQL mode too. Strict-mode differences cause sneaky bugs.
- Run two app containers on different ports (old app and new app) against **one shared database**, so both can be tested side by side.

### Watch out for

- **Cookies ignore ports.** Both apps on `localhost` share cookies. Give each app its own session cookie name, or use different hostnames, or they keep logging each other out.
- **Writes are shared.** Anything the new app writes also hits the old app. Snapshot the database first so you can reset it.
- **Use a copy of production data,** never the live database.
- **Two codebases at once.** Changes requested during the move have to go into both apps. Agree on a feature freeze, or keep the window short.
- **One big cutover.** The move is gradual but going live is not. Run the journeys against the new build before switching.

## Testing

### Init-web 4.x move

**Intent:** The existing 4.x build still works after moving the scripts.
**Surfaces:** `init-web/4.x/`, `sourceFiles/`

- [x] `init-web/4.x/1-Install.php` runs to the end with no failures (2026-11-11, exit 0)
- [ ] A rebuild diffed against the previous `sourceFiles/` shows no unexpected differences
- [x] README links point to the new entry point
- [x] `3-View-CodeBlocks.php` opens `/sourceFiles/CodeBlocks` from its new location
- [x] AGENTS.md has the directory map

### CakePHP 5 foundation

**Intent:** A fresh 5.x build loads the Application Foundation demo.
**Surfaces:** `init-web/5.x/`, `codeBlocks/cakePHP/5.x/`

- [x] `init-web/5.x/` builds CakePHP 5.4.x on PHP 8.3 (2026-11-11: 5.4.4 on PHP 8.3.36)
- [ ] Two builds on different days both stay on 5.4.x
- [x] The foundation demo page loads with the basic layout
- [x] No errors in `logs/`
- [x] Staff/Manager pages are not reachable by URL before Step 2 (404)
- [x] The DebugKit toolbar loads

### CakePHP 5 login and access

**Intent:** Login, RBAC and Access work with prefixes on 5.x.
**Surfaces:** login, `RbacMiddleware`, Access middleware, prefixed routes

- [x] A user can log in and log out (2026-11-11, curl through the real form with CSRF token)
- [x] A wrong password shows "Email or password is incorrect."
- [ ] A user created by the 4.x app logs in on the 5.x app with the same password (shared database). Not run: the only 4.x user's password is unknown. Same hasher code verified in source.
- [ ] A password changed on the 5.x app still logs in on the 4.x app. Not run: needs a 4.x build.
- [x] Each role reaches only its allowed prefixes (STAFF refused on Manager/Admin, MANAGER refused on Admin)
- [x] A refused request never reaches the controller (STAFF POST to a Manager delete is redirected by AccessMiddleware)
- [x] A logged-out user is redirected to the login page, and back to the page they asked for after logging in
- [x] An external `?redirect=` URL is ignored after login
- [x] Remember me logs the user back in on a new session
- [x] Application Foundation with database: STAFF can list, view and create; MANAGER can edit and delete; STAFF is refused on edit
- [x] Delete and duplicate refuse GET (405); a delete button removes the row
- [x] Edit ignores a non-local `cameFrom`
- [x] There is no public signup URL (`/en/users/add` returns 404)
- [x] `bin/cake users add` creates a user who can then log in (generated and typed password)
- [x] `bin/cake users add` refuses a duplicate email, an invalid email, an unknown role and a password under 12 characters

## Open questions

None. Both earlier questions are answered and moved into the plan:

- Lock to 5.4.x: see Decisions and Step 1. Uses `~5.4.0`, not `^5.4`, because `^5.4` would also allow 5.5.
- Directory map in AGENTS.md: see Step 0.
