<?php

declare(strict_types=1);

namespace CFK\Models;

use DateTime;

/**
 * Child model representing a child in the sponsorship program
 */
class Child
{
    public function __construct(
        public readonly int $id,
        public readonly string $familyId,
        public readonly string $name,
        public readonly int $age,
        public readonly string $gender,
        public readonly string $grade,
        public readonly string $interests,
        public readonly string $avatar,
        public readonly string $status,
        public readonly ?DateTime $createdAt = null,
        public readonly ?DateTime $updatedAt = null,
        public readonly ?DateTime $sponsoredAt = null,
        public readonly ?string $sponsorNotes = null
    ) {}

    /**
     * Get family group (letter part of family ID)
     */
    public function getFamilyGroup(): string
    {
        return substr($this->familyId, -1);
    }

    /**
     * Get base family ID (numeric part)
     */
    public function getBaseFamilyId(): string
    {
        return rtrim($this->familyId, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Check if child is available for sponsorship
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, ['available', 'selected'], true);
    }

    /**
     * Check if child is currently sponsored
     */
    public function isSponsored(): bool
    {
        return $this->status === 'sponsored';
    }

    /**
     * Check if child is temporarily selected
     */
    public function isSelected(): bool
    {
        return $this->status === 'selected';
    }

    /**
     * Get child age group
     */
    public function getAgeGroup(): string
    {
        return match (true) {
            $this->age <= 5 => 'Early Childhood (0-5)',
            $this->age <= 10 => 'Elementary (6-10)',
            $this->age <= 13 => 'Middle School (11-13)',
            $this->age <= 17 => 'High School (14-17)',
            default => 'Young Adult (18+)'
        };
    }

    /**
     * Get interests as array
     */
    public function getInterestsArray(): array
    {
        return array_filter(array_map('trim', explode(',', $this->interests)));
    }

    /**
     * Create child from database row
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            familyId: $data['family_id'],
            name: $data['name'],
            age: (int) $data['age'],
            gender: $data['gender'],
            grade: $data['grade'],
            interests: $data['interests'] ?? '',
            avatar: $data['avatar'],
            status: $data['status'],
            createdAt: isset($data['created_at']) ? new DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new DateTime($data['updated_at']) : null,
            sponsoredAt: isset($data['sponsored_at']) ? new DateTime($data['sponsored_at']) : null,
            sponsorNotes: $data['sponsor_notes'] ?? null
        );
    }

    /**
     * Convert to array for database operations
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'family_id' => $this->familyId,
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender,
            'grade' => $this->grade,
            'interests' => $this->interests,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'sponsored_at' => $this->sponsoredAt?->format('Y-m-d H:i:s'),
            'sponsor_notes' => $this->sponsorNotes
        ];
    }
}