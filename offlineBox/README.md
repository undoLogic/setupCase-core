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

**Status of `offlineBox/OfflineBox.php` (2026-11-05):** read, not run. The sections from `## Requirements` down describe Phase 1 when finished. The class and the module do not match them yet; `### Phase 1` lists what is left.

## Decisions to verify

Proposed and not built yet. Please confirm or change each one before programming starts.

1. **Module contents.** `offlineBox/` holds `OfflineBox.php`, this README and a new `OfflineBoxCommand.php` template. The class is copied unchanged. The command is copied and then edited for the project.
2. **Namespace.** The class currently declares `namespace offlineBox;`, but it installs to `src/Util/` and is used as `App\Util\OfflineBox`, so it would not autoload. Change it to `namespace App\Util;`, so "copy unchanged" stays true.
3. DELETE THE OLD VERSION **Remove the old code from core.** `src/Util/OfflineBox.php` is an older class (no-argument constructor, empty credentials, password on the command line). Nothing in core uses it.
    - Remove it and `src/Util/deprecated-OfflineBox.php` from `sourceFiles/` and `codeBlocks/`, so only one version exists.
    - Remove `Admin/SetupPagesController::downloadBackup()` from both as well. It is disabled by a `die()`, nothing links to it, and it is reachable by GET with its own dump code behind the `die()`.
    - Projects that already copied the old class keep working, but moving to this one is not drop-in because the constructor changed.
4. YES SERVER WILL TARGET REAL CLI PATHS ON CAKEPHP **Schedule with a console command, not `config/cron.php`.** `run_all` runs every enabled job on every call and has no interval scheduler, so a registered job would dump everything each time cron fires. `cron.php` also runs inside a web request, which a large dump can outlast. The trade-off is that no cron heartbeat is recorded, so Phase 1B fills that gap.
    - **Please confirm:** the hosting must allow a real CLI cron entry. If some hosts can only call `webroot/cron.php`, this decision needs another look.
5. GOOD - **Secrets use the ENV Vars pattern** (`get_cfg_var('X') ?: env('X') ?: ''`), like the Files and Status Endpoint blocks. Backups get their own `OFFLINEBOX_S3_*` settings instead of reusing `FILES_S3_*`, so backups can have a separate bucket and key. Unlike those blocks, a missing setting is a failure, not "off": a backup that silently never runs is the failure this module exists to prevent.
6. **Bucket layout.** One bucket per project, with objects at `backups/<filename>`. The class has no opinion; the command builds the key. This fixes the mismatch with the local client without changing the client: its example config reads `backups/`, but objects currently land at the bucket root, so it would find nothing.
7. **One upload method.** `upload()` uses `putObject`, which fails above 5 GB. `uploadLarge()` is never verified, and it does not catch `MultipartUploadException` (not an `AwsException`), so a failure would throw instead of returning `STATUS` 500. Replace both with one `upload()` using the SDK's `ObjectUploader` (as the Files block does), always verified. Multipart ETags are not MD5, so those uploads verify size only.
8. **Dump flags.** Add `--single-transaction` (consistent InnoDB snapshot without blocking the live site) and `--routines` (otherwise stored procedures are lost). Add `--no-tablespaces` (MySQL 8 otherwise needs the `PROCESS` privilege, often missing on shared hosting). To be confirmed against the live server versions, since Docker uses the MariaDB client.
9. **Excludes fail closed.** A name in `exclude` that matches no table fails the dump. Today a typo silently backs up the data it was meant to leave out. The cost is that dropping an excluded table fails the next backup until the list is updated.
10. **Out of scope for the MVP.** Dumps stay plain `.sql` (no gzip or encryption), and backups are never deleted by this module. Set a bucket lifecycle rule if storage growth matters. Encryption and thinning stay in Phase 5.

## For AI assistants installing OfflineBox

Follow the steps below in order. The PHP class is the single source of truth. Fetch it from the raw link and copy it unchanged:

- Class: `https://raw.githubusercontent.com/undoLogic/setupCase-core/main/offlineBox/OfflineBox.php`
- Command template: `https://raw.githubusercontent.com/undoLogic/setupCase-core/main/offlineBox/OfflineBoxCommand.php`

