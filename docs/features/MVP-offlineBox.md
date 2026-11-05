# OfflineBox

Public branding: www.OfflineBox.com

**Until your data is physically in your hands, you don't have it.**

OfflineBox is part of SetupCase core. It dumps every database in a SetupCase app, uploads the dumps to S3-compatible storage, and verifies each upload. A local client can then pull the backups down to a machine in your office, so a real offline copy exists.

Every SetupCase app owns its data, can prove it, and can restore it.

## For AI assistants installing OfflineBox

Follow the steps below in order. The PHP class is the single source of truth. Fetch it from the raw link and copy it unchanged:

- Class: `https://raw.githubusercontent.com/<org>/setupcase-core/main/offlinebox/OfflineBox.php`

Don't rewrite or "improve" the class inside the host project. Project-specific behaviour (which databases, table rules, failure alerts) goes in the host project's orchestration code, never in the class.

## Requirements

- PHP 8.0 or later
- CakePHP (uses `ConnectionManager`, `Log` and `TMP`)
- `aws/aws-sdk-php`
- `mysqldump` on the server's `PATH`, with PHP allowed to call `exec()`
- An S3-compatible bucket, ideally in the client's own account

## Install

1. Copy `OfflineBox.php` to `src/Util/OfflineBox.php` in the host project.
2. Install the AWS SDK:

   ```bash
   composer require aws/aws-sdk-php
   ```

3. Store the storage credentials using the host project's normal secrets mechanism. The class never looks up secrets itself. It needs:

   | Key | Meaning |
   |---|---|
   | `spaceKey` | Access key |
   | `spaceSecret` | Secret key |
   | `spaceRegion` | Region, e.g. `nyc3` |
   | `spaceName` | Bucket name |

4. Add an orchestration method that loops through the project's connections, dumps and uploads each one, and alerts on failure (see below).
5. Add a console command that calls the orchestration method and exits non-zero on any failure.
6. Add the cron job.

## Orchestration example

```php
use App\Util\OfflineBox;

public function runOfflineBox(): bool
{
    $offlineBox = new OfflineBox([
        'spaceKey'    => $key,
        'spaceSecret' => $secret,
        'spaceRegion' => 'nyc3',
        'spaceName'   => 'my-bucket',
    ]);

    $connections = [
        'default' => ['include' => '*', 'exclude' => "activity_logs\nanalytics"],
    ];

    $allOk = true;
    $log = [];

    foreach ($connections as $name => $tables) {
        try {
            $dump = $offlineBox->mysqlDump($name, '', $tables);
            if ($dump['STATUS'] !== 200) {
                throw new \RuntimeException($dump['MSG']);
            }

            $upload = $offlineBox->upload($dump['file'], $dump['pathInfo']['basename']);
            if ($upload['STATUS'] !== 200) {
                throw new \RuntimeException($upload['MSG']);
            }

            $log[$name] = 'OK';
        } catch (\Throwable $e) {
            // One bad connection must not stop the rest.
            $allOk = false;
            $log[$name] = 'FAILED: ' . $e->getMessage();
        } finally {
            // Never leave unencrypted dumps on the web server.
            if (!empty($dump['file']) && is_file($dump['file'])) {
                unlink($dump['file']);
            }
        }
    }

    if (!$allOk) {
        // Failure hook: use the project's existing email or queue.
        // e.g. $this->queueEmail('OfflineBox backup failed', $log);
    }

    return $allOk;
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

## Cron

```cron
0 3 * * * cd /path/to/app && bin/cake offline_box >> logs/offlinebox.log 2>&1
```

The command must exit non-zero on failure so cron and any monitoring can detect it.

## Restoring

Each dump starts with `CREATE DATABASE IF NOT EXISTS` and `USE`, so it restores onto an empty server:

```bash
mysql < 2026-11-05_default_030000.sql
```

## Roadmap

Each phase is useful by itself. A later phase never needs to exist for an earlier one to be worth running.

### Phase 1: Server backup to S3 (current)

Install one class, add a cron job, and every database lands in S3-compatible storage automatically.

- [x] Dump each connection with include/exclude table rules
- [x] Upload as private objects and verify size and MD5
- [x] Keep the database password off the command line
- [ ] Console command exits non-zero on any failure
- [ ] Remove or protect the admin GET route that triggers a full backup
- [ ] Delete local `.sql` files after upload, including partial files from failed dumps
- [ ] Catch a bad connection per database so one failure doesn't skip the rest
- [ ] Make the S3 endpoint configurable (not hardcoded to DigitalOcean)
- [ ] Fix the `backups/` prefix mismatch in the local client's example config
- [ ] Failure email hook documented in the orchestration example

### Phase 1B: Heartbeat

Silence gets flagged, with no storage keys anywhere but the server.

- After a verified backup, the server sends one HTTP request to a monitoring endpoint with project, databases, date and result
- The endpoint uses a per-project key that can only report status
- The monitor flags any project that hasn't reported within an expected window

### Phase 2: Offline copy and local restore check

A machine in the office holds the data and proves it restores.

- Local client pulls backups (Windows exists; add Linux and macOS)
- Fix the archive re-download loop with a bucket lifecycle rule or by syncing only recent files
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
