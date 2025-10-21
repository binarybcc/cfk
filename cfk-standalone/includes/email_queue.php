<?php

declare(strict_types=1);

/**
 * Email Queue System
 * Provides reliable, asynchronous email delivery with retry logic
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class CFK_Email_Queue
{
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Add email to queue
     */
    public static function queue(
        string $recipient,
        string $subject,
        string $body,
        array $options = []
    ): int {
        $data = [
            'recipient' => $recipient,
            'recipient_name' => $options['recipient_name'] ?? '',
            'subject' => $subject,
            'body' => $body,
            'from_email' => $options['from_email'] ?? config('from_email'),
            'from_name' => $options['from_name'] ?? config('from_name'),
            'priority' => $options['priority'] ?? self::PRIORITY_NORMAL,
            'max_attempts' => $options['max_attempts'] ?? 3,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'metadata' => empty($options['metadata']) ? null : json_encode($options['metadata']),
            'status' => self::STATUS_QUEUED
        ];

        try {
            return Database::insert('email_queue', $data);
        } catch (Exception $e) {
            error_log('Failed to queue email: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process queued emails (called by cron)
     */
    public static function processQueue(int $limit = 10): array
    {
        $stats = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            // Get emails ready to send (ordered by priority and queued time)
            $emails = self::getEmailsToProcess($limit);

            foreach ($emails as $email) {
                $stats['processed']++;

                // Mark as processing
                self::markAsProcessing($email['id']);

                // Attempt to send
                $result = self::sendEmail($email);

                if ($result['success']) {
                    self::markAsSent($email['id']);
                    $stats['sent']++;
                } else {
                    self::handleFailure($email['id'], $result['error']);
                    $stats['failed']++;
                    $stats['errors'][] = "Email #{$email['id']}: {$result['error']}";
                }
            }
        } catch (Exception $e) {
            error_log('Email queue processing error: ' . $e->getMessage());
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Get emails ready to process
     */
    private static function getEmailsToProcess(int $limit): array
    {
        $sql = "
            SELECT *
            FROM email_queue
            WHERE status = :status
              AND next_attempt_at <= NOW()
              AND attempts < max_attempts
            ORDER BY
              FIELD(priority, 'urgent', 'high', 'normal', 'low'),
              queued_at ASC
            LIMIT :limit
        ";

        return Database::fetchAll($sql, [
            'status' => self::STATUS_QUEUED,
            'limit' => $limit
        ]);
    }

    /**
     * Mark email as processing
     */
    private static function markAsProcessing(int $id): void
    {
        Database::update(
            'email_queue',
            ['status' => self::STATUS_PROCESSING],
            ['id' => $id]
        );
    }

    /**
     * Mark email as sent
     */
    private static function markAsSent(int $id): void
    {
        Database::update('email_queue', [
            'status' => self::STATUS_SENT,
            'sent_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    /**
     * Handle email sending failure
     */
    private static function handleFailure(int $id, string $error): void
    {
        // Get current attempt count
        $email = Database::fetchRow("SELECT attempts, max_attempts FROM email_queue WHERE id = ?", [$id]);
        $newAttempts = $email['attempts'] + 1;

        // Calculate next retry time (exponential backoff)
        $retryMinutes = min(60, 2 ** $newAttempts); // 2, 4, 8, 16, 32, 60 minutes
        $nextAttempt = date('Y-m-d H:i:s', strtotime("+$retryMinutes minutes"));

        $updates = [
            'attempts' => $newAttempts,
            'error_count' => Database::fetchRow("SELECT error_count FROM email_queue WHERE id = ?", [$id])['error_count'] + 1,
            'last_error' => substr($error, 0, 500),
            'next_attempt_at' => $nextAttempt
        ];

        // If max attempts reached, mark as failed
        if ($newAttempts >= $email['max_attempts']) {
            $updates['status'] = self::STATUS_FAILED;
        } else {
            $updates['status'] = self::STATUS_QUEUED; // Back to queued for retry
        }

        Database::update('email_queue', $updates, ['id' => $id]);
    }

    /**
     * Send individual email
     */
    private static function sendEmail(array $email): array
    {
        try {
            // Load email manager
            if (!class_exists('CFK_Email_Manager')) {
                require_once __DIR__ . '/email_manager.php';
            }

            $mailer = CFK_Email_Manager::getMailer();

            $mailer->clearAddresses();
            $mailer->clearReplyTos();

            $mailer->setFrom($email['from_email'], $email['from_name']);
            $mailer->addAddress($email['recipient'], $email['recipient_name'] ?: '');
            $mailer->Subject = $email['subject'];
            $mailer->Body = $email['body'];

            // Handle metadata (CC, BCC, etc.)
            if ($email['metadata']) {
                $metadata = json_decode((string) $email['metadata'], true);
                if (isset($metadata['cc'])) {
                    foreach ((array)$metadata['cc'] as $cc) {
                        $mailer->addCC($cc);
                    }
                }
                if (isset($metadata['bcc'])) {
                    foreach ((array)$metadata['bcc'] as $bcc) {
                        $mailer->addBCC($bcc);
                    }
                }
            }

            $success = $mailer->send();

            return [
                'success' => $success,
                'error' => $success ? null : 'Failed to send email'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get queue statistics
     */
    public static function getStats(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) as count,
                AVG(attempts) as avg_attempts
            FROM email_queue
            GROUP BY status
        ";

        $results = Database::fetchAll($sql);

        $stats = [
            'queued' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'total' => 0
        ];

        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }

    /**
     * Retry failed emails
     */
    public static function retryFailed(int $limit = 10): int
    {
        return Database::query("
            UPDATE email_queue
            SET status = :queued,
                attempts = 0,
                error_count = 0,
                next_attempt_at = NOW()
            WHERE status = :failed
            LIMIT :limit
        ", [
            'queued' => self::STATUS_QUEUED,
            'failed' => self::STATUS_FAILED,
            'limit' => $limit
        ]);
    }

    /**
     * Clean old sent/failed emails
     */
    public static function cleanup(int $daysOld = 30): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-$daysOld days"));

        return Database::query("
            DELETE FROM email_queue
            WHERE status IN ('sent', 'failed')
              AND queued_at < :cutoff
        ", ['cutoff' => $cutoffDate]);
    }

    /**
     * Quick helper to queue sponsor confirmation
     */
    public static function queueSponsorConfirmation(array $sponsorship): int
    {
        if (!class_exists('CFK_Email_Manager')) {
            require_once __DIR__ . '/email_manager.php';
        }

        return self::queue(
            $sponsorship['sponsor_email'],
            'Christmas for Kids - Sponsorship Confirmation',
            CFK_Email_Manager::getSponsorConfirmationTemplate($sponsorship),
            [
                'recipient_name' => $sponsorship['sponsor_name'],
                'priority' => self::PRIORITY_HIGH,
                'reference_type' => 'sponsorship',
                'reference_id' => $sponsorship['id']
            ]
        );
    }

    /**
     * Quick helper to queue admin notification
     */
    public static function queueAdminNotification(string $subject, string $message, array $data = []): int
    {
        if (!class_exists('CFK_Email_Manager')) {
            require_once __DIR__ . '/email_manager.php';
        }

        return self::queue(
            config('admin_email'),
            'CFK Admin - ' . $subject,
            CFK_Email_Manager::getAdminNotificationTemplate($subject, $message, $data),
            [
                'priority' => self::PRIORITY_NORMAL,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null
            ]
        );
    }
}
