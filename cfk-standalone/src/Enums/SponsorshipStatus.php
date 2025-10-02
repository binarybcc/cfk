<?php

declare(strict_types=1);

namespace CFK\Enums;

/**
 * Sponsorship status enumeration
 */
enum SponsorshipStatus: string
{
    case SELECTED = 'selected';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    /**
     * Get display name for status
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::SELECTED => 'Pending Confirmation',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled'
        };
    }

    /**
     * Get CSS class for status
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::SELECTED => 'sponsorship-pending',
            self::CONFIRMED => 'sponsorship-confirmed',
            self::CANCELLED => 'sponsorship-cancelled'
        };
    }

    /**
     * Check if status is pending
     */
    public function isPending(): bool
    {
        return $this === self::SELECTED;
    }

    /**
     * Check if status is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }

    /**
     * Check if status is cancelled
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }
}