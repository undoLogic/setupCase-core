# OfflineBox

Public branding: www.OfflineBox.com

**Until your data is physically in your hands, you don't have it.**

OfflineBox is a separate, opt-in module in SetupCase core. Nothing in `sourceFiles/` loads it until a project installs it. It dumps each database connection you list, uploads the dumps to S3-compatible storage, and verifies each upload. A local client can then pull the backups down to a machine in your office, so a real offline copy exists.

Every SetupCase app owns its data, can prove it, and can restore it.

## MVP Status

This is MVP work (AGENTS.md "MVP Work"):

- No automated tests are created or scaffolded.
- `## Testing` lists the expected behaviour to check by hand against a test bucket. Nothing is marked covered until it has been run.
- This README is the feature file. It lives with the module instead of in `docs/features/`, so it travels with the class.

**Status (2026-11-05):** built, and not yet tried on a real project. The module is ready for its first install, and core itself is unchanged: the old OfflineBox code stays in `sourceFiles/` and `codeBlocks/` until the install steps remove it from a project.

Tested in throwaway containers (MySQL 5.7 with the project's PHP 8.3 image and a fake S3 server):

- Dump and restore: exact bytes for `latin1` accents and emoji, excluded tables empty, and trigger, view and procedure restored
- Failures: exclude typo, include typo, zero tables, bad connection, wrong bucket, corrupted upload and missing setting all fail loudly, with no files left behind
- Web guard: `mysqlDump()` and `upload()` refuse to run from a web server
- `--check`, and rendering the page templates with real results

Not tested yet: the page through a real login and route, real DigitalOcean Spaces, the multipart upload path (the test upload did not trigger it), a UNIX socket connection, and PHP 8.4. The `## Testing` boxes stay unticked until they pass on a real project.

## Supported for the MVP

| | Version | Notes |
|---|---|---|
| Database server | MySQL 5.7 (first target: 5.7.44 Percona) | Reached over a UNIX socket as `localhost`. Server charset is `latin1`. Docker's `mysql:5.7` is the same major version. |
| PHP | 8.4 (minimum 8.2) | 8.4.27 is the web version on the first target. CLI PHP must be checked separately, see `## Requirements`. |
| Web server | Apache | Not involved: backups never run through the web. |

Other database servers are not supported or tested in the MVP, including MySQL 8 and MariaDB servers.

## Decisions

### Confirmed (2026-11-05)

- **3. Delete the old version, per project.** Full removal, nothing kept, but only when the AI installs OfflineBox on an actual project (see `## For AI assistants installing OfflineBox

Follow these steps in order, on the actual project. Paths are relative to the app folder (`sourceFiles/` in a SetupCase project). Don't stage or commit anything. Leave the changes for the developer to review.

Don't rewrite or "improve" the class inside the host project. Project-specific behaviour (which databases, table rules, failure alerts) goes in the host project's command, never in the class. Keep the copyright headers, and carry the `LICENSE` and `NOTICE` files into the destination project.

**1. Check the project.** Confirm it meets `## Requirements`. Run `php -v` and `mysqldump --version` on the CLI, and check `vendor/aws/aws-sdk-php` exists. If the SDK is missing, install it with `composer require aws/aws-sdk-php`, and commit `vendor/` if the project does.

**2. Remove any old version.** First search the project for `OfflineBox`, `uploadDatabaseBackupToS3`, `downloadBackup` and `mysqldump`.

- Delete `src/Util/OfflineBox.php` and `src/Util/deprecated-OfflineBox.php` if they exist.
- Delete `downloadBackup()` from `src/Controller/Admin/SetupPagesController.php` if it exists. Also remove its `ConnectionManager` and `Response` imports if nothing else in that file uses them.
- **If anything else uses the old code (a shell, a cron job, a route, a link, another class), stop and show the developer.** Don't guess. The old constructor took no arguments, so those callers would break.
- Don't edit anything under `codeBlocks/` if the project has that folder.

**3. Copy the files unchanged.** Fetch each one from its raw link, under `https://raw.githubusercontent.com/undoLogic/setupCase-core/main/offlineBox/`:

| Fetch | Copy to |
|---|---|
| `OfflineBox.php` | `src/Util/OfflineBox.php` |
| `OfflineBoxCommand.php` | `src/Command/OfflineBoxCommand.php` |
| `src/Controller/Admin/OfflineBoxController.php` | same path |
| `templates/Admin/OfflineBox/index.php` | same path |
| `templates/element/2026/admin/offlineBox/index_summary.php` | same path |
| `templates/element/2026/admin/offlineBox/index_databases.php` | same path |
| `templates/element/2026/admin/offlineBox/index_rows.php` | same path |

**4. Edit the command for the project.** This is the only copied file you edit. Set `CONNECTIONS` to the project's real connections (see `## Choosing tables`), and ask the developer which tables to exclude. Add the failure alert at the failure hook, using the project's existing email or queue. `SetupCase::sendEmail()` may contain a `die()` for the live environment, so check it before relying on it.

**5. Set the storage credentials** using the ENV Vars pattern, where CLI PHP can read them. Ask the developer for the values and where they go. Never commit them, and never write them in a file in the repo. CLI often uses a different `php.ini` than Apache (check with `php --ini`). The class never looks up secrets itself; the command passes them in:

| Setting | Meaning |
|---|---|
| `OFFLINEBOX_S3_KEY` | Access key |
| `OFFLINEBOX_S3_SECRET` | Secret key |
| `OFFLINEBOX_S3_REGION` | Region, e.g. `nyc3` |
| `OFFLINEBOX_S3_BUCKET` | Bucket name |
| `OFFLINEBOX_S3_ENDPOINT` | Optional. Defaults to DigitalOcean Spaces for the region. |

**6. Add the admin link.** Add a link to the validation page on the project's admin index page, normally `admin/pages/index`, alongside the other admin links and features. If the project doesn't have that page, ask the developer where the link goes. The page must stay under the `Admin` prefix:

```php
<?= $this->Html->link('OfflineBox', ['prefix' => 'Admin', 'controller' => 'OfflineBox', 'action' => 'index']); ?>
```

**7. Verify.**

1. Open the page and report anything it flags.
2. Run `bin/cake offline_box --check`. Confirm it names the real database.
3. Run `bin/cake offline_box` once. Confirm the object is in the bucket and no `.sql` file remains in `tmp/`.
4. Restore that dump onto an empty server, never the live one (see `## Restoring`).

**8. Set up cron, only once the developer has confirmed.** **Ensure you are connected to the real environment before setting up cron.** See `## Cron`.

**Never:** add this to `config/cron.php`, add a route or controller action that runs a backup, or weaken the CLI guard in the class. To change the class itself, change it in SetupCase core and copy it again.

## Requirements

- PHP 8.2 or later on the **CLI** (required by `aws/aws-sdk-php` 3.402, the version in core). The CLI can differ from the web version; check with `php -v`.
- CakePHP (uses `ConnectionManager`, `Log`, `TMP` and console commands)
- `aws/aws-sdk-php`
- `mysqldump` on the cron user's `PATH`, with CLI PHP allowed to call `exec()`. Check with `mysqldump --version`: an 8.0 client fails against a 5.7 server (`COLUMN_STATISTICS` error).
- A server that can run a CLI cron job, with a cron user that can write to `TMP` and `logs/`
- An S3-compatible bucket, ideally in the client's own account

## Command template

`OfflineBoxCommand.php` is copied into the project and then edited (step 4). `bin/cake offline_box` runs the backup for each connection in `CONNECTIONS`, one bad connection doesn't stop the rest, and it exits non-zero on any failure. `--check` runs the validation only and exits non-zero only on a problem, not a warning.

## Admin validation page

Answers "is this set up correctly, and what will be exported?" without running a backup.

- **URL:** `GET /admin/en/offline-box`. Admin prefix RBAC only, with no role checks in the action.
- **Read-only:** no dump, no bucket write, no temp file, no POST. A check that fails shows red instead of breaking the page.
- **Secrets:** passwords and keys are never shown, only "set" or "missing".
- **Files in `offlineBox/`** (folders match the install path):
    - `src/Controller/Admin/OfflineBoxController.php`
    - `templates/Admin/OfflineBox/index.php`
    - `templates/element/2026/admin/offlineBox/index_summary.php`, `index_databases.php` and `index_rows.php`

The controller calls `OfflineBox::validate()` and hands the template ready-to-show rows. Every section uses Bootstrap badges: green OK, yellow warning, red problem.

**Summary.** "Ready" or "Problems found", with the count of each. It always includes: "Checked from the web server. Run `bin/cake offline_box --check` as the cron user to check the cron environment."

**Databases: what will be exported.** One row per configured CakePHP connection:

| Column | Shows |
|---|---|
| Connection and database | Connection name and database name |
| Will export | **Yes** if it is in `OfflineBoxCommand::CONNECTIONS`, otherwise **No** |
| Connects | OK or the error, with the server version. Only tried for connections that will export. |
| Tables | Count with data, and count structure only |
| Size | Estimated size, from `information_schema` |

Each exported database expands to its full table list, with a **Data + structure** or **Structure only** badge per table. Red: an `exclude` name matching no table, or rules that resolve to no tables. Yellow: non-InnoDB tables, because the snapshot doesn't protect them.

**Environment.**

- Web PHP version, and whether `exec()` is allowed
- `mysqldump` found and its version. Red for an 8.0 client against a 5.7 server.
- `TMP` is writable, with free disk space compared to the estimated dump size

**Storage.**

- Each `OFFLINEBOX_S3_*` setting: set or missing, plus the bucket name and endpoint
- Bucket reachable with these credentials
- Newest object under `backups/` with its date, size and age. Red if it is 2 days old or more. Yellow if there is none yet, as on a new install.

**What this page can't prove:** that the key can write to the bucket, that a real dump succeeds, that cron is scheduled, or that a backup restores. Those need the first real run and `## Restoring`.

## Choosing tables

Each connection takes an `include` and an `exclude` value.

| `include` | `exclude` | Result |
|---|---|---|
| `*` or not set | empty | Whole database |
| `*` or not set | table list | All tables; excluded tables are structure only |
| table list | empty | Only the listed tables |
| table list | table list | Only the listed tables; those also excluded are structure only |

- Excluded tables are restored empty, so the app still finds them.
- Table lists can be an array or a newline-separated string (paste straight from phpMyAdmin).
- If the rules resolve to no tables, the dump fails instead of dumping everything.
- If an `exclude` name matches no table, the dump fails instead of backing up data meant to be left out.

## Cron

```cron
0 3 * * * cd /path/to/app && bin/cake offline_box >> logs/offlinebox.log 2>&1
```

**Before adding this, ensure the CLI connects to the real environment, not dev.** Run `bin/cake offline_box --check` on the server and confirm the database name. Cron has a short `PATH`, so add the folders holding `php` and `mysqldump` if needed.

The command exits non-zero on failure so cron and any monitoring can detect it. Failures are also logged at `error`, so they land in `logs/cli-error.log`, which the Status Endpoint block counts.

Don't add this job to `config/cron.php`, and don't call the command or the class from a web route. The class refuses to run outside the CLI.

## Restoring

Each dump starts with `CREATE DATABASE IF NOT EXISTS` and `USE`, so it restores onto an empty server of the same major version (MySQL 5.7 in the MVP):

```bash
mysql -h <host> -u <user> -p < 2026-11-05_default_030000.sql
```

**Never restore onto a server holding the live database to "test" a backup.** The dump switches to the original database name and replaces its tables.

## Roadmap

Each phase is useful by itself. A later phase never needs to exist for an earlier one to be worth running.

### Phase 1: Server backup to S3 (current)

Install one class, add a cron job, and every listed database lands in S3-compatible storage automatically.

Built, and tested only as listed in `## MVP Status`:

- [x] Dump each connection with include/exclude table rules
- [x] Upload as private objects and verify size and MD5
- [x] Keep the database password off the command line
- [x] CLI guard on `mysqlDump()` and `upload()`, with a read-only `validate()`
- [x] `App\Util` namespace and the Apache-2.0 copyright header
- [x] Configurable S3 endpoint with an optional `spaceEndpoint`
- [x] One upload method using `ObjectUploader`, always verified
- [x] MySQL 5.7 dump flags, and the charset in the `CREATE DATABASE` header
- [x] Fail on an `exclude` name that matches no table
- [x] Remove partial dumps and credentials files, create them as `0600`, and quote the password
- [x] Include the `mysqldump` error text in `MSG`
- [x] `OfflineBoxCommand.php`: exits non-zero on failure, handles each database separately, deletes the local `.sql`, uploads under `backups/`, and has the failure hook
- [x] Admin validation page and `--check`
- [x] Install steps for removing the old version from a project

Still to do:

- [ ] First install and test on a real project
- [ ] Decide when to remove the old code from core itself

### Phase 1B: Heartbeat

Silence gets flagged, with no storage keys anywhere but the server.

- After a verified backup, the server sends one HTTP request to a monitoring endpoint with project, databases, date and result
- The endpoint uses a per-project key that can only report status
- The monitor flags any project that hasn't reported within an expected window

### Phase 2: Offline copy and local restore check

A machine in the office holds the data and proves it restores.

- Local client pulls backups (Windows exists in the separate `undoLogic/offlineBox` repo; add Linux and macOS)
- Fix the archive re-download loop with a bucket lifecycle rule or by syncing only recent files. The client moves old files out of `Current`, so the next `aws s3 sync` downloads them again.
- Automated restore check: load the latest dump into a throwaway MySQL container of the same major version (5.7), compare table and row counts, confirm the newest record is recent, log pass or fail
- Move local credentials into a protected store

### Phase 3: Local heartbeat

- The office machine reports each restore check to the monitor
- Catches every failure mode: dump failed, sync failed, office machine off, restore failed

### Phase 4: Full app boot test

- Boot the app in a container against the restored database
- Run a few smoke checks (login page loads, key records display)

### Phase 5: Dashboard

- offlinebox.com dashboard showing each project's last verified backup
- History of checks for audit and client reporting
- Daily-then-monthly backup thinning
- Optional encryption before upload

## Manual quarterly check

Automation proves the data loads. A person confirms it makes sense. Once a quarter:

- Restore the latest backup by following the steps above
- Spot-check real records
- Send the client a short report: which databases, from what date, and the result

## Testing

Phase 1 only. All scenarios are manual, against a test bucket and a throwaway database.

### Dump

**Intent:** A dump holds the expected tables and exposes no credentials.

**Surfaces:**
- `OfflineBox::mysqlDump()`

#### Scenarios

- [ ] A whole-database dump contains every table with its data.
- [ ] Excluded tables appear with structure and no rows.
- [ ] With an include list, only the listed tables appear.
- [ ] Rules that resolve to no tables fail, and no full dump is produced.
- [ ] An `exclude` name that matches no table fails the dump.
- [ ] A table list pasted with Windows line endings works.
- [ ] A password containing `#`, a space or quotes logs in.
- [ ] The password never appears in the process list.
- [ ] The dump and credentials files are `0600` while they exist.
- [ ] The credentials file is gone after both success and failure.

### Upload

**Intent:** A backup counts as done only once the stored object is confirmed to match.

**Surfaces:**
- `OfflineBox::upload()`

#### Scenarios

- [ ] A small dump uploads as a private object and verifies by size and MD5.
- [ ] A dump above the multipart threshold uploads and verifies by size.
- [ ] A size or MD5 mismatch returns `STATUS` 500.
- [ ] Wrong credentials or a missing bucket return `STATUS` 500 without throwing.
- [ ] A custom `spaceEndpoint` is used, and leaving it out uses DigitalOcean Spaces.

### Command and failure handling

**Intent:** Any failure is loud, and one bad database does not stop the others.

**Surfaces:**
- `bin/cake offline_box`
- `OfflineBoxCommand`

#### Scenarios

- [ ] A full success exits 0 and objects are stored under `backups/`.
- [ ] A bad connection is reported, the remaining connections still back up, and the exit code is non-zero.
- [ ] A missing `OFFLINEBOX_S3_*` setting exits non-zero and logs an `error`.
- [ ] A failure appears in `logs/cli-error.log` and is counted by the Status Endpoint.
- [ ] On the real server, the output and `--check` name the real database, not a dev one.
- [ ] No `.sql` file remains in `TMP` after a success, a failed dump or a failed upload.
- [ ] The Windows client's example config finds the uploaded objects under `backups/`.

### Restore

**Intent:** A backup can actually be turned back into a working database.

**Surfaces:**
- `mysql < <dump>.sql`

#### Scenarios

- [ ] A dump restores onto an empty server with no database created first.
- [ ] Table and row counts match the source for included tables.
- [ ] Excluded tables exist and are empty.
- [ ] Stored procedures, triggers and views are present and work, including on a server without the original database user (`DEFINER`).
- [ ] Accented characters in `latin1` tables and emoji in `utf8mb4` tables match the source exactly.
- [ ] The restored database has the source's charset and collation.
- [ ] The app runs against the restored database.

### Admin validation page

**Intent:** An admin can see what will be exported and what is wrong, without anything being changed.

**Surfaces:**
- `/admin/en/offline-box`
- `OfflineBox::validate()`
- `bin/cake offline_box --check`

#### Scenarios

- [ ] A non-admin can't reach the page.
- [ ] Each listed database shows Will export: Yes, with the right tables and data or structure-only badges.
- [ ] A configured connection not in the list shows Will export: No.
- [ ] A connection that can't connect shows red, and the rest of the page still renders.
- [ ] An `exclude` name matching no table shows red.
- [ ] A missing `OFFLINEBOX_S3_*` setting shows red and the page still renders.
- [ ] Wrong storage credentials show red.
- [ ] The newest backup shows its date, size and age, and a missing or old backup shows red.
- [ ] No password or secret key appears in the page source.
- [ ] Loading the page creates no file in `TMP`, no object in the bucket and no dump.
- [ ] The page works at phone width.
- [ ] `--check` reports the same checks from the CLI, changes nothing, and exits non-zero on a problem.

### First live target (MySQL 5.7, UNIX socket)

**Intent:** The module works with the real server, database user and cron environment, not just Docker. Docker's `mysqldump` is the MariaDB client, so a pass there doesn't prove this.

**Surfaces:**
- The cron entry on the server
- The server's `mysqldump`

#### Scenarios

- [ ] CLI `php -v` is 8.2 or later, and the app boots from cron.
- [ ] `mysqldump --version` is a 5.7-compatible client.
- [ ] `mysqldump` connects over the UNIX socket as `localhost`.
- [ ] The database user can run every dump flag, including `--routines`, without extra privileges.
- [ ] The `OFFLINEBOX_S3_*` settings are readable from CLI PHP.
- [ ] Any MyISAM tables have been listed and accepted.

### CLI only and old code removed

**Intent:** An installed project has one version of this feature, and nothing on the web can start a backup.

**Surfaces:**
- `src/Util/` in the host project
- `Admin/SetupPagesController` in the host project
- `config/cron.php`
- `OfflineBox::__construct()`

#### Scenarios

- [ ] Calling `mysqlDump()` or `upload()` from a web request throws, and nothing is dumped or uploaded.
- [ ] `cron.php?action=run_all` does not start a backup.

- [ ] After install, `/admin/en/setup-pages/download-backup` returns 404.
- [ ] After install, nothing in the project references the removed class or action.
- [ ] Old code used elsewhere in a project is reported to the developer, not deleted.
