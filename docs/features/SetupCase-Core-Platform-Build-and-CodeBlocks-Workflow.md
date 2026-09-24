# SetupCase Core Platform Build and CodeBlocks Update Workflow

## Purpose

SetupCase Core is an **opinionated, deterministic application foundation** for quickly starting new software projects from a known working baseline.

AI may help design, improve, document, test, and maintain SetupCase Core, but **AI must not dynamically generate the initial project foundation**.

> **AI helps build and maintain the factory. The factory deterministically builds the project.**

This feature defines the full loop:

```text
Build a new project  ->  Add a feature on TestFlight  ->  Promote it into CodeBlocks  ->  Next build includes it
```

It covers how SetupCase Core organizes platform initialization, reusable CodeBlocks, TestFlight development, and the AI-assisted process for propagating proven improvements back into the reusable baseline.

---

# Core Principles

## Deterministic Project Creation

New projects must be created using version-controlled scripts and known source files.

The initial project build must:

- be repeatable
- produce a known baseline
- use tested SetupCase Core assets
- not depend on AI deciding how the project should be assembled
- remain understandable and executable without AI

AI may create or improve deterministic scripts. Once reviewed and committed to SetupCase Core, those scripts become part of the authoritative build procedure.

## Opinionated Platforms

SetupCase Core intentionally chooses a preferred technology for each supported platform.

| Platform | Current Technology | Current Location | Status |
|---|---|---|---|
| Web | CakePHP 4.x / PHP | `init-web/`, `codeBlocks/cakePHP/4.x/`, `sourceFiles/` | Primary focus |
| Mobile | Vue + Vite (template) | `mobile-template/` | Experimental / future |
| Desktop | Python | `init-desktopApp/`, `template_localApp/` | Experimental / future |

These choices may evolve. SetupCase should not attempt to support every framework simultaneously.

## Don't Rename Working Infrastructure

Existing directory names are kept as-is (`codeBlocks/`, `cakePHP/`, `init-web/`, `dockerLinux/`, `launchPad_linux/`, ...). Casing or layout changes are only made when they solve a real problem, and then in their own isolated change.

---

# Current State (as of 2026-09-24)

This section records what the code actually does today, so the target workflow can be built on facts rather than assumptions. Update it as gaps are closed.

## Repository Layout

```text
/
├── codeBlocks/                     Reusable baseline (the "factory stock")
│   ├── README.md
│   ├── import_new_changes.sh       Legacy reverse-sync script (see Known Gaps)
│   └── cakePHP/4.x/                Files rsync-ed on top of a fresh CakePHP 4 app
│       ├── README.md
│       ├── config/                 app_DEV.php, bootstrap-setupCase.php
│       ├── src/                    Controllers, Middleware, Models, Util, View helpers
│       └── templates/              CodeBlocks demo pages, Users, layouts, elements, email
├── init-web/                       Deterministic web build scripts (browser-run)
├── install_setupCaseCore_modules.sh  Pulls factory modules from GitHub main into an existing project
├── dockerLinux/ dockerWSL/ dockerWIN/ dockerIDE/ dockerCOPYINTO/
├── launchPad_linux/ launchPad_win/
├── deploy/ deploy-init/
├── git-hooks/                      Soft pre-commit hook (line-limit checks)
├── mobile-template/ init-desktopApp/ template_localApp/
├── sourceFiles/                    Generated CakePHP app (TestFlight branch only)
└── docs/features/
```

## Branch Model

| Branch | Contains `sourceFiles/`? | Role |
|---|---|---|
| `main` | No | The factory. GitHub template source for new projects. |
| `testFlight` | Yes (generated + developed) | The running proof. Factory + a built and extended app. |

New projects are created from `main` using GitHub **Use this template** (see root `README.md`). `init-web/5-Create-New-Project.php` is deprecated in favour of that.

## Build Pipeline (web)

Entry point: `http://localhost/init-web/1-Install.php` (after `dockerLinux/1buildDocker.sh` or `dockerWSL/1buildDocker.sh`).

