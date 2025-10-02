<?php

declare(strict_types=1);

namespace CFK\Enums;

/**
 * Child status enumeration
 */
enum ChildStatus: string
{
    case AVAILABLE = 'available';
    case SELECTED = 'selected';
    case SPONSORED = 'sponsored';
    case INACTIVE = 'inactive';

    /**
     * Get display name for status
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::AVAILABLE => 'Available for Sponsorship',
            self::SELECTED => 'Temporarily Selected',
            self::SPONSORED => 'Sponsored',
            self::INACTIVE => 'Inactive'
        };
    }

    /**
     * Get CSS class for status
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::AVAILABLE => 'status-available',
            self::SELECTED => 'status-selected',
            self::SPONSORED => 'status-sponsored',
            self::INACTIVE => 'status-inactive'
        };
    }

    /**
     * Check if status allows sponsorship
     */
    public function isAvailableForSponsorship(): bool
    {
        return in_array($this, [self::AVAILABLE, self::SELECTED], true);
    }
}