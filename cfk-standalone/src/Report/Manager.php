<?php

declare(strict_types=1);

namespace CFK\Report;

use CFK\Database\Connection;
use RuntimeException;

/**
 * Report Manager - Comprehensive Reporting for CFK
 *
 * Handles all report generation and data export functionality including
 * sponsor directories, child lookups, gift delivery tracking, and statistics.
 *
 * @package CFK\Report
 */
class Manager
{
    /**
     * Get sponsor directory report
     *
     * @param array<string, mixed> $filters Optional filters (status, sponsor_email)
     * @return array<int, array<string, mixed>> Sponsor directory data
     */
    public static function getSponsorDirectoryReport(array $filters = []): array
    {
        $sql = "
            SELECT
                s.id,
                s.sponsor_name,
                s.sponsor_email,
                s.sponsor_phone,
                s.sponsor_address,
                s.status,
                s.request_date,
                s.confirmation_date,
                c.id as child_id,
                CONCAT(f.family_number, c.child_letter) as child_name,
                c.age_months as child_age,
                c.gender as child_gender,
                c.shirt_size,
                c.pant_size,
                c.shoe_size,
                c.jacket_size,
                c.interests,
                c.wishes,
                c.special_needs,
                f.id as family_id,
                f.family_number,
                CONCAT(f.family_number, c.child_letter) as child_display_id
            FROM sponsorships s
            JOIN children c ON s.child_id = c.id
            JOIN families f ON c.family_id = f.id
            WHERE s.status != 'cancelled'
        ";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['sponsor_email'])) {
            $sql .= " AND s.sponsor_email LIKE :email";
            $params['email'] = '%' . $filters['sponsor_email'] . '%';
        }

        $sql .= " ORDER BY s.sponsor_name, f.family_number, c.child_letter";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Get child-sponsor lookup
     *
     * @param string $childId Child ID to search for (optional)
     * @return array<int, array<string, mixed>> Child-sponsor lookup data
     */
    public static function getChildSponsorLookup(string $childId = ''): array
    {
        $sql = "
            SELECT
                c.id as child_id,
                CONCAT(f.family_number, c.child_letter) as child_display_id,
                CONCAT(f.family_number, c.child_letter) as child_name,
                c.age_months,
                c.gender,
                c.status as child_status,
                c.interests,
                c.wishes,
                c.special_needs,
                c.shirt_size,
                c.pant_size,
                c.jacket_size,
                c.shoe_size,
                s.id as sponsorship_id,
                s.sponsor_name,
                s.sponsor_email,
                s.sponsor_phone,
                s.status as sponsorship_status,
                s.request_date,
                s.confirmation_date,
                s.completion_date
            FROM children c
            JOIN families f ON c.family_id = f.id
            LEFT JOIN sponsorships s ON c.id = s.child_id AND s.status != 'cancelled'
        ";

        $params = [];

        if ($childId !== '' && $childId !== '0') {
            $sql .= " WHERE CONCAT(f.family_number, c.child_letter) LIKE :child_id";
            $params['child_id'] = '%' . $childId . '%';
        }

        $sql .= " ORDER BY f.family_number, c.child_letter";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Get family sponsorship report
     *
     * @return array<int, array<string, mixed>> Family sponsorship data
     */
    public static function getFamilySponsorshipReport(): array
    {
        return Connection::fetchAll("
            SELECT
                f.id as family_id,
                f.family_number,
                COUNT(c.id) as total_children,
                SUM(CASE WHEN c.status = 'available' THEN 1 ELSE 0 END) as available_count,
                SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN c.status = 'sponsored' THEN 1 ELSE 0 END) as sponsored_count,
                GROUP_CONCAT(
                    CONCAT(f.family_number, c.child_letter, ':', c.status)
                    ORDER BY c.child_letter
                    SEPARATOR '|'
                ) as children_details
            FROM families f
            LEFT JOIN children c ON f.id = c.family_id
            GROUP BY f.id, f.family_number
            ORDER BY f.family_number
        ");
    }

    /**
     * Get gift delivery tracking report
     *
     * @param array<string, mixed> $filters Optional filters (status)
     * @return array<int, array<string, mixed>> Gift delivery tracking data
     */
    public static function getGiftDeliveryReport(array $filters = []): array
    {
        $sql = "
            SELECT
                s.id,
                s.sponsor_name,
                s.sponsor_email,
                s.sponsor_phone,
                s.status,
                s.request_date,
                s.confirmation_date,
                s.completion_date,
                CONCAT(f.family_number, c.child_letter) as child_display_id,
                CONCAT(f.family_number, c.child_letter) as child_name,
                c.age_months,
                c.gender,
                DATEDIFF(NOW(), s.confirmation_date) as days_since_confirmed
            FROM sponsorships s
            JOIN children c ON s.child_id = c.id
            JOIN families f ON c.family_id = f.id
            WHERE s.status IN ('confirmed', 'completed')
        ";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY s.confirmation_date ASC";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Get available children report
     *
     * @param array<string, mixed> $filters Optional filters (age_min, age_max, gender)
     * @return array<int, array<string, mixed>> Available children data
     */
    public static function getAvailableChildrenReport(array $filters = []): array
    {
        $sql = "
            SELECT
                c.id,
                CONCAT(f.family_number, c.child_letter) as display_id,
                CONCAT(f.family_number, c.child_letter) as name,
                c.age_months,
                c.gender,
                c.grade,
                c.shirt_size,
                c.pant_size,
                c.shoe_size,
                c.jacket_size,
                c.interests,
                c.wishes,
                c.special_needs,
                f.family_number,
                (SELECT COUNT(*) FROM children c2 WHERE c2.family_id = f.id) as family_size,
                (SELECT COUNT(*) FROM children c3 WHERE c3.family_id = f.id AND c3.status = 'available') as available_siblings
            FROM children c
            JOIN families f ON c.family_id = f.id
            WHERE c.status = 'available'
        ";

        $params = [];

        if (!empty($filters['age_min'])) {
            $sql .= " AND c.age_months >= :age_min";
            $params['age_min'] = $filters['age_min'];
        }

        if (!empty($filters['age_max'])) {
            $sql .= " AND c.age_months <= :age_max";
            $params['age_max'] = $filters['age_max'];
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND c.gender = :gender";
            $params['gender'] = $filters['gender'];
        }

        $sql .= " ORDER BY f.family_number, c.child_letter";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Export data to CSV
     *
     * @param array<int, array<string, mixed>> $data Data to export
     * @param array<int, string> $headers CSV column headers
     * @param string $filename Output filename
     * @return never This function exits after sending headers
     */
    public static function exportToCSV(array $data, array $headers, string $filename): never
    {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            throw new RuntimeException("Failed to open output stream for CSV export");
        }

        // Write headers
        fputcsv($output, $headers);

        // Write data
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $key = strtolower(str_replace(' ', '_', $header));
                $csvRow[] = $row[$key] ?? '';
            }
            fputcsv($output, $csvRow);
        }

        fclose($output);
        exit;
    }

