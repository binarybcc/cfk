<?php

declare(strict_types=1);

namespace CFK\CSV;

use CFK\Database\Connection;
use Exception;
use RuntimeException;

/**
 * CSV Handler - Import/Export for Children Data
 *
 * Handles standardized CSV format for bulk operations with validation
 * and error reporting.
 *
 * @package CFK\CSV
 */
class Handler
{
    /** @var array<string> Required CSV columns */
    public const REQUIRED_COLUMNS = [
        'age', 'gender'
    ];

    /** @var array<string> All possible CSV columns */
    public const ALL_COLUMNS = [
        'age', 'gender', 'grade',
        'shirt_size', 'pant_size', 'shoe_size', 'jacket_size',
        'interests', 'greatest_need', 'wish_list', 'special_needs', 'family_situation'
    ];

    /** @var array<string, int> Maximum lengths for text fields */
    public const MAX_LENGTHS = [
        'interests' => 500,
        'greatest_need' => 200,
        'wish_list' => 500,
        'special_needs' => 200,
        'family_situation' => 300,
        'shirt_size' => 10,
        'pant_size' => 10,
        'shoe_size' => 10,
        'jacket_size' => 10
    ];

    /** @var array<string> Import errors */
    private array $errors = [];

    /** @var array<string> Import warnings */
    private array $warnings = [];

    /** @var array<int, array<string, mixed>> Imported children */
    private array $imported = [];

    /**
     * Import children from CSV file
     *
     * @param string $csvPath Path to CSV file
     * @param array<string, mixed> $options Import options (dry_run, etc.)
     * @return array<string, mixed> Import results
     */
    public function importChildren(string $csvPath, array $options = []): array
    {
        $this->resetCounters();

        if (!file_exists($csvPath) || !is_readable($csvPath)) {
            $this->errors[] = 'CSV file not found or not readable';
            return $this->getResults();
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->errors[] = 'Could not open CSV file';
            return $this->getResults();
        }

        // Read and validate header
        $headers = fgetcsv($handle);
        if (!is_array($headers) || !$this->validateHeaders($headers)) {
            fclose($handle);
            return $this->getResults();
        }

        $rowNumber = 1;
        $familiesCreated = [];

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (array_filter($data) === []) {
                continue; // Skip empty rows
            }

            $row = $this->parseRow($headers, $data, $rowNumber);
            if (!$row) {
                continue;
            }

            // Create family if not exists (skip in dry run)
            if ($options['dry_run'] ?? false) {
                // In dry run, simulate family creation without database operations
                $familyId = $row['family_id']; // Use parsed family ID
                $this->imported[] = [
                    'name' => $row['name'],
                    'age' => $row['age'],
                    'family_id' => $familyId,
                    'child_id' => 999 // Fake ID for dry run
                ];
            } else {
                // Normal operation: create family and child
                $familyId = $this->ensureFamilyExists($row, $familiesCreated);
                if (!$familyId) {
                    continue;
                }

                // Create child
                $childId = $this->createChild($row, $familyId);
                if ($childId) {
                    $this->imported[] = [
                        'name' => $row['name'],
                        'age' => $row['age'],
                        'family_id' => $familyId,
                        'child_id' => $childId
                    ];
                }
            }
        }

