<?php

// CakePHP 5.x AppController wiring: Authentication component, setupCase()
// (temp password + SSL + view vars), the menu, getUserId() and writeToLog().
//
// Prefix access is NOT checked here. AccessMiddleware refuses a request before
// any controller runs. The component therefore never requires
// an identity itself: requireIdentity => false.
// The menu only lists pages that exist in codeBlocks/cakePHP/5.x/.

$appControllerFile = dirname(__DIR__, 2) . '/sourceFiles/src/Controller/AppController.php';

if (!file_exists($appControllerFile)) {
    echo "ERROR - AppController.php not found";
    exit(1);
}

$contents = file_get_contents($appControllerFile);

if ($contents === false) {
    echo "ERROR - Could not read AppController.php";
    exit(1);
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);
$updated = false;

$imports = [
    "use App\\Util\\SetupCase;",
    "use Cake\\Event\\EventInterface;",
    "use Cake\\Log\\Log;",
    "use Cake\\Routing\\Router;",
];

if (preg_match('/namespace App\\\\Controller;\n\n((?:use [^\n]+;\n)+)/', $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
    echo "ERROR - use import block not found";
    exit(1);
}

$importInsertPos = $matches[1][1] + strlen($matches[1][0]);

foreach ($imports as $import) {
    if (strpos($contents, $import) !== false) {
        continue;
    }

    $contents = substr($contents, 0, $importInsertPos) . $import . "\n" . substr($contents, $importInsertPos);
    $importInsertPos += strlen($import) + 1;
    $updated = true;
}

if (strpos($contents, "\$this->loadComponent('Authentication.Authentication'") === false) {
    $anchor = "\$this->loadComponent('Flash');\n";
    $component = "        \$this->loadComponent('Authentication.Authentication', ['requireIdentity' => false]);\n";
    $contents = str_replace($anchor, $anchor . $component, $contents, $count);
    if ($count !== 1) {
        echo "ERROR - initialize anchor not found";
        exit(1);
    }
    $updated = true;
}

$getUserIdBlock = <<<'PHP'

    public function getUserId()
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && $identity->get('id') !== null) {
            return $identity->get('id');
        }

        return false;
    }

PHP;

$beforeFilterBlock = <<<'PHP'

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->setupCase();
        $this->setupMenu();
    }

PHP;

$setupCaseBlock = <<<'PHP'

    public function setupCase()
    {
        $setupCase = new SetupCase();
        $setupCase->requirePasswordExcept(['www.LIVESITE.com', 'LIVESITE.com','testflight.setupcase.com'], $_SERVER, $this->request->getSession());
        $setupCase->requireSSLExcept([
            'localhost', //add other hosts which should NOT redict to SSL
        ], $this);

        $this->set('webroot', Router::url('/'));

        $token = $this->request->getAttribute('csrfToken');
        $this->set('csrf', $token);
    }

PHP;

$setupMenuBlock = <<<'PHP'

    private function setupMenu()
    {
        $prefix = $this->request->getParam('prefix');
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');

        $menu = [
            [
                'name' => 'Dashboard',
                'link' => ['prefix' => false, 'controller' => 'CodeBlocks', 'action' => 'index'],
            ],
            [
                'name' => 'Blocks',
                'children' => [
                    [
                        'name' => 'Application Foundation',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'applicationFoundation',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Blocks with DB',
                'children' => [
                    [
                        'name' => 'Application Foundation',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'CodeBlocks',
                            'action' => 'index',
                        ],
                    ],
                ],
            ],
        ];

        $matchesRoute = function (array $link) use ($prefix, $controller, $action): bool {
            // PREFIX logic
            if (array_key_exists('prefix', $link)) {
                // Explicitly NO prefix (false or null)
                if (($link['prefix'] === false || $link['prefix'] === null) && $prefix !== null) {
                    return false;
                }

                // Explicit prefix required
                if (is_string($link['prefix']) && $link['prefix'] !== $prefix) {
                    return false;
                }
            }

            // Controller / action
            if (($link['controller'] ?? null) !== $controller) {
                return false;
            }

            if (($link['action'] ?? null) !== $action) {
                return false;
            }

            return true;
        };

        foreach ($menu as &$item) {
            $item['active'] = false;

            // Check direct link
            if (isset($item['link']) && $matchesRoute($item['link'])) {
                $item['active'] = true;
            }

            // Check children
            if (isset($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $child['active'] = isset($child['link']) && $matchesRoute($child['link']);

                    // Bubble up active state to parent
                    if ($child['active']) {
                        $item['active'] = true;
                    }
                }
                unset($child);
            }
        }
        unset($item);

        $this->set('menu', $menu);
    }

PHP;

$writeToLogBlock = <<<'PHP'

    /**
     * Write message to the log.
     *
     * @param string $level      Log level (as defined in app.php)
     * @param string|array $message  Message or data to log
     * @param bool $newLine      Add separator newline after message
     * @return void
     */
    public function writeToLog(string $level, $message, bool $newLine = true): void
    {
        Log::write($level, $message);
    }

PHP;

$insertBeforeClassEnd = static function (string $input, string $block, int &$count): string {
    $count = 0;
    $pos = strrpos($input, "\n}");
    if ($pos === false) {
        return $input;
    }

    $count = 1;

    return substr($input, 0, $pos) . $block . substr($input, $pos);
};

$blocks = [
    'public function beforeFilter(EventInterface $event)' => $beforeFilterBlock,
    'public function getUserId(' => $getUserIdBlock,
    'public function setupCase(' => $setupCaseBlock,
    'private function setupMenu()' => $setupMenuBlock,
    'public function writeToLog(' => $writeToLogBlock,
];

foreach ($blocks as $marker => $block) {
    if (strpos($contents, $marker) !== false) {
        continue;
    }

    $contents = $insertBeforeClassEnd($contents, $block, $count);
    if ($count !== 1) {
        echo "ERROR - class closing brace not found for " . htmlspecialchars($marker, ENT_QUOTES, 'UTF-8');
        exit(1);
    }
    $updated = true;
}

if (!$updated) {
    echo "AppController SetupCase wiring already exists — skipping<br/>";
    return;
}

file_put_contents($appControllerFile, $contents);

echo "AppController SetupCase wiring added successfully<br/><br/>";