    /**
     * Generate shopping list for sponsor
     *
     * @param string $sponsorEmail Sponsor email address
     * @return array<int, array<string, mixed>> Shopping list data
     */
    public static function generateShoppingList(string $sponsorEmail): array
    {
        return Connection::fetchAll("
            SELECT
                s.sponsor_name,
                s.sponsor_email,
                CONCAT(f.family_number, c.child_letter) as child_display_id,
                CONCAT(f.family_number, c.child_letter) as child_name,
                c.age_months,
                c.gender,
                c.shirt_size,
                c.pant_size,
                c.shoe_size,
                c.jacket_size,
                c.interests,
                c.wishes,
                c.special_needs,
                f.family_number
            FROM sponsorships s
            JOIN children c ON s.child_id = c.id
            JOIN families f ON c.family_id = f.id
            WHERE s.sponsor_email = :email
            AND s.status != 'cancelled'
            ORDER BY f.family_number, c.child_letter
        ", ['email' => $sponsorEmail]);
    }

    /**
     * Get complete children and sponsor report
     * Includes all child information and sponsor details (if sponsored)
     *
     * @param array<string, mixed> $filters Optional filters (status, sponsored_only)
     * @return array<int, array<string, mixed>> Complete report data
     */
    public static function getCompleteChildSponsorReport(array $filters = []): array
    {
        $sql = "
            SELECT
                -- Child Information
                CONCAT(f.family_number, c.child_letter) as child_id,
                CONCAT(f.family_number, c.child_letter) as child_name,
                c.age_months,
                c.gender,
                c.grade,
                c.school,
                c.shirt_size,
                c.pant_size,
                c.shoe_size,
                c.jacket_size,
                c.interests,
                c.wishes,
                c.special_needs,
                c.status as child_status,
                -- Family Information
                f.family_number,
                -- Sponsor Information (null if not sponsored)
                s.sponsor_name,
                s.sponsor_email,
                s.sponsor_phone,
                s.sponsor_address,
                s.status as sponsorship_status,
                s.request_date,
                s.confirmation_date,
                s.completion_date,
                -- Sponsorship Date (prefer confirmation, fallback to request)
                COALESCE(s.confirmation_date, s.request_date) as sponsorship_date
            FROM children c
            JOIN families f ON c.family_id = f.id
            LEFT JOIN sponsorships s ON c.id = s.child_id AND s.status != 'cancelled'
            WHERE 1=1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['sponsored_only'])) {
            $sql .= " AND s.id IS NOT NULL";
        }

        $sql .= " ORDER BY f.family_number, c.child_letter";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Get statistics summary
     *
     * @return array<string, mixed> Statistics summary
     */
    public static function getStatisticsSummary(): array
    {
        $stats = [];

        // Children stats
        $childStats = Connection::fetchRow("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sponsored' THEN 1 ELSE 0 END) as sponsored
            FROM children
        ");
        $stats['children'] = $childStats;

        // Sponsorship stats
        $sponsorshipStats = Connection::fetchRow("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM sponsorships
        ");
        $stats['sponsorships'] = $sponsorshipStats;

        // Unique sponsors
        $uniqueSponsors = Connection::fetchRow("
            SELECT COUNT(DISTINCT sponsor_email) as count
            FROM sponsorships
            WHERE status != 'cancelled'
        ");
        $stats['unique_sponsors'] = $uniqueSponsors['count'] ?? 0;

        // Families
        $familyStats = Connection::fetchRow("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN (
                    SELECT COUNT(*)
                    FROM children c
                    WHERE c.family_id = f.id AND c.status = 'sponsored'
                ) = (
                    SELECT COUNT(*)
                    FROM children c2
                    WHERE c2.family_id = f.id
                ) THEN 1 ELSE 0 END) as fully_sponsored
            FROM families f
        ");
        $stats['families'] = $familyStats;

        return $stats;
    }
}