        fclose($handle);
        return $this->getResults();
    }

    /**
     * Static wrapper for importChildren (for backward compatibility)
     *
     * @param string $csvPath Path to CSV file
     * @param array<string, mixed> $options Import options
     * @return array<string, mixed> Import results
     */
    public static function importChildrenFromCsv(string $csvPath, array $options = []): array
    {
        $handler = new self();
        return $handler->importChildren($csvPath, $options);
    }

    /**
     * Parse CSV and return children data without importing
     * Used for preview/analysis
     *
     * @param string $csvPath Path to CSV file
     * @return array<string, mixed> Preview results
     */
    public function parseCSVForPreview(string $csvPath): array
    {
        $this->resetCounters();
        $children = [];

        if (!file_exists($csvPath) || !is_readable($csvPath)) {
            return ['success' => false, 'error' => 'CSV file not found or not readable'];
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Could not open CSV file'];
        }

        // Read and validate header
        $headers = fgetcsv($handle);
        if (!is_array($headers) || !$this->validateHeaders($headers)) {
            fclose($handle);
            return ['success' => false, 'errors' => $this->errors];
        }

        $rowNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (array_filter($data) === []) {
                continue; // Skip empty rows
            }

            $row = $this->parseRow($headers, $data, $rowNumber);
            if ($row) {
                $children[] = $row;
            }
        }

        fclose($handle);

        return [
            'success' => $this->errors === [],
            'children' => $children,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * Export children to CSV format
     *
     * @param array<string, mixed> $filters Export filters
     * @return string CSV content
     */
    public function exportChildren(array $filters = []): string
    {
        $children = $this->getChildrenForExport($filters);

        $output = fopen('php://temp', 'w');

        if ($output === false) {
            throw new RuntimeException("Failed to open temporary stream for CSV export");
        }

        // Write header
        fputcsv($output, self::ALL_COLUMNS);

        // Write data rows
        foreach ($children as $child) {
            $row = $this->formatChildForExport($child);
            fputcsv($output, $row);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent !== false ? $csvContent : '';
    }

    /**
     * Get import results
     *
     * @return array<string, mixed> Results with success status and details
     */
    public function getResults(): array
    {
        $success = $this->errors === [];
        $imported_count = count($this->imported);

        return [
            'success' => $success,
            'imported' => $imported_count,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'details' => $this->imported,
            'message' => $success
                ? "Successfully imported {$imported_count} children"
                : "Import failed with " . count($this->errors) . " error(s)"
        ];
    }

    /**
     * Validate CSV headers
     *
     * @param array<int|string, mixed> $headers Header row from CSV
     * @return bool True if valid
     */
    private function validateHeaders(array $headers): bool
    {
        if ($headers === []) {
            $this->errors[] = 'Empty CSV file or no headers found';
            return false;
        }

        // Check for required columns
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!in_array($required, $headers)) {
                $missing[] = $required;
            }
        }

        if ($missing !== []) {
            $this->errors[] = 'Missing required columns: ' . implode(', ', $missing);
            return false;
        }

        return true;
    }

    /**
     * Parse single CSV row
     *
     * @param array<int|string, mixed> $headers CSV headers
     * @param array<int|string, mixed> $data Row data
     * @param int $rowNumber Current row number for error reporting
     * @return array<string, mixed>|null Parsed row data or null on error
     */
    private function parseRow(array $headers, array $data, int $rowNumber): ?array
    {
        if (count($headers) !== count($data)) {
            $this->errors[] = "Row $rowNumber: Column count mismatch";
            return null;
        }

        $row = array_combine($headers, $data);
        if ($row === false) {
            $this->errors[] = "Row $rowNumber: Failed to combine headers and data";
            return null;
        }

        // Validate required fields
        foreach (self::REQUIRED_COLUMNS as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            // Special handling for age field - 0 is valid, also accept months like "10m"
            if ($field === 'age') {
                if ($value === '' || (!is_numeric($value) && !preg_match('/^\d+m$/', $value))) {
                    $this->errors[] = "Row $rowNumber: Missing or invalid required field '$field'";
                    return null;
                }
            } elseif ($value === '' || $value === '0') {
                $this->errors[] = "Row $rowNumber: Missing required field '$field'";
                return null;
            }
        }

        // Clean and validate data
        $row = $this->cleanRowData($row, $rowNumber);

        return $this->validateRowData($row, $rowNumber) ? $row : null;
    }

    /**
     * Clean row data
     *
     * @param array<string, mixed> $row Row data
     * @param int $rowNumber Current row number
     * @return array<string, mixed> Cleaned row data
     */
    private function cleanRowData(array $row, int $rowNumber): array
    {
        // Trim all text fields
        foreach ($row as $key => $value) {
            $row[$key] = is_string($value) ? trim($value) : $value;
        }

        // Ensure required fields have values - handle months format
        if (preg_match('/^(\d+)m$/', (string) $row['age'], $matches)) {
            // Convert months to decimal age (e.g., 10m = 0.83 years)
            $months = (int) $matches[1];
            $row['age'] = round($months / 12, 2);
            if ($row['age'] == 0 && $months > 0) {
                $row['age'] = 0.1;
            } // Ensure non-zero for babies
        } else {
            $row['age'] = (int) $row['age'];
        }
        $row['gender'] = strtoupper((string) $row['gender']);

        // Parse family ID from name field (e.g., "001A" -> family_id=001, child_letter=A)
        if (preg_match('/^(\d{1,4})([A-Z])$/', (string) $row['name'], $matches)) {
            $row['family_id'] = (int) $matches[1];
            $row['child_letter'] = $matches[2];
        } else {
            $this->errors[] = "Row $rowNumber: Invalid name format '{$row['name']}' (use format: 123A)";
            return $row;
        }

        // Set defaults for optional fields
        $row['grade'] ??= '';
        $row['special_needs'] ??= 'None';
        $row['family_situation'] ??= '';
        $row['greatest_need'] ??= '';
        $row['wish_list'] ??= '';
        $row['interests'] ??= '';
        $row['shirt_size'] ??= '';
        $row['pant_size'] ??= '';
        $row['shoe_size'] ??= '';
        $row['jacket_size'] ??= '';

        return $row;
    }

    /**
     * Validate row data
     *
     * @param array<string, mixed> $row Row data (passed by reference for truncation)
     * @param int $rowNumber Current row number
     * @return bool True if valid
     */
    private function validateRowData(array &$row, int $rowNumber): bool
    {
        $valid = true;

        // Age validation - handle numeric ages (including decimals for months)
        if ($row['age'] < 0 || $row['age'] > 18) {
            $this->errors[] = "Row $rowNumber: Age must be between 0 and 18";
            $valid = false;
        }

        // Gender validation
        if (!in_array($row['gender'], ['M', 'F'])) {
            $this->errors[] = "Row $rowNumber: Gender must be 'M' or 'F'";
            $valid = false;
        }

        // Family ID validation
        if ($row['family_id'] <= 0) {
            $this->errors[] = "Row $rowNumber: Family ID must be positive number";
            $valid = false;
        }

        // Text length validation
        foreach (self::MAX_LENGTHS as $field => $maxLength) {
            if (isset($row[$field]) && strlen((string) $row[$field]) > $maxLength) {
                $this->warnings[] = "Row $rowNumber: $field exceeds $maxLength characters, will be truncated";
                $row[$field] = substr((string) $row[$field], 0, $maxLength);
            }
        }

        // Age/grade consistency check (convert decimals to int for checking)
        if (!empty($row['grade']) && $this->isAgeGradeMismatch((int) $row['age'], (string) $row['grade'])) {
            $this->warnings[] = "Row $rowNumber: Age {$row['age']} and grade '{$row['grade']}' may not match";
        }

        return $valid;
    }

    /**
     * Ensure family exists, create if needed
     *
     * @param array<string, mixed> $row Row data with family_id
     * @param array<int, int> $familiesCreated Cache of created families
     * @return int|null Database family ID or null on error
     */
    private function ensureFamilyExists(array $row, array &$familiesCreated): ?int
    {
        $familyId = (int) $row['family_id'];

        if (isset($familiesCreated[$familyId])) {
            return $familiesCreated[$familyId];
        }

        // Check if family already exists in database
        $existing = Connection::fetchRow(
            "SELECT id FROM families WHERE family_number = ?",
            [(string) $familyId]
        );

        if ($existing) {
            $familiesCreated[$familyId] = (int) $existing['id'];
            return (int) $existing['id'];
        }

        // Create new family
        try {
            $dbFamilyId = Connection::insert('families', [
                'family_number' => (string) $familyId,
                'notes' => $row['family_situation'] ?? ''
            ]);

            $familiesCreated[$familyId] = $dbFamilyId;
            return $dbFamilyId;
        } catch (Exception $e) {
            $this->errors[] = "Failed to create family {$familyId}: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Create child record
     *
     * @param array<string, mixed> $row Row data
     * @param int $familyId Database family ID
     * @return int|null Child ID or null on error
     */
    private function createChild(array $row, int $familyId): ?int
    {
        // Generate next child letter for this family
        $childLetter = $this->getNextChildLetter($familyId);

        $childData = [
            'family_id' => $familyId,
            'child_letter' => $childLetter,
            'name' => $row['name'],
            'age' => $row['age'],
            'gender' => $row['gender'],
            'grade' => $row['grade'] ?? '',
            'school' => '', // Not in CSV
            'shirt_size' => $row['shirt_size'] ?? '',
            'pant_size' => $row['pant_size'] ?? '',
            'shoe_size' => $row['shoe_size'] ?? '',
            'jacket_size' => $row['jacket_size'] ?? '',
            'interests' => $row['interests'] ?? '',
            'wishes' => $row['greatest_need'] . ($row['wish_list'] ? '. Wish List: ' . $row['wish_list'] : ''),
            'special_needs' => $row['special_needs'] ?? 'None',
            'status' => 'available'
        ];

        try {
            return Connection::insert('children', $childData);
        } catch (Exception $e) {
            // Get family number for error message
            $familyInfo = Connection::fetchRow("SELECT family_number FROM families WHERE id = ?", [$familyId]);
            $familyNumber = $familyInfo ? $familyInfo['family_number'] : $familyId;
            $this->errors[] = "Failed to create child (family {$familyNumber}{$childLetter}): " . $e->getMessage();
            return null;
        }
    }

    /**
     * Get next available child letter for family
     *
     * @param int $familyId Database family ID
     * @return string Next available letter (A-Z)
     */
    private function getNextChildLetter(int $familyId): string
    {
        $existingLetters = Connection::fetchAll(
            "SELECT child_letter FROM children WHERE family_id = ? ORDER BY child_letter",
            [$familyId]
        );

        $usedLetters = array_column($existingLetters, 'child_letter');

        for ($i = 0; $i < 26; $i++) {
            $letter = chr(65 + $i); // A, B, C...
            if (!in_array($letter, $usedLetters)) {
                return $letter;
            }
        }

        return 'A'; // Fallback
    }

    /**
     * Get children for export
     *
     * @param array<string, mixed> $filters Export filters
     * @return array<int, array<string, mixed>> Children data
     */
    private function getChildrenForExport(array $filters): array
    {
        $sql = "
            SELECT c.*, f.family_number, f.notes as family_notes
            FROM children c
            JOIN families f ON c.family_id = f.id
            WHERE 1=1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['age_min'])) {
            $sql .= " AND c.age >= :age_min";
            $params['age_min'] = $filters['age_min'];
        }

        if (!empty($filters['age_max'])) {
            $sql .= " AND c.age <= :age_max";
            $params['age_max'] = $filters['age_max'];
        }

        $sql .= " ORDER BY f.family_number, c.child_letter";

        return Connection::fetchAll($sql, $params);
    }

    /**
     * Format child data for CSV export
     *
     * @param array<string, mixed> $child Child data from database
     * @return array<int, mixed> Formatted row for CSV
     */
    private function formatChildForExport(array $child): array
    {
        // Split wishes back into greatest_need and wish_list
        $wishes = $child['wishes'] ?? '';
        $wishParts = explode('. Wish List: ', (string) $wishes);
        $greatestNeed = $wishParts[0] ?? '';
        $wishList = $wishParts[1] ?? '';

        return [
            $child['display_id'] ?? $child['family_number'] . $child['child_letter'],
            $child['age'],
            $child['gender'],
            $child['family_number'],
            $child['grade'] ?? '',
            $child['shirt_size'] ?? '',
            $child['pant_size'] ?? '',
            $child['shoe_size'] ?? '',
            $child['jacket_size'] ?? '',
            $child['interests'] ?? '',
            $greatestNeed,
            $wishList,
            $child['special_needs'] ?? 'None',
            $child['family_notes'] ?? ''
        ];
    }

    /**
     * Check for age/grade mismatch
     *
     * @param int $age Child age
     * @param string $grade Child grade
     * @return bool True if mismatch detected
     */
    private function isAgeGradeMismatch(int $age, string $grade): bool
    {
        $gradeMap = [
            'Pre-K' => [3, 4, 5],
            'K' => [4, 5, 6],
            '1st' => [5, 6, 7],
            '2nd' => [6, 7, 8],
            '3rd' => [7, 8, 9],
            '4th' => [8, 9, 10],
            '5th' => [9, 10, 11],
            '6th' => [10, 11, 12],
            '7th' => [11, 12, 13],
            '8th' => [12, 13, 14],
            '9th' => [13, 14, 15],
            '10th' => [14, 15, 16],
            '11th' => [15, 16, 17],
            '12th' => [16, 17, 18]
        ];

        if (!isset($gradeMap[$grade])) {
            return false; // Unknown grade, can't validate
        }

        return !in_array($age, $gradeMap[$grade]);
    }

    /**
     * Reset counters for new operation
     */
    private function resetCounters(): void
    {
        $this->errors = [];
        $this->warnings = [];
        $this->imported = [];
    }
}