| Step | Script | Kind | What it does |
|---|---|---|---|
| 1 | `init-web/1-Install.php` | Orchestrator | Runs step 2a and 2b as CLI sub-processes; stops on non-zero exit. |
| 2a | `init-web/2-Install-Cake.php` | Framework install | `composer create-project cakephp/app:^4.0 sourceFiles` + `cakephp/authentication:^2.0`. Skips if `sourceFiles/` exists. |
| 2b | `init-web/2-Install-CodeBlocks.php` | Sync + transform | `rsync` `codeBlocks/cakePHP/4.x/.` -> `sourceFiles/.`, removes `sourceFiles/.gitignore`, then includes the `9-*` transforms. |
| 2b.1 | `9-Install-CodeBlocks_layout.php` | Transform | Layout wiring |
| 2b.2 | `9-Install-CodeBlocks_routes.php` | Transform | Language/login routes, prefix routing in `config/routes.php` |
| 2b.3 | `9-Install-CodeBlocks_bootstrap.php` | Transform | Loads `bootstrap-setupCase.php` |
| 2b.4 | `9-Install-CodeBlocks_application.php` | Transform | Authentication service provider in `src/Application.php` |
| 2b.5 | `9-Install-CodeBlocks_middleware.php` | Transform | Adds Lang/Rbac/Access/Auth middleware |
| 2b.6 | `9-Install-CodeBlocks_appController.php` | Transform | `setupCase()`, menu, Authentication component |
| 2b.7 | `9-Install-CodeBlocks_helpers.php` | Transform | Loads Auth/Lang helpers in `AppView` |
| 2b.8 | `9-Install-CodeBlocks_app.php` | Transform | `allowedLanguages` + `rbac` in `config/app.php` |
| 2b.9 | `9-install-CodeBlocks_citesting.php` | Sync | Copies `codeBlocks/.github` -> `/.github` |
| 3 | `init-web/3-View-CodeBlocks.php` | Redirect | Opens `/sourceFiles/CodeBlocks` |

The transforms follow a consistent pattern: read file, normalize line endings, check for a marker string, insert at a known anchor, fail if the anchor is missing. That pattern is the model for all future transforms.

`init-web/2-install-CodeBlocks-MANUAL-STEPS.md` lists steps that were historically manual. Several are now covered by the `9-*` transforms; the remainder still need to be identified and either automated or explicitly kept manual.

## Reverse Path (TestFlight -> CodeBlocks)

Two mechanisms exist today, and they disagree:

- `init-web/8-Save-CodeBlocks.php` - hardcoded list of `sourceFiles` paths rsync-ed back into `codeBlocks/cakePHP/4.x/`.
- `codeBlocks/import_new_changes.sh` - older, separate hardcoded list (includes a non-existent `Model/Table/Users.php` and a pasted PHP snippet at the end).

Neither records *when* the last promotion happened, so there is no reliable "changes since last update" boundary.

## Distribution to Existing Projects

`install_setupCaseCore_modules.sh` clones `main` and `rsync --delete`s selected factory modules (`codeBlocks`, `init-web`, docker, launchPad) into an existing project. It updates the factory folders only; it does not re-apply CodeBlocks into that project's `sourceFiles/`.

## Known Gaps

Found by comparing `codeBlocks/cakePHP/4.x/` against `sourceFiles/` on `testFlight` and reading the scripts. Each should become a task or be explicitly accepted.

1. **Drift - CodeBlocks older than TestFlight.** These differ between `codeBlocks/cakePHP/4.x/` and `sourceFiles/`:
   - `src/Controller/CodeBlocksController.php`
   - `src/Controller/SetupPagesController.php`
   - `src/Controller/UsersController.php`
   - `src/Controller/Admin/SetupPagesController.php`
   - `src/Util/SetupCase.php`
