<?php
/**
 * OfflineBox by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */

// Requires: composer require aws/aws-sdk-php
// Spec: offlineBox/README.md in SetupCase core. Copy this class unchanged.

namespace App\Util;

use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

/**
 * Self-contained backup utility. It has no dependency on any other class in
 * the host project, and takes its S3 credentials as arguments so each project
 * can source them however it already does secrets.
 *
 * mysqlDump() and upload() only run on the CLI. validate() runs anywhere: it
 * is read-only, so the admin page can use it.
 */
class OfflineBox
{
    /** Where backups live in the bucket. The local client reads the same prefix. */
    public const KEY_PREFIX = 'backups/';

    /** A newest backup older than this shows as a problem in validate(). */
    private const MAX_BACKUP_AGE_DAYS = 2;

    private const MIN_PHP_VERSION = '8.2.0';

    private const DUMP_FLAGS = '--single-transaction --no-tablespaces --default-character-set=utf8mb4';

    private $spaceName;
    private $s3;

    // mysqlDump() working state
    private $dumpConnectionName;
    private $dumpConnection;
    private $dumpDatabaseHeader;
    private $dumpTablesFull; // structure + data; null means "whole database, no filtering"
    private $dumpTablesStructureOnly; // excluded tables: structure kept, data dropped
    private $dumpCredentialsFile;
    private $dumpErrorFile;
    private $dumpFile;
    private $dumpCommand;

    /**
     * @param array $config Required: spaceKey, spaceSecret, spaceRegion, spaceName.
     *   Optional: spaceEndpoint (defaults to DigitalOcean Spaces for the region).
     */
    public function __construct(array $config)
    {
        foreach (self::requiredS3Keys() as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("OfflineBox config missing required key: {$key}");
            }
        }

