<?php

declare(strict_types=1);

namespace CFK\Repository;

use CFK\Database\Connection;

/**
 * Admin Repository
 *
 * Data access layer for admin dashboard and reports.
 */
class AdminRepository
{
    private Connection $db;

    /**
     * Get dashboard statistics
     *
     * @return array Statistics for dashboard
     */
    public function getDashboardStats(): array
    {
        $stats = [];

        // Total children (all statuses)
        $result = Connection::fetchRow("SELECT COUNT(*) as total FROM children");
        $stats['total_children'] = (int)($result['total'] ?? 0);

        // Available children
        $result = Connection::fetchRow("SELECT COUNT(*) as total FROM children WHERE status = 'available'");
        $stats['available_children'] = (int)($result['total'] ?? 0);

        // Pending sponsorships
        $result = Connection::fetchRow("SELECT COUNT(*) as total FROM sponsorships WHERE status = 'pending'");
        $stats['pending_sponsorships'] = (int)($result['total'] ?? 0);

        // Completed sponsorships
        $result = Connection::fetchRow("SELECT COUNT(*) as total FROM sponsorships WHERE status = 'completed'");
        $stats['completed_sponsorships'] = (int)($result['total'] ?? 0);

        // Total families
        $result = Connection::fetchRow("SELECT COUNT(*) as total FROM families");
        $stats['total_families'] = (int)($result['total'] ?? 0);

        return $stats;
    }

    /**
     * Get recent sponsorships
     *
     * @param int $limit Number of records to return
     * @return array Recent sponsorship records
     */
    public function getRecentSponsorships(int $limit = 10): array
    {
        $sql = "SELECT s.*,
                       CONCAT(f.family_number, c.child_letter) as child_display_id,
                       c.age_months,
                       c.gender
                FROM sponsorships s
                JOIN children c ON s.child_id = c.id
                JOIN families f ON c.family_id = f.id
                ORDER BY s.request_date DESC
                LIMIT ?";

        return Connection::fetchAll($sql, [$limit]);
    }

    /**
     * Get children needing attention (pending too long)
     *
     * @return array Children with pending sponsorships older than 48 hours
     */
    public function getChildrenNeedingAttention(): array
    {
        $sql = "SELECT c.*,
                       f.family_number,
                       CONCAT(f.family_number, c.child_letter) as display_id,
                       s.request_date,
                       s.sponsor_email
                FROM children c
                JOIN families f ON c.family_id = f.id
                LEFT JOIN sponsorships s ON c.id = s.child_id AND s.status = 'pending'
                WHERE c.status = 'pending'
                  AND s.request_date < DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY s.request_date ASC";

        return Connection::fetchAll($sql);
    }

    /**
     * Get all sponsorships for reports
     *
     * @param array $filters Optional filters (status, date_from, date_to)
     * @return array All sponsorship records matching filters
     */
    public function getAllSponsorships(array $filters = []): array
    {
        $sql = "SELECT s.*,
                       CONCAT(f.family_number, c.child_letter) as child_display_id,
                       c.age_months,
                       c.gender,
                       c.grade
                FROM sponsorships s
                JOIN children c ON s.child_id = c.id
                JOIN families f ON c.family_id = f.id
                WHERE 1=1";

        $params = [];

        if (! empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (! empty($filters['date_from'])) {
            $sql .= " AND s.request_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (! empty($filters['date_to'])) {
            $sql .= " AND s.request_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY s.request_date DESC";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Get sponsorship summary statistics
     *
     * @return array Summary statistics
     */
    public function getSponsorshipSummary(): array
    {
        $summary = [];

        // By status
        $result = Connection::fetchAll("
            SELECT status, COUNT(*) as count
            FROM sponsorships
            GROUP BY status
        ");

        $summary['by_status'] = [];
        foreach ($result as $row) {
            $summary['by_status'][$row['status']] = (int)$row['count'];
        }

        // Total amount (if you have an amount field)
        $summary['total_sponsorships'] = array_sum($summary['by_status']);

        // Unique sponsors
        $result = Connection::fetchRow("SELECT COUNT(DISTINCT sponsor_email) as count FROM sponsorships");
        $summary['unique_sponsors'] = (int)($result['count'] ?? 0);

        return $summary;
    }

    /**
     * Get children statistics for reports
     *
     * @return array Children statistics
     */
    public function getChildrenStats(): array
    {
        $stats = [];

        // By status
        $result = Connection::fetchAll("
            SELECT status, COUNT(*) as count
            FROM children
            GROUP BY status
        ");

        $stats['by_status'] = [];
        foreach ($result as $row) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }

        // By age group
        $result = Connection::fetchAll("
            SELECT
                CASE
                    WHEN age_months <= 48 THEN 'Birth to 4'
                    WHEN age_months <= 120 THEN 'Elementary (5-10)'
                    WHEN age_months <= 156 THEN 'Middle School (11-13)'
                    ELSE 'High School (14-18)'
                END as age_group,
                COUNT(*) as count
            FROM children
            GROUP BY age_group
        ");

        $stats['by_age_group'] = [];
        foreach ($result as $row) {
            $stats['by_age_group'][$row['age_group']] = (int)$row['count'];
        }

        // By gender
        $result = Connection::fetchAll("
            SELECT gender, COUNT(*) as count
            FROM children
            GROUP BY gender
        ");

        $stats['by_gender'] = [];
        foreach ($result as $row) {
            $stats['by_gender'][$row['gender']] = (int)$row['count'];
        }

        return $stats;
    }

    /**
     * Get sponsor information by email
     *
     * @param string $email Sponsor email
     * @return array|null Sponsor information or null if not found
     */
    public function getSponsorByEmail(string $email): ?array
    {
        $sql = "SELECT sponsor_name, sponsor_email, sponsor_phone, sponsor_address
                FROM sponsorships
                WHERE sponsor_email = ?
                LIMIT 1";

        $result = Connection::fetchRow($sql, [$email]);

        return $result ?: null;
    }

    /**
     * Update sponsor information
     *
     * @param string $oldEmail Original sponsor email
     * @param array $data New sponsor data
     * @return int Number of rows affected
     */
    public function updateSponsor(string $oldEmail, array $data): int
    {
        $sql = "UPDATE sponsorships
                SET sponsor_name = ?,
                    sponsor_email = ?,
                    sponsor_phone = ?,
                    sponsor_address = ?
                WHERE sponsor_email = ?";

        return Connection::execute($sql, [
            $data['sponsor_name'] ?? '',
            $data['sponsor_email'] ?? '',
            $data['sponsor_phone'] ?? '',
            $data['sponsor_address'] ?? '',
            $oldEmail,
        ]);
    }
}
