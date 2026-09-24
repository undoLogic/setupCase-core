<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

class CronService
{
    private array $config;

    private array $jobs;

    private string $statusPath;

    private DateTimeImmutable $generatedAt;

    private bool $debug;

    public function __construct(?array $config = null, ?DateTimeImmutable $generatedAt = null)
    {
        $this->debug = (bool)Configure::read('debug');
        $this->generatedAt = $generatedAt ?: new DateTimeImmutable();
        $this->config = $this->normalizeConfig($config === null ? $this->loadConfig() : $config);
        $this->jobs = $this->config['jobs'];
        $this->statusPath = $this->config['status_path'];
    }

    public function run(string $jobKey): array
    {
        if (!$this->canRunJob($jobKey)) {
            return $this->notFoundResponse();
        }

        return $this->run_executeJob($jobKey, $this->jobs[$jobKey]);
    }

    public function runAll(): array
    {
        $results = [];
        foreach ($this->jobs as $jobKey => $job) {
            $jobKey = (string)$jobKey;
            if ($this->jobIsEnabled($job)) {
                $results[$jobKey] = $this->runAll_runJob($jobKey, $job);
            }
        }

        return $this->runAll_response($results);
    }

    public function status(): array
    {
        $jobs = [];
        foreach ($this->jobs as $jobKey => $job) {
            $jobs[$jobKey] = $this->status_buildJobStatus((string)$jobKey, $job);
        }

        return $this->status_response($jobs);
    }

    private function loadConfig(): array
    {
        $file = CONFIG . 'cron.php';
        if (!is_file($file)) {
            return [];
        }

        $config = require $file;

        return is_array($config) ? $config : [];
    }

    private function normalizeConfig(array $config): array
    {
        $config += [
            'status_path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'setupcase-cron',
            'jobs' => [],
        ];
        $config['status_path'] = is_string($config['status_path']) && $config['status_path'] !== ''
            ? $config['status_path']
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'setupcase-cron';
        $config['jobs'] = $this->normalizeConfig_jobs($config['jobs']);

        return $config;
    }

    private function normalizeConfig_jobs($jobs): array
    {
        if (!is_array($jobs)) {
            return [];
        }
        foreach ($jobs as $jobKey => $job) {
            $jobs[$jobKey] = is_array($job) ? $job : [];
        }

        return $jobs;
    }

    private function canRunJob(string $jobKey): bool
    {
        if (!$this->isValidJobKey($jobKey) || empty($this->jobs[$jobKey])) {
            return false;
        }

        return $this->jobIsEnabled($this->jobs[$jobKey]);
    }

    private function run_executeJob(string $jobKey, array $job): array
    {
        try {
            $result = $this->normalizeJobResult($this->run_callJob($job));
            $heartbeatWritten = false;
            if ($this->isSuccessResponse($result)) {
                $heartbeatWritten = $this->writeHeartbeat($jobKey);
                $result = $this->run_mergeHeartbeatResult($result, $heartbeatWritten);
            }

            return $result + ['heartbeat_written' => $heartbeatWritten];
        } catch (Throwable $e) {
            return $this->exceptionResponse('Cron job failed.', $e) + ['heartbeat_written' => false];
        }
    }

    private function runAll_runJob(string $jobKey, array $job): array
    {
        if (!$this->isValidJobKey($jobKey)) {
            return [
                'STATUS' => 500,
                'MSG' => 'Invalid cron job key.',
                'heartbeat_written' => false,
            ];
        }

        return $this->run_executeJob($jobKey, $job);
    }

    private function run_callJob(array $job)
    {
        $model = (string)($job['model'] ?? '');
        $action = (string)($job['action'] ?? '');
        $table = $this->getTable($model);
        if ($action === '' || !$this->isPublicTableAction($table, $action)) {
            throw new \RuntimeException('Cron job action is not callable.');
        }

        return $table->{$action}();
    }

    private function isPublicTableAction(Table $table, string $action): bool
    {
        if (!method_exists($table, $action)) {
            return false;
        }

        return (new \ReflectionMethod($table, $action))->isPublic();
    }

    private function getTable(string $model): Table
    {
        if ($model === '') {
            throw new \RuntimeException('Cron job model is missing.');
        }

        return TableRegistry::getTableLocator()->get($model, ['allowFallbackClass' => false]);
    }

    private function normalizeJobResult($result): array
    {
        if (!is_array($result)) {
            return ['STATUS' => 500, 'MSG' => 'Cron job did not return a response array.'];
        }
        $result['STATUS'] = (int)($result['STATUS'] ?? 500);
        $result['MSG'] = (string)($result['MSG'] ?? 'Cron job completed.');

        return $result;
    }

    private function run_mergeHeartbeatResult(array $result, bool $heartbeatWritten): array
    {
        if ($heartbeatWritten) {
            return $result;
        }

        return [
            'STATUS' => 500,
            'MSG' => 'Cron job completed but heartbeat could not be written.',
        ];
    }

    private function runAll_response(array $results): array
    {
        return [
            'generated_at' => $this->generatedAt->format(DateTimeInterface::ATOM),
            'STATUS' => 200,
            'MSG' => 'Execution loop completed',
            'total_jobs' => count($this->jobs),
            'executed_jobs' => count($results),
            'successful_jobs' => $this->countResults($results, true),
            'failed_jobs' => $this->countResults($results, false),
            'jobs' => $results,
        ];
    }

    private function countResults(array $results, bool $success): int
    {
        $count = 0;
        foreach ($results as $result) {
            if ($this->isSuccessResponse($result) === $success) {
                $count++;
            }
        }

        return $count;
    }

    private function status_buildJobStatus(string $jobKey, array $job): array
    {
        $status = $this->status_baseJobStatus($jobKey, $job);
        if (!$this->isValidJobKey($jobKey)) {
            return $status + [
                    'healthy' => false,
                    'last_success' => null,
                    'age_seconds' => null,
                    'MSG' => 'Invalid cron job key.',
                ];
        }
        if (!$this->jobIsEnabled($job) || empty($job['monitor'])) {
            return $status + ['healthy' => null, 'last_success' => null, 'age_seconds' => null];
        }

        return $status + $this->status_heartbeatStatus($jobKey, $job);
    }

    private function status_baseJobStatus(string $jobKey, array $job): array
    {
        return [
            'enabled' => $this->jobIsEnabled($job),
            'command' => $this->status_jobCommand($jobKey),
            'description' => (string)($job['description'] ?? ''),
            'schedule' => (string)($job['schedule'] ?? ''),
            'max_age' => (int)($job['max_age'] ?? 0),
            'monitor' => !empty($job['monitor']),
        ];
    }

    private function status_heartbeatStatus(string $jobKey, array $job): array
    {
        $lastSuccess = $this->readHeartbeat($jobKey);
        if ($lastSuccess === null) {
            return ['healthy' => false, 'last_success' => null, 'age_seconds' => null];
        }

        $age = $this->generatedAt->getTimestamp() - $lastSuccess->getTimestamp();

        return [
            'healthy' => $age <= (int)$job['max_age'],
            'last_success' => $lastSuccess->format(DateTimeInterface::ATOM),
            'age_seconds' => $age,
        ];
    }

    private function status_response(array $jobs): array
    {
        $enabledJobs = array_filter($jobs, static fn(array $job): bool => $job['enabled']);
        $monitoredJobs = array_filter($jobs, static fn(array $job): bool => $job['enabled'] && $job['monitor']);
        $healthyJobs = array_filter($monitoredJobs, static fn(array $job): bool => $job['healthy'] === true);
        $failedJobKeys = array_keys(array_filter($monitoredJobs, static fn(array $job): bool => $job['healthy'] !== true));
        $failedJobs = count($monitoredJobs) - count($healthyJobs);

        return [
            'STATUS' => $failedJobs === 0 ? 200 : 500,
            'MSG' => $failedJobs === 0 ? 'Cron jobs healthy' : 'Cron jobs unhealthy',
            'healthy' => $failedJobs === 0,
            'failed_jobs' => $failedJobs,
            'failed_jobs_text' => implode(', ', $failedJobKeys),
            'generated_at' => $this->generatedAt->format(DateTimeInterface::ATOM),
            'status_path' => $this->statusPath,
            'available_commands' => $this->status_availableCommands($jobs),
            'total_jobs' => count($jobs),
            'enabled_jobs' => count($enabledJobs),
            'monitored_jobs' => count($monitoredJobs),
            'healthy_jobs' => count($healthyJobs),
            'jobs' => $jobs,
        ];
    }

    private function status_availableCommands(array $jobs): array
    {
        return [
            'status' => './bin/cake cron status',
            //'note' => 'Use one command per server cron entry when scheduling jobs separately.',
            'run_all' => $this->status_separateJobCommands($jobs),
        ];
    }

    private function status_separateJobCommands(array $jobs): array
    {
        $commands = [];
        foreach ($jobs as $jobKey => $job) {
            $commands[$jobKey] = [
                'enabled' => !empty($job['enabled']),
                'command' => $this->status_jobCommand((string)$jobKey),
            ];
        }

        return $commands;
    }

    private function status_jobCommand(string $jobKey): string
    {
        return './bin/cake cron run ' . $jobKey;
    }

    private function writeHeartbeat(string $jobKey): bool
    {
        if (!$this->ensureStatusPath()) {
            return false;
        }

        $timestamp = $this->generatedAt->format(DateTimeInterface::ATOM);

        return @file_put_contents($this->heartbeatPath($jobKey), $timestamp) !== false;
    }

    private function readHeartbeat(string $jobKey): ?DateTimeImmutable
    {
        $file = $this->heartbeatPath($jobKey);
        if (!is_readable($file)) {
            return null;
        }
        $contents = trim((string)@file_get_contents($file));
        if ($contents === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($contents);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function ensureStatusPath(): bool
    {
        if (is_dir($this->statusPath)) {
            return is_writable($this->statusPath);
        }

        return @mkdir($this->statusPath, 0775, true) && is_writable($this->statusPath);
    }

    private function heartbeatPath(string $jobKey): string
    {
        return rtrim($this->statusPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobKey . '.status';
    }

    private function jobIsEnabled(array $job): bool
    {
        return !empty($job['enabled']);
    }

    private function isValidJobKey(string $jobKey): bool
    {
        return (bool)preg_match('/^[a-z0-9_]+$/', $jobKey);
    }

    private function isSuccessResponse(array $result): bool
    {
        $status = (int)($result['STATUS'] ?? 500);

        return $status >= 200 && $status < 300;
    }

    private function notFoundResponse(): array
    {
        return ['STATUS' => 404, 'MSG' => 'Not found'];
    }

    private function exceptionResponse(string $message, Throwable $e): array
    {
        if ($this->debug) {
            $message .= ' ' . $e->getMessage();
        }

        return ['STATUS' => 500, 'MSG' => $message];
    }
}
