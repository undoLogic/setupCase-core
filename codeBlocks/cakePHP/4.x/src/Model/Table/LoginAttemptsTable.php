<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\I18n\FrozenTime;
use Cake\ORM\Table;

/**
 * Login Throttling - failed password logins per IP + username.
 * See docs/features/MVP-login_throttling.md
 *
 * Rolling window: a key is blocked while it has $maxFailures or more failures
 * inside the last $windowMinutes. The block lifts as old failures age out.
 * An IP is also blocked for every username once it has $maxFailuresPerIp
 * failures in the window, so rotating usernames does not give unlimited
 * password checks.
 *
 * NO GROUP SCOPE (deliberate AGENTS.md exception): attempts are recorded
 * before anyone is logged in, so there is no group_id / user_id to scope by.
 * Deletes are limited instead: rows older than the window (purge), or the
 * exact ip + username_hash of a successful login (clear). Nothing deletes all rows.
 */
class LoginAttemptsTable extends Table
{
    protected int $maxFailures = 5;
    protected int $maxFailuresPerIp = 50;
    protected int $windowMinutes = 5;

    // Set by setKey(), used by the public methods
    private array $key = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('login_attempts');
    }

    public function settings(): array
    {
        return [
            'STATUS' => 200,
            'MSG' => 'Login throttling settings',
            'maxFailures' => $this->maxFailures,
            'maxFailuresPerIp' => $this->maxFailuresPerIp,
            'windowMinutes' => $this->windowMinutes,
        ];
    }

    public function isBlocked(string $ip, string $username): array
    {
        if (!$this->setKey($ip, $username)) {
            return ['STATUS' => 400, 'MSG' => 'IP is required'];
        }

        $since = ['created >=' => $this->windowStart()];
        $blocked = $this->find()->where($this->key + $since)->count() >= $this->maxFailures
            || $this->find()->where(['ip' => $this->key['ip']] + $since)->count() >= $this->maxFailuresPerIp;

        return [
            'STATUS' => 200,
            'MSG' => $blocked ? 'Too many failed attempts' : 'Not blocked',
            'blocked' => $blocked,
        ];
    }

    public function recordFailure(string $ip, string $username): array
    {
        if (!$this->setKey($ip, $username)) {
            return ['STATUS' => 400, 'MSG' => 'IP is required'];
        }

        $entity = $this->newEntity($this->key + ['created' => FrozenTime::now()]);
        if (!$this->save($entity)) {
            return ['STATUS' => 500, 'MSG' => 'Failed attempt not saved'];
        }

        // Precondition: only rows already outside the window are purged
        $this->deleteAll(['created <' => $this->windowStart()]);

        return ['STATUS' => 200, 'MSG' => 'Failed attempt recorded'];
    }

    public function clearFailures(string $ip, string $username): array
    {
        if (!$this->setKey($ip, $username)) {
            return ['STATUS' => 400, 'MSG' => 'IP is required'];
        }

        // Filter on the full key in the same query: a different user is never touched
        $deleted = $this->deleteAll($this->key);

        return ['STATUS' => 200, 'MSG' => 'Failed attempts cleared', 'deleted' => $deleted];
    }

    // The raw username is never stored: people sometimes type their password into it
    private function setKey(string $ip, string $username): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }

        $this->key = [
            'ip' => $ip,
            'username_hash' => hash('sha256', mb_strtolower(trim($username))),
        ];

        return true;
    }

    private function windowStart(): FrozenTime
    {
        return FrozenTime::now()->subMinutes($this->windowMinutes);
    }
}
