<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\CronService;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class CronCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Run jobs from config/cron.php.')
            ->addArgument('action', [
                'help' => 'Action: status, run, or run_all.',
                'choices' => ['status', 'run', 'run_all'],
                'required' => true,
            ])
            ->addArgument('job', [
                'help' => 'Job key from config/cron.php. Required for action=run.',
                'required' => false,
            ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $service = new CronService();
        $response = $this->execute_action(
            $service,
            (string)$args->getArgument('action'),
            (string)($args->getArgument('job') ?? '')
        );

        $io->out(json_encode($response, JSON_PRETTY_PRINT));

        return $this->isSuccessResponse($response) ? static::CODE_SUCCESS : static::CODE_ERROR;
    }

    private function execute_action(CronService $service, string $action, string $job): array
    {
        if ($action === 'status') {
            return $this->execute_status($service);
        }
        if ($action === 'run_all') {
            return $service->runAll();
        }
        if ($job === '') {
            return ['STATUS' => 400, 'MSG' => 'Job argument is required for run.'];
        }

        return $service->run($job);
    }

    private function execute_status(CronService $service): array
    {
        return $service->status();
    }

    private function isSuccessResponse(array $response): bool
    {
        if (array_key_exists('healthy', $response) && !$response['healthy']) {
            return false;
        }
        if (!empty($response['failed_jobs'])) {
            return false;
        }

        $status = (int)($response['STATUS'] ?? 500);

        return $status >= 200 && $status < 300;
    }
}
