# SetupCase Core Block: Files (Local-First S3 Offload)

## Purpose

A plain file storage system for SetupCase core. A user uploads a file, it is saved locally and works right away, then a cron job pushes it to an S3-compatible bucket (AWS S3 or DigitalOcean Spaces). There are no assets and no parent entities: one `files` row per uploaded file.

- Uploads never wait on S3, and page renders never touch it.
- The database decides what files exist, not the bucket.
- A local file is only deleted after its S3 copy is confirmed.

> **Scope:** this is the MVP. Files always stream through the app. Presigned links, a CDN and shared files tables are later optimizations (see `## Out of scope (later)`). In a client project this table becomes `{table}_files` (for example `asset_files`): one files table per owning entity, never shared.

## MVP Status

This is MVP work (AGENTS.md "MVP Work"):

- No automated tests are created or scaffolded.
- The `## Testing` section lists the expected behaviour, to be checked by hand against a test bucket. Nothing is marked covered until it has been run.
- Only lightweight checks: PHP lint, the pages render, and the cron job runs.

## Context (checked in the code, 2026-10-27)

- **Framework:** CakePHP 4.6.5 on **PHP 8.3**. The Docker image now builds from `php:8.3-apache-bookworm` (`dockerLinux/web81/Dockerfile`), replacing `undologic/setupcase-base` (PHP 8.1) for now. Avoid APIs that CakePHP 5 removes.
- **AWS SDK:** `vendor/` is committed (production never runs Composer). Before this block there was no `vendor/aws`; `src/Util/OfflineBox.php` used `Aws\S3\S3Client` but only worked where someone had run Composer by hand. The SDK is now installed (decision 1). **It requires PHP 8.2 or newer:** Composer's `vendor/composer/platform_check.php` now stops the whole app on PHP 8.1.
- **Cron runs through the Unified Cron Framework**, not `bin/cake`. Hosting calls `webroot/cron.php`, which runs the jobs in `config/cron.php`. Each job is a **public Table method called with no arguments** that returns `STATUS` / `MSG` (`CronService::run_callJob()`). `run_all` runs every enabled job on every call, and there is no interval scheduler. Execution is limited to `security.allowed_ips`.
- **Roles:** prefix RBAC in `config/app.php`. `ADMIN` reaches Admin/Staff/Manager, `MANAGER` reaches Staff/Manager, and `STAFF` reaches Staff. Prefix URLs look like `/staff/en/files/view/<id>`, and other pages already use numeric ids in URLs.
- **Users:** core `users` has no `group_id` (`id, user_type, email, password, ...`). The only scope available in core is `user_id`.
- **Secrets:** use the ENV Vars pattern (`get_cfg_var('X') ?: env('X') ?: default`), as `STATUS_TOKEN` and `DOCUMENTATION_REGENERATE_CODE` do. `config/app_local.php` is committed in this repo, so it must never hold real keys.
- **Existing uploads:** `EmailQueueAttachmentsTable::saveUpload()` moves uploads into `TMP . 'uploads/...'`, trusts the browser MIME type and stores the original filename unsanitized. Do not copy that pattern here.
- **PHP limits (Docker `php.ini`):** `upload_max_filesize = 200M`, `post_max_size = 200M`, `memory_limit = 1024M`. Sessions use the `php` (file) handler, so a long request holds the session lock.
- **Responses:** CakePHP's `Response::withFile()` already handles `Range` / `206` for local files.
- **Logging:** `error` / `warning` lines land in `logs/error.log`, which the Status Endpoint block counts. Push failures logged at `error` therefore show up on the status monitor.

## Decisions

1. **Add the AWS SDK to the committed `vendor/`.** This is the first new Composer dependency for a core block. Run `composer require aws/aws-sdk-php` in Docker, then trim it to S3 only with the SDK's `Aws\Script\Composer\Composer::removeUnusedServices` hook (`"extra": {"aws/aws-sdk-php": ["S3"]}`). Without the trim the SDK adds tens of MB to the repo. It also brings `guzzlehttp/*` and `mtdowling/jmespath.php`, so commit those too.
    - **Built 2026-10-27:** `aws/aws-sdk-php` 3.402 plus `aws/aws-crt-php`, `guzzlehttp/guzzle`, `guzzlehttp/promises`, `guzzlehttp/psr7`, `mtdowling/jmespath.php` and `ralouphie/getallheaders`. Composer installed 7 packages and updated none. The trim removed 428 services, leaving about 6.5 MB.
    - Every server running this app must be on **PHP 8.2 or newer** (see `## Context`).
2. **The push is a cron job, not a `bin/cake files push` command.** It is `FilesTable::pushToS3()`, registered in `config/cron.php` as `files_push`. That matches every other scheduled job and gets heartbeat monitoring for free. The `--id` / `--limit` options go away: the limit is a constant, and one file can be retried by resetting its row.
3. **Each push run has a time budget.** It runs inside a web request (`cron.php`) on every `run_all`. It pushes at most `PUSH_LIMIT = 10` rows and starts no new file after `PUSH_SECONDS = 50`. A file that is already uploading finishes.
4. **No reconciliation in the MVP (decided 2026-10-27).** Every push either confirms the S3 copy (size check) before deleting the local file, or fails, keeps the file on the server and logs an `error`. Nothing is left broken by the block itself.
    - Drift from outside causes is rare: objects deleted in the bucket, or a disk losing files. `serve()` still logs a `warning` when a row and its storage disagree, so it surfaces on first access.
    - A daily compare is described as a later stability option. See `### Daily compare (reconciliation)` under `## Out of scope (later)`.
