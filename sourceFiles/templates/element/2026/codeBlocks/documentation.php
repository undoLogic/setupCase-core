<?php
/**
 * CodeBlocks setup guide for the Documentation block.
 * Spec: docs/features/MVP-documentation.md
 *
 * @var \App\View\AppView $this
 */
?>
<style>
    .documentation-guide pre {
        max-height: 32rem;
        overflow: auto;
    }
</style>

<div class="documentation-guide">
    <div class="alert alert-danger">
        <strong>Privacy warning:</strong> in SetupCase core this block is <strong>public</strong> for demo
        purposes. On client projects it <strong>must</strong> be placed under a logged-in prefix
        (step 4), otherwise anyone with the URL can read the client's documentation.
    </div>

    <h2 class="h4">Demo</h2>
    <p>The core demo opens doc 1 on page 2.</p>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?= $this->element('2026/documentation/view_modal'); ?>
    </div>

    <h2 class="h4">Setup on a client project</h2>
    <p>
        Follow these steps in order. Do not skip step 4. Full spec:
        <code>docs/features/MVP-documentation.md</code> in SetupCase core.
    </p>

    <ol class="mb-4">
        <li class="mb-3">
            <strong>What it is.</strong>
            Some clients sit behind networks that block Google Docs. This block downloads a link-shared
            Google Doc as a PDF onto the project's own domain and shows it in a PDF.js viewer with
            page links (<code>view/1/22</code> opens page 22). On client projects it is private behind login.
        </li>
        <li class="mb-3">
            <strong>Confirm prerequisites.</strong>
            <ul>
                <li>CakePHP prefix routing with a logged-in prefix (e.g. <code>Staff</code>) whose routes require login.</li>
                <li>The server can make outbound HTTPS requests to <code>docs.google.com</code>.</li>
                <li>The Google Doc is shared as "anyone with the link can view".</li>
            </ul>
        </li>
        <li class="mb-3">
            <strong>Copy the files</strong> (all shown below this guide):
            <table class="table table-sm mt-2">
                <thead>
                    <tr><th>SetupCase core</th><th>Client project</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>src/Controller/DocumentationController.php</code></td><td><code>src/Controller/&lt;Prefix&gt;/DocumentationController.php</code></td></tr>
                    <tr><td><code>src/Service/DocumentationService.php</code></td><td>same path</td></tr>
                    <tr><td><code>templates/Documentation/view.php</code></td><td><code>templates/&lt;Prefix&gt;/Documentation/view.php</code></td></tr>
                    <tr><td><code>templates/element/2026/documentation/</code></td><td>same path</td></tr>
                    <tr><td><code>templates/layout/documentation_embed.php</code></td><td>same path</td></tr>
                    <tr><td><code>webroot/js/documentation-viewer.js</code></td><td>same path</td></tr>
                    <tr><td><code>webroot/modules/pdfjs-3.11.174/</code></td><td>same path (vendor, do not edit)</td></tr>
                </tbody>
            </table>
        </li>
        <li class="mb-3">
            <strong>Move under a prefix (required).</strong>
            Place the controller in <code>src/Controller/&lt;Prefix&gt;/</code>, change its namespace to
            <code>App\Controller\&lt;Prefix&gt;</code>, add <code>use App\Controller\AppController;</code>, and
            put the template in <code>templates/&lt;Prefix&gt;/Documentation/</code>. Then, logged out, confirm
            that <code>view</code>, <code>file</code> and <code>regenerate</code> are all refused.
        </li>
        <li class="mb-3">
            <strong>Fill in the values</strong> in <code>DocumentationService</code>:
            <code>$documents</code> (title, Google Doc URL or ID, fixed filename) and <code>$storagePath</code>
            (default <code>TMP . 'documentation' . DS</code>, never under webroot).
            Set <code>DOCUMENTATION_REGENERATE_CODE</code> on the server (php.ini value) or in the Docker env
            to a random string. Never put the code in source. If it is not set, regenerate is disabled.
        </li>
        <li class="mb-3">
            <strong>Wire it into the project.</strong>
            Add <code>/sourceFiles/tmp/documentation/</code> to <code>.gitignore</code>, and add a link to
            <code>/&lt;prefix&gt;/en/documentation/view/1</code> in the client's own navigation. To open it in
            context on another page, render element <code>2026/documentation/view_modal</code> with
            <code>docModalUrl</code> (<code>view/1/&lt;page&gt;?embed=1</code>), <code>docOwnTabUrl</code> and
            <code>docModalTitle</code> set by that page's controller.
        </li>
        <li class="mb-3">
            <strong>First run and verify.</strong>
            Open <code>/&lt;prefix&gt;/en/documentation/regenerate/1?code=&lt;code&gt;</code>. You are redirected to
            the viewer with a success message. Then check:
            <ul>
                <li><code>view/1</code> opens page 1, <code>view/1/22</code> opens page 22, a page past the end opens the last page.</li>
                <li>A wrong or missing <code>code</code> returns 404 and changes nothing.</li>
                <li>A doc that is not link-shared fails with a clear message and the old PDF is kept.</li>
                <li>Logged out: <code>view</code>, <code>file</code> and <code>regenerate</code> are refused.</li>
                <li>No unprefixed <code>/en/documentation/...</code> URL serves the document.</li>
            </ul>
        </li>
        <li class="mb-3">
            <strong>Known limitations.</strong>
            Link-shared docs only. Page numbers drift when the doc is edited. Regenerate is a GET link with
            a code (MVP exception, deterrence only: the code shows up in logs and history). Any role holding
            the prefix can regenerate if they know the code. On larger projects, move regenerate into the
            admin area (POST or a console command) and split the controller.
        </li>
    </ol>
</div>
