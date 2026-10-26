<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Util\SetupCase;
use Cake\I18n\FrozenTime;
use Cake\ORM\Table;

class EmailQueuesTable extends Table
{
    public const DEFAULT_FROM = 'from@example.com';

    public const FROM_OPTIONS = [
        'from@example.com' => 'from@example.com',
    ];

    public function initialize(array $config): void
    {
        $this->setTable('email_queues');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('EmailQueueAttachments', [
            'foreignKey' => 'email_queue_id',
        ]);
    }

    /**
     * Writes an email to the queue. No project sends email directly.
     */
    public function queueEmail(string $to, string $subject, string $body, array $options = []): array
    {
        $entity = $this->queueEmail_buildEntity($to, $subject, $body, $options);

        if (!$this->save($entity)) {
            return ['STATUS' => 400, 'MSG' => 'Email could not be queued'];
        }

        $attachmentResult = $this->attachFiles((int)$entity->id, $options['attachments'] ?? []);
        if ($attachmentResult['STATUS'] !== 200) {
            return $attachmentResult;
        }

        return ['STATUS' => 200, 'MSG' => 'Email queued', 'id' => (int)$entity->id];
    }

    private function queueEmail_buildEntity(string $to, string $subject, string $body, array $options)
    {
        $entity = $this->newEmptyEntity();
        $entity->user_id = !empty($options['user_id']) ? $options['user_id'] : null;
        $entity->email_to = $to;
        $entity->email_from = $options['email_from'] ?? self::DEFAULT_FROM;
        $entity->cc = $options['cc'] ?? null;
        $entity->bcc = $options['bcc'] ?? null;
        $entity->subject = $subject;
        $entity->body = $body;
        $entity->language = $options['language'] ?? 'en';
        $entity->sent = false;
        $entity->removed = false;

        return $entity;
    }

    /**
     * Moves each upload to disk via EmailQueueAttachments and records it.
     * Shared by queueEmail() and the edit-form "add attachment" flow.
     *
     * @param \Psr\Http\Message\UploadedFileInterface|\Psr\Http\Message\UploadedFileInterface[] $uploads
     *   A single file input without `[]` in its name comes back from
     *   getUploadedFiles() as one object, not an array — accept both.
     */
    public function attachFiles(int $emailQueueId, $uploads): array
    {
        if (!is_iterable($uploads)) {
            $uploads = [$uploads];
        }

        foreach ($uploads as $upload) {
            if ($upload->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = $this->EmailQueueAttachments->saveUpload($emailQueueId, $upload);
            if ($result['STATUS'] !== 200) {
                return $result;
            }
        }

        return ['STATUS' => 200, 'MSG' => 'Attachments saved'];
    }

    /**
     * Sends one queued row via the SetupCase utility.
     * Failures park (no retry): error + lastAttemptAt are written either way.
     */
    public function send(int $id, bool $markAsSent = true): array
    {
        $entity = $this->get($id, ['contain' => ['EmailQueueAttachments']]);

        $attachments = $this->send_resolveAttachments($entity);
        if ($attachments === false) {
            return $this->send_recordFailure($entity, 'One or more attachments are missing from disk');
        }

        $result = SetupCase::sendEmail(
            $entity->email_to,
            'email_queues',
            $entity->email_from ?: self::DEFAULT_FROM,
            $entity->subject,
            ['message_html' => $entity->body, 'message_text' => strip_tags($entity->body)],
            $entity->cc ?: false,
            $attachments
        );

        if ($result !== true) {
            return $this->send_recordFailure($entity, (string)$result);
        }

        return $this->send_recordSuccess($entity, $markAsSent);
    }

    private function send_resolveAttachments($entity)
    {
        $attachments = [];
        foreach ($entity->email_queue_attachments as $row) {
            $absolutePath = TMP . $row->path;
            if (!is_file($absolutePath)) {
                return false;
            }
            $attachments[$row->original_filename] = ['file' => $absolutePath];
        }

        return $attachments;
    }

    private function send_recordFailure($entity, string $message): array
    {
        $entity->error = $message;
        $entity->last_attempt_at = FrozenTime::now();
        $this->save($entity);

        return ['STATUS' => 400, 'MSG' => $message];
    }

    private function send_recordSuccess($entity, bool $markAsSent): array
    {
        $entity->error = null;
        $entity->last_attempt_at = FrozenTime::now();

        if ($markAsSent) {
            $entity->sent = true;
            $entity->sent_at = FrozenTime::now();
        }

        $this->save($entity);

        return ['STATUS' => 200, 'MSG' => $markAsSent ? 'Email sent' : 'Test email sent'];
    }

    /**
     * Loops every waiting row and sends it. Used by "Send All" / "Send All Test".
     */
    public function sendAll(bool $markAsSent = true): array
    {
        $ids = $this->find()
            ->select(['id'])
            ->where(['sent' => false, 'removed' => false])
            ->all()
            ->extract('id');

        $sent = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $result = $this->send((int)$id, $markAsSent);
            $result['STATUS'] === 200 ? $sent++ : $failed++;
        }

        return ['STATUS' => 200, 'MSG' => 'Queue processed', 'total' => $sent + $failed, 'sent' => $sent, 'failed' => $failed];
    }

    /**
     * Soft delete only — this table never issues a hard delete.
     */
    public function remove(int $id): array
    {
        $entity = $this->get($id);
        $entity->removed = true;

        if (!$this->save($entity)) {
            return ['STATUS' => 400, 'MSG' => 'Email could not be removed'];
        }

        return ['STATUS' => 200, 'MSG' => 'Email removed'];
    }

    /**
     * Bulk soft delete of every visible waiting row. Never a hard delete.
     */
    public function removeAll(): array
    {
        $count = $this->updateAll(['removed' => true], ['sent' => false, 'removed' => false]);

        return ['STATUS' => 200, 'MSG' => 'Waiting emails removed', 'count' => $count];
    }

    /**
     * Attaches a ready-to-render status label/badge class to each row so the
     * listing template stays a passive view (no business branching in it).
     */
    public function decorateStatus(iterable $rows): array
    {
        $decorated = [];
        foreach ($rows as $row) {
            $row->status_label = $this->decorateStatus_label($row);
            $row->status_badge_class = $this->decorateStatus_badgeClass($row);
            $decorated[] = $row;
        }

        return $decorated;
    }

    private function decorateStatus_label($row): string
    {
        if ($row->sent) {
            return 'Sent';
        }

        return !empty($row->error) ? 'Failed' : 'Waiting';
    }

    private function decorateStatus_badgeClass($row): string
    {
        if ($row->sent) {
            return 'bg-success';
        }

        return !empty($row->error) ? 'bg-danger' : 'bg-secondary';
    }
}
