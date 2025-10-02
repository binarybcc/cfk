<?php

declare(strict_types=1);

namespace CFK\Enums;

/**
 * Sponsorship type enumeration
 */
enum SponsorshipType: string
{
    case INDIVIDUAL = 'individual';
    case SIBLING = 'sibling';
    case FAMILY = 'family';

    /**
     * Get display name for type
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Individual Child',
            self::SIBLING => 'Sibling Group',
            self::FAMILY => 'Full Family'
        };
    }

    /**
     * Get description for type
     */
    public function getDescription(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Sponsor this child individually',
            self::SIBLING => 'Sponsor this child and their available siblings',
            self::FAMILY => 'Sponsor the entire family group'
        };
    }

    /**
     * Get estimated cost multiplier
     */
    public function getCostMultiplier(): float
    {
        return match($this) {
            self::INDIVIDUAL => 1.0,
            self::SIBLING => 1.5,
            self::FAMILY => 2.0
        };
    }
}