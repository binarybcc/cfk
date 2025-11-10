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

    /**
     * Find child by ID with full details
     *
     * @param int $id Child ID
     * @return array<string, mixed>|null Child data or null if not found
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
     * @return array<int, array<string, mixed>> Array of sibling records
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
     * Find all children with filters and pagination
     *
     * @param array<string, mixed> $filters Filter criteria (search, age_category, gender, status, family_id)
     * @param int $page Current page number
     * @param int $limit Results per page
     * @return array<int, array<string, mixed>> Array of child records
     */
    public function findAll(array $filters = [], int $page = 1, int $limit = 12): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT c.*, f.family_number,
                       CONCAT(f.family_number, c.child_letter) as display_id
                FROM children c
                JOIN families f ON c.family_id = f.id
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (! empty($filters['search'])) {
            $searchValue = '%' . $filters['search'] . '%';
            $sql .= " AND (CONCAT(f.family_number, c.child_letter) LIKE ?
                      OR c.interests LIKE ?
                      OR c.wishes LIKE ?)";
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
        }

        if (! empty($filters['age_category'])) {
            // Get age categories from config
            global $ageCategories;
            if (isset($ageCategories[$filters['age_category']])) {
                $category = $ageCategories[$filters['age_category']];
                $sql .= " AND c.age_months BETWEEN ? AND ?";
                $params[] = $category['min'] * 12;
                $params[] = $category['max'] * 12;
            }
        }

        if (! empty($filters['gender'])) {
            $sql .= " AND c.gender = ?";
            $params[] = $filters['gender'];
        }

        if (! empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (! empty($filters['family_id'])) {
            $sql .= " AND c.family_id = ?";
            $params[] = $filters['family_id'];
        }

        // Default to available children only
        if (! isset($filters['status'])) {
            $sql .= " AND c.status = 'available'";
        }

        // Order by family number (numerically), then child letter
        $sql .= " ORDER BY CAST(f.family_number AS UNSIGNED), c.child_letter";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Count children matching filters
     *
     * @param array<string, mixed> $filters Filter criteria (same as findAll)
     * @return int Total count
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM children c
                JOIN families f ON c.family_id = f.id
                WHERE 1=1";

        $params = [];

        // Apply same filters as findAll()
        if (! empty($filters['search'])) {
            $searchValue = '%' . $filters['search'] . '%';
            $sql .= " AND (CONCAT(f.family_number, c.child_letter) LIKE ?
                      OR c.interests LIKE ?
                      OR c.wishes LIKE ?)";
            $params[] = $searchValue;
            $params[] = $searchValue;
            $params[] = $searchValue;
        }

        if (! empty($filters['age_category'])) {
            global $ageCategories;
            if (isset($ageCategories[$filters['age_category']])) {
                $category = $ageCategories[$filters['age_category']];
                $sql .= " AND c.age_months BETWEEN ? AND ?";
                $params[] = $category['min'] * 12;
                $params[] = $category['max'] * 12;
            }
        }

        if (! empty($filters['gender'])) {
            $sql .= " AND c.gender = ?";
            $params[] = $filters['gender'];
        }

        if (! empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (! empty($filters['family_id'])) {
            $sql .= " AND c.family_id = ?";
            $params[] = $filters['family_id'];
        }

        // Default to available children only
        if (! isset($filters['status'])) {
            $sql .= " AND c.status = 'available'";
        }

        $result = Connection::fetchRow($sql, $params);

        return (int)($result['total'] ?? 0);
    }

    /**
     * Eager load family members for multiple children (prevents N+1 queries)
     *
     * @param array<int, array<string, mixed>> $children Array of child records
     * @return array<int, array<int, array<string, mixed>>> Associative array indexed by family_id containing siblings
     */
    public function eagerLoadFamilyMembers(array $children): array
    {
        if (empty($children)) {
            return [];
        }

        // Get unique family IDs
        $familyIds = array_unique(array_column($children, 'family_id'));

        if (empty($familyIds)) {
            return [];
        }

        // Build placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($familyIds), '?'));

        $sql = "SELECT c.*, f.family_number,
                       CONCAT(f.family_number, c.child_letter) as display_id
                FROM children c
                JOIN families f ON c.family_id = f.id
                WHERE c.family_id IN ($placeholders)
                ORDER BY c.age_months DESC";

        $allChildren = Connection::fetchAll($sql, $familyIds);

        // Group by family_id
        $siblingsByFamily = [];
        foreach ($allChildren as $child) {
            $siblingsByFamily[$child['family_id']][] = $child;
        }

        return $siblingsByFamily;
    }

    /**
     * Find family by ID
     *
     * @param int $familyId Family ID
     * @return array<string, mixed>|null Family record or null if not found
     */
    public function findFamilyById(int $familyId): ?array
    {
        $sql = "SELECT * FROM families WHERE id = ? LIMIT 1";
        $result = Connection::fetchRow($sql, [$familyId]);

        return $result ?: null;
    }
}
