-- Email Queue Table for Reliable Email Delivery
-- Allows emails to be processed asynchronously and retried on failure

CREATE TABLE IF NOT EXISTS email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Email Details
    recipient VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    from_email VARCHAR(255) DEFAULT 'noreply@cforkids.org',
    from_name VARCHAR(100) DEFAULT 'Christmas for Kids',

    -- Queue Status
    status ENUM('queued', 'processing', 'sent', 'failed') DEFAULT 'queued',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,

    -- Timestamps
    queued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    next_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Error Tracking
    last_error TEXT NULL,
    error_count INT DEFAULT 0,

    -- Optional Reference
    reference_type VARCHAR(50) NULL COMMENT 'e.g., "sponsorship", "notification"',
    reference_id INT NULL COMMENT 'ID of related record',

    -- Metadata
    metadata JSON NULL COMMENT 'Additional data (CC, BCC, attachments, etc.)',

    INDEX idx_status (status),
    INDEX idx_next_attempt (next_attempt_at, status),
    INDEX idx_priority (priority),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_queued_at (queued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
