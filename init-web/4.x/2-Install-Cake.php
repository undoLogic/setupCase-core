<?php

ini_set('max_execution_time', 300);

$rootDir = dirname(__DIR__, 2);
$sourceFilesDir = $rootDir . '/sourceFiles';

if (is_dir($sourceFilesDir)) {
    echo "<h1 style='color: #b95c00;'>Skipping install</h1>";
    echo "sourceFiles already exists at: " . htmlspecialchars($sourceFilesDir, ENT_QUOTES, 'UTF-8') . "<br/>";
    echo "Delete/move sourceFiles first if you need a fresh install.<br/>";
    exit(0);
}

if (!is_dir('/var/www/.composer')) {
    @mkdir('/var/www/.composer', 0755, true);
}

// Prevent Composer issues for web process
putenv('HOME=/var/www');
putenv('COMPOSER_HOME=/var/www/.composer');

function printCommandOutput(array $lines): void
{
    foreach ($lines as $line) {
        echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "<br/>";
    }
}

function runInstallCommand(string $title, string $command): void
{
    echo "<h1 style='color: cornflowerblue;'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1>";
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);
    printCommandOutput($output);

    if ($exitCode !== 0) {
        echo "<br/><strong style='color:#b00020;'>Command failed with exit code {$exitCode}.</strong><br/>";
        exit($exitCode);
    }
}

runInstallCommand(
    'Installing CakePHP',
    'composer create-project --prefer-dist cakephp/app:^4.0 ' . escapeshellarg($sourceFilesDir)
);

if (!is_dir($sourceFilesDir)) {
    echo "<br/><strong style='color:#b00020;'>sourceFiles directory was not created.</strong><br/>";
    exit(1);
}

runInstallCommand(
    'Installing authentication',
    'composer require "cakephp/authentication:^2.0" -d ' . escapeshellarg($sourceFilesDir)
);

echo "<br/><strong style='color:green;'>Install complete.</strong><br/>";
?>
