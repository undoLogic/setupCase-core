<?php

$rootDir = dirname(__DIR__);
$sourceDir = $rootDir . '/codeBlocks/.github';
$targetDir = $rootDir . '/.github';

// CI is optional - not every project uses it
if (!is_dir($sourceDir)) {
    echo "No codeBlocks/.github folder — skipping CI setup<br/>";
    return;
}

echo "<h1 style='color: cornflowerblue;'>Installing GitHub CI workflow</h1>";

if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    echo "ERROR - Could not create .github folder<br/>";
    exit(1);
}

copyDirectoryContents($sourceDir, $targetDir);
echo "GitHub CI files installed to /.github<br/>";

function copyDirectoryContents(string $sourceDir, string $targetDir): void
{
    $items = scandir($sourceDir);

    if ($items === false) {
        echo "ERROR - Could not read source directory: {$sourceDir}<br/>";
        exit(1);
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $sourceDir . '/' . $item;
        $targetPath = $targetDir . '/' . $item;

        if (is_dir($sourcePath)) {
            if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                echo "ERROR - Could not create directory: {$targetPath}<br/>";
                exit(1);
            }

            copyDirectoryContents($sourcePath, $targetPath);
            continue;
        }

        if (!copy($sourcePath, $targetPath)) {
            echo "ERROR - Could not copy file: {$item}<br/>";
            exit(1);
        }

        echo "Copied: .github/" . ltrim(str_replace($targetDir, '', $targetPath), '/') . "<br/>";
    }
}

