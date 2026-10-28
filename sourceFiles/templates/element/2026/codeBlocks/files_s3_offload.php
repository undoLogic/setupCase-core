<?php
/**
 * CodeBlocks guide for the Files S3 Offload block. Rendered on the public
 * CodeBlocks page (/en/code-blocks/files-s3-offload) and on the working Staff
 * page, so there is one copy. Not part of the block itself: never copy it to a project.
 * Spec: docs/features/MVP-Files-S3-Offload.md
 *
 * @var \App\View\AppView $this
 */
?>
<h2 class="h4">Overview</h2>
<table class="table table-sm mb-4">
    <tbody>
        <tr><th>One way in</th><td>Uploads are saved to the private path (<code>FILES_LOCAL_PATH</code>) and work at once. No S3 call during upload.</td></tr>
        <tr><th>One mover</th><td>The cron job <code>files_push</code> copies local files to S3, confirms each copy (object exists, size matches), then deletes the local original. The <strong>push-s3</strong> link does the same for one file.</td></tr>
        <tr><th>One way out</th><td>Pages only link to this controller's <code>view</code> / <code>download</code> / <code>thumb</code> actions. They serve from disk first, then S3. No S3 URL or key ever reaches a page.</td></tr>
        <tr><th>Owner only</th><td>Every lookup filters on <code>user_id</code>. Another user's file, an unknown id and a missing file all return 404.</td></tr>
        <tr><th>Thumbnails</th><td>Images get a 300 x 300 thumbnail at upload (kept locally for good, backed up to S3). Other files get an icon by extension. Every thumbnail first shows a local loading GIF.</td></tr>
        <tr><th>Delete</th><td>Removes the S3 copies, then the local files, then the row. An S3 object already removed by hand is fine; if S3 cannot be reached, nothing is deleted.</td></tr>
        <tr><th>Failures</th><td>A failed push keeps the file on the server and logs an <code>error</code> (the Status Endpoint counts it). Retried up to 5 times; the last failure says <code>giving up</code>.</td></tr>
    </tbody>
</table>

<h2 class="h4">On disk and in the bucket</h2>
<pre class="bg-light p-2 mb-4"><code>&lt;FILES_LOCAL_PATH&gt;/1043/site-photo.jpg     -&gt;  &lt;bucket&gt;/files/1043/site-photo.jpg
&lt;FILES_LOCAL_PATH&gt;/1043/_thumb.jpg         -&gt;  &lt;bucket&gt;/files/1043/_thumb.jpg</code></pre>

<h2 class="h4">Build it on a project</h2>
<p class="mb-2">
    Written so a developer or an AI assistant can add this block to another CakePHP 4.x project from this page
    alone. Every file is shown in full at the bottom of the page. The full spec is
    <code>docs/features/MVP-Files-S3-Offload.md</code>.
</p>
<ol class="mb-4">
    <li class="mb-2">
        <strong>Add the AWS SDK, S3 only.</strong> In <code>composer.json</code> add
        <code>"pre-autoload-dump": "Aws\\Script\\Composer\\Composer::removeUnusedServices"</code> to
        <code>scripts</code> and <code>"extra": {"aws/aws-sdk-php": ["S3"]}</code>, then run
        <code>composer require aws/aws-sdk-php</code>. Commit <code>vendor/</code> if the project does.
        The SDK needs PHP 8.2 or newer.
    </li>
    <li class="mb-2">
        <strong>Run the SQL</strong> (<code>files</code> table) and copy the Table, Entity, Controller, template, CSS and JS
        to the same paths. The template is <strong>one file</strong> on purpose: remove its "CodeBlocks guide" section,
        then split it into elements following the project's AGENTS.md.
    </li>
    <li class="mb-2">
        <strong>Copy the images:</strong> <code>webroot/img/files-s3-offload-loading.gif</code> and the icons in
        <code>webroot/img/files-s3-offload/</code> (<code>thumb-file.jpg</code>, <code>thumb-image.jpg</code>,
        <code>thumb-csv.jpg</code>, <code>thumb-xlsx.jpg</code>, ...).
    </li>
    <li class="mb-2"><strong>Register the cron job</strong> <code>files_push</code> in <code>config/cron.php</code> (shown below).</li>
    <li class="mb-2"><strong>Set the settings</strong> below on the server, then upload a file on the Files page (Blocks with DB, Staff login) and run the push.</li>
    <li class="mb-2">
        <strong>Client projects:</strong> rename the table to <code>{table}_files</code> (for example
        <code>asset_files</code>), one per owning entity, and add <code>group_id</code> scoping if users have groups.
    </li>
</ol>

<h2 class="h4">Settings (ENV Vars pattern)</h2>
<p class="mb-2">Server <code>php.ini</code> first, then Docker env. Never commit real values.</p>
<table class="table table-sm mb-4">
    <thead><tr><th>Name</th><th>Notes</th></tr></thead>
    <tbody>
        <tr><td><code>FILES_LOCAL_PATH</code></td><td><strong>Required.</strong> The hosting's private path, which survives redeploys. Must exist and be writable. If <code>open_basedir</code> is set, include it there.</td></tr>
        <tr><td><code>FILES_S3_KEY</code> / <code>FILES_S3_SECRET</code></td><td>Access key limited to this bucket. Empty key = S3 off, files stay local.</td></tr>
        <tr><td><code>FILES_S3_BUCKET</code></td><td>e.g. <code>setupcase-test</code></td></tr>
        <tr><td><code>FILES_S3_REGION</code></td><td>Default <code>us-east-1</code>. Spaces: e.g. <code>tor1</code></td></tr>
        <tr><td><code>FILES_S3_ENDPOINT</code></td><td>Empty for AWS. Spaces: <code>https://tor1.digitaloceanspaces.com</code> (region host only, never the bucket name)</td></tr>
        <tr><td><code>FILES_S3_PREFIX</code></td><td>Default <code>files/</code></td></tr>
    </tbody>
</table>

<h2 class="h4">Running the push</h2>
<ul class="mb-4">
    <li>Hosting cron calls <code>cron.php?action=run_all</code>, which runs <code>files_push</code> (at most 10 files or 50 seconds per run).</li>
    <li>By hand, from the server: <code>bin/cake cron run files_push</code>.</li>
    <li>One file now: the <strong>push-s3</strong> link on a file (shown while S3 is on and the file is local or failed). It pushes only that file, only for its owner, and waits if cron is pushing.</li>
</ul>

<h2 class="h4 mb-0">Source files</h2>