2. **Built feature not promoted.** The email queue / unified cron work (`docs/features/email-queue-feature.md`, `unified_cron_framework_and_monitoring.md`) lives in `sourceFiles/` but not in CodeBlocks: `src/Command/CronCommand.php`, `src/Service/CronService.php`, `src/Controller/Staff/EmailQueuesController.php`, `src/Model/Table/EmailQueuesTable.php`, `src/Model/Table/EmailQueueAttachmentsTable.php`, `config/schema/2026-09-08.sql`. A new project built today would not get it.
3. **`8-Save-CodeBlocks.php` references files that don't exist** in `sourceFiles/` (e.g. `AuditLogsTable.php`, `AuditContext.php`, `MenuStateHelper.php`, `Staff/AuditLogsController.php`, non-prefixed/Manager `EmailQueuesController.php`). The list is out of date with reality.
4. **Two reverse-sync scripts** (`8-Save-CodeBlocks.php`, `codeBlocks/import_new_changes.sh`) with different lists. There should be one.
5. **No schema path in the build.** `codeBlocks/cakePHP/4.x/` has no `config/schema/`, and no build step applies SQL. Features that need tables cannot be fully built deterministically yet.
6. **CI step expects a missing folder.** `9-install-CodeBlocks_citesting.php` requires `codeBlocks/.github`, which does not exist, and exits `1` - so a fresh `1-Install.php` run is expected to end in "Script failed" after all other steps succeed. (Read from code; not yet executed.)
7. **`exit` inside included transforms.** The `9-*` files are `include_once`-d by `2-Install-CodeBlocks.php`. Any bare `exit` (error path *or* the "already exists - skipping" path, e.g. in `9-Install-CodeBlocks_app.php`) stops every later transform, and error paths use `exit` with code `0`, so `1-Install.php` can report success on a partial install.
8. **No changelog, no last-sync marker.**
9. **Docs vs. code naming.** Earlier drafts of this doc used `CodeBlocks/CakePHP/4.x/`; the repo uses `codeBlocks/cakePHP/4.x/`. The repo names are authoritative (see "Don't Rename Working Infrastructure").

---

# CodeBlocks

`codeBlocks/` contains reusable, proven SetupCase implementation assets. Keep it cross-platform at the root, with technology/version-specific implementations underneath it.

Target layout for CakePHP 4.x (additions marked `+`):

```text
codeBlocks/
└── cakePHP/
    └── 4.x/
        ├── README.md
        ├── changelog-2026.md        +
        ├── config/
        │   └── schema/              +  dated SQL files, same convention as sourceFiles/config/schema
        ├── src/
        ├── templates/
        └── tests/                   +  tests that belong to promoted features
```

## Version Meaning

`codeBlocks/cakePHP/4.x/` means the contained files and procedures are intended and tested for CakePHP 4.x. A future `cakePHP/5.x/` is a sibling, not an edit.

## Where Build Scripts Live

Today the web build scripts live in `init-web/` and are version-specific in practice (they hardcode `codeBlocks/cakePHP/4.x`). Keep `init-web/` as the stable browser entry point. Moving the `9-*` transforms under `codeBlocks/cakePHP/4.x/scripts/` is optional and only worth doing when a second CakePHP version exists (see Open Decisions).

---

# CodeBlock Installation Strategy

```text
Known files + deterministic synchronization + deterministic transformation scripts
```

## Direct Synchronization

Use `rsync` of `codeBlocks/cakePHP/4.x/` into `sourceFiles/` for files SetupCase fully owns:

- SetupCase controllers, tables, entities, behaviors
- middleware, utilities, view helpers
- templates, elements, layouts, email templates
- `config/app_DEV.php`, `config/bootstrap-setupCase.php`
- dated schema SQL files

Rule: **a file is either fully owned by CodeBlocks (synced) or owned by the framework/project (transformed) - never both.** If a promoted feature needs to change a framework-generated file (`Application.php`, `AppController.php`, `AppView.php`, `routes.php`, `app.php`, `bootstrap.php`), that change is a transform, not a synced copy.

## Deterministic Transforms

Rules every `9-*` transform must follow:

- **Idempotent.** Detect a marker and skip that edit; running the build twice produces the same result.
- **Anchor or fail.** Insert at a known anchor; if the anchor is missing, fail loudly.
- **Non-zero on failure.** Error paths return a non-zero status so `1-Install.php` stops.
- **Never `exit` on skip.** Skipping one edit must not stop later transforms (the scripts are included, not sub-processes).
- **One file, one concern.** One transform per framework file being modified.

## Schema

Features that need tables ship their SQL in `codeBlocks/cakePHP/4.x/config/schema/YYYY-MM-DD.sql`, synced into `sourceFiles/config/schema/`. How and when the SQL is applied on a new project is an Open Decision; until decided, applying schema is a documented manual step in the build.