Don't rewrite or "improve" the class inside the host project. Project-specific behaviour (which databases, table rules, failure alerts) goes in the host project's command, never in the class.

Keep the copyright header, and carry the `LICENSE` and `NOTICE` files into the destination project.

## Requirements

- PHP 8.2 or later (required by `aws/aws-sdk-php` 3.402, the version in core)
- CakePHP (uses `ConnectionManager`, `Log`, `TMP` and console commands)
- `aws/aws-sdk-php`
- `mysqldump` on the server's `PATH`, with PHP allowed to call `exec()`
- A server that can run a CLI cron job
- An S3-compatible bucket, ideally in the client's own account

## Install

1. Copy `OfflineBox.php` to `src/Util/OfflineBox.php` in the host project.
2. Copy `OfflineBoxCommand.php` to `src/Command/OfflineBoxCommand.php`. Set its connections and table rules, and add the failure alert.
3. Make sure the AWS SDK is present. SetupCase core already has it in the committed `vendor/`. For any other project, install it and commit `vendor/` if that project does:

   ```bash
   composer require aws/aws-sdk-php
   ```

4. Set the storage credentials using the ENV Vars pattern. Never commit them. The class never looks up secrets itself; the command passes them in:

   | Setting | Class key | Meaning |
   |---|---|---|
   | `OFFLINEBOX_S3_KEY` | `spaceKey` | Access key |
   | `OFFLINEBOX_S3_SECRET` | `spaceSecret` | Secret key |
   | `OFFLINEBOX_S3_REGION` | `spaceRegion` | Region, e.g. `nyc3` |
   | `OFFLINEBOX_S3_BUCKET` | `spaceName` | Bucket name |
   | `OFFLINEBOX_S3_ENDPOINT` | `spaceEndpoint` | Optional. Defaults to DigitalOcean Spaces for the region. |

5. Add the cron job.
6. Run `bin/cake offline_box` once, confirm the object is in the bucket, and do one restore (see `## Restoring`).

## Command template

```php
namespace App\Command;

use App\Util\OfflineBox;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Log\Log;

class OfflineBoxCommand extends Command
{
    private const CONNECTIONS = [
        'default' => ['include' => '*', 'exclude' => "activity_logs\nanalytics"],
    ];

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $log = [];

        foreach (self::CONNECTIONS as $name => $tables) {
            $log[$name] = $this->execute_backup($name, $tables);
            $io->out($name . ': ' . $log[$name]);
        }

        if (array_diff($log, ['OK']) === []) {
            return static::CODE_SUCCESS;
        }

        Log::error('[OfflineBox] Backup failed: ' . json_encode($log));
        // Failure hook: use the project's existing email or queue.
        // e.g. $this->queueEmail('OfflineBox backup failed', $log);

        return static::CODE_ERROR;
    }

    // One bad connection must not stop the rest.
    private function execute_backup(string $name, array $tables): string
    {
        $dump = [];

        try {
            $client = $this->execute_client();
            $dump = $client->mysqlDump($name, '', $tables);
            if ($dump['STATUS'] !== 200) {
                throw new \RuntimeException($dump['MSG']);
            }

            $key = 'backups/' . $dump['pathInfo']['basename'];
            $upload = $client->upload($dump['file'], $key);
            if ($upload['STATUS'] !== 200) {
                throw new \RuntimeException($upload['MSG']);
            }

            return 'OK';
        } catch (\Throwable $e) {
            return 'FAILED: ' . $e->getMessage();
        } finally {
            // Never leave unencrypted dumps on the web server.
            if (!empty($dump['file']) && is_file($dump['file'])) {
                unlink($dump['file']);
            }
        }
    }

    private function execute_client(): OfflineBox
    {
        return new OfflineBox([
            'spaceKey' => self::execute_setting('OFFLINEBOX_S3_KEY'),
            'spaceSecret' => self::execute_setting('OFFLINEBOX_S3_SECRET'),
            'spaceRegion' => self::execute_setting('OFFLINEBOX_S3_REGION'),
            'spaceName' => self::execute_setting('OFFLINEBOX_S3_BUCKET'),
            'spaceEndpoint' => self::execute_setting('OFFLINEBOX_S3_ENDPOINT'),
        ]);
    }

    private static function execute_setting(string $name): string
    {
        return (string)(get_cfg_var($name) ?: env($name) ?: '');
    }
}
```

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

