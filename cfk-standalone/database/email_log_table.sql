-- Email Log Table for CFK Sponsorship System
-- Tracks all email communications for audit trail

CREATE TABLE IF NOT EXISTS email_log (
    id int NOT NULL AUTO_INCREMENT,
    recipient varchar(255) NOT NULL,
    type enum('sponsor_confirmation', 'admin_notification', 'sponsorship_update', 'system_alert') NOT NULL,
    status enum('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    sponsorship_id int DEFAULT NULL,
    subject varchar(255) DEFAULT NULL,
    error_message text,
    sent_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recipient (recipient),
    KEY idx_type_status (type, status),
    KEY idx_sent_date (sent_date),
    FOREIGN KEY (sponsorship_id) REFERENCES sponsorships(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;