<?php

declare(strict_types=1);

namespace CFK\Interfaces;

/**
 * Interface for sponsorship service operations
 */
interface SponsorshipServiceInterface
{
    /**
     * Select a child for sponsorship
     */
    public function selectChild(
        int $childId, 
        array $sponsorData, 
        string $type = 'individual',
        string $sessionId = null
    ): array;

    /**
     * Confirm a sponsorship
     */
    public function confirmSponsorship(int $sponsorshipId, array $additionalData = []): array;

    /**
     * Cancel a sponsorship
     */
    public function cancelSponsorship(int $sponsorshipId, string $reason = null): array;

    /**
     * Get available sponsorship options for a child
     */
    public function getAvailableOptions(int $childId): array;

    /**
     * Clean up expired selections
     */
    public function cleanupExpiredSelections(): int;

    /**
     * Get sponsorship statistics
     */
    public function getStatistics(int $days = 30): array;
}