5. **Config: secrets use the ENV Vars pattern, and behaviour lives in Table constants** (like `StatusController`). The block works with no edits to `app.php` or `app_local.php`. S3 is **off when `FILES_S3_KEY` is not set**, the same opt-in as `STATUS_TOKEN`. That replaces `S3.enabled`.
6. **Access is owner only in the MVP.** Files live under the **Staff** prefix (every role can reach it), and each query filters on `user_id`. `user_id` is `NOT NULL` because uploading requires login. The original "owner or admin" rule would mean a role check inside the action, which AGENTS forbids. If admins need every file later, add `Admin/FilesController` calling the same Table method without the user filter, with a comment saying why it is unscoped.
7. **A missing file returns 404, not 403.** Another user's file, an unknown id and an inconsistent row all give the same 404. A logged-in user cannot tell whether someone else's file exists.
8. **Close the session before streaming.** Sessions are file based, so a 200 MB download would block the user's other tabs. Call `$this->request->getSession()->close()` after the ownership check. This is one line, so it moves from "later" into the MVP.
9. **Use the SDK's `ObjectUploader`** instead of choosing between `putObject` and `MultipartUploader` ourselves. It switches to multipart above its threshold (16 MB by default) and streams from disk either way.
10. **Loading placeholder for images (requested 2026-10-27).** Images served from S3 can take a moment, so every file image first shows a local animated GIF. See `## Image loading placeholder`.
    - The GIF is a static file in `webroot/img/`, served by Apache. It never goes through PHP or S3, so it appears instantly and rule 4 still holds.
    - The real image is swapped in by JS rather than shown through a CSS background, because a CSS background would show through transparent PNGs and gives no way to handle a failed load.
11. **Thumbnails (requested 2026-10-27, moved from "later" into the MVP).** Every file gets a thumbnail for lists. See `## Thumbnails`.
    - Images (JPEG, PNG, GIF, WebP) get a **generated fixed-size thumbnail**, made at upload time.
    - Other files get a **static icon picked by extension**, e.g. `thumb-csv.jpg` or `thumb-xlsx.jpg`, with a generic `thumb-file.jpg` fallback.
    - **Size:** 300 x 300, centre-cropped ("cover") and saved as JPEG, so every tile in a grid is the same shape. Change `THUMB_SIZE` to resize; switching to letterboxed means changing the crop in `saveUpload_thumb()`.
    - Thumbnails live on the same row (`thumb_path`, `thumb_key`), not as separate rows with a `kind` column. That is the simplest form for the MVP.
12. **`FILES_LOCAL_PATH` is required, with no fallback (decided 2026-10-27).** Most installs run on undoLogic hosting, which has a private path outside the website files. A deploy can destroy and replace the website files while the private path stays. Files and thumbnails always go there.
    - A default inside `sourceFiles/` would quietly store files where the next deploy deletes them, so there is none. A missing setting fails loudly: uploads are refused with a clear message, and the push job returns `500` and logs an `error`, which shows on the Status Endpoint.
    - The same check runs if the path does not exist or is not writable by the web user.
    - Customer-hosted installs set it to any persistent folder outside the deployed tree.
13. **Folders are named by `files.id` (decided 2026-10-27).** Each file gets its own folder, `{id}/`, holding the original and its thumbnail, and the bucket uses the same layout under the prefix. Opening the private path or the bucket and finding a row's files is then easy.
    - The same `id` is used in URLs, so one identifier covers the URL, the folder and the bucket key (decision 14).
    - The row is inserted first, to get the `id`, inside a transaction (see `## Upload flow`).
    - The thumbnail is named `_thumb.jpg`, and sanitized filenames never start with `_`, so an upload called `thumb.jpg` cannot overwrite it.
    - Everything sits flat in one folder. That is fine into the hundreds of thousands of files. If a project ever gets that big, shard as `{floor(id / 1000)}/{id}/` (out of scope).
14. **No uuid in the MVP; numeric ids in URLs (decided 2026-10-27).** The owner filter and the 404 rule (decisions 6 and 7) protect the files, as on every other page in the project. A uuid would only be a second layer. It is a later step, to take once the block runs on a client project. See `### Unguessable file links (uuid)` under `## Out of scope (later)`.
15. **A "push-s3" link per file (requested 2026-10-27).** Each file in the list has a **push-s3** link that pushes that one file now, so you don't have to wait for cron. This is a deliberate, narrow exception to AGENTS "cron/console logic must never be reachable from a web route". The reasons behind that rule still hold:
    - The cron job `pushToS3()` stays unscoped and is not reachable from the web. The link calls a separate `FilesTable::pushOne($id, $userId)`, which filters on id **and** `user_id` and touches one row. The two share only the private `pushFile()` (upload one file and confirm it).
    - POST only (`Form->postLink()`), owner only, and shown only while S3 is on and the file is `local` or `failed`. The Table sets `can_push`, and the controller turns it into `push_url`.
    - It takes the same lock as cron, so a file is never pushed twice at once. If cron holds the lock, the link answers "A push is already running".
    - A manual push also retries a file that cron gave up on (`attempts >= MAX_ATTEMPTS`).
    - It runs inside the web request with `set_time_limit(0)`, so a big file keeps the browser waiting until it is done.
16. **Delete per file (requested 2026-10-27).** Each file in the list has a **Delete** link (POST, with a confirm). It calls `FilesTable::deleteForUser($id, $userId)`, which follows AGENTS "Destructive Operations":
    - **Scope is required and never inferred:** no id or `user_id` throws. The controller turns a bad id in the URL into a 404 first. The row is found and deleted by id **and** `user_id` in the same query, so another user's id deletes nothing ("File not found").
    - **Order: S3 copies, then local files, then the row.** S3 deletes are idempotent, and a 404 is also treated as "already gone", so an object removed by hand in the bucket is fine. If S3 cannot be reached, nothing is deleted and the row stays. A bucket copy is therefore never left behind with no row pointing at it, and deleting again later finishes the job.
    - **Preconditions on the target:** refused while the file is `uploading` (no link is shown then), refused when the row has an S3 copy but S3 is off, and refused when it has local files but `FILES_LOCAL_PATH` is broken. Refuse rather than half-delete.
    - Takes the push lock, so it never races the cron job or the push-s3 link.
    - The `{id}/` folder is removed once empty. Each delete logs a `notice` (`[Files] user <id> deleted file <id> (<filename>)`) to `debug.log`.
