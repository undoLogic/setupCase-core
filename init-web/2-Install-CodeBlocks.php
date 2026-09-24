<?php

$rootDir = dirname(__DIR__);
$codeBlocksDir = $rootDir . '/codeBlocks/cakePHP/4.x/.';
$sourceFilesDir = $rootDir . '/sourceFiles/.';

if (!is_dir($rootDir . '/sourceFiles')) {
    echo "<h1 style='color: #b00020;'>sourceFiles does not exist</h1>";
    echo "Run init-web/1-Install-Cake.php first.<br/>";
    exit(1);
}

echo "<h1 style='color: cornflowerblue;'>Installing SetupCase CodeBlocks</h1>";
exec(
    // README.md / changeLog.md document CodeBlocks itself - they are not app files
    'rsync -av --no-perms --omit-dir-times --fake-super --exclude=/README.md --exclude=/changeLog.md ' .
    escapeshellarg($codeBlocksDir) . ' ' . escapeshellarg($sourceFilesDir),
    $install
);
echo implode("<br/>", $install);

$sourceGitignore = $rootDir . '/sourceFiles/.gitignore';
if (is_file($sourceGitignore)) {
    unlink($sourceGitignore);
    echo "<br/>Removed sourceFiles/.gitignore";
}

// The AGENTS.md CodeBlocks page shows this copy (sourceFiles is what gets uploaded)
if (!copy($rootDir . '/AGENTS.md', $rootDir . '/sourceFiles/AGENTS-copy.md')) {
    echo "<br/>ERROR - Could not copy AGENTS.md to sourceFiles/AGENTS-copy.md<br/>";
    exit(1);
}
echo "<br/>Copied AGENTS.md to sourceFiles/AGENTS-copy.md";

echo "<br/>";

include_once(__DIR__ . '/9-Install-CodeBlocks_layout.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_routes.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_bootstrap.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_application.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_middleware.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_appController.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_helpers.php');
include_once(__DIR__ . '/9-Install-CodeBlocks_app.php');
include_once(__DIR__ . '/9-install-CodeBlocks_citesting.php');
