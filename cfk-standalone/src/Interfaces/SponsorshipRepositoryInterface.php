<?php

declare(strict_types=1);

namespace CFK\Interfaces;

use CFK\Models\Sponsorship;

/**
 * Interface for sponsorship repository operations
 */
interface SponsorshipRepositoryInterface
{
    /**
     * Find sponsorship by ID
     */
    public function findById(int $id): ?Sponsorship;

    /**
     * Find sponsorship by child ID
     */
    public function findByChildId(int $childId): ?Sponsorship;

    /**
     * Find sponsorship by session ID
     */
    public function findBySessionId(string $sessionId): ?Sponsorship;

    /**
     * Create new sponsorship
     */
    public function create(array $data): int;

    /**
     * Confirm sponsorship
     */
    public function confirm(int $sponsorshipId): bool;

    /**
     * Cancel sponsorship
     */
    public function cancel(int $sponsorshipId, string $reason = null): bool;

    /**
     * Find expired sponsorships
     */
    public function findExpired(): array;

    /**
     * Get sponsorship statistics
     */
    public function getStats(int $days = 30): array;

    /**
     * Get recent sponsorship activity
     */
    public function getRecentActivity(int $limit = 10): array;

    /**
     * Update sponsorship notes
     */
    public function updateNotes(int $sponsorshipId, string $notes): bool;

    /**
     * Cleanup expired selections
     */
    public function cleanupExpired(): int;
}