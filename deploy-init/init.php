<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

$base = '/home/undoweb';
$deployDir = "$base/deploy";

if (!is_dir($deployDir)) {
    mkdir($deployDir, 0755, true);
}

$repo = 'https://github.com/undoLogic/setupCase-core.git';
//$branch = 'main';
$branch = 'devJan13';

$destination = '/home/undoweb/www/codeblocks/release1';

$cmds = [
    "rm -rf $destination",
    "cd $base",
    "git clone --depth=1 $repo --branch $branch --single-branch $destination",
    "cd $destination/sourceFiles && composer install",
    //"php deploy/deploy.php dev"
];

foreach ($cmds as $cmd) {
    echo "→ $cmd\n";
    passthru($cmd, $exit);
    if ($exit !== 0) {
        exit("FAILED\n");
    }
}

echo "Bootstrap complete\n";