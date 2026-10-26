<style>
    .email-queue-guide pre {
        max-height: 32rem;
        overflow: auto;
    }
</style>

<div class="email-queue-guide">
    <div class="alert alert-info">
        <strong>MVP rule:</strong> no project sends email directly. Every message is written to
        <code>email_queues</code> via <code>EmailQueuesTable::queueEmail()</code>, and a worker
        (manual button for now, cron later) drains it through the SetupCase send utility.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <h2 class="h5">Write</h2>
                <p class="mb-0">Any code calls <code>EmailQueuesTable::queueEmail()</code> to enqueue a message. Nothing sends synchronously.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <h2 class="h5">Drain</h2>
                <p class="mb-0"><code>EmailQueuesTable::send()</code> / <code>sendAll()</code> pull waiting rows and hand them to the SetupCase send utility.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <h2 class="h5">Park, don't retry</h2>
                <p class="mb-0">A failed send writes its message to <code>error</code> and stamps <code>last_attempt_at</code>. No automatic retry in this alpha.</p>
            </div>
        </div>
    </div>

    <h2 class="h4">Self-Contained Block</h2>
    <p>
        This is a single copy-paste unit: migrations, <code>EmailQueuesTable</code>,
        <code>EmailQueueAttachmentsTable</code>, <code>EmailQueuesController</code> (staff prefix), and the
        <code>Staff/EmailQueues</code> templates. Copy the whole set into a new project without unpicking
        dependencies. Full spec: <code>docs/features/email-queue-feature.md</code>.
    </p>

    <table class="table table-sm mb-4">
        <thead>
            <tr>
                <th>Route</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>/staff/email-queues</code></td>
                <td>Waiting / Sent tabs (<code>?tab=sent</code>)</td>
            </tr>
            <tr>
                <td><code>/staff/email-queues/create</code></td>
                <td>Queue a new email, with optional attachments</td>
            </tr>
            <tr>
                <td><code>/staff/email-queues/edit/{id}</code></td>
                <td>Edit a waiting row before it sends</td>
            </tr>
            <tr>
                <td><code>/staff/email-queues/send/{id}</code>, <code>send-all</code></td>
                <td>Real send: marks <code>sent</code> and stamps <code>sent_at</code></td>
            </tr>
            <tr>
                <td><code>/staff/email-queues/send-test/{id}</code>, <code>send-all-test</code></td>
                <td>Sends via the same utility but never marks <code>sent</code> — safe to repeat while debugging</td>
            </tr>
            <tr>
                <td><code>/staff/email-queues/remove/{id}</code>, <code>remove-all</code></td>
                <td>Soft delete only (<code>removed = true</code>) — never a hard delete</td>
            </tr>
        </tbody>
    </table>

    <h2 class="h4">Recipients &amp; Attachments</h2>
    <ul class="mb-4">
        <li><code>to</code>/<code>cc</code>/<code>bcc</code> are comma-delimited bare addresses — no display names.</li>
        <li>Attachments store a <code>tmp/</code>-relative <code>path</code> reference only (never webroot, never blob bytes), so they're never directly downloadable by URL and a failed send can be retried without re-uploading.</li>
        <li>No filename hashing in this MVP — original filenames are kept as-is; collisions are accepted.</li>
    </ul>

    <h2 class="h4">Language</h2>
    <p class="mb-4">
        <code>language</code> is metadata only — translation happens upstream, before the row is queued.
        It exists so the queue can be filtered by locale and so a future per-language footer has the
        information already available. Two-letter codes only, defaults to <code>en</code>.
    </p>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5">Enqueue from anywhere in the app</h2>
            <p>Pass <code>getUserId()</code> as <code>user_id</code> to record who queued the message — it is never the recipient.</p>
            <pre class="mb-0"><code>$this->EmailQueues->queueEmail(
    $to,
    $subject,
    $body,
    [
        'user_id' => $this->getUserId(),
        'cc' => $cc,
        'language' => 'en',
        'attachments' => $uploadedFiles,
    ]
);</code></pre>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5">Table response contract</h2>
            <p>Every public <code>EmailQueuesTable</code> method returns at minimum:</p>
            <pre class="mb-0"><code>['STATUS' => 200, 'MSG' => 'Email queued', 'id' => 42]</code></pre>
        </div>
    </div>
</div>
