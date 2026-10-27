<?php
/**
 * CodeBlocks guide for the Status Endpoint block.
 * Spec: docs/features/MVP-Status-Endpoint.md
 *
 * @var \App\View\AppView $this
 * @var string $statusExampleUrl
 * @var string $statusLocalUrl
 */
?>
<h2 class="h4">Overview</h2>
<table class="table table-sm mb-4">
    <tbody>
        <tr><th>Each site</th><td>Serves <code>GET /en/status</code>: error and warning counts from its logs for the last 24 hours.</td></tr>
        <tr><th>Daily check</th><td>Now: one bookmark per site, left open in a tab (re-checks hourly, red favicon on errors). Later: a central cron job calls every site.</td></tr>
        <tr><th>Result</th><td>One daily report: "any errors on any site?" No log contents ever leave the site.</td></tr>
        <tr><th>Opt out</th><td>No <code>STATUS_TOKEN</code> on a site = endpoint off (404). Customer Controlled installs leave it unset.</td></tr>
    </tbody>
</table>

<h2 class="h4">Build it on a project</h2>
<p class="mb-2">
    Written so a developer or an AI assistant can add this block to another CakePHP 4.x project from this page
    alone. Every file is shown in full under <strong>Source files</strong> at the bottom of the page.
</p>
<ol class="mb-3">
    <li class="mb-2">
        <strong>Copy the new files</strong> to the same paths (shown in full at the bottom of the page):
        <ul class="mt-1">
            <li><code>src/Controller/StatusController.php</code>: handles <code>GET /en/status</code> and counts the log lines (self-contained, no Table or Service)</li>
            <li><code>templates/Status/index.php</code>: browser monitor page (one file; split into elements once integrated, if needed)</li>
            <li><code>webroot/css/status-endpoint.css</code> and <code>webroot/js/status-endpoint.js</code></li>
        </ul>
    </li>
    <li class="mb-2">
        <strong>Leave the project's config alone.</strong> No changes to <code>Application.php</code>,
        <code>config/app.php</code> or the routes.
    </li>
    <li class="mb-2">
        <strong>No route, no database table, no Composer package.</strong> The default fallback routes
        (<code>/{language}/{controller}</code>) reach <code>StatusController::index()</code>; if the project has
        removed them, connect <code>/{language}/status</code> to <code>Status::index</code>.
        <code>StatusController</code> extends Cake's base <code>Controller</code>, <em>not</em>
        <code>AppController</code>, so the login, menu, testing-site password gate and database are skipped. Keep it
        that way. If the project's <code>Log</code> config writes somewhere other than
        <code>LOGS . 'error.log'</code> / <code>'cli-error.log'</code>, change <code>LOG_FILES</code> in the controller.
    </li>
    <li class="mb-2"><strong>Set <code>STATUS_TOKEN</code></strong> on the server (see Setup below). Without it the endpoint stays off.</li>
    <li class="mb-2">
        <strong>Verify</strong> with curl:
        <ul class="mt-1">
            <li>No token configured: <code>/en/status</code> returns 404.</li>
            <li>Correct header: 200 + JSON; wrong token or no token: 404.</li>
            <li>Browser with <code>?token=</code>: monitor page, favicon green or red.</li>
            <li>A wrong-token request raises <code>ignored</code>, not <code>errors</code>.</li>
            <li>Stop the database: <code>/en/status</code> still returns 200.</li>
        </ul>
    </li>
</ol>
<p class="mb-4">
    Full spec in SetupCase core: <code>docs/features/MVP-Status-Endpoint.md</code>.
</p>

<h2 class="h4">Part 1: on each site</h2>

<h3 class="h5">What it returns</h3>
<p class="mb-2">With the correct token, curl and cron jobs get HTTP 200 and JSON (a browser gets the monitor page, Part 2):</p>
<pre class="mb-3"><code>{
  "app": "www.example.com",
  "generated_at": "2026-10-27T08:00:00+00:00",
  "window_hours": 24,
  "errors": 3,
  "warnings": 12,
  "ignored": 140,
  "php_version": "8.3.12",
  "cakephp_version": "4.6.5"
}</code></pre>
<ul class="mb-4">
    <li><code>errors</code>: log lines at level error, critical, alert or emergency in the last 24 hours.</li>
    <li><code>warnings</code>: log lines at level warning in the last 24 hours.</li>
    <li><code>ignored</code>: error / warning entries skipped because they match the ignore list (see below).</li>
    <li>Counted from <code>logs/error.log</code> and <code>logs/cli-error.log</code>, including rotated files. Stack-trace lines are not counted.</li>
    <li>No database, session or login is used, so it still answers when the database is down.</li>
