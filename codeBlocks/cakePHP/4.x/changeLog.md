# CodeBlocks CakePHP 4.x - Change Log

Newest entry first. One entry per logical feature. `Source commit` is the last `testFlight` commit included; the next `Update CodeBlocks` starts from it.

See `docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md`.

---

## 2026-09-24 - Build Script Hardening

Made `init-web/1-Install.php` finish cleanly on a fresh build and safe to re-run.

- Feature file: `docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md`

### Included

- transform: all `init-web/9-*` errors exit 1; "already applied" skips end only that transform.
- transform: routes use `{language}` placeholders; fixed the "already installed" marker so re-runs don't duplicate routes.
- transform: layout creates `icons/fonts/` and fails the build on a failed download/save.
- transform: CI setup is optional (skips when `codeBlocks/.github/` is absent).
- build: `README.md` and `changeLog.md` are no longer copied into `sourceFiles/`.
- build: `1-Install.php` shows sub-script output as HTML.

---

## 2026-09-08 - Email Queue

All email is written to a queue and sent through the SetupCase mail utility; Staff can view, create, edit, send, test-send and remove queued emails.

- Feature file: `docs/features/email-queue-feature.md`
- Source commit: `89cff894`

### Included

- sync: `src/Model/Table/EmailQueuesTable.php`, `src/Model/Table/EmailQueueAttachmentsTable.php`
- sync: `src/Controller/Staff/EmailQueuesController.php`, `templates/Staff/EmailQueues/`
- sync: `src/Util/SetupCase.php` (mail helper returns the error message on failure), `src/Controller/UsersController.php` (updated for that return value)
- sync: `templates/CodeBlocks/email_queues.php`, `templates/element/2026/codeBlocks/email_queues.php`, `CodeBlocksController::emailQueues()`
- transform: `AppController::getUserId()`; menu "Email Queues" (Blocks) and "Automated Emailers" renamed to "Email Queues" (Blocks with DB)
- schema: `config/schema/2026-09-08.sql` (apply manually until the schema build step exists)

---

## 2026-07-20 - AGENTS.md Page

CodeBlocks page showing the repository `AGENTS.md`.

- Source commit: `89cff894`

### Included

- sync: `templates/CodeBlocks/agents.php`, `CodeBlocksController::agents()`
- build: `2-Install-CodeBlocks.php` copies the root `AGENTS.md` to `sourceFiles/AGENTS-copy.md` on every build
- transform: menu "AGENTS.md"

### Excluded

- Integration Testing page (`intergration_testing.php`, `Intergration_testing-COPY.md`): dropped; testing now lives in each feature file's `## Testing` section.
- Passwordless Email Login page: unfinished stub.

---

## 2026-07-19 - Unified Cron Framework

One IP-restricted `cron.php` entry point runs configured jobs (public Table methods) and reports status for monitoring.

- Feature file: `docs/features/unified_cron_framework_and_monitoring.md`
- Source commit: `89cff894`

### Included

- sync: `src/Service/CronService.php`, `src/Command/CronCommand.php`, `config/cron.php`, `webroot/cron.php`
- sync: `SetupPagesController::cronStatus()`
- sync: `templates/CodeBlocks/unified_cron_framework_and_monitoring.php`, `templates/element/2026/codeBlocks/unifiedCronFrameworkAndMonitoring_setup.php`, `CodeBlocksController::unifiedCronFrameworkAndMonitoring()`
- transform: menu "Unified Cron Framework"

### Known Issues (promoted as-is from TestFlight)

- `webroot/cron.php` calls `CronService::isExecutionAllowed()`, which no longer exists; `run_all` / `run` return 500.
- `config/cron.php` enables `EmailQueues::processCronQueue`, which does not exist.
- `/setup-pages/cron-status` returns 500 because `SetupPagesController` loads a missing `ObjectStorages` table.