17. **The page is one template file (decided 2026-10-28).** `templates/Staff/Files/index.php` holds all the HTML: the upload form, setup checks, file grid and guide. This deliberately breaks the AGENTS 45-line base-template rule and the element convention, as the Status Endpoint block does.
    - **Why:** it is a copy-and-paste code block. One file is simpler to copy, and once the block is in a project, that project's AGENTS.md splits it into elements.
    - The guide is wrapped in "CodeBlocks guide ... Remove this section on a project" markers, because it only belongs on this page.
    - **Pre-commit hook:** the template is about 200 lines. Where `git-hooks/pre-commit` is installed, commit it with `PRECOMMIT_TEMPLATE_MAX_LINES=250` or `--no-verify`.

## Configuration

ENV Vars pattern, each read as `get_cfg_var('X') ?: env('X') ?: default`. Server `php.ini` comes first, then Docker env. Never commit the values.

| Name | Default | Notes |
|---|---|---|
| `FILES_LOCAL_PATH` | **none, required** | Absolute path to the hosting's private folder (e.g. `<private>/files`), which survives when the website files are destroyed and redeployed. If it is not set, uploads are refused and the push returns `500` (decision 12). |
| `FILES_S3_KEY` | empty = S3 off | Access key, scoped to this bucket only |
| `FILES_S3_SECRET` | | |
| `FILES_S3_BUCKET` | | e.g. `setupcase-test` |
| `FILES_S3_REGION` | `us-east-1` | Spaces: e.g. `tor1` |
| `FILES_S3_ENDPOINT` | empty (AWS) | Spaces: `https://tor1.digitaloceanspaces.com` |
| `FILES_S3_PREFIX` | `files/` | Key prefix inside the bucket |

Constants in `FilesTable`:

| Constant | Value |
|---|---|
| `MAX_UPLOAD_BYTES` | `200 * 1024 * 1024` (keep `php.ini` `upload_max_filesize` / `post_max_size` at least this high) |
| `ALLOWED_MIME` | whitelist, e.g. `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `application/pdf`, `video/mp4`, `application/zip`, `text/csv`, `text/plain` (finfo often reports CSV as this), the `xlsx` / `xls` / `docx` types. Check what `finfo` really reports for each sample file while building, because Office files sometimes come back as `application/zip` or `application/octet-stream`. |
| `THUMB_MIME` | `image/jpeg`, `image/png`, `image/gif`, `image/webp`: the types that get a generated thumbnail |
| `THUMB_SIZE` | `300` (square, pixels) |
| `THUMB_MAX_PIXELS` | `40000000` (40 MP): larger images get the generic image icon instead, so decoding cannot exhaust memory |
| `THUMB_ICONS` | extension => icon, e.g. `csv => thumb-csv.jpg`, `xlsx` / `xls => thumb-xlsx.jpg`, `docx` / `doc => thumb-docx.jpg`, `pdf => thumb-pdf.jpg`, `zip => thumb-zip.jpg`, `mp4 => thumb-video.jpg`. Fallbacks: `thumb-image.jpg` (an image whose thumbnail could not be made) and `thumb-file.jpg` (anything else). |
| `DELETE_LOCAL_AFTER_PUSH` | `true` (`false` keeps a local copy too) |
| `MAX_ATTEMPTS` | `5` |
| `PUSH_LIMIT` / `PUSH_SECONDS` | `10` / `50` |
| `STUCK_MINUTES` | `60` |

S3 client: one private helper in `FilesTable` builds it and reuses it for the push and serve.

```php
new S3Client([
    'version' => 'latest',
    'region' => $region,
    'endpoint' => $endpoint ?: null,
    'use_path_style_endpoint' => false,
    'credentials' => ['key' => $key, 'secret' => $secret],
    'retries' => 1,
    'http' => ['connect_timeout' => 3], // connect only; no total timeout
]);
```

**Docker:** `dockerLinux/ini/php.ini` sets `FILES_LOCAL_PATH = "/var/www/vhosts/website.com/www/dockerLinux/private/files"`. `dockerLinux/private/` stands in for the hosting private path and is in `.gitignore`. Docker already mounts the repo, so no new volume and no container recreate are needed; a `php.ini` change only needs `apachectl graceful`.
- Edit `dockerLinux/ini/php.ini` in place. It is mounted as a single file, so a tool that replaces the file (e.g. `sed -i`) detaches the container's copy until the container restarts.

## Core rules

1. **The database is the source of truth.** Never call `listObjects` (or scan the bucket) to find out what files exist.
2. **One way in.** Uploads always land on local disk first and write a row with `status = local`.
3. **One mover.** `FilesTable::pushFile()` is the only code that writes to S3. It is called by the cron job `pushToS3()` and by the scoped "push-s3" link (`pushOne()`, decision 15).
4. **One way out.** Views only output links to the app's own Files actions. The browser fetches files after the page loads, and page renders never touch S3.
5. **S3 fails fast.** The client has a 3 s connect timeout and 1 retry. Transfer time is not capped.
6. **Never delete a local file until the S3 copy is confirmed** (the object exists and its size matches).

## Data model

Table `files`, one row per uploaded file. Put the SQL in `sourceFiles/config/schema/2026-10-27.sql` (append to it if it already exists).

| Column | Type | Notes |
|---|---|---|
| `id` | int, PK | Used in URLs, as the folder name on disk and in the bucket key (decisions 13 and 14) |
| `user_id` | int, NOT NULL | Uploader; every lookup filters on it (decision 6) |
| `filename` | varchar(255) | Original filename, shown to users and used on download |
| `mime_type` | varchar(127) | Detected server-side with `finfo`, never trusted from the browser |
| `size_bytes` | bigint | Used to confirm the push |
| `checksum` | char(64) null | SHA-256, computed on upload |
| `local_path` | varchar(500) null | Relative to `FILES_LOCAL_PATH`; null once deleted locally |
| `storage_key` | varchar(500) null | Key in the bucket; set when pushed |
| `thumb_path` | varchar(500) null | Generated thumbnail, relative to `FILES_LOCAL_PATH`. Never deleted locally. Null for non-images and for failed thumbnails. |
| `thumb_key` | varchar(500) null | Thumbnail key in the bucket; set when pushed |
| `status` | varchar(20) | `local`, `uploading`, `remote`, `failed` |
| `attempts` | int, default 0 | Push attempts |
| `last_error` | text null | Last push failure |
| `uploaded_at` | datetime null | When the S3 copy was confirmed |
| `created` / `modified` | datetime | CakePHP timestamps |

Indexes: `status`, `user_id`.

Status flow:

```
local ──push──> uploading ──confirmed──> remote
                    │
                    └──error──> failed ──(next run, attempts < 5)──> uploading
