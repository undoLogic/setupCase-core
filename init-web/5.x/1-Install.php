<?php

$phpBinary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';

function runInstallScript(string $title, string $scriptPath, string $phpBinary): void
{
    echo "<h1 style='color: cornflowerblue;'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1>";

    $output = [];
    $exitCode = 0;
    $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' 2>&1';
    exec($command, $output, $exitCode);

    // Sub-scripts are our own install scripts and already emit HTML
    foreach ($output as $line) {
        echo $line . "<br/>";
    }

    if ($exitCode !== 0) {
        echo "<br/><strong style='color:#b00020;'>Script failed with exit code {$exitCode}.</strong><br/>";
        exit($exitCode);
    }

    echo "<br/>";
}

runInstallScript('Running 2-Install-Cake.php', __DIR__ . '/2-Install-Cake.php', $phpBinary);
runInstallScript('Running 2-Install-CodeBlocks.php', __DIR__ . '/2-Install-CodeBlocks.php', $phpBinary);

echo "<script>window.location.href = '3-View-CodeBlocks.php';</script>";
echo "<noscript><a href='3-View-CodeBlocks.php'>Continue to CodeBlocks</a></noscript>";
