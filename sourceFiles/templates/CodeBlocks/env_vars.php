<div class="card mb-4">
    <div class="card-body">
        <h2 class="h4">Server PHP_SETTINGS</h2>
        <p>Add the variable as a new line in <code>PHP_SETTINGS</code>:</p>
        <pre class="mb-0"><code>VAR = "your-value"</code></pre>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h4">PHP</h2>
        <p>Read the value from the server configuration:</p>
        <pre class="mb-0"><code><?= h('$value = get_cfg_var(\'VAR\');') ?></code></pre>
    </div>
</div>
