<?php

declare(strict_types=1);

namespace CFK\Repositories;

use CFK\Config\Database;
use CFK\Models\Child;
use CFK\Interfaces\ChildRepositoryInterface;
use PDO;

/**
 * Repository for child data access operations
 */
class ChildRepository implements ChildRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find child by ID
     */
    public function findById(int $id): ?Child
    {
        $stmt = $this->db->prepare('SELECT * FROM children WHERE id = ?');
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        return $data ? Child::fromArray($data) : null;
    }

    /**
     * Find children by family ID
     */
    public function findByFamilyId(string $familyId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM children WHERE family_id = ? ORDER BY age ASC');
        $stmt->execute([$familyId]);
        
        return array_map([Child::class, 'fromArray'], $stmt->fetchAll());
    }

    /**
     * Find all available children with pagination
     */
    public function findAvailable(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        $conditions = ['status IN (?, ?)'];
        $params = ['available', 'selected'];

        // Apply filters
        if (!empty($filters['gender'])) {
            $conditions[] = 'gender = ?';
            $params[] = $filters['gender'];
        }

        if (!empty($filters['age_min'])) {
            $conditions[] = 'age >= ?';
            $params[] = (int) $filters['age_min'];
        }

        if (!empty($filters['age_max'])) {
            $conditions[] = 'age <= ?';
            $params[] = (int) $filters['age_max'];
        }

        if (!empty($filters['grade'])) {
            $conditions[] = 'grade = ?';
            $params[] = $filters['grade'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE ? OR interests LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $conditions);
        $sql = "SELECT * FROM children WHERE {$whereClause} ORDER BY family_id, age ASC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map([Child::class, 'fromArray'], $stmt->fetchAll());
    }

    /**
     * Count available children with filters
     */
    public function countAvailable(array $filters = []): int
    {
        $conditions = ['status IN (?, ?)'];
        $params = ['available', 'selected'];

        // Apply same filters as findAvailable
        if (!empty($filters['gender'])) {
            $conditions[] = 'gender = ?';
            $params[] = $filters['gender'];
        }

        if (!empty($filters['age_min'])) {
            $conditions[] = 'age >= ?';
            $params[] = (int) $filters['age_min'];
        }

        if (!empty($filters['age_max'])) {
            $conditions[] = 'age <= ?';
            $params[] = (int) $filters['age_max'];
        }

        if (!empty($filters['grade'])) {
            $conditions[] = 'grade = ?';
            $params[] = $filters['grade'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE ? OR interests LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) FROM children WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Update child status
     */
    public function updateStatus(int $childId, string $status): bool
    {
        $stmt = $this->db->prepare('
            UPDATE children 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ');
        
        return $stmt->execute([$status, $childId]);
    }

    /**
     * Update child sponsorship details
     */
    public function updateSponsorshipDetails(int $childId, ?string $notes = null): bool
    {
        $stmt = $this->db->prepare('
            UPDATE children 
            SET sponsor_notes = ?, sponsored_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ');
        
        return $stmt->execute([$notes, $childId]);
    }

    /**
     * Get family statistics
     */
    public function getFamilyStats(string $baseFamilyId): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                COUNT(*) as total_children,
                SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_children,
                SUM(CASE WHEN status = "sponsored" THEN 1 ELSE 0 END) as sponsored_children,
                SUM(CASE WHEN status = "selected" THEN 1 ELSE 0 END) as selected_children
            FROM children 
            WHERE family_id LIKE ?
        ');
        
        $stmt->execute([$baseFamilyId . '%']);
        return $stmt->fetch() ?: [];
    }

    /**
     * Get siblings for a child
     */
    public function getSiblings(int $childId): array
    {
        $child = $this->findById($childId);
        if (!$child) {
            return [];
        }

        $baseFamilyId = $child->getBaseFamilyId();
        
        $stmt = $this->db->prepare('
            SELECT * FROM children 
            WHERE family_id LIKE ? AND id != ?
            ORDER BY age ASC
        ');
        
        $stmt->execute([$baseFamilyId . '%', $childId]);
        
        return array_map([Child::class, 'fromArray'], $stmt->fetchAll());
    }

    /**
     * Batch insert children from CSV
     */
    public function batchInsert(array $children): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO children
            (family_id, child_letter, age, gender, grade, interests, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        Database::beginTransaction();

        try {
            foreach ($children as $child) {
                $stmt->execute([
                    $child['family_id'],
                    $child['child_letter'] ?? '',
                    $child['age'],
                    $child['gender'],
                    $child['grade'],
                    $child['interests'] ?? '',
                    'available'
                ]);
            }
            
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}