```

Naming note: the entity class is `App\Model\Entity\File`. Always import it by its full namespace so it is not confused with `Cake\Filesystem\File` or PHP filesystem functions.

## Upload flow

`Staff/FilesController::index()` (GET) lists the user's files and shows the upload form. `Staff/FilesController::add()` accepts **POST only** and calls `FilesTable::saveUpload($userId, $upload)`:

1. Validate the upload error code and that size ≤ `MAX_UPLOAD_BYTES`.
2. Detect the MIME type with `finfo` on the temp file. Reject it if it is not in `ALLOWED_MIME`.
3. Sanitize the filename (basename only, safe characters, max length, no leading `_` or `.`).
4. In one database transaction (`$this->getConnection()->transactional()`):
    1. Insert the row with `status = local` to get its `id`.
    2. Move the upload to `{id}/{sanitized_filename}` under `FILES_LOCAL_PATH` (`$upload->moveTo()`), then compute the size and SHA-256.
    3. If the MIME type is in `THUMB_MIME`, generate `{id}/_thumb.jpg` and set `thumb_path`. If that fails, log a `warning`, leave `thumb_path` null and continue; **a failed thumbnail never fails the upload.**
    4. Update the row with `local_path`, `size_bytes`, `checksum` and `thumb_path`.
    5. If any step fails, roll back and delete the `{id}/` folder. Because the row is not committed until the file is in place, the push job never sees a row without a file.
5. Return `STATUS` / `MSG` / `id`. The controller flashes the message and redirects to `index`. The file and its thumbnail work from local disk right away.

No S3 call happens during upload.

## Push job (the mover)

`FilesTable::pushToS3()` is registered in `config/cron.php`:

```php
'files_push' => [
    'enabled' => true,
    'model' => 'Files',
    'action' => 'pushToS3',
    'description' => 'Push local files to S3.',
    'schedule' => 'Every time cron.php?action=run_all is called.',
    'max_age' => 600,
    'monitor' => true,
],
```

Each run:

1. If `FILES_S3_KEY` is not set, return `200 S3 disabled` and stop.
2. Take an `flock` on `sys_get_temp_dir() . '/files_push_' . md5(ROOT) . '.lock'` (outside the project, so there is nothing to commit by accident; keyed per site). If it is already held, return `200 Already running`.
3. Reset rows stuck in `uploading` for more than `STUCK_MINUTES` to `failed`.
4. Select up to `PUSH_LIMIT` rows with `status = local`, or `status = failed` and `attempts < MAX_ATTEMPTS`, oldest first. For each, until `PUSH_SECONDS` have passed:
    1. Set `status = uploading` and increment `attempts`.
    2. Set `storage_key = {prefix}{id}/{sanitized_filename}` (the bucket mirrors the local layout).
    3. Upload with `ObjectUploader`, streaming from disk, with `ACL = private` and `ContentType = mime_type`.
    4. **Confirm:** `headObject`, then check `(int)ContentLength === size_bytes`.
    5. If `thumb_path` is set, push the thumbnail to `{prefix}{id}/_thumb.jpg`, confirm it the same way, and set `thumb_key`. A thumbnail failure fails the row, so the next run retries both.
    6. On success: set `status = remote` and `uploaded_at = now`, and clear `last_error`. If `DELETE_LOCAL_AFTER_PUSH` is on, delete the local original and set `local_path = null`. **The local thumbnail is always kept**, because lists must stay fast; the S3 copy is the backup for disaster recovery.
    7. On any failure: set `status = failed`, save `last_error` and log `error: [FilesPush] <id> <message>`. On the last allowed attempt, add `giving up` to the log line, so a file that will stay on the server is reported once. **Never delete the local file.**
5. Return `200` with the pushed and failed counts. Any failure in the run returns `500`, so the cron heartbeat is not refreshed.

## Serving

All three actions are GET, under the Staff prefix, and take the id only:

- `/staff/en/files/view/<id>`: `Content-Disposition: inline`
- `/staff/en/files/download/<id>`: `Content-Disposition: attachment; filename="<filename>"`
- `/staff/en/files/thumb/<id>`: the generated thumbnail, `Content-Disposition: inline`. It uses `thumb_path` / `thumb_key` instead of `local_path` / `storage_key`, and sends `Cache-Control: private, max-age=86400`, because a thumbnail never changes for an id. Non-image files never call this action (their icon is a static file).

All three call one private helper, `serve($id, $which, $download)`. The data access lives in the Table:

1. `FilesTable::serveInfo($id, $userId, $which)` filters on id **and** `user_id` in one query, and returns where to serve from (a local path or a bucket key) plus the MIME type and a header-safe filename. Anything else gives 404 (decision 7).
2. Close the session (decision 8).
3. If `local_path` is set and the file exists, use `withFile()`, which handles Range / 206.
4. Otherwise, if `status = remote`, `FilesTable::openRemote($key, $range)` calls `getObject` (`'@http' => ['stream' => true]`), passing on the `Range` header if one was sent. The controller streams the body in 1 MB chunks through a `CallbackStream` (never loading the whole object into memory) and returns 206 with `Content-Range` when a range was requested.
5. Otherwise return 404 and log a `warning` (the row and storage disagree).
6. Headers: `Content-Type` (from the row), `Content-Length`, `X-Content-Type-Options: nosniff`, `Cache-Control: private`.

Views only ever output these URLs, or a static icon path. Thumbnails and images go through the loading placeholder. `thumb_src`, `view_url` and `download_url` arrive ready-made (see `## Thumbnails`), so the template does not choose between a generated thumbnail and an icon:

