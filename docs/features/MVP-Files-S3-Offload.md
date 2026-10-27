# FEATURE: Files (Local-First S3 Offload)

SetupCase by undoLogic, core feature. A vanilla file storage system: upload a file, it is saved locally and works instantly, then a command pushes it to an S3-compatible bucket (AWS S3 or DigitalOcean Spaces). No assets, no parent entities. One `files` row per uploaded file.

Target: CakePHP 4.6, PHP 8.3, AWS SDK for PHP (committed in `vendor/`).

> **Scope:** this is the MVP. Files always stream through the app. Presigned links, CDN and shared/polymorphic file tables are later optimizations (see bottom). In a client project, this table becomes `{table}_files` (for example `asset_files`), one files table per owning entity, never shared.

---

## 1. Core rules

1. **The database is the source of truth.** Never call `listObjects` (or scan the bucket) to find out what files exist.
2. **One way in.** Uploads always land on local disk first and write a row with `status = local`.
3. **One mover.** A CLI command pushes local files to S3 and updates the row. Nothing else talks to S3 on write.
4. **One way out.** Views only output links to the app's own endpoints. The browser fetches files after the page loads. Page renders never touch S3.
5. **S3 fails fast.** The client has a connect timeout and low retries. Transfer time is not capped.
6. **Never delete a local file until the S3 copy is confirmed** (object exists and size matches).

---

## 2. Configuration

`config/app_local.php` (never committed with real values):

```php
'Files' => [
    'localPath' => ROOT . DS . 'storage' . DS . 'files', // persistent, outside webroot, survives deploys
    'deleteLocalAfterPush' => true,   // false = keep local copy as well
    'maxUploadBytes' => 200 * 1024 * 1024,
    'S3' => [
        'enabled' => true,             // false = everything stays local (pure local testing)
        'bucket' => 'setupcase-test',
        'region' => 'us-east-1',       // Spaces: e.g. 'tor1'
        'endpoint' => null,            // Spaces: 'https://tor1.digitaloceanspaces.com'
        'key' => '...',
        'secret' => '...',
        'prefix' => 'files/',          // key prefix inside the bucket
    ],
],
```

S3 client (single factory, e.g. `src/Util/FileStorage.php`):

```php
new S3Client([
    'version' => 'latest',
    'region' => $cfg['region'],
    'endpoint' => $cfg['endpoint'],
    'use_path_style_endpoint' => false,
    'credentials' => ['key' => $cfg['key'], 'secret' => $cfg['secret']],
    'retries' => 1,
    'http' => ['connect_timeout' => 3], // connect only; no total timeout
]);
```

`storage/files/` must be in `.gitignore` and must not be inside `webroot/`.

---

## 3. Data model

Table `files`, one row per uploaded file.

| Column | Type | Notes |
|---|---|---|
| `id` | int, PK | |
| `uuid` | char(36), unique | Used in URLs and storage keys. Never expose numeric IDs. |
| `filename` | varchar(255) | Original filename, shown to users and used on download |
| `mime_type` | varchar(100) | Detected server-side, not trusted from the browser |
| `size_bytes` | bigint | Used to confirm the upload |
| `checksum` | char(64) null | SHA-256 of the file, optional integrity check |
| `local_path` | varchar(500) null | Relative to `Files.localPath`; null once deleted locally |
| `storage_key` | varchar(500) null | Key in the bucket; set when pushed |
| `status` | varchar(20) | `local`, `uploading`, `remote`, `failed` |
| `attempts` | int, default 0 | Push attempts |
| `last_error` | text null | Last push failure |
| `uploaded_at` | datetime null | When S3 upload was confirmed |
| `user_id` | int null | Who uploaded (for permission checks) |
| `created` / `modified` | datetime | CakePHP timestamps |

Indexes: `uuid` (unique), `status`.

Status flow:

```
local ──push──> uploading ──confirmed──> remote
                    │
                    └──error──> failed ──(retry, attempts < 5)──> uploading
```

Naming note: the entity class is `App\Model\Entity\File`. Always import it by full namespace to avoid confusion with PHP/Cake filesystem classes.

---

## 4. Upload flow

`FilesController::add()` (POST):

1. Validate: upload error code, size ≤ `maxUploadBytes`, allowed MIME types (whitelist).
2. Generate `uuid`. Local path: `{uuid[0..1]}/{uuid}/{sanitized_filename}`.
3. Move the uploaded file into `Files.localPath`.
4. Detect MIME with `finfo`, compute size and SHA-256.
5. Save the row with `status = local`.
6. Redirect to the file's view page. It works immediately from local disk.

No S3 call happens during upload.

---

## 5. Push command (the mover)

`bin/cake files push [--limit=50] [--id=<uuid>]`

Run by cron (e.g. every 5 minutes). For each row with `status = local`, or `failed` with `attempts < 5`:

