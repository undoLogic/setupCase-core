<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/requirements.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use App\Service\CronService;

(new Application(dirname(__DIR__) . '/config'))->bootstrap();

$cronService = new CronService();
$action = (string)($_GET['action'] ?? '');

switch ($action) {
    case 'status':
        cron_emit_json($cronService->status());
        break;
    case 'run_all':
        cron_require_allowed_ip($cronService);
        cron_emit_json($cronService->runAll());
        break;
    case 'run':
        cron_require_allowed_ip($cronService);
        $response = $cronService->run((string)($_GET['job'] ?? ''));
        if (($response['STATUS'] ?? 500) === 404) {
            cron_emit_404();
        }
        cron_emit_json($response);
        break;
    default:
        cron_emit_404();
}

function cron_require_allowed_ip(CronService $cronService): void
{
    if (!$cronService->isExecutionAllowed($_SERVER['REMOTE_ADDR'] ?? null)) {
        cron_emit_404();
    }
}

function cron_emit_json(array $response): void
{
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function cron_emit_404(): void
{
    http_response_code(404);
    exit;
}