```php
<?= $this->Html->image('files-s3-offload-loading.gif', [
    'class' => 'files-s3-offload-img',
    'data-src' => $file->thumb_src,
    'alt' => $file->filename,
]) ?>
<a href="<?= h($file->download_url); ?>">Download</a>
```

Never put a bucket key or an S3 URL in a page.

## Image loading placeholder

Every thumbnail (and any full image shown on a page) is rendered with the local GIF as its `src` and the real URL in `data-src`. `files-s3-offload.js` then swaps in the real image.

- **Placeholder:** `webroot/img/files-s3-offload-loading.gif`, a small animated GIF (a 48 x 48 spinner, 4 KB, generated with ImageMagick; replace it with your own if you like). Apache serves it as a static file, with no PHP and no S3.
- **On page load,** the JS finds every `img.files-s3-offload-img[data-src]` and starts loading `data-src` in a separate `Image()`.
- **Loaded:** it sets the real URL as the `img`'s `src` and adds the `files-s3-offload-loaded` class.
- **Failed** (404, S3 down, timeout): the GIF is replaced with a local "not available" state (the `files-s3-offload-failed` class with a short text or icon), so the spinner does not run forever.
- **Size:** `files-s3-offload.css` gives `.files-s3-offload-img` a square box (`aspect-ratio: 1 / 1`), so the GIF has room and the layout does not jump when the real image arrives. The GIF is centred in that box.
- **No JS:** the GIF stays visible and the alt text names the file. That is acceptable for the MVP, because the app needs JS anyway.
- **Same markup for every file:** generated thumbnails, static icons, local files and S3 files all use it. A static icon simply swaps in at once. The template does not branch on `status` or file type, which keeps it a Passive View.
- **Links stay plain:** download links and the PDF / video `view` links need no placeholder.
- **Upload size check:** the same JS refuses a file over `MAX_UPLOAD_BYTES` before sending it. A request over PHP's `post_max_size` would arrive empty and fail the CSRF check with a 403 instead of a clear message.

## Thumbnails

Lists show a thumbnail for every file. Where it comes from depends on the file:

| File | Thumbnail | Served by |
|---|---|---|
| JPEG, PNG, GIF, WebP (`THUMB_MIME`) | Generated at upload: `THUMB_SIZE` square, centre-cropped, JPEG | `/staff/en/files/thumb/<id>`, from local disk (S3 only if the local copy is missing) |
| Image whose thumbnail failed (corrupt, over `THUMB_MAX_PIXELS`) | `thumb-image.jpg` | Static file |
| Other types with an icon in `THUMB_ICONS` (`csv`, `xlsx`, ...) | `thumb-csv.jpg`, `thumb-xlsx.jpg`, ... | Static file |
| Anything else | `thumb-file.jpg` | Static file |

- **Icons** live in `webroot/img/files-s3-offload/`. Simple placeholder icons (a coloured card with the type label) were generated with ImageMagick; replace them with your own using the same file names. Lookups use the lowercase extension of the sanitized filename, because `finfo` is unreliable for CSV and Office files. The MIME whitelist still decides whether the upload is allowed at all.
- **Generation** uses GD, which the Docker image and production both have (built with JPEG / WebP support).
    - Read the EXIF orientation on JPEGs (`exif` extension) and rotate first. Otherwise phone photos come out sideways.
    - A transparent PNG, GIF or WebP is flattened onto a white background.
    - An animated GIF uses its first frame.
    - Images larger than `THUMB_MAX_PIXELS` skip generation and use `thumb-image.jpg`.
- **`thumb_src`:** `FilesTable::findAllForUser()` sets `thumb_icon` on each row (null when a generated thumbnail exists, otherwise the icon file), plus `status_badge_class` and `size_label`, using the same idea as `EmailQueuesTable::decorateStatus()`. The controller's view-shaping helper `index_decorate()` turns that into `thumb_src` (the `thumb` action URL or the icon path), `view_url` and `download_url`. Templates only ever print them.
- **Storage:** the thumbnail stays on local disk forever (it is small) and is pushed to S3 with the original as a backup. If the local copy is missing (e.g. after a server rebuild), `thumb` serves it from S3. Saving it back locally is out of scope.
- **Existing files:** there are none in core, so no backfill is needed. A client project that adopts this block with existing files needs a one-off backfill (out of scope).

## Disaster recovery

- **Server lost:** restore the database backup and redeploy, and the app serves everything from S3. Files that still had `status = local` at the time of the loss are gone, so keep the cron interval short.
- **Recommended:** schedule an `rclone copy` of the bucket to a second location (a local machine, a NAS or another provider). Use `copy`, not `sync`, so deletions in the bucket don't remove the backup. That way S3 is not the only copy.