1. Set `status = uploading`, increment `attempts`.
2. `storage_key = {prefix}{uuid}/{sanitized_filename}`.
3. `putObject` with `ACL = private`, `ContentType = mime_type`, streaming the file from disk (`SourceFile`), or `MultipartUploader` for files over ~100 MB.
4. **Confirm:** `headObject` and check `ContentLength === size_bytes`.
5. On success: `status = remote`, `uploaded_at = now`, clear `last_error`. If `deleteLocalAfterPush`, delete the local file and set `local_path = null`.
6. On any failure: `status = failed`, save `last_error`. **Never delete the local file.**

Locking: use a lock file (or `flock`) so two cron runs never push the same rows. On start, reset rows stuck in `uploading` for more than 1 hour back to `failed`.

If `S3.enabled = false`, the command logs "S3 disabled" and exits.

---

## 6. Serving

Routes (UUID only):

- `GET /files/{uuid}/view` — `Content-Disposition: inline`
- `GET /files/{uuid}/download` — `Content-Disposition: attachment; filename="{filename}"`

Both go through one private method, `FilesController::serve($uuid, $disposition)`:

1. Find the row by `uuid`, 404 if missing.
2. **Permission check** (core default: logged-in user owns the file or is admin). Projects override this.
3. If `local_path` is set and the file exists: stream from disk.
4. Else if `status = remote`: `getObject` and stream the body to the browser.
5. Else: 404 and log it (row says remote but object is missing, or vice versa).
6. Headers: `Content-Type`, `Content-Length`, `X-Content-Type-Options: nosniff`.
7. Support `Range` / `206 Partial Content` for video and PDF.

Views must only ever output these URLs, for example:

```php
<?= $this->Html->image(['controller' => 'Files', 'action' => 'view', $file->uuid]) ?>
<?= $this->Html->link('Download', ['controller' => 'Files', 'action' => 'download', $file->uuid]) ?>
```

Never put a bucket key or S3 URL in a page.

---

## 7. Reconciliation

`bin/cake files reconcile`

Compares the `files` table against local disk and the bucket. **Reports only, never deletes.**

Reports:

- Rows `remote` with no object in S3 (or size mismatch).
- Objects in S3 under `prefix` with no row (orphans).
- Rows with `local_path` set but no file on disk.
- Rows stuck in `uploading`, or `failed` with `attempts >= 5`.

This is the only place allowed to list the bucket. Run it after setup, then on a schedule (e.g. nightly), and email or log the result.

---

## 8. Disaster recovery

- **Server lost:** restore the database backup, redeploy, and the app serves everything from S3. Files with `status = local` at the time of loss are gone, so keep the push interval short.
- **Recommended:** schedule `rclone copy` of the bucket to a second location (local machine, NAS, or another provider). Use `copy`, not `sync`, so deletions in the bucket don't remove the backup. This makes sure S3 is not your single source of truth.

---

## 9. Test plan (test bucket)

Set up a dedicated test bucket with its own access key, limited to that bucket.

| # | Test | Expected |
|---|---|---|
| 1 | Upload an image with `S3.enabled = false` | Row `local`, file on disk, `/view` shows it |
| 2 | Enable S3, run `bin/cake files push` | Row `remote`, object in bucket, size matches, local file deleted |
| 3 | Open `/view` and `/download` after push | Served from S3; download keeps original filename |
| 4 | Set a wrong secret, upload, push | Row `failed`, `last_error` set, local file still there, page still works |
| 5 | Fix secret, push again | Row goes to `remote` |
| 6 | Upload a ~200 MB video, push, then seek in the player | Multipart upload succeeds; seeking works (206) |
| 7 | Point endpoint at a dead host, load a page listing files | Page loads instantly (no S3 on render); `/view` fails within ~3s |
| 8 | Delete an object manually in the bucket, run reconcile | Reported as missing; nothing deleted |
| 9 | Upload a file directly to the bucket, run reconcile | Reported as orphan |
| 10 | Request another user's file `/download` | 403 |
| 11 | Run two `push` commands at once | No row pushed twice |

---

## 10. Later optimizations (not in MVP)

- **Presigned links for large files:** after the permission check, 302-redirect videos, big PDFs and zips to a short-lived presigned URL (1–5 minutes) instead of streaming through PHP. Trade-off: the bucket key is visible and the link works for anyone until it expires.
- **Close the session early** in `serve()` (`session_write_close()` after the permission check) so long downloads don't block the user's other tabs.
- **Thumbnails / derived files:** add a `kind` column (`original`, `thumbnail`). Push thumbnails too, but keep the local copy and serve it first; if missing locally, fetch from S3 and save it back.
- **CDN** in front of the bucket for public, high-traffic files.
- **Shared files table** across entities: only once everything runs smoothly. Default is always one `{table}_files` per owner.