</ul>

<h3 class="h5">Ignore list</h3>
<p class="mb-2">
    Every public site logs noise: bots requesting <code>/wp-admin</code>, old links, and so on. Instead of hiding
    it from the log, <code>StatusController::IGNORE</code> lists strings that are not counted. An entry is
    skipped when its first log line contains one of them (plain, case-sensitive). Skipped entries show up as
    <code>ignored</code>, so you can see the filter working.
</p>
<ol class="mb-3">
    <li class="mb-2">Start with the default list: only <code>[Cake\Http\Exception\NotFoundException]</code>, which also covers wrong tokens on this endpoint, so nobody can inflate your count.</li>
    <li class="mb-2">When a tab turns red, read <code>logs/error.log</code> on that site.</li>
    <li class="mb-2">A real bug: fix it. Known noise: copy a distinctive part of its first line into <code>IGNORE</code>.</li>
</ol>
<pre class="mb-2"><code>private const IGNORE = [
    '[Cake\Http\Exception\NotFoundException]', // includes wrong tokens on this endpoint
    'Controller class DebugKit',                // narrow: one known nuisance
    // '[Cake\Http\Exception\MissingControllerException]', // broad: every unknown controller
];</code></pre>
<p class="mb-4">
    Prefer narrow entries. A broad one can hide a real problem, e.g. ignoring every
    <code>MissingActionException</code> also hides a link to an action someone forgot to write.
</p>

<h3 class="h5">Access rules</h3>
<table class="table table-sm mb-4">
    <tbody>
        <tr><th>Correct <code>?token=</code>, opened in a browser</th><td>200 + monitor page (hourly re-check, coloured favicon)</td></tr>
        <tr><th>Correct <code>?token=</code> with <code>&amp;format=json</code></th><td>200 + JSON</td></tr>
        <tr><th>Correct <code>X-Status-Token</code> header (curl, cron)</th><td>200 + JSON (for automation)</td></tr>
        <tr><th>Missing or wrong token</th><td>404, same page as any missing URL</td></tr>
        <tr><th>No token configured on the site</th><td>404 (endpoint off)</td></tr>
        <tr><th>Anything other than GET</th><td>403 from CSRF for POST (like any POST without a token), otherwise 404</td></tr>
    </tbody>
</table>
<p class="mb-4">
    It never returns 401 or 403, so a scanner cannot tell the endpoint exists.
</p>

<h3 class="h5">Setup</h3>
<ol class="mb-4">
    <li class="mb-2">
        Generate a token for this site (one per site, never reused):
        <pre class="mt-2 mb-0"><code>openssl rand -hex 32</code></pre>
    </li>
    <li class="mb-2">
        Set it with the <strong>ENV Vars</strong> pattern: <code>STATUS_TOKEN</code> in the server
        <code>php.ini</code> on hosting, or in the Docker env locally. Never commit it.
    </li>
    <li class="mb-2">
        Optional: set <code>STATUS_APP_NAME</code> to the name the report should show. Without it, the site's
        host name is used.
    </li>
    <li class="mb-2">
        Test it by opening the URL in a browser:
        <pre class="mt-2 mb-0"><code><?= h($statusExampleUrl); ?>?token=&lt;token&gt;</code></pre>
        On this install the URL is <code><?= h($statusLocalUrl); ?></code>.
    </li>
    <li class="mb-2">Bookmark that URL and keep it open in a tab (Part 2).</li>
</ol>

<h2 class="h4">Part 2: daily check</h2>

<h3 class="h5">Now: keep a tab open per site</h3>
<ol class="mb-3">
    <li class="mb-2">Bookmark each site: <code>&lt;site&gt;/en/status?token=&lt;token&gt;</code>. Keep them in a "Status" folder.</li>
    <li class="mb-2">
        Open the folder in tabs (or pin the tabs) and leave them open. In a browser the URL shows a small
        monitor page, not raw JSON.
    </li>
    <li class="mb-2">
        Each page re-checks its site every 60 minutes without reloading, and straight away when you return to
        a tab that is overdue. A <strong>Check now</strong> button checks on demand.
    </li>
    <li class="mb-2">Watch the favicons in your tab bar:</li>