---

# Generated Source

After initialization the generated application lives in `sourceFiles/`. Future mobile or desktop implementations use separate source directories rather than mixing into `sourceFiles/`.

---

# TestFlight

TestFlight is the **working proof of the reusable baseline**: a real, running app built by the factory and then extended.

> Do not provide only examples or theoretical snippets. Maintain a real, running implementation that proves the SetupCase baseline works.

- `testFlight` branch = factory files + committed `sourceFiles/`.
- Develop and verify new features directly in `sourceFiles/`, deployed to the SetupCase TestFlight environment.
- The developer does not have to think about CodeBlocks while building the feature.
- The feature file under `docs/features/` is written/updated as part of the feature (per `AGENTS.md`).

---

# Development Flow

```text
main (factory)
   |  Use this template / 1-Install.php
   v
testFlight: sourceFiles built by factory
   |  build feature, feature file, verify on TestFlight
   v
"Update CodeBlocks" on testFlight  (factory-only commit)
   |  codeBlocks/, init-web/ transforms, schema, changelog, docs
   v
Rebuild check: fresh 1-Install.php reproduces the feature
   |
   v
Bring factory-only changes to main
   |
   v
Next new project includes the feature
```

Two concerns stay separate:

- **Building a new project - deterministic.** Known scripts and CodeBlocks build the project.
- **Improving SetupCase Core - AI-assisted.** AI analyzes a proven TestFlight implementation and decides how it is represented as synced files and/or transforms.

## Commit Separation (important)

Because `main` has no `sourceFiles/`, promotion to `main` must never carry `sourceFiles/`:

- On `testFlight`, keep **feature commits** (touching `sourceFiles/`) separate from **factory commits** (touching `codeBlocks/`, `init-web/`, `docs/`, scripts).
- Bring factory commits to `main` by cherry-pick, or by checking out only factory paths from `testFlight` onto `main`. Never merge `testFlight` into `main` wholesale.

---

# Update CodeBlocks

`Update CodeBlocks` is a standard SetupCase Core maintenance operation. When the user tells an AI coding tool `Update CodeBlocks`, it means: promote proven, reusable changes from `sourceFiles/` into the factory.

## Safety Check

Before doing anything, confirm `codeBlocks/cakePHP/4.x/` exists. If it doesn't, stop and warn that this may not be SetupCase Core. Do not invent a CodeBlocks structure inside an unrelated project.

## Establish the Boundary

1. Read the latest `codeBlocks/cakePHP/4.x/changelog-YYYY.md` entry and its `Source commit`.
2. Evidence set = `git log <source-commit>..HEAD -- sourceFiles/` plus the diffs.
3. If no changelog exists yet (first run), use the drift report below as the boundary.

## Drift Report (deterministic, read-only)

Before deciding anything, produce a report with three lists:

| List | Meaning | Default action |
|---|---|---|
| `DIFFERS` | File exists in CodeBlocks and `sourceFiles/`, contents differ | Review diff; copy back if the change is reusable |
| `MISSING-IN-SOURCE` | File exists in CodeBlocks but not in `sourceFiles/` | Investigate (deleted? renamed? never installed?) |
| `CANDIDATE` | File changed/added in `sourceFiles/` in the evidence set, not in CodeBlocks | Decide: promote as sync, promote as transform, or exclude |

The report is a script, not an AI judgement, so it can be re-run to confirm the result.

## AI Responsibilities

1. Run the safety check and drift report.
2. Read the feature files in `docs/features/` for the changes in the evidence set.
3. Group related commits and files into logical features.
4. For each feature, classify every file:
   - **sync** - SetupCase-owned file, copy into `codeBlocks/cakePHP/4.x/`
   - **transform** - change to a framework-owned file, add/extend a `9-*` script
   - **schema** - dated SQL into `codeBlocks/cakePHP/4.x/config/schema/`
   - **exclude** - project-specific or TestFlight-only; say why
5. Apply the changes (factory paths only).
6. Update `changelog-YYYY.md` with a grouped entry and the source commit.
7. Update `codeBlocks/cakePHP/4.x/README.md` / feature files if behaviour or procedure changed.
8. Report: what was promoted, as what, what was excluded and why, and whether the rebuild check ran.