        $this->spaceName = $config['spaceName'];
        $this->s3 = self::s3Client($config);
    }

    /**
     * Dumps one database connection to a local .sql file. CLI only.
     *
     * @param string $connectionName CakePHP connection name (e.g. 'default').
     * @param string $filenamePrefix Optional prefix for the dump filename.
     * @param array $tableConfig ['include' => '*'|string|array, 'exclude' => string|array].
     *   'exclude' drops DATA only - the table's structure is still dumped (empty),
     *   so software relying on the table existing still works against the restored backup.
     */
    public function mysqlDump($connectionName = 'default', $filenamePrefix = '', array $tableConfig = [])
    {
        self::requireCli('mysqlDump');
        Log::debug('Starting mysqlDump: ' . $connectionName);

        $this->dumpConnectionName = $connectionName;
        $this->dumpCredentialsFile = null;
        $this->dumpErrorFile = null;
        $this->dumpFile = null;

        try {
            $res = $this->mysqlDump_run($connectionName, $filenamePrefix, $tableConfig);
        } catch (\Throwable $e) {
            $res = ['STATUS' => 500, 'MSG' => 'mysqlDump failed for ' . $connectionName . ': ' . $e->getMessage()];
        }

        $this->mysqlDump_cleanUp($res['STATUS'] === 200);
        Log::debug('mysqlDump completed: ' . $res['MSG']);

        return $res;
    }

    private function mysqlDump_run($connectionName, $filenamePrefix, array $tableConfig): array
    {
        $connection = ConnectionManager::get($connectionName);
        $this->mysqlDump_resolveConnection($connection);

        $tables = self::resolveTables($connection, $tableConfig);
        if (!empty($tables['missing'])) {
            return [
                'STATUS' => 500,
                'MSG' => 'mysqlDump aborted: exclude names no such table: ' . implode(', ', $tables['missing']),
            ];
        }

        $this->dumpTablesFull = $tables['full'];
        $this->dumpTablesStructureOnly = $tables['structure'];

        if ($this->dumpTablesFull !== null && empty($this->dumpTablesFull) && empty($this->dumpTablesStructureOnly)) {
            return [
                'STATUS' => 500,
                'MSG' => 'mysqlDump aborted: include/exclude configuration resolved to zero tables for ' . $connectionName,
            ];
        }

        $this->mysqlDump_resolveDatabaseHeader($connection);
        $this->mysqlDump_writeCredentialsFile();
        $this->mysqlDump_buildCommand($filenamePrefix);

        return $this->mysqlDump_execute();
    }

    private function mysqlDump_resolveConnection($connection): void
    {
        $config = $connection->config();

        $this->dumpConnection = [
            'host' => $config['host'] ?? 'localhost',
            'port' => $config['port'] ?? 3306,
            'socket' => $config['unix_socket'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'database' => $config['database'],
        ];
    }

    /**
     * Reads the source database's charset, so a restore doesn't fall back to
     * whatever the new server defaults to.
     */
    private function mysqlDump_resolveDatabaseHeader($connection): void
    {
        $database = $this->dumpConnection['database'];
        $row = $connection->execute(
            'SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation_name
             FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$database]
        )->fetch('assoc');

        if (empty($row)) {
            throw new \RuntimeException('database not found on the server: ' . $database);
        }

        $quoted = '`' . str_replace('`', '``', $database) . '`';
        $this->dumpDatabaseHeader = "CREATE DATABASE IF NOT EXISTS {$quoted}"
            . " CHARACTER SET {$row['charset']} COLLATE {$row['collation_name']};\n"
            . "USE {$quoted};\n\n";
    }

    /**
     * Writes a 0600 mysqldump --defaults-extra-file so the DB password
     * never appears on the command line / process list. Values are quoted, as
     * an unquoted # or space in an option file breaks the login.
     */
    private function mysqlDump_writeCredentialsFile(): void
    {
        $connection = $this->dumpConnection;

        $lines = ['[client]'];
        $lines[] = 'user=' . $this->mysqlDump_quoteOption($connection['username']);
        $lines[] = 'password=' . $this->mysqlDump_quoteOption($connection['password']);
        $lines[] = 'host=' . $this->mysqlDump_quoteOption($connection['host']);
        $lines[] = 'port=' . (int)$connection['port'];
        if ($connection['socket'] !== '') {
            $lines[] = 'socket=' . $this->mysqlDump_quoteOption($connection['socket']);
        }

        $this->dumpCredentialsFile = $this->mysqlDump_createPrivateFile(
            TMP . 'offlinebox_' . uniqid('', true) . '.cnf',
            implode("\n", $lines) . "\n"
        );
    }

    private function mysqlDump_quoteOption($value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string)$value) . '"';
    }

    /**
     * Creates the file as 0600 from the first byte, so it is never readable
     * by anyone else, not even between creating it and a later chmod().
     */
    private function mysqlDump_createPrivateFile(string $file, string $contents): string
    {
        $umask = umask(0077);
        $written = file_put_contents($file, $contents);
        umask($umask);

        if ($written === false) {
            throw new \RuntimeException('could not write to ' . $file);
        }

        return $file;
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
        $name = $date . '_' . $filenamePrefix . $this->dumpConnectionName . '_' . $version . '.sql';

        $this->dumpFile = $this->mysqlDump_createPrivateFile(TMP . $name, $this->dumpDatabaseHeader);
        $this->dumpErrorFile = $this->mysqlDump_createPrivateFile(TMP . $name . '.err', '');

        $passes = [];
        if ($this->dumpTablesFull === null || !empty($this->dumpTablesFull)) {
            $passes[] = $this->mysqlDump_buildPass('', $this->dumpTablesFull ?? []);
        }
        if (!empty($this->dumpTablesStructureOnly)) {
            $passes[] = $this->mysqlDump_buildPass('--no-data', $this->dumpTablesStructureOnly);
        }

        // Routines belong to the database, not a table: dump them once.
        $passes[0] = str_replace('--no-tablespaces', '--no-tablespaces --routines', $passes[0]);

        $redirected = array_map(function ($command) {
            return $command . ' >> ' . escapeshellarg($this->dumpFile)
                . ' 2>> ' . escapeshellarg($this->dumpErrorFile);
        }, $passes);

        $this->dumpCommand = implode(' && ', $redirected);
    }

    private function mysqlDump_buildPass(string $extraFlag, array $tables): string
    {
        // --defaults-extra-file must be the first option, or mysqldump rejects it.
        $command = 'mysqldump --defaults-extra-file=' . escapeshellarg($this->dumpCredentialsFile)
            . ' ' . self::DUMP_FLAGS
            . ($extraFlag !== '' ? ' ' . $extraFlag : '')
            . ' ' . escapeshellarg($this->dumpConnection['database']);

        foreach ($tables as $table) {
            $command .= ' ' . escapeshellarg($table);
        }

        return $command;
    }

    private function mysqlDump_execute(): array
    {
        $output = [];
        $returnVar = 1;
        exec($this->dumpCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            $error = trim(substr((string)file_get_contents($this->dumpErrorFile), 0, 500));

            return [
                'STATUS' => 500,
                'MSG' => 'mysqldump failed for ' . $this->dumpConnectionName . ': ' . ($error ?: 'exit code ' . $returnVar),
            ];
        }

        return [
            'STATUS' => 200,
            'MSG' => 'mysqldump completed: ' . $this->dumpFile,
            'file' => $this->dumpFile,
            'pathInfo' => pathinfo($this->dumpFile),
            'database' => $this->dumpConnection['database'],
        ];
    }

    /**
     * Removes the credentials and error files always, and a partial dump on
     * failure. A failed dump returns no 'file', so only this class can.
     */
    private function mysqlDump_cleanUp(bool $succeeded): void
    {
        $files = [$this->dumpCredentialsFile, $this->dumpErrorFile];
        if (!$succeeded) {
            $files[] = $this->dumpFile;
        }

        foreach ($files as $file) {
            if ($file !== null && is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Uploads as a private object and confirms it arrived intact. CLI only.
     * ObjectUploader streams from disk and switches to multipart for large
     * files, so this works for any dump size.
     */
    public function upload($filename, $keyName)
    {
        self::requireCli('upload');
        Log::debug('Starting upload: ' . $keyName);

        $stream = null;

        try {
            $stream = fopen($filename, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('could not open ' . $filename);
            }

            $uploader = new ObjectUploader($this->s3, $this->spaceName, $keyName, $stream, 'private');
            $result = $uploader->upload();

            $res = $this->upload_verify($filename, $keyName);
            if ($res['STATUS'] === 200) {
                $res['MSG'] = "File uploaded and verified. URL: {$result['ObjectURL']}";
            }
        } catch (\Throwable $e) {
            $res = ['STATUS' => 500, 'MSG' => 'Upload failed: ' . $e->getMessage()];
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($res['STATUS'] !== 200) {
            Log::error('[OfflineBox] ' . $res['MSG']);
        }
        Log::debug('upload completed');

        return $res;
    }

    /**
     * Confirms the uploaded object matches the local file (size, and
     * MD5/ETag for non-multipart uploads) rather than assuming a
     * successful upload means the backup is intact.
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

        // A multipart ETag (contains "-") is not an MD5, so those verify by size only.
        $etag = strtolower(trim($result['ETag'], '"'));
        if (!str_contains($etag, '-') && $etag !== strtolower(md5_file($filename))) {
            return [
                'STATUS' => 500,
                'MSG' => 'Upload verification failed. Local MD5 does not match remote ETag.',
            ];
        }

        return ['STATUS' => 200, 'MSG' => 'Upload verified'];
    }

    /**
     * Reports whether the setup is valid and what will be exported. Read-only:
     * it never dumps and writes nothing to disk or the bucket. Each check turns
     * a failure into a 'problem' row instead of throwing.
     *
     * @param array $connections Same shape the command backs up: name => table rules.
     * @param array $s3Config Same keys as the constructor.
     * @return array STATUS, MSG, counts, databases, environment, storage.
     *   Each row holds a 'level' of ok, warning or problem.
     */
    public static function validate(array $connections, array $s3Config): array
    {
        $databases = self::validate_databases($connections);
        $environment = self::validate_environment($databases);
        $storage = self::validate_storage($s3Config);

        $levels = array_merge(
            array_column($databases, 'level'),
            array_column($environment, 'level'),
            array_column($storage, 'level')
        );
        $counts = array_merge(['ok' => 0, 'warning' => 0, 'problem' => 0], array_count_values($levels));

        return [
            'STATUS' => $counts['problem'] === 0 ? 200 : 500,
            'MSG' => $counts['problem'] === 0 ? 'Ready' : 'Problems found',
            'counts' => $counts,
            'databases' => $databases,
            'environment' => $environment,
            'storage' => $storage,
        ];
    }

    private static function validate_databases(array $connections): array
    {
        $names = array_unique(array_merge(array_keys($connections), ConnectionManager::configured()));

        $rows = [];
        foreach ($names as $name) {
            $rows[] = array_key_exists($name, $connections)
                ? self::validate_exportedDatabase($name, $connections[$name])
                : self::validate_databaseRow($name, ConnectionManager::getConfig($name)['database'] ?? '', false);
        }

        return $rows;
    }

    private static function validate_databaseRow(string $name, string $database, bool $willExport): array
    {
        return [
            'connection' => $name,
            'database' => $database,
            'willExport' => $willExport,
            'level' => 'ok',
            'connects' => $willExport ? 'Not checked' : 'Not checked: not exported',
            'version' => '',
            'tablesWithData' => 0,
            'tablesStructureOnly' => 0,
            'estimatedBytes' => 0,
            'size' => '',
            'tables' => [],
            'messages' => [],
        ];
    }

    private static function validate_exportedDatabase(string $name, array $tableConfig): array
    {
        $row = self::validate_databaseRow($name, '', true);

        try {
            $connection = ConnectionManager::get($name);
            $row['database'] = (string)($connection->config()['database'] ?? '');
            if (!str_contains((string)($connection->config()['driver'] ?? ''), 'Mysql')) {
                throw new \RuntimeException('only MySQL connections can be exported');
            }

            $row['version'] = (string)$connection->execute('SELECT VERSION()')->fetch()[0];
            $row['connects'] = 'OK';
            $row = self::validate_tables($row, $connection, $tableConfig);
        } catch (\Throwable $e) {
            $row['level'] = 'problem';
            $row['connects'] = 'Failed';
            $row['messages'][] = $e->getMessage();
        }

        return $row;
    }

    private static function validate_tables(array $row, $connection, array $tableConfig): array
    {
        $rules = self::resolveTables($connection, $tableConfig);
        $info = $connection->execute(
            'SELECT TABLE_NAME AS name, TABLE_TYPE AS type, ENGINE AS engine,
                COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0) AS bytes
             FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$row['database']]
        )->fetchAll('assoc');

        $full = $rules['full'] ?? array_column($info, 'name');
        $notProtected = [];

        foreach ($info as $table) {
            $hasData = in_array($table['name'], $full, true);
            if (!$hasData && !in_array($table['name'], $rules['structure'], true)) {
                continue;
            }

            $row['tables'][] = ['name' => $table['name'], 'export' => $hasData ? 'Data + structure' : 'Structure only'];
            $row[$hasData ? 'tablesWithData' : 'tablesStructureOnly']++;
            $row['estimatedBytes'] += $hasData ? (int)$table['bytes'] : 0;

            if ($hasData && $table['type'] === 'BASE TABLE' && $table['engine'] !== 'InnoDB') {
                $notProtected[] = $table['name'] . ' (' . $table['engine'] . ')';
            }
        }

        $row['size'] = self::formatBytes($row['estimatedBytes']);

        return self::validate_tableMessages($row, $rules, $notProtected);
    }

    private static function validate_tableMessages(array $row, array $rules, array $notProtected): array
    {
        if (!empty($notProtected)) {
            $row['level'] = 'warning';
            $row['messages'][] = 'Not protected by the consistent snapshot: ' . implode(', ', $notProtected);
        }
        if (!empty($rules['missing'])) {
            $row['level'] = 'problem';
            $row['messages'][] = 'exclude names no such table: ' . implode(', ', $rules['missing']);
        }
        if (!empty($rules['notFound'])) {
            $row['level'] = 'problem';
            $row['messages'][] = 'include names no such table: ' . implode(', ', $rules['notFound']);
        }
        if (empty($row['tables'])) {
            $row['level'] = 'problem';
            $row['messages'][] = 'The table rules resolve to no tables.';
        }

        return $row;
    }

    private static function validate_environment(array $databases): array
    {
        $sapi = PHP_SAPI === 'cli' ? 'CLI' : 'Web';
        $phpOk = version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=');
        $estimated = array_sum(array_column($databases, 'estimatedBytes'));

        $rows = [
            self::row($sapi . ' PHP', PHP_VERSION, $phpOk ? 'ok' : 'problem', $phpOk ? '' : 'PHP 8.2 or later is required.'),
            self::validate_environmentExec(),
            self::validate_environmentMysqldump($databases),
            self::validate_environmentTmp($estimated),
        ];

        if (!class_exists(S3Client::class)) {
            $rows[] = self::row('AWS SDK', 'Missing', 'problem', 'Install aws/aws-sdk-php.');
        }

        return $rows;
    }

    private static function validate_environmentExec(): array
    {
        return self::execAllowed()
            ? self::row('exec()', 'Allowed', 'ok')
            : self::row('exec()', 'Disabled', 'problem', 'mysqldump can\'t run without exec().');
    }

    private static function validate_environmentMysqldump(array $databases): array
    {
        if (!self::execAllowed()) {
            return self::row('mysqldump', 'Not checked', 'warning', 'exec() is disabled.');
        }

        $output = [];
        $code = 1;
        exec('mysqldump --version 2>&1', $output, $code);
        $version = trim((string)($output[0] ?? ''));

        if ($code !== 0) {
            return self::row('mysqldump', 'Not found', 'problem', 'Not on the PATH of the user running this check.');
        }

        $hasOldServer = false;
        foreach ($databases as $database) {
            $hasOldServer = $hasOldServer || str_starts_with($database['version'], '5.');
        }

        // An 8.0+ client asks for COLUMN_STATISTICS, which a 5.7 server doesn't have.
        if ($hasOldServer && preg_match('/Ver (8|9)\.\d+/', $version)) {
            return self::row('mysqldump', $version, 'problem', 'An 8.0 or later client fails against a 5.7 server.');
        }

        return self::row('mysqldump', $version, 'ok');
    }

    private static function validate_environmentTmp(int $estimatedBytes): array
    {
        if (!is_dir(TMP) || !is_writable(TMP)) {
            return self::row('TMP folder', TMP, 'problem', 'Not writable by the user running this check.');
        }

        $free = disk_free_space(TMP);
        $detail = self::formatBytes((int)$free) . ' free, dump estimated at ' . self::formatBytes($estimatedBytes);

        return $free !== false && $free < $estimatedBytes
            ? self::row('TMP folder', $detail, 'problem', 'Not enough free space for the dump.')
            : self::row('TMP folder', $detail, 'ok');
    }

    private static function validate_storage(array $config): array
    {
        $rows = [];
        $missing = false;

        foreach (self::requiredS3Keys() as $key) {
            $isSet = !empty($config[$key]);
            $missing = $missing || !$isSet;
            $rows[] = self::row($key, $isSet ? 'Set' : 'Missing', $isSet ? 'ok' : 'problem');
        }

        if ($missing || !class_exists(S3Client::class)) {
            $rows[] = self::row('Bucket', 'Not checked', 'warning', 'Needs the settings above and the AWS SDK.');

            return $rows;
        }

        $rows[] = self::row('Endpoint', self::s3Endpoint($config), 'ok');

        try {
            $client = self::s3Client($config);
            $client->headBucket(['Bucket' => $config['spaceName']]);
            $rows[] = self::row('Bucket', $config['spaceName'] . ': reachable', 'ok');
            $rows[] = self::validate_storageNewest($client, $config['spaceName']);
        } catch (\Throwable $e) {
            $rows[] = self::row('Bucket', $config['spaceName'] . ': not reachable', 'problem', self::shortMessage($e));
        }

        return $rows;
    }

    private static function validate_storageNewest($client, string $bucket): array
    {
        $newest = null;
        $pages = $client->getPaginator('ListObjectsV2', ['Bucket' => $bucket, 'Prefix' => self::KEY_PREFIX]);

        foreach ($pages as $page) {
            foreach ($page['Contents'] ?? [] as $object) {
                if ($newest === null || $object['LastModified'] > $newest['LastModified']) {
                    $newest = $object;
                }
            }
        }

        if ($newest === null) {
            return self::row('Newest backup', 'None yet', 'warning', 'Expected until the first backup runs.');
        }

        $days = (int)floor((time() - $newest['LastModified']->getTimestamp()) / 86400);
        $detail = $newest['Key'] . ', ' . self::formatBytes((int)$newest['Size'])
            . ', ' . $newest['LastModified']->format('Y-m-d H:i') . ' UTC, ' . $days . ' day(s) old';

        return $days >= self::MAX_BACKUP_AGE_DAYS
            ? self::row('Newest backup', $detail, 'problem', 'Older than ' . self::MAX_BACKUP_AGE_DAYS . ' days.')
            : self::row('Newest backup', $detail, 'ok');
    }

    /**
     * Resolves include/exclude config into:
     * - full: structure + data (null means "whole database", no filtering).
     * - structure: excluded tables that stay in the dump as empty, structure-only tables.
     * - missing: exclude names matching no table. The dump refuses these, because
     *   a typo would otherwise back up the data it was meant to leave out.
     * - notFound: include names matching no table. mysqldump itself fails on these.
     */
    private static function resolveTables($connection, array $tableConfig): array
    {
        $include = $tableConfig['include'] ?? '*';
        $exclude = self::parseTableList($tableConfig['exclude'] ?? '');
        $isWildcard = is_string($include) && trim($include) === '*';

        if ($isWildcard && empty($exclude)) {
            return ['full' => null, 'structure' => [], 'missing' => [], 'notFound' => []];
        }

        $actual = self::listTables($connection);
        $included = $isWildcard ? $actual : self::parseTableList($include);

        return [
            'full' => array_values(array_diff($included, $exclude)),
            'structure' => array_values(array_intersect($included, $exclude)),
            'missing' => array_values(array_diff($exclude, $actual)),
            'notFound' => array_values(array_diff($included, $actual)),
        ];
    }

    /**
     * Splits a newline-separated (or array) table list into a trimmed,
     * de-duplicated array. Supports \r\n and \n line endings so lists
     * pasted directly from phpMyAdmin work without manual conversion.
     */
    private static function parseTableList($value): array
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

    private static function listTables($connection): array
    {
        $tables = [];
        foreach ($connection->execute('SHOW TABLES')->fetchAll() as $row) {
            $tables[] = (string)reset($row);
        }

        return $tables;
    }

    private static function requireCli(string $method): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new \RuntimeException("OfflineBox::{$method}() only runs on the CLI.");
        }
    }

    private static function requiredS3Keys(): array
    {
        return ['spaceKey', 'spaceSecret', 'spaceRegion', 'spaceName'];
    }

    private static function s3Endpoint(array $config): string
    {
        return !empty($config['spaceEndpoint'])
            ? $config['spaceEndpoint']
            : 'https://' . $config['spaceRegion'] . '.digitaloceanspaces.com';
    }

    private static function s3Client(array $config): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $config['spaceRegion'],
            'endpoint' => self::s3Endpoint($config),
            'credentials' => [
                'key' => $config['spaceKey'],
                'secret' => $config['spaceSecret'],
            ],
        ]);
    }

    private static function execAllowed(): bool
    {
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));

        return function_exists('exec') && !in_array('exec', $disabled, true);
    }

    private static function row(string $label, string $value, string $level, string $message = ''): array
    {
        return ['label' => $label, 'value' => $value, 'level' => $level, 'message' => $message];
    }

    private static function shortMessage(\Throwable $e): string
    {
        return substr($e->getMessage(), 0, 200);
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float)$bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 1) . ' ' . $units[$unit];
    }
}
