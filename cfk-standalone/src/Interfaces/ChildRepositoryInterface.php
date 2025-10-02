<?php

declare(strict_types=1);

namespace CFK\Interfaces;

use CFK\Models\Child;

/**
 * Interface for child repository operations
 */
interface ChildRepositoryInterface
{
    /**
     * Find child by ID
     */
    public function findById(int $id): ?Child;

    /**
     * Find children by family ID
     */
    public function findByFamilyId(string $familyId): array;

    /**
     * Find all available children with pagination and filters
     */
    public function findAvailable(int $limit = 20, int $offset = 0, array $filters = []): array;

    /**
     * Count available children with filters
     */
    public function countAvailable(array $filters = []): int;

    /**
     * Update child status
     */
    public function updateStatus(int $childId, string $status): bool;

    /**
     * Update child sponsorship details
     */
    public function updateSponsorshipDetails(int $childId, ?string $notes = null): bool;

    /**
     * Get family statistics
     */
    public function getFamilyStats(string $baseFamilyId): array;

    /**
     * Get siblings for a child
     */
    public function getSiblings(int $childId): array;

    /**
     * Batch insert children from CSV
     */
    public function batchInsert(array $children): bool;
}