AI must not blindly copy every changed file into CodeBlocks. Do not `git add` / `git commit` unless explicitly asked (see `AGENTS.md`).

## Rebuild Check

A promotion is only proven when a fresh build reproduces it:

1. Move `sourceFiles/` aside (don't delete it).
2. Run `init-web/1-Install.php` (and the schema step, while manual).
3. For every file under `codeBlocks/cakePHP/4.x/`, the new `sourceFiles/` copy must be identical.
4. For each transformed framework file, the new output must contain the promoted change.
5. The promoted feature works in the rebuilt app.
6. Run the build a second time on top; nothing changes (idempotency).
7. Restore the original `sourceFiles/`.

---

# Git History as Change Evidence

AI should inspect commit dates, messages, diffs, affected files, existing CodeBlocks, existing changelog entries, and feature files.

Git history is evidence of the development sequence, but **commit boundaries do not define changelog boundaries**. The `FEATURE: <file>.md - <stage>` commit message convention already used in this repo is the primary grouping hint.

---

# Changelog

Each platform/version keeps a yearly changelog: `codeBlocks/cakePHP/4.x/changelog-YYYY.md`. Start a new file each calendar year; never rewrite old ones.

## Grouping

One entry per logical feature, not per commit. The changelog describes **what changed in SetupCase**, not raw Git history.

## Dates

Use Git commit dates as evidence; normally the date the grouped work reached its completed/reusable state. Do not fabricate dates.

## Entry Format

```markdown
## 2026-09-24 - Email Queue

Added reusable queued email sending with cron-driven processing and monitoring.

- Feature file: `docs/features/email-queue-feature.md`
- Source commit: `95504bf7`

### Included

- sync: `src/Model/Table/EmailQueuesTable.php`, `src/Service/CronService.php`, ...
- transform: (none)
- schema: `config/schema/2026-09-08.sql`

### Excluded

- (anything intentionally left out, with reason)
```

`Source commit` is the last `testFlight` commit included in the promotion. It is the boundary for the next `Update CodeBlocks`.

---

# CodeBlocks README

`codeBlocks/cakePHP/4.x/README.md` explains the system (not each feature):

- what the directory represents and which CakePHP version it targets
- the sync vs. transform rule and where transforms live (`init-web/9-*`)
- the build pipeline order
- how TestFlight relates to CodeBlocks
- how `Update CodeBlocks`, the drift report, and the rebuild check work
- how changelogs are maintained
- platform-specific limitations

The current README only contains a pasted `bootstrap()` snippet and needs rewriting.

---

# AGENTS.md Integration

Root `AGENTS.md` gets a concise pointer so AI tools discover this workflow:

```markdown
## Update CodeBlocks

When the user asks to `Update CodeBlocks`, follow
`docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md`:

- Stop if `codeBlocks/cakePHP/4.x/` does not exist.
- Boundary = `Source commit` in the latest `changelog-YYYY.md`.
- Run the drift report first; classify each change as sync / transform / schema / exclude.
- Only touch factory paths (`codeBlocks/`, `init-web/`, `docs/`); never commit `sourceFiles/` to `main`.
- One changelog entry per logical feature, dated from Git.
- New-project creation must remain deterministic and AI-free.
```

---

# AI Boundary

AI may analyze Git changes, group commits, identify reusable code, update CodeBlocks, create or improve deterministic scripts, update feature docs and changelogs, identify missing files or dependencies, and propose improvements.

AI must not become a runtime dependency of the initial project build.

```text
AI-assisted maintenance
        ↓
Reviewed deterministic baseline
        ↓
Version-controlled scripts + CodeBlocks
        ↓
Deterministic project creation
```

---

# Open Decisions

To be settled with the project owner as we work through this; record the decision here when made.

1. **Reverse-sync tool.** Replace `8-Save-CodeBlocks.php` + `codeBlocks/import_new_changes.sh` with one tool. Proposal: the CodeBlocks tree itself is the manifest (anything already in `codeBlocks/cakePHP/4.x/` is copied back from `sourceFiles/`); new files are added deliberately during `Update CodeBlocks`. Removes the hardcoded lists that have drifted.
2. **Drift report form.** CLI script (bash/PHP) runnable in and out of Docker, vs. an `init-web/` page. Proposal: CLI script, so AI and humans get the same output.
3. **Schema application.** Manual step, `init-web` step that runs dated SQL, or CakePHP Migrations.
4. **CI files.** Create `codeBlocks/.github/` or make `9-install-CodeBlocks_citesting.php` optional.
5. MOVE to codeblocks/cakePHP/4.x/init-scripts/ => **Transform location.** Keep `init-web/9-*`, or move under `codeBlocks/cakePHP/4.x/scripts/`.
6. **Updating existing projects.** Whether a project created earlier can re-apply newer CodeBlocks into its own `sourceFiles/`, or only new projects benefit.
7. **First promotion.** Use the email queue / cron feature as the pilot run of this workflow.

---

# Testing

## Deterministic Build

**Intent:** A new SetupCase project can be created from the maintained baseline without AI decisions during the build.

**Surfaces:**
- `init-web/1-Install.php`, `2-Install-Cake.php`, `2-Install-CodeBlocks.php`
- `init-web/9-*` transforms
- `codeBlocks/cakePHP/4.x/`
- `sourceFiles/`

### Scenarios

- [ ] From `main` with no `sourceFiles/`, `1-Install.php` completes with exit code 0 and opens CodeBlocks.
- [ ] Every file under `codeBlocks/cakePHP/4.x/` is present and identical in the generated `sourceFiles/`.
- [ ] Each transform inserts its change once at the expected anchor.
- [ ] Running the build a second time changes no files.
- [ ] A transform whose anchor is missing stops the build with a non-zero exit code.
- [ ] A transform that skips (already applied) does not prevent later transforms from running.
- [ ] Schema files for promoted features are present in `sourceFiles/config/schema/` after build.

## Update CodeBlocks

**Intent:** Proven TestFlight improvements are propagated back into the reusable baseline accurately and reviewably.

**Surfaces:**
- Git history on `testFlight`
- drift report script
- `codeBlocks/cakePHP/4.x/` and its `changelog-YYYY.md` / `README.md`
- `init-web/9-*` transforms
- `docs/features/`

### Scenarios

- [ ] `Update CodeBlocks` stops with a warning when `codeBlocks/cakePHP/4.x/` is missing.
- [ ] The evidence boundary is the `Source commit` of the latest changelog entry.
- [ ] The drift report lists `DIFFERS`, `MISSING-IN-SOURCE`, and `CANDIDATE` files and gives the same result on re-run.
- [ ] Related commits are grouped into one changelog entry per logical feature.
- [ ] Each promoted file is classified as sync, transform, schema, or exclude, with a reason for excludes.
- [ ] Changes to framework-owned files are promoted as transforms, not synced copies.
- [ ] Only factory paths are modified; `sourceFiles/` is never promoted to `main`.
- [ ] Historical changelog files are unchanged.
- [ ] After promotion, the rebuild check reproduces the feature in a fresh build.

## Promotion to Main

**Intent:** New projects created from `main` receive promoted features without carrying TestFlight's app source.

**Surfaces:**
- `main` / `testFlight` branches
- GitHub "Use this template"
- `install_setupCaseCore_modules.sh`

### Scenarios

- [ ] After promotion, `main` contains the new CodeBlocks, transforms, schema, and changelog entry.
- [ ] `main` still contains no `sourceFiles/`.
- [ ] A project created from the template after promotion includes the feature after `1-Install.php`.

---

# Completion Criteria

This workflow is established when:

- the Known Gaps above are closed or explicitly accepted
- one reverse-sync tool exists and the hardcoded lists are gone
- the drift report exists and is documented
- `codeBlocks/cakePHP/4.x/README.md` describes the system
- `codeBlocks/cakePHP/4.x/changelog-2026.md` exists with at least one promoted feature and source commit
- the rebuild check has been run successfully for that feature
- `AGENTS.md` contains the concise Update CodeBlocks pointer
- new-project initialization remains fully deterministic

---

# Architectural Principle

> **Use AI to improve the factory, not to replace the factory.**

SetupCase Core harnesses AI where reasoning adds value, and converts those improvements into transparent, version-controlled, deterministic procedures that run repeatedly without AI.