## Blocks with DB page

The block's own page is the working feature **and** its code blocks, like the other "Blocks with DB" pages (e.g. Email Queues). It is linked as **Files S3 Offload** under **Blocks with DB**.

- **URL:** `/staff/en/files` (`Staff/FilesController::index()`, `code_blocks` layout).
- **Top:** a working upload form, the setup checks (`FILES_LOCAL_PATH` ready? S3 on, and which bucket?), and the user's file counts per status.
- **Middle:** the user's files as thumbnail cards, with View / Download and a status badge (the last error shows on hover).
- **Guide:** an overview, the folder and bucket layout, "Build it on a project" (Composer step, SQL, files, images, cron, settings, client-project notes), the settings table, and how to run the push.
- **Bottom:** every source file in full, through `codeBlocks_renderFiles`, so another project (or an AI assistant) can build the block from this page alone. These are the SQL, Table, Entity, Controller, template (one file), JS, CSS and `config/cron.php`.
- Each file has a **push-s3** link to push it now (decision 15) and a **Delete** link (decision 16). The cron job itself stays unreachable from the web, and the page shows `bin/cake cron run files_push` for running it by hand.

## Wiring

- `config/cron.php`: the `files_push` job.
- Menu: **Files S3 Offload** under **Blocks with DB**, after Email Queues (in `AppController::setupMenu()` and `init-web/9-Install-CodeBlocks_appController.php`).
- `.gitignore`: `/dockerLinux/private/`.
- `dockerLinux/ini/php.ini`: `FILES_LOCAL_PATH` for Docker.
- `composer.json`: `aws/aws-sdk-php`, the `pre-autoload-dump` trim script and `extra.aws/aws-sdk-php: ["S3"]`.
- `init-web/8-Save-CodeBlocks.php`: sync entries for the SQL, Table, Entity, Controller, template, CSS, JS, loading GIF and icons.
- No route changes: the prefix fallback routes reach `Staff/Files`.

## Known limitations

- **Hosting restrictions on the private path:** if the host sets `open_basedir`, the private path must be included in it, or PHP cannot read or write there. Check this once per server when setting `FILES_LOCAL_PATH`.
- **Web-request cron:** the push runs inside `cron.php`, so hosting `max_execution_time` and proxy timeouts apply. The time budget and the stuck-row reset cover this, but a single file can still take longer than the hosting limit allows.
- **Streaming through PHP:** every remote view or download ties up a PHP worker for the whole transfer.
- **Owner only:** nobody, not even an admin, can see another user's files in the MVP.
- **PHP 8.2 or newer** is required everywhere this app runs, because of the AWS SDK (see `## Context`).
- **Existing problems, not this feature:**
    - `config/cron.php` points `email_queue` at `EmailQueues::processCronQueue`, which does not exist in `EmailQueuesTable`. That job currently fails on every run.
    - `config/cron.php` still has the placeholder `status_path` `/absolute/path/to/status/dir/`, so every job reports "Cron job completed but heartbeat could not be written". The jobs themselves run.

## Implementation Surfaces

- `sourceFiles/src/Model/Table/FilesTable.php`: `setupStatus()`, `saveUpload()`, `findAllForUser()`, `serveInfo()`, `openRemote()`, `pushToS3()`, `pushOne()`, `deleteForUser()`, and the private helpers (incl. the shared `pushFile()`, `pushLock()`) (`<public>_<helper>`)
- `sourceFiles/src/Model/Entity/File.php`
- `sourceFiles/src/Controller/Staff/FilesController.php`: `index()`, `add()`, `push()`, `delete()`, `view()`, `download()`, `thumb()`, and the private `index_decorate()`, `index_codeBlocks()`, `serve()` and `serve_remote()`
- `sourceFiles/templates/Staff/Files/index.php`: the whole page in one file (upload form, setup checks, file grid, CodeBlocks guide; decision 17)
- `sourceFiles/config/cron.php`
- `sourceFiles/config/schema/2026-10-27.sql`
- `sourceFiles/composer.json`, `composer.lock`, `vendor/aws`, `vendor/guzzlehttp`, `vendor/mtdowling`, `vendor/ralouphie`, `vendor/bin/jp.php`, `vendor/composer/*`
- `sourceFiles/webroot/js/files-s3-offload.js`: the placeholder swap and the upload size check, loaded from `templates/Staff/Files/index.php`
- `sourceFiles/webroot/css/files-s3-offload.css`: placeholder box and the loaded / failed states, selectors prefixed `.files-s3-offload-*`
- `sourceFiles/webroot/img/files-s3-offload-loading.gif`: the animated placeholder (generated; replaceable)
- `sourceFiles/webroot/img/files-s3-offload/thumb-*.jpg`: `csv`, `xlsx`, `docx`, `pdf`, `zip`, `video`, `image`, `file` (generated; replaceable)
- `dockerLinux/ini/php.ini`, `.gitignore`, `init-web/8-Save-CodeBlocks.php`, `init-web/9-Install-CodeBlocks_appController.php`, `sourceFiles/src/Controller/AppController.php` (menu)

## Build checklist

