<?php

declare(strict_types=1);

namespace CFK\Models;

use DateTime;

/**
 * Sponsorship model representing a sponsorship relationship
 */
class Sponsorship
{
    public function __construct(
        public readonly int $id,
        public readonly int $childId,
        public readonly string $sponsorName,
        public readonly string $sponsorEmail,
        public readonly string $sponsorPhone,
        public readonly string $type,
        public readonly string $status,
        public readonly DateTime $selectedAt,
        public readonly ?DateTime $confirmedAt = null,
        public readonly ?DateTime $cancelledAt = null,
        public readonly ?string $notes = null,
        public readonly ?string $sessionId = null,
        public readonly ?DateTime $expiresAt = null
    ) {}

    /**
     * Check if sponsorship is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if sponsorship is pending confirmation
     */
    public function isPending(): bool
    {
        return $this->status === 'selected';
    }

    /**
     * Check if sponsorship is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if sponsorship has expired
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new DateTime();
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemaining(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now = new DateTime();
        $diff = $this->expiresAt->getTimestamp() - $now->getTimestamp();
        
        return max(0, $diff);
    }

    /**
     * Get sponsorship type display name
     */
    public function getTypeDisplayName(): string
    {
        return match ($this->type) {
            'individual' => 'Individual Child',
            'sibling' => 'Sibling Group',
            'family' => 'Full Family',
            default => ucfirst($this->type)
        };
    }

    /**
     * Create sponsorship from database row
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            childId: (int) $data['child_id'],
            sponsorName: $data['sponsor_name'],
            sponsorEmail: $data['sponsor_email'],
            sponsorPhone: $data['sponsor_phone'],
            type: $data['type'],
            status: $data['status'],
            selectedAt: new DateTime($data['selected_at']),
            confirmedAt: isset($data['confirmed_at']) ? new DateTime($data['confirmed_at']) : null,
            cancelledAt: isset($data['cancelled_at']) ? new DateTime($data['cancelled_at']) : null,
            notes: $data['notes'] ?? null,
            sessionId: $data['session_id'] ?? null,
            expiresAt: isset($data['expires_at']) ? new DateTime($data['expires_at']) : null
        );
    }

    /**
     * Convert to array for database operations
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'child_id' => $this->childId,
            'sponsor_name' => $this->sponsorName,
            'sponsor_email' => $this->sponsorEmail,
            'sponsor_phone' => $this->sponsorPhone,
            'type' => $this->type,
            'status' => $this->status,
            'selected_at' => $this->selectedAt->format('Y-m-d H:i:s'),
            'confirmed_at' => $this->confirmedAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'session_id' => $this->sessionId,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s')
        ];
    }
}