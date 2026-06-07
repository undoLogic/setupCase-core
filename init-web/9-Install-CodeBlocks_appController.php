<?php

$appControllerFile = dirname(__DIR__) . '/sourceFiles/src/Controller/AppController.php';

if (!file_exists($appControllerFile)) {
    echo "ERROR - AppController.php not found";
    exit;
}

$contents = file_get_contents($appControllerFile);

if ($contents === false) {
    echo "ERROR - Could not read AppController.php";
    exit;
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
    exit;
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

if (strpos($contents, "\$this->loadComponent('Authentication.Authentication');") === false) {
    $anchor = "\$this->loadComponent('Flash');\n";
    $contents = str_replace($anchor, $anchor . "        \$this->loadComponent('Authentication.Authentication');\n", $contents, $count);
    if ($count !== 1) {
        echo "ERROR - initialize anchor not found";
        exit;
    }
    $updated = true;
}

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

        //redirect older langs
        // $oldLangCheck = $this->request->getParam('language');
        // if ($oldLangCheck == 'eng') {
        //     $this->redirect(['language' => 'en']);
        // } elseif ($oldLangCheck == 'fre') {
        //     $this->redirect(['language' => 'fr']);
        // }

        //RBAC/Access middleware decides if they are allowed in - here we redirect if needed
        $accessGranted = $this->request->getAttribute('access_granted');
        if (!$accessGranted) {
            $this->Flash->error($this->request->getAttribute('access_msg'));
            $this->redirect($this->referer());
        } else {
            //We handle all RBAC from our RBAC middleware - disable the CakePHP authentication for all pages
            $this->Authentication->addUnauthenticatedActions([$this->request->getAttribute('params')['action']]);
        }
        $this->set('webroot', Router::url('/'));

        $token = $this->request->getAttribute('csrfToken');
        $this->set('csrf', $token);
    }

PHP;

$setupMenuBlock = <<<'PHP'

    private function setupMenu()
    {
        //dd($this->request);
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
                    [
                        'name' => 'Responsive Table',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'responsiveTable',
                        ],
                    ],
                    [
                        'name' => 'Upload File',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'uploadFile',
                        ],
                    ],
                    [
                        'name' => 'Read More Expand',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'readMore',
                        ],
                    ],
                    [
                        'name' => 'Sticky',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'sticky',
                        ],
                    ],
                    [
                        'name' => 'VueJS',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'vueJs',
                        ],
                    ],
                    [
                        'name' => 'Hide And Show Desktop And Mobile',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'hideAndShowDesktopAndMobile',
                        ],
                    ],
                    [
                        'name' => 'Lazy Loading Images',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'lazyLoadingImages',
                        ],
                    ],
                    [
                        'name' => 'Html Anchor',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'anchor',
                        ],
                    ],
                    [
                        'name' => 'Html Link',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'link',
                        ],
                    ],
                    [
                        'name' => 'Class Associations in Model',
                        'link' => [
                            'prefix' => false,
                            'controller' => 'CodeBlocks',
                            'action' => 'associations',
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
                    [
                        'name' => 'Audit Logs',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'AuditLogs',
                            'action' => 'index',
                        ],
                    ],
                    [
                        'name' => 'Automated Emailers',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'EmailQueues',
                            'action' => 'index',
                        ],
                    ],
                    [
                        'name' => 'Download To CSV',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'CodeBlocks',
                            'action' => 'downloadCsv',
                        ],
                    ],
                    [
                        'name' => 'Download To PDF',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'CodeBlocks',
                            'action' => 'downloadPdf/0',
                        ],
                    ],
                    [
                        'name' => 'Switch into user',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'Users',
                            'action' => 'switchToUser',
                        ],
                    ],
                    [
                        'name' => 'Reset Password',
                        'link' => [
                            'prefix' => 'Staff',
                            'controller' => 'Users',
                            'action' => 'resetPassword',
                        ],
                    ],
                    [
                        'name' => 'Edit - BelongsToManyAssociation',
                        'link' => [
                            'prefix' => 'Manager',
                            'controller' => 'Locations',
                            'action' => 'edit',
                            1,
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

        //dd($menu);
        $this->set('menu', $menu);
    }

PHP;

$writeToLogBlock = <<<'PHP'

    /**
     * Write message to a specific log configuration.
     *
     * @param string $whichLog   The log config name (as defined in app.php)
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

if (strpos($contents, 'public function beforeFilter(EventInterface $event)') === false) {
    $contents = $insertBeforeClassEnd($contents, $beforeFilterBlock, $count);
    if ($count !== 1) {
        echo "ERROR - class closing brace not found for beforeFilter";
        exit;
    }
    $updated = true;
}

if (strpos($contents, 'public function setupCase(') === false) {
    $contents = $insertBeforeClassEnd($contents, $setupCaseBlock, $count);
    if ($count !== 1) {
        echo "ERROR - class closing brace not found for setupCase";
        exit;
    }
    $updated = true;
}

if (strpos($contents, 'private function setupMenu()') === false) {
    $contents = $insertBeforeClassEnd($contents, $setupMenuBlock, $count);
    if ($count !== 1) {
        echo "ERROR - class closing brace not found for setupMenu";
        exit;
    }
    $updated = true;
}

if (strpos($contents, 'public function writeToLog(') === false) {
    $contents = $insertBeforeClassEnd($contents, $writeToLogBlock, $count);
    if ($count !== 1) {
        echo "ERROR - class closing brace not found for writeToLog";
        exit;
    }
    $updated = true;
}

if (!$updated) {
    echo "AppController SetupCase wiring already exists — skipping<br/>";
    exit;
}

file_put_contents($appControllerFile, $contents);

echo "AppController SetupCase wiring added successfully<br/><br/>";