- [x] Confirm the decisions above.
- [x] Add the AWS SDK (S3 only) to `vendor/` (you commit it).
- [x] Write the SQL into `config/schema/2026-10-27.sql` and apply it to Docker.
- [x] Build `FilesTable` + `File`.
- [x] Build `Staff/FilesController` with its template (one file, decision 17).
- [x] Add the loading GIF, `files-s3-offload.js` and `files-s3-offload.css`.
- [x] Add the thumbnail icons and the `thumb` action.
- [x] Register the cron job, the menu link and `.gitignore`.
- [x] Set `FILES_LOCAL_PATH` in the Docker `php.ini`.
- [x] Blocks with DB page with the working upload and every source file.
- [x] Create a real test bucket and key (DigitalOcean Spaces), set in the gitignored `dockerLinux/secrets.env`, which `docker-compose.yml` loads.
- [ ] On each hosting server: PHP 8.2+, and `FILES_LOCAL_PATH` in the server `php.ini` pointing at the private path.
- [ ] Run the remaining `## Testing` scenarios by hand.

## Out of scope (later)

- **Presigned links for large files:** after the ownership check, 302-redirect videos, big PDFs and zips to a short-lived presigned URL (1 to 5 minutes) instead of streaming through PHP. The trade-off: the bucket key is visible, and the link works for anyone until it expires.
- **More derived files:** PDF first-page and video-frame thumbnails (they need Ghostscript / ffmpeg; the MVP uses icons), several thumbnail sizes, and saving a thumbnail fetched from S3 back to local disk. A `kind` column or a separate table becomes worth it at that point.
- **Thumbnail backfill** for projects that adopt this block with existing files.
- **CDN** in front of the bucket for public, high-traffic files.
- **Shared files table** across entities, only once everything runs smoothly. The default is always one `{table}_files` per owner.
- **Admin access to all files** (see `## Known limitations`).
- **Group scoping:** client projects whose users have `group_id` add it to the files table and filter on it, as AGENTS "Data Scoping" requires.


### Daily compare (reconciliation)

This is an explanation only, not part of the MVP build. It is an option for more stability once the block runs on a client project.

**Why it is not needed for the MVP:** the push never leaves a half state. A file is either confirmed in S3 or still on the server with an `error` logged. The compare only catches problems caused **outside** the block:
- objects deleted or changed in the bucket (by hand, or by a lifecycle rule),
- files lost from the private path,
- objects in the bucket with no row (orphans),
- rows stuck in `uploading`, or `failed` rows that have run out of attempts.

**How it would work:**
- `FilesTable::reconcile()` would be registered in `config/cron.php` and limit itself to once a day (a timestamp file in `TMP`), because `run_all` has no interval scheduler.
- It would be the only code allowed to list the bucket (`listObjects`).
- It would **report only and never delete**, logging each finding as a `warning` so it shows on the Status Endpoint.

### Unguessable file links (uuid)

This is an explanation only, not part of the MVP build. Do it once the block runs on a client project.

**Why:** with numeric ids, the owner filter is the only thing between a user and someone else's file. That is fine while it holds. An unguessable value in the URL adds a second layer:
- It hides how many files exist (`/view/1043` hints at about 1,000).
- It keeps files hard to find if scoping is loosened (shared group files, an admin view) or broken by a bug.

**When it becomes required, not optional:** as soon as a link must work **without** the owner check, such as presigned S3 URLs, public share links, or a file link in an email. Then the link itself is the permission.

**How to add it:**
1. Add a `uuid` column, `char(36)` with a unique index. Fill it with `Cake\Utility\Text::uuid()` on upload, and backfill existing rows with a one-off update in that day's `config/schema/YYYY-MM-DD.sql`.
2. Switch the `view` / `download` / `thumb` URLs and `serveInfo()` to the uuid. Keep the numeric `id` for folders and bucket keys, so nothing on disk or in S3 moves.
3. For links without login, consider a separate `share_token` (and expiry) per feature instead of reusing the uuid, so revoking a share does not change the file's normal URL.
4. Keep the 404 rule: an unknown uuid and another user's file must look the same.

## Testing

Use a dedicated test bucket with its own access key, limited to that bucket. Run the push with `bin/cake cron run files_push`, or `cron.php?action=run&job=files_push` from an allowed IP.

**Run so far (2026-10-27, Docker, PHP 8.3).** These checks used two temporary STAFF users and curl, and the users and files were removed afterwards.
- No real bucket was available: Docker registries were blocked, so MinIO could not be started either.
- S3 was therefore exercised in two ways:
    - a dead endpoint (`http://127.0.0.1:9`), for the failure paths;
    - the AWS SDK's own handler hook, which replaces the network with an in-process fake bucket. That fake ran the real `ObjectUploader` / `headObject` / `getObject` calls.
- Scenarios marked `(fake S3)` passed that way. Later the same day, the push-s3 link and serving were run against the real test bucket (DigitalOcean Spaces), marked `(real bucket)`. The test objects were deleted afterwards.
- Browser-only behaviour (the JS placeholder swap) has not been run.

### Upload

**Intent:** Uploads land in the private path, are validated server-side, and work immediately with S3 off.

**Surfaces:**
- `Staff/FilesController::add()`
- `FilesTable::saveUpload()`

#### Scenarios

- [x] With `FILES_S3_KEY` unset, uploading an image creates a `local` row, the file is on disk, and `/view` shows it.
- [ ] A file whose real type is not on the whitelist is rejected, even with a whitelisted extension or browser MIME type.
  - Partly run: a `.sh` file was rejected (`text/x-shellscript`). Renaming it to a whitelisted extension has not been tried.
- [ ] A file over `MAX_UPLOAD_BYTES` is rejected and leaves no row and no file.
- [x] A filename containing `../` or odd characters is stored sanitized, and the download keeps the original name.
- [x] Each upload lands in its own `{id}/` folder under `FILES_LOCAL_PATH`, and after a push the bucket has the same `{id}/` layout. (fake S3)
- [x] An upload named `thumb.jpg` or `_thumb.jpg` does not overwrite the generated thumbnail.
- [ ] A failed upload leaves no row and no `{id}/` folder.
- [x] A GET to `add` changes nothing (405).
- [ ] With `FILES_LOCAL_PATH` unset, missing or not writable, uploads are refused with a clear message, nothing is written inside `sourceFiles/`, and the push job returns `500`.
  - Partly run: with a missing path the upload was refused with the message, no row was written, the setup panel showed it, and an `error` was logged. The push's `500` has not been run.