The command exits non-zero on failure so cron and any monitoring can detect it. Failures are also logged at `error`, so they land in `logs/cli-error.log`, which the Status Endpoint block counts.

Don't add this job to `config/cron.php`, and don't call the command or the class from a web route.

## Restoring

Each dump starts with `CREATE DATABASE IF NOT EXISTS` and `USE`, so it restores onto an empty server:

```bash
mysql -h <host> -u <user> -p < 2026-11-05_default_030000.sql
```

**Never restore onto a server holding the live database to "test" a backup.** The dump switches to the original database name and replaces its tables.

## Roadmap

Each phase is useful by itself. A later phase never needs to exist for an earlier one to be worth running.

### Phase 1: Server backup to S3 (current)

Install one class, add a cron job, and every listed database lands in S3-compatible storage automatically.

Already written in the class, not yet run:

- [x] Dump each connection with include/exclude table rules
- [x] Upload as private objects and verify size and MD5 (`upload()` only, see decision 7)
- [x] Keep the database password off the command line

Class changes:

- [ ] Change the namespace to `App\Util` (decision 2)
- [ ] Add the Apache-2.0 copyright header that other core files carry
- [ ] Make the S3 endpoint configurable with an optional `spaceEndpoint` (not hardcoded to DigitalOcean)
- [ ] Merge the upload methods and always verify (decision 7)
- [ ] Add the dump flags (decision 8)
- [ ] Fail on an `exclude` name that matches no table (decision 9)
- [ ] Delete the partial `.sql` inside `mysqlDump()` when a dump fails. A failed dump returns no `file`, so the caller can't clean it up.
- [ ] Quote the password in the credentials file. Unquoted, a `#` or a space in the password breaks the login.
- [ ] Create the credentials file and the dump as `0600` from the start. The credentials file is currently open until `chmod` runs, and the dump keeps default permissions.
- [ ] Always remove the credentials file, even if a step before `exec()` throws
- [ ] Include the `mysqldump` error text in `MSG`. Today it only says the dump failed.

Module and core changes:

- [ ] Add `OfflineBoxCommand.php`: exits non-zero on any failure, catches a bad connection per database so one failure doesn't skip the rest, deletes the local `.sql` after upload, uploads under `backups/` (decision 6), and includes the failure email hook
- [ ] Remove the old class, the deprecated file and `downloadBackup()` from `sourceFiles/` and `codeBlocks/` (decision 3)

### Phase 1B: Heartbeat

Silence gets flagged, with no storage keys anywhere but the server.

- After a verified backup, the server sends one HTTP request to a monitoring endpoint with project, databases, date and result
- The endpoint uses a per-project key that can only report status
- The monitor flags any project that hasn't reported within an expected window

### Phase 2: Offline copy and local restore check

A machine in the office holds the data and proves it restores.

- Local client pulls backups (Windows exists in the separate `undoLogic/offlineBox` repo; add Linux and macOS)
- Fix the archive re-download loop with a bucket lifecycle rule or by syncing only recent files. The client moves old files out of `Current`, so the next `aws s3 sync` downloads them again.
- Automated restore check: load the latest dump into a throwaway MySQL container, compare table and row counts, confirm the newest record is recent, log pass or fail
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
- [ ] Stored procedures and triggers are present.
- [ ] The app runs against the restored database.

### Old code removed

**Intent:** Core has one version of this feature, with no web route to it.

**Surfaces:**
- `sourceFiles/src/Util/`
- `codeBlocks/cakePHP/4.x/src/Util/`
- `Admin/SetupPagesController`

#### Scenarios

- [ ] `/admin/en/setup-pages/download-backup` returns 404.
- [ ] Nothing in `sourceFiles/` or `codeBlocks/` references the removed class or action.
- [ ] A fresh build with CodeBlocks still runs.
