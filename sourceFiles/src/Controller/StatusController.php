<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\View\View;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Status Endpoint block: token-protected GET /en/status with error / warning
 * counts for the last 24 hours. See docs/features/MVP-Status-Endpoint.md.
 *
 * Self-contained on purpose (a drop-in code block): the log counting lives here
 * as private helpers, not in a Table or Service, and nothing outside this
 * controller, its templates and its CSS / JS is touched. A project may move the
 * counting into its own model layer once the block is integrated.
 *
 * Extends Cake's base Controller, NOT AppController: no testing-site password
 * gate, no Authentication component, no menu, no database. Reached by the
 * default fallback routes; no route of its own.
 *
 * - Token: STATUS_TOKEN (ENV Vars pattern). Not set = endpoint off.
 * - Sent as header X-Status-Token (automation) or ?token= (bookmarks).
 * - Anything wrong = the normal 404 page, never 401/403.
 * - Browsers get the monitor page; everything else (or ?format=json) gets JSON.
 * - Only counts leave this class, never log contents.
 *
 * Scoping: log files are install-wide and only read here, so nothing is scoped
 * by group_id / user_id. This is a deliberate exception.
 */
class StatusController extends Controller
{
    private const WINDOW_HOURS = 24;

    private const CHECK_MINUTES = 60;

    // Log files counted, as base names in LOGS (console commands write cli-error)
    private const LOG_FILES = ['error', 'cli-error'];

    // Log level => response field
    private const LEVEL_FIELDS = [
        'emergency' => 'errors',
        'alert' => 'errors',
        'critical' => 'errors',
        'error' => 'errors',
        'warning' => 'warnings',
    ];

    // Log entries whose first line contains any of these strings are not counted
    // as errors / warnings (they are reported as `ignored`). Plain, case-sensitive
    // substring match. Start small: watch logs/error.log and add known noise here.
    // Narrow beats broad: 'Controller class DebugKit' silences one nuisance,
    // '[Cake\Http\Exception\MissingControllerException]' silences them all.
    private const IGNORE = [
        '[Cake\Http\Exception\NotFoundException]', // includes wrong tokens on this endpoint
    ];

    // Read logs backwards in chunks of this size, so large files are never loaded whole
    private const CHUNK_BYTES = 65536;

    private int $cutoff = 0;

    private array $counts = [];

    public function index()
    {
        if (!$this->request->is('get') || !$this->index_tokenValid()) {
            throw new NotFoundException();
        }

        $now = new DateTimeImmutable();
        $this->index_countLogs($now);
        $status = [
            'app' => (string)(get_cfg_var('STATUS_APP_NAME') ?: env('STATUS_APP_NAME') ?: $this->request->host()),
            'generated_at' => $now->format(DATE_ATOM),
            'window_hours' => self::WINDOW_HOURS,
            'errors' => $this->counts['errors'],
            'warnings' => $this->counts['warnings'],
            'ignored' => $this->counts['ignored'],
            'php_version' => PHP_VERSION,
            'cakephp_version' => Configure::version(),
        ];

        $this->response = $this->response
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Robots-Tag', 'noindex')
            ->withHeader('Referrer-Policy', 'no-referrer');

        if (!$this->index_wantsHtml()) {
            return $this->response->withType('json')->withStringBody((string)json_encode($status));
        }
        $this->index_setMonitorVars($status);
    }

    /**
     * Header first, then ?token=. Off (false) when STATUS_TOKEN is not set.
     */
    private function index_tokenValid(): bool
    {
        $expected = (string)(get_cfg_var('STATUS_TOKEN') ?: env('STATUS_TOKEN') ?: '');
        if ($expected === '') {
            return false;
        }
        $given = $this->request->getHeaderLine('X-Status-Token');
        if ($given === '') {
            $given = (string)$this->request->getQuery('token', '');
        }

        return $given !== '' && hash_equals($expected, $given);
    }

    private function index_wantsHtml(): bool
    {
        if ($this->request->getQuery('format') === 'json') {
            return false;
        }

        return strpos($this->request->getHeaderLine('Accept'), 'text/html') !== false;
    }

    /**
     * Monitor page: plain View, no layout (AppView helpers expect a full app request).
     */
    private function index_setMonitorVars(array $status): void
    {
        $hasErrors = $status['errors'] > 0;
        $this->viewBuilder()->setClassName(View::class)->disableAutoLayout();
        $this->set([
            'status' => $status,
            'statusState' => $hasErrors ? 'errors' : 'ok',
            'statusTitle' => ($hasErrors ? '(' . $status['errors'] . ') ERRORS' : 'OK') . ' - ' . $status['app'],
            'statusCheckMinutes' => self::CHECK_MINUTES,
        ]);
    }

    /**
     * Sets $this->counts (errors, warnings, ignored) for entries inside the window,
     * across LOG_FILES and their rotated siblings.
     */
    private function index_countLogs(DateTimeImmutable $now): void
    {
        // Log lines carry no timezone, so compare as wall-clock time in $now's timezone
        $local = new DateTimeImmutable($now->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
        $this->cutoff = $local->getTimestamp() - self::WINDOW_HOURS * 3600;
        $this->counts = ['errors' => 0, 'warnings' => 0, 'ignored' => 0];
        foreach ($this->index_logFiles() as $file) {
            $this->index_countFile($file);
        }
    }

    /**
     * Current files plus rotated ones (`error.log.<unix time>`) modified inside the window.
     */
    private function index_logFiles(): array
    {
        $files = [];
        foreach (self::LOG_FILES as $baseName) {
            $current = LOGS . $baseName . '.log';
            if (is_file($current)) {
                $files[] = $current;
            }
            foreach (glob($current . '.*') ?: [] as $rotated) {
                if (preg_match('/\.log\.\d+$/', $rotated) && @filemtime($rotated) >= time() - self::WINDOW_HOURS * 3600) {
                    $files[] = $rotated;
                }
            }
        }

        return $files;
    }

    /**
     * Read one file from the end, line by line, until a dated line is older than the window.
     */
    private function index_countFile(string $file): void
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return; // unreadable counts as zero, never an error
        }
        clearstatcache(true, $file); // the log may have grown since PHP last looked
        $position = (int)filesize($file);
        $partial = '';
        while ($position > 0) {
            $read = min(self::CHUNK_BYTES, $position);
            $position -= $read;
            fseek($handle, $position);
            $lines = explode("\n", fread($handle, $read) . $partial);
            // The first piece may be cut mid-line; keep it for the next chunk
            $partial = $position > 0 ? array_shift($lines) : '';
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                if ($this->index_countLine($lines[$i]) === false) {
                    fclose($handle);

                    return;
                }
            }
        }
        fclose($handle);
    }

    /**
     * Count one line. Returns false once a dated line is older than the window.
     * Lines without a leading timestamp (stack traces, multi-line messages) are ignored.
     */
    private function index_countLine(string $line): bool
    {
        if (!preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (\w+): /', $line, $match)) {
            return true;
        }
        $time = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $match[1], new DateTimeZone('UTC'));
        if ($time === false) {
            return true;
        }
        if ($time->getTimestamp() < $this->cutoff) {
            return false;
        }
        $field = self::LEVEL_FIELDS[strtolower($match[2])] ?? null;
        if ($field === null) {
            return true;
        }
        foreach (self::IGNORE as $needle) {
            if ($needle !== '' && strpos($line, $needle) !== false) {
                $this->counts['ignored']++;

                return true;
            }
        }
        $this->counts[$field]++;

        return true;
    }
}
