<?php

// CakePHP 5.x AppView helpers: Auth and Lang.
// App\View\Helper\HtmlHelper replaces the core Html helper automatically, so it
// needs no loading here.

$appViewFile = dirname(__DIR__, 2) . '/sourceFiles/src/View/AppView.php';

if (!file_exists($appViewFile)) {
    echo "ERROR - AppView.php not found";
    exit(1);
}

$contents = file_get_contents($appViewFile);

if ($contents === false) {
    echo "ERROR - Could not read AppView.php";
    exit(1);
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);

if (strpos($contents, "\$this->addHelper('Auth');") !== false) {
    echo "AppView helpers already exist — skipping<br/>";
    return;
}

$anchor = "    public function initialize(): void\n    {\n";
$helpers = "        \$this->addHelper('Auth');\n        \$this->addHelper('Lang');\n";
$contents = str_replace($anchor, $anchor . $helpers, $contents, $count);

if ($count !== 1) {
    echo "ERROR - initialize method anchor not found";
    exit(1);
}

file_put_contents($appViewFile, $contents);

echo "AppView helpers added successfully<br/><br/>";
