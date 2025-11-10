<?php

declare(strict_types=1);

namespace CFK\Repository;

use CFK\Database\Connection;

/**
 * Child Repository
 *
 * Data access layer for child-related queries.
 * Provides clean separation between business logic and database operations.
 */
class ChildRepository
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Find child by ID with full details
     *
     * @param int $id Child ID
     * @return array|null Child data or null if not found
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT c.*,
                       f.family_number,
                       f.family_notes,
                       f.id as family_id
                FROM children c
                JOIN families f ON c.family_id = f.id
                WHERE c.id = ?
                LIMIT 1";

        $result = Connection::fetchRow($sql, [$id]);

        return $result ?: null;
    }

    /**
     * Find all family members except the specified child
     *
     * @param int $familyId Family ID
     * @param int|null $excludeChildId Child ID to exclude
     * @return array Array of sibling records
     */
    public function findFamilyMembers(int $familyId, ?int $excludeChildId = null): array
    {
        $sql = "SELECT c.*,
                       f.family_number
                FROM children c
                JOIN families f ON c.family_id = f.id
                WHERE c.family_id = ?";

        $params = [$familyId];

        if ($excludeChildId !== null) {
            $sql .= " AND c.id != ?";
            $params[] = $excludeChildId;
        }

        $sql .= " ORDER BY c.age_months DESC";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Check if a child exists
     *
     * @param int $id Child ID
     * @return bool True if child exists
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT COUNT(*) as count FROM children WHERE id = ?";
        $result = Connection::fetchRow($sql, [$id]);

        return isset($result['count']) && $result['count'] > 0;
    }

    /**
     * Get child's display ID (family number)
     *
     * @param int $id Child ID
     * @return string|null Display ID or null if not found
     */
    public function getDisplayId(int $id): ?string
    {
        $sql = "SELECT f.family_number
                FROM children c
                JOIN families f ON c.family_id = f.id
                WHERE c.id = ?
                LIMIT 1";

        $result = Connection::fetchRow($sql, [$id]);

        return $result['family_number'] ?? null;
    }
}
