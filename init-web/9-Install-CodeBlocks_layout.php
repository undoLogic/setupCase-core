<?php

if (file_exists(dirname(__DIR__) . '/sourceFiles/webroot/css/bootstrap.min.css')) {
    echo "<br/>Layout already exists — skipping<br/>";
} else {


//////////////////////////////////////////////////////////////////// LAYOUT ///////////////////////
    echo "<h1 style='color: cornflowerblue;'>Setting up Bootstrap Layout</h1>";
    $base = dirname(__DIR__) . '/sourceFiles/webroot/';

// Create folders if missing
    $dirs = [
        $base . 'css/',
        $base . 'js/',
        $base . 'icons/',
        $base . 'icons/fonts/'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

// Files to download (all MIT licensed)
    $files = [
        'css/bootstrap.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        'js/bootstrap.bundle.min.js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        //'icons/bootstrap-icons.css' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
        'icons/fonts/bootstrap-icons.woff' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff',
        'icons/fonts/bootstrap-icons.woff2' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2'
    ];

// Download each file
    foreach ($files as $local => $remote) {
        $target = $base . $local;
        $content = @file_get_contents($remote);
        if ($content === false) {
            echo "ERROR - Could not download: $remote<br/>";
            exit(1);
        }
        if (file_put_contents($target, $content) === false) {
            echo "ERROR - Could not save: $local<br/>";
            exit(1);
        }
        echo "✅ Saved: $local<br/><br/>";
    }
/////////////////////////////////////////////////////////// end layout ///////////////////////


}
