# CakePHP 5 Upgrade Plan - SetupCase Core

As of 2026-11-11

## Intent

SetupCase Core uses an **immutable foundation**. We don't upgrade an existing install in place. We rebuild from scripts, prove the build works, and then bring features over one at a time.

The goal of this work is **not** a full migration. The goal is a proven CakePHP 5 base that CUSTOMER projects can start from, built up one feature at a time.

## Decisions

- **Target:** latest CakePHP 5.4.x on **PHP 8.3**. 8.3 is already proven on CakePHP 4.6 in production, and we are not going higher for now.
- **Locked to 5.4.x:** the 5.x build installs with `cakephp/app:~5.4.0`, so it picks up 5.4 patches but never jumps to 5.5 on its own. Moving to 5.5 is a deliberate change to the build script.
- **codeBlocks:** add a new sibling folder `codeBlocks/cakePHP/5.x/`. `4.x/` is not edited. Features are ported into `5.x/` one at a time, not copied in bulk.
- **init-web:** split into `init-web/4.x/` (the existing scripts, which keep working) and `init-web/5.x/` (builds the new version).
- **Fully independent scripts:** `4.x` and `5.x` share no code, so `4.x` can later be deprecated and deleted outright. The 5.x rebuild is also a chance to clean up.
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
- `init-web/5.x/2-Install-Cake.php` installs `cakephp/app:~5.4.0` (see Decisions).
- Bring over only the basic layout and the Application Foundation demo page (the one with no database).
- Expect the `9-*` transforms to fail at first. The CakePHP 5 skeleton has different anchors in `Application.php`, the routes and `AppController`. Each failure shows exactly what to port next.
- Done when the foundation page loads and you can click around it.

### Step 2 - Login, RBAC and Access

- Port login, `RbacMiddleware` and the Access middleware.
- Prefixes must work, because the security rules in AGENTS.md depend on them.
- CakePHP 5 uses version 3 of the authentication plugin. Read the official [2.x to 3.x upgrade guide](https://book.cakephp.org/authentication/3/en/upgrade-2-to-3.html) before porting the service setup in `Application.php`.

### Later

- Failed-login blocking (brute-force protection).
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

- [ ] `init-web/5.x/` builds CakePHP 5.4.x on PHP 8.3
- [ ] Two builds on different days both stay on 5.4.x
- [ ] The foundation demo page loads with the basic layout
- [ ] No errors in `logs/`

### CakePHP 5 login and access

**Intent:** Login, RBAC and Access work with prefixes on 5.x.
**Surfaces:** login, `RbacMiddleware`, Access middleware, prefixed routes

- [ ] A user can log in and log out
- [ ] Each role reaches only its allowed prefixes
- [ ] A logged-out user is redirected away from protected prefixes

## Open questions

None. Both earlier questions are answered and moved into the plan:

- Lock to 5.4.x: see Decisions and Step 1. Uses `~5.4.0`, not `^5.4`, because `^5.4` would also allow 5.5.
- Directory map in AGENTS.md: see Step 0.
