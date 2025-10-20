<?php
/**
 * DEPRECATED: Moved to src/CSV/Handler.php
 * Class available via class_alias() in config.php
 */
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}
return;

class CFK_CSV_Handler_DEPRECATED {
    
    const REQUIRED_COLUMNS = [
        'age', 'gender'
    ];

    const ALL_COLUMNS = [
        'age', 'gender', 'grade',
        'shirt_size', 'pant_size', 'shoe_size', 'jacket_size',
        'interests', 'greatest_need', 'wish_list', 'special_needs', 'family_situation'
    ];
    
    const MAX_LENGTHS = [
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
    
    private array $errors = [];
    private array $warnings = [];
    private array $imported = [];
    
    /**
     * Import children from CSV file
     */
    public function importChildren(string $csvPath, array $options = []): array {
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
        if (!$this->validateHeaders($headers)) {
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
     */
    public static function importChildrenFromCsv(string $csvPath, array $options = []): array {
        $handler = new self();
        return $handler->importChildren($csvPath, $options);
    }

    /**
     * Parse CSV and return children data without importing
     * Used for preview/analysis
     */
    public function parseCSVForPreview(string $csvPath): array {
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
        if (!$this->validateHeaders($headers)) {
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
     */
    public function exportChildren(array $filters = []): string {
        $children = $this->getChildrenForExport($filters);
        
        $output = fopen('php://temp', 'w');
        
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
        
        return $csvContent;
    }
    
    /**
     * Get import results
     */
    public function getResults(): array {
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
     */
    private function validateHeaders(array $headers): bool {
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
     */
    private function parseRow(array $headers, array $data, int $rowNumber): ?array {
        if (count($headers) !== count($data)) {
            $this->errors[] = "Row $rowNumber: Column count mismatch";
            return null;
        }
        
        $row = array_combine($headers, $data);
        
        // Validate required fields
        foreach (self::REQUIRED_COLUMNS as $field) {
            $value = trim($row[$field] ?? '');
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
     */
    private function cleanRowData(array $row, int $rowNumber): array {
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
     */
    private function validateRowData(array &$row, int $rowNumber): bool {
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
        if (!empty($row['grade']) && $this->isAgeGradeMismatch((int) $row['age'], $row['grade'])) {
            $this->warnings[] = "Row $rowNumber: Age {$row['age']} and grade '{$row['grade']}' may not match";
        }
        
        return $valid;
    }
    
    /**
     * Ensure family exists, create if needed
     */
    private function ensureFamilyExists(array $row, array &$familiesCreated): ?int {
        $familyId = $row['family_id'];
        
        if (isset($familiesCreated[$familyId])) {
            return $familiesCreated[$familyId];
        }
        
        // Check if family already exists in database
        $existing = Database::fetchRow(
            "SELECT id FROM families WHERE family_number = ?",
            [(string) $familyId]
        );
        
        if ($existing) {
            $familiesCreated[$familyId] = $existing['id'];
            return $existing['id'];
        }
        
        // Create new family
        try {
            $dbFamilyId = Database::insert('families', [
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
     */
    private function createChild(array $row, int $familyId): ?int {
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
            return Database::insert('children', $childData);
        } catch (Exception $e) {
            $this->errors[] = "Failed to create child (family {$familyNumber}{$childLetter}): " . $e->getMessage();
            return null;
        }
    }
    
    /**
     * Get next available child letter for family
     */
    private function getNextChildLetter(int $familyId): string {
        $existingLetters = Database::fetchAll(
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
     */
    private function getChildrenForExport(array $filters): array {
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
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Format child data for CSV export
     */
    private function formatChildForExport(array $child): array {
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
     */
    private function isAgeGradeMismatch(int $age, string $grade): bool {
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
    private function resetCounters(): void {
        $this->errors = [];
        $this->warnings = [];
        $this->imported = [];
    }
}