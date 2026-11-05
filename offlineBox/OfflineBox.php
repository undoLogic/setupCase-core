<?php
// install
//composer require aws/aws-sdk-php

namespace offlineBox;

use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\MultipartUploader;

/**
 * Self-contained, portable backup utility. Intended to be copied as-is into
 * other CakePHP/PHP projects: it has no dependency on any other Util/Table
 * class in this project, and takes its S3 credentials via the constructor
 * rather than reading them itself, so each project can source that config
 * however it already does secrets.
 */
class OfflineBox
{
    // Private data to store sensitive information
    private $spaceKey;
    private $spaceSecret;
    private $spaceRegion;
    private $spaceName;
    private $s3;

    // mysqlDump() working state
    private $dumpConnectionName;
    private $dumpConnection;
    private $dumpTablesFull; // structure + data; null means "whole database, no filtering"
    private $dumpTablesStructureOnly; // excluded tables: structure kept, data dropped
    private $dumpCredentialsFile;
    private $dumpFile;
    private $dumpCommand;

    /**
     * @param array $config Required keys: spaceKey, spaceSecret, spaceRegion, spaceName.
     */
    public function __construct(array $config)
    {
        $this->__construct_validateConfig($config);

        $this->spaceKey = $config['spaceKey'];
        $this->spaceSecret = $config['spaceSecret'];
        $this->spaceRegion = $config['spaceRegion'];
        $this->spaceName = $config['spaceName'];

        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => $this->spaceRegion,
            'endpoint' => 'https://' . $this->spaceRegion . '.digitaloceanspaces.com',
            'credentials' => [
                'key' => $this->spaceKey,
                'secret' => $this->spaceSecret,
            ],
        ]);
    }

    private function __construct_validateConfig(array $config): void
    {
        foreach (['spaceKey', 'spaceSecret', 'spaceRegion', 'spaceName'] as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("OfflineBox config missing required key: {$key}");
            }
        }
    }

    /**
     * Dumps one database connection to a local .sql file.
     *
     * @param string $connectionName CakePHP connection name (e.g. 'default').
     * @param string $filenamePrefix Optional prefix for the dump filename.
     * @param array $tableConfig ['include' => '*'|string|array, 'exclude' => string|array].
     *   'exclude' drops DATA only - the table's structure is still dumped (empty),
     *   so software relying on the table existing still works against the restored backup.
     */
    public function mysqlDump($connectionName = 'default', $filenamePrefix = '', array $tableConfig = [])
    {
        Log::debug('Starting mysqlDump: ' . $connectionName);

        $this->dumpConnectionName = $connectionName;
        $this->mysqlDump_resolveConnection($connectionName);
        $this->mysqlDump_resolveTables($connectionName, $tableConfig);

        $resolvedNothing = $this->dumpTablesFull !== null
            && empty($this->dumpTablesFull)
            && empty($this->dumpTablesStructureOnly);

        if ($resolvedNothing) {
            return [
                'STATUS' => 500,
                'MSG' => 'mysqlDump aborted: include/exclude configuration resolved to zero tables for ' . $connectionName,
            ];
        }

        $this->mysqlDump_writeCredentialsFile();
        $this->mysqlDump_buildCommand($filenamePrefix);

        $res = $this->mysqlDump_execute();

        Log::debug('mysqlDump completed: ' . $res['MSG']);

        return $res;
    }

    private function mysqlDump_resolveConnection($connectionName): void
    {
        $connection = ConnectionManager::get($connectionName);
        $config = $connection->config();

        $this->dumpConnection = [
            'host' => $config['host'],
            'port' => $config['port'] ?? 3306,
            'username' => $config['username'],
            'password' => $config['password'],
            'database' => $config['database'],
        ];
    }

    /**
     * Resolves include/exclude config into two table sets:
     * - dumpTablesFull: structure + data (null means "whole database", no filtering).
     * - dumpTablesStructureOnly: excluded tables that stay in the dump as empty,
     *   structure-only tables rather than disappearing entirely.
     */
    private function mysqlDump_resolveTables($connectionName, array $tableConfig): void
    {
        $include = $tableConfig['include'] ?? '*';
        $exclude = $this->mysqlDump_parseTableList($tableConfig['exclude'] ?? '');

        $isWildcard = is_string($include) && trim($include) === '*';

        if ($isWildcard && empty($exclude)) {
            $this->dumpTablesFull = null;
            $this->dumpTablesStructureOnly = [];
            return;
        }

        $included = $isWildcard
            ? $this->mysqlDump_expandWildcardTables($connectionName)
            : $this->mysqlDump_parseTableList($include);

        $this->dumpTablesStructureOnly = array_values(array_intersect($included, $exclude));
        $this->dumpTablesFull = array_values(array_diff($included, $exclude));
    }

    /**
     * Splits a newline-separated (or array) table list into a trimmed,
     * de-duplicated array. Supports \r\n and \n line endings so lists
     * pasted directly from phpMyAdmin work without manual conversion.
     */
    private function mysqlDump_parseTableList($value): array
    {
        $lines = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string)$value);

        $tables = [];
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line !== '') {
                $tables[$line] = $line;
            }
        }

        return array_values($tables);
    }

    private function mysqlDump_expandWildcardTables($connectionName): array
    {
        $connection = ConnectionManager::get($connectionName);
        $rows = $connection->execute('SHOW TABLES')->fetchAll();

        $tables = [];
        foreach ($rows as $row) {
            $tables[] = (string)reset($row);
        }

        return $tables;
    }

    /**
     * Writes a 0600 mysqldump --defaults-extra-file so the DB password
     * never appears on the command line / process list.
     */
    private function mysqlDump_writeCredentialsFile(): void
    {
        $file = TMP . 'offlinebox_' . uniqid('', true) . '.cnf';

        $contents = "[client]\n"
            . 'user=' . $this->dumpConnection['username'] . "\n"
            . 'password=' . $this->dumpConnection['password'] . "\n"
            . 'host=' . $this->dumpConnection['host'] . "\n"
            . 'port=' . $this->dumpConnection['port'] . "\n";

        file_put_contents($file, $contents);
        chmod($file, 0600);

        $this->dumpCredentialsFile = $file;
    }

    /**
     * Builds the dump file. Writes a CREATE DATABASE IF NOT EXISTS / USE
     * header directly (not via `mysqldump --databases`, which is NOT
     * combinable with an explicit table list - it would reinterpret our
     * table names as extra database names), then chains mysqldump pass(es)
     * appending to the same file: a full pass for dumpTablesFull
     * (structure + data), then a --no-data pass for dumpTablesStructureOnly,
     * so excluded tables land in the file as empty tables rather than being
     * left out entirely. The result restores cleanly with `mysql < dump.sql`
     * against an empty server.
     */
    private function mysqlDump_buildCommand($filenamePrefix): void
    {
        $date = date('Y-m-d');
        $version = date('His');
        $this->dumpFile = TMP . $date . '_' . $filenamePrefix . $this->dumpConnectionName . '_' . $version . '.sql';

        $this->mysqlDump_writeDatabaseCreateHeader();

        $passes = [];
        if ($this->dumpTablesFull === null || !empty($this->dumpTablesFull)) {
            $passes[] = $this->mysqlDump_buildFullPass();
        }
        if (!empty($this->dumpTablesStructureOnly)) {
            $passes[] = $this->mysqlDump_buildStructureOnlyPass();
        }

        $redirected = array_map(function ($command) {
            return $command . ' >> ' . escapeshellarg($this->dumpFile);
        }, $passes);

        $this->dumpCommand = implode(' && ', $redirected);
    }

    private function mysqlDump_writeDatabaseCreateHeader(): void
    {
        $database = $this->dumpConnection['database'];

        file_put_contents($this->dumpFile, "CREATE DATABASE IF NOT EXISTS `{$database}`;\nUSE `{$database}`;\n\n");
    }

    private function mysqlDump_buildFullPass(): string
    {
        $command = 'mysqldump --defaults-extra-file=' . escapeshellarg($this->dumpCredentialsFile)
            . ' ' . escapeshellarg($this->dumpConnection['database']);

        if ($this->dumpTablesFull !== null) {
            foreach ($this->dumpTablesFull as $table) {
                $command .= ' ' . escapeshellarg($table);
            }
        }

        return $command;
    }

    private function mysqlDump_buildStructureOnlyPass(): string
    {
        $command = 'mysqldump --defaults-extra-file=' . escapeshellarg($this->dumpCredentialsFile)
            . ' --no-data ' . escapeshellarg($this->dumpConnection['database']);

        foreach ($this->dumpTablesStructureOnly as $table) {
            $command .= ' ' . escapeshellarg($table);
        }

        return $command;
    }

    private function mysqlDump_execute(): array
    {
        try {
            exec($this->dumpCommand, $output, $returnVar);
        } finally {
            @unlink($this->dumpCredentialsFile);
        }

        if ($returnVar !== 0) {
            return [
                'STATUS' => 500,
                'MSG' => 'mysqldump failed: ' . $this->dumpFile,
            ];
        }

        return [
            'STATUS' => 200,
            'MSG' => 'mysqldump completed: ' . $this->dumpFile,
            'file' => $this->dumpFile,
            'pathInfo' => pathinfo($this->dumpFile),
        ];
    }

    public function upload($filename, $keyName, $contentMD5 = false)
    {
        Log::debug('Starting upload: ' . $keyName);

        try {
            $options = [
                'Bucket' => $this->spaceName,
                'Key' => $keyName,
                'SourceFile' => $filename,
                'ACL' => 'private',
            ];
            if ($contentMD5) {
                $options['ContentMD5'] = $contentMD5;
            }

            $result = $this->s3->putObject($options);

            $verified = $this->upload_verify($filename, $keyName);
            if ($verified['STATUS'] !== 200) {
                Log::error($verified['MSG']);
                return $verified;
            }

            $res = [
                'STATUS' => 200,
                'MSG' => "File uploaded successfully. URL: {$result['ObjectURL']}",
            ];
        } catch (AwsException $e) {
            Log::error($e->getMessage());

            $res = [
                'STATUS' => 500,
                'MSG' => json_encode($e->getMessage()),
            ];
        }

        Log::debug('upload completed');

        return $res;
    }

    /**
     * Confirms the uploaded object matches the local file (size, and
     * MD5/ETag for non-multipart uploads) rather than assuming a
     * successful putObject() call means the backup is intact.
     */
    private function upload_verify($filename, $keyName): array
    {
        $result = $this->s3->headObject([
            'Bucket' => $this->spaceName,
            'Key' => $keyName,
        ]);

        if ((int)$result['ContentLength'] !== filesize($filename)) {
            return [
                'STATUS' => 500,
                'MSG' => 'Upload verification failed. Local file size does not match remote object size.',
            ];
        }

        $etag = strtolower(trim($result['ETag'], '"'));
        if (!str_contains($etag, '-') && $etag !== strtolower(md5_file($filename))) {
            return [
                'STATUS' => 500,
                'MSG' => 'Upload verification failed. Local MD5 does not match remote ETag.',
            ];
        }

        return [
            'STATUS' => 200,
            'MSG' => 'Upload verified',
        ];
    }

    /**
     * Multipart upload for large dumps.
     */
    public function uploadLarge($filePath, $keyName)
    {
        Log::debug('Starting uploadLarge: ' . $keyName);

        try {
            $uploader = new MultipartUploader($this->s3, $filePath, [
                'bucket' => $this->spaceName,
                'key' => $keyName,
                'acl' => 'private',
            ]);

            $result = $uploader->upload();

            $res = [
                'STATUS' => 200,
                'MSG' => "File uploaded successfully. URL: {$result['ObjectURL']}",
            ];
        } catch (AwsException $e) {
            Log::error($e->getMessage());

            $res = [
                'STATUS' => 500,
                'MSG' => json_encode($e->getMessage()),
            ];
        }

        Log::debug('uploadLarge completed');

        return $res;
    }
}
