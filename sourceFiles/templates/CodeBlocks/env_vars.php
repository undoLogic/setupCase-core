<div class="card mb-4">
    <div class="card-body">
        <h2 class="h4">1. Server PHP_SETTINGS (prod)</h2>
        <p>Add the variable as a new line in <code>PHP_SETTINGS</code> (php.ini):</p>
        <pre class="mb-0"><code>server_VAR = your-value</code></pre>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h4">2. Docker environment (local)</h2>
        <p>Add the variable under <code>environment:</code> in <code>docker-compose.yml</code>:</p>
        <pre class="mb-0"><code>environment:
  docker_VAR: your-value</code></pre>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h4">3. PHP</h2>
        <p>Read the server value first, fall back to the Docker value, then to a default:</p>
        <pre><code><?= h("\$value = get_cfg_var('server_VAR')\n    ?: env('docker_VAR')\n        ?: 'defaultVal';") ?></code></pre>
        <ul class="mb-0">
            <li><code>get_cfg_var()</code> returns <code>false</code> and <code>env()</code> returns <code>null</code> when not set, so <code>?:</code> moves on to the next source.</li>
            <li>An empty value also counts as not set.</li>
            <li>Example: the database URLs in <code>config/app_DEV.php</code>.</li>
        </ul>
    </div>
</div>