</ol>
<table class="table table-sm mb-3 w-auto">
    <thead>
        <tr><th>Favicon</th><th>Tab title</th><th>Meaning</th></tr>
    </thead>
    <tbody>
        <tr>
            <td><svg width="20" height="20" viewBox="0 0 32 32" aria-label="Green"><circle cx="16" cy="16" r="15" fill="#198754"/></svg></td>
            <td><code>OK - &lt;app&gt;</code></td>
            <td>No errors in the last 24 hours (warnings may still be shown on the page)</td>
        </tr>
        <tr>
            <td><svg width="20" height="20" viewBox="0 0 32 32" aria-label="Red"><circle cx="16" cy="16" r="15" fill="#dc3545"/><text x="16" y="23" font-size="20" font-weight="bold" font-family="Arial, sans-serif" text-anchor="middle" fill="#fff">!</text></svg></td>
            <td><code>(3) ERRORS - &lt;app&gt;</code></td>
            <td>Errors logged in the last 24 hours; check that site's <code>logs/error.log</code></td>
        </tr>
        <tr>
            <td><svg width="20" height="20" viewBox="0 0 32 32" aria-label="Grey"><circle cx="16" cy="16" r="15" fill="#6c757d"/><text x="16" y="23" font-size="20" font-weight="bold" font-family="Arial, sans-serif" text-anchor="middle" fill="#fff">?</text></svg></td>
            <td><code>UNREACHABLE - &lt;app&gt;</code></td>
            <td>The check failed: site down, timeout, or the token was changed</td>
        </tr>
    </tbody>
</table>
<p class="mb-3">
    A red favicon stays red until the error is more than 24 hours old, even after it is fixed. Browsers can
    put background tabs to sleep, so a tab may turn red only when you look at it.
</p>
<div class="alert alert-warning mb-4">
    A token in the URL ends up in the site's access logs, browser history and synced bookmarks. Accepted
    because the response only holds counts and versions. If a token may have leaked, change it.
</div>

<h3 class="h5">Later: central cron job</h3>
<p class="mb-2">Same endpoint, with the token in a header instead of the URL:</p>
<ol class="mb-3">
    <li class="mb-2">Once a day (e.g. 07:00), loop over every registered site.</li>
    <li class="mb-2">
        Send <code>GET &lt;site&gt;/en/status</code> with the header <code>X-Status-Token: &lt;token&gt;</code>,
        a short timeout (e.g. 10 seconds), and no redirects followed.
    </li>
    <li class="mb-2">Record the result per site, then send one report listing every site that needs a look.</li>
    <li class="mb-2">Store each site's URL and token together as a secret, not in source code.</li>
</ol>
<pre class="mb-3"><code>curl -s --max-time 10 \
  -H "X-Status-Token: $TOKEN" \
  <?= h($statusExampleUrl); ?></code></pre>

<h3 class="h5">Reading the result</h3>
<table class="table table-sm mb-4">
    <thead>
        <tr><th>Response</th><th>Meaning</th><th>Report as</th></tr>
    </thead>
    <tbody>
        <tr><td>200, <code>errors</code> = 0</td><td>No errors logged in 24 hours</td><td>OK</td></tr>
        <tr><td>200, <code>errors</code> &gt; 0</td><td>Errors were logged; check that site's <code>logs/error.log</code></td><td>Needs a look</td></tr>
        <tr><td>200, <code>warnings</code> &gt; 0 only</td><td>Warnings only</td><td>Info (optional)</td></tr>
        <tr><td>404</td><td>Token wrong, token not set on the site, or the feature is not deployed</td><td>Monitoring broken</td></tr>
        <tr><td>Timeout / 5xx / no connection</td><td>Site down or failing before it can log</td><td>Site down</td></tr>
    </tbody>
</table>
<p class="mb-4">
    <code>php_version</code> and <code>cakephp_version</code> let the report list every site affected by a
    security advisory without logging in anywhere.
</p>

<h2 class="h4">Changing a token</h2>
<ol class="mb-4">
    <li class="mb-2">Generate a new token and set <code>STATUS_TOKEN</code> on the site.</li>
    <li class="mb-2">Update the bookmark (or the central system) the same day; the old token stops working immediately.</li>
</ol>

<h2 class="h4">Known limitations</h2>
<ul class="mb-4">
    <li>Counts only: a non-zero count means "look at the logs on that site", never what failed.</li>
    <li>Problems that never reach the logger (site down, early PHP fatals) only show as a failed poll.</li>
    <li>A very noisy site can rotate logs away within 24 hours; the count is then a minimum.</li>
    <li>Job heartbeats are a separate check: <code>/cron.php?action=status</code> (Unified Cron Framework).</li>
</ul>