- [ ] Files survive deleting and redeploying the website files.

### Push

**Intent:** Local files reach S3 exactly once, and a local file is never deleted without a confirmed copy.

**Surfaces:**
- `FilesTable::pushToS3()`
- `config/cron.php` `files_push`

#### Scenarios

- [x] With S3 set up, a push turns the row `remote`, the object is in the bucket with a matching size, and the local file is deleted. (fake S3; the stored copy was byte-identical. A forced size mismatch kept every local file.)
- [x] With a wrong secret, the row turns `failed`, `last_error` is set, the local file is still there, the page still works, and an `error` line is logged. (Run with a dead endpoint, not a wrong secret.)
- [x] After the secret is fixed, the next push turns the row `remote`. (fake S3)
- [x] A row that has failed `MAX_ATTEMPTS` times is not retried, and its last failure is logged once with `giving up`.
- [ ] A ~200 MB video pushes successfully (multipart).
- [ ] Two push runs at the same time never push the same row twice.
- [x] A row stuck in `uploading` for more than an hour is reset to `failed` and retried.
- [x] With S3 off, the job returns `S3 disabled` and touches nothing.
- [x] The push-s3 link pushes only that file: the row turns `remote`, the original and thumbnail are confirmed in the bucket, the local original is deleted, and the link disappears. (real bucket)
- [ ] The push-s3 link is hidden while S3 is off, and never shown for another user's file.
- [ ] Clicking push-s3 while the cron push is running answers "A push is already running" and pushes nothing.

### Serving

**Intent:** Files are served only to their owner, from local disk first and from S3 otherwise, without S3 slowing page renders.

**Surfaces:**
- `Staff/FilesController::view()`, `download()`, `serve()`
- `FilesTable::serveInfo()`, `openRemote()`

#### Scenarios

- [x] After a push, `/view` and `/download` are served from S3, and the download keeps the original filename. (real bucket; the bytes were identical to the upload)
- [ ] Seeking in a pushed video works (206 responses).
  - Partly run: Range requests returned 206 with the right bytes, both locally and from the real bucket. Not yet run with a video in a browser.
- [x] Requesting another user's file returns 404, the same as an unknown id (`view`, `download` and `thumb`).
- [ ] With the endpoint pointed at a dead host, the files list page still loads instantly, and `/view` fails within about 3 s.
- [x] Page source never contains a bucket key or an S3 URL. (File tiles only use app URLs and icon paths. The guide text names an example Spaces endpoint.)
- [ ] Other tabs keep working during a long download.

### Image loading placeholder

**Intent:** While an image loads from S3 the user sees a local animated GIF, never a blank or broken image.

**Surfaces:**
- `webroot/img/files-s3-offload-loading.gif`
- `webroot/js/files-s3-offload.js`
- `webroot/css/files-s3-offload.css`

#### Scenarios

- [ ] On a page of pushed images, the GIF shows instantly and each image replaces it once loaded.
- [ ] With S3 slowed or unreachable, the GIF keeps animating while waiting, then turns into the "not available" state on failure.
- [ ] A transparent PNG shows no spinner behind it once loaded.
- [ ] The layout does not jump when images replace the GIF.
- [ ] The GIF is served as a static file: no PHP request and no S3 call for it.
- [ ] Local (not yet pushed) images use the same markup and swap just as well.

### Thumbnails

**Intent:** Every file has a thumbnail in lists: a generated one for images, a type icon for everything else.

**Surfaces:**
- `FilesTable::saveUpload()`, `findAllForUser()`
- `Staff/FilesController::thumb()`
- `webroot/img/files-s3-offload/thumb-*.jpg`

#### Scenarios

- [x] Uploading a JPG or PNG creates a square `THUMB_SIZE` thumbnail that shows in the list straight away.
- [x] A portrait phone photo's thumbnail is the right way up (EXIF orientation 6).
- [x] A transparent PNG's thumbnail has a white background, not black.
- [ ] A CSV shows `thumb-csv.jpg`, an XLSX shows `thumb-xlsx.jpg`, and an unknown type shows `thumb-file.jpg`.
  - Partly run: CSV (detected as `text/plain`) and XLSX (detected as `application/zip`) showed the right icons. The unknown-type fallback has not been run.
- [ ] A corrupt or oversized image still uploads and shows `thumb-image.jpg`.
- [x] After a push, the local thumbnail is kept while the original is deleted, and the list still shows it. (fake S3)
- [ ] With the local thumbnail deleted, `thumb` serves it from S3.
- [x] Another user's `thumb` URL returns 404.

### Delete

**Intent:** A user can delete their own file everywhere it is stored, and a failure never leaves a copy in S3 with no row pointing at it.

**Surfaces:**
- `Staff/FilesController::delete()`
- `FilesTable::deleteForUser()`

#### Scenarios

- [x] Deleting a pushed file removes both S3 objects, the local thumbnail, the `{id}/` folder and the row. (real bucket)
- [x] Deleting a file whose S3 original was already removed by hand succeeds. (real bucket)
- [x] Deleting a local-only file removes its files, folder and row.
- [x] Another user's id deletes nothing ("File not found"), and the row stays.
- [x] A file that is `uploading` shows no Delete link, and a direct POST is refused.
- [x] With S3 unreachable, nothing is deleted (the row, local thumbnail and both bucket objects stay), and deleting again later works.
- [x] Each delete logs a `notice` with the user, file id and filename.
- [ ] A GET to `delete` changes nothing.
- [ ] Deleting while the cron push holds the lock answers "A push is running" and deletes nothing.
