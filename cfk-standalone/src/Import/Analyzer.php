<?php

declare(strict_types=1);

namespace CFK\Import;

use CFK\CSV\Handler as CSVHandler;
use CFK\Database\Connection;

/**
 * Import Analyzer - Analyzes CSV imports and detects important changes
 * Provides smart warnings for sponsored children, data loss, etc.
 *
 * @package CFK\Import
 */
class Analyzer
{
    /**
     * Analyze CSV import and generate change report
     *
     * @param array<int, array<string, mixed>> $newChildren Array of new child data
     *
     * @return (array|int)[][] Analysis results
     *
     * @psalm-return array{new_children: list<array<string, mixed>>, updated_children: list<array{changes: non-empty-array<string, array<string, mixed>>, new: array<string, mixed>, old: array<string, mixed>}>, removed_children: list<array<string, mixed>>, warnings: list{0?: array<string, mixed>,...}, errors: array<never, never>, stats: array{total_new: 0|1|2, total_updated: 0|1|2, total_removed: 0|1|2, total_unchanged: 0|1|2}}
     */
    public static function analyzeImport(array $newChildren): array
    {
        $currentChildren = self::getCurrentChildren();

        $analysis = [
            'new_children' => [],
            'updated_children' => [],
            'removed_children' => [],
            'warnings' => [],
            'errors' => [],
            'stats' => [
                'total_new' => 0,
                'total_updated' => 0,
                'total_removed' => 0,
                'total_unchanged' => 0,
            ],
        ];

        // Build lookup of current children by family_id + child_letter
        $currentLookup = [];
        foreach ($currentChildren as $child) {
            $key = $child['family_id'] . '_' . $child['child_letter'];
            $currentLookup[$key] = $child;
        }

        // Build lookup of new children
        $newLookup = [];
        foreach ($newChildren as $child) {
            $key = $child['family_id'] . '_' . $child['child_letter'];
            $newLookup[$key] = $child;
        }

        // Find new and updated children
        foreach ($newChildren as $newChild) {
            $key = $newChild['family_id'] . '_' . $newChild['child_letter'];

            if (! isset($currentLookup[$key])) {
                // New child
                $analysis['new_children'][] = $newChild;
                $analysis['stats']['total_new']++;
            } else {
                // Existing child - check for changes
                $oldChild = $currentLookup[$key];
                $changes = self::detectChanges($oldChild, $newChild);

                if ($changes !== []) {
                    $analysis['updated_children'][] = [
                        'old' => $oldChild,
                        'new' => $newChild,
                        'changes' => $changes,
                    ];
                    $analysis['stats']['total_updated']++;

                    // Check for concerning changes
                    $warnings = self::checkForWarnings($oldChild, $newChild, $changes);
                    if ($warnings !== []) {
                        $analysis['warnings'] = array_merge($analysis['warnings'], $warnings);
                    }
                } else {
                    $analysis['stats']['total_unchanged']++;
                }
            }
        }

        // Find removed children
        foreach ($currentChildren as $oldChild) {
            $key = $oldChild['family_id'] . '_' . $oldChild['child_letter'];

            if (! isset($newLookup[$key])) {
                $analysis['removed_children'][] = $oldChild;
                $analysis['stats']['total_removed']++;

                // Warn about sponsored children being removed
                if ($oldChild['status'] === 'sponsored' || $oldChild['status'] === 'pending') {
                    $analysis['warnings'][] = [
                        'type' => 'sponsored_child_removed',
                        'severity' => 'high',
                        'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}) is {$oldChild['status']} but not in new upload",
                        'child' => $oldChild,
                    ];
                }
            }
        }

        return $analysis;
    }

    /**
     * Detect changes between old and new child data
     *
     * @param array<string, mixed> $oldChild Old child data
     * @param array<string, mixed> $newChild New child data
     *
     * @return (mixed|string)[][] Changes detected
     *
     * @psalm-return array{special_needs?: array{old: ''|mixed, new: ''|mixed}, wishes?: array{old: ''|mixed, new: ''|mixed}, interests?: array{old: ''|mixed, new: ''|mixed}, jacket_size?: array{old: ''|mixed, new: ''|mixed}, shoe_size?: array{old: ''|mixed, new: ''|mixed}, pant_size?: array{old: ''|mixed, new: ''|mixed}, shirt_size?: array{old: ''|mixed, new: ''|mixed}, grade?: array{old: ''|mixed, new: ''|mixed}, gender?: array{old: ''|mixed, new: ''|mixed}, age?: array{old: ''|mixed, new: ''|mixed}, name?: array{old: ''|mixed, new: ''|mixed}}
     */
    private static function detectChanges(array $oldChild, array $newChild): array
    {
        $changes = [];
        $fieldsToCheck = ['name', 'age', 'gender', 'grade', 'shirt_size', 'pant_size',
                         'shoe_size', 'jacket_size', 'interests', 'wishes', 'special_needs'];

        foreach ($fieldsToCheck as $field) {
            $oldValue = $oldChild[$field] ?? '';
            $newValue = $newChild[$field] ?? '';

            if ($oldValue != $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check for concerning changes that need warnings
     *
     * @param array<string, mixed> $oldChild Old child data
     * @param array<string, mixed> $newChild New child data (unused but kept for consistency)
     * @param array<string, array<string, mixed>> $changes Detected changes
     *
     * @return (array|string)[][] Warnings
     *
     * @psalm-return list{0?: array{type: 'age_decreased'|'data_loss'|'gender_changed', severity: 'low'|'medium', message: string, child: array<string, mixed>, field?: string},...}
     */
    private static function checkForWarnings(array $oldChild, array $newChild, array $changes): array
    {
        $warnings = [];

        // Check for data becoming blank
        foreach ($changes as $field => $change) {
            if (! empty($change['old']) && empty($change['new'])) {
                $warnings[] = [
                    'type' => 'data_loss',
                    'severity' => 'medium',
                    'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}): {$field} will be cleared (was: {$change['old']})",
                    'child' => $oldChild,
                    'field' => $field,
                ];
            }
        }

        // Check for age decrease (likely error)
        if (isset($changes['age'])) {
            $oldAge = (int) $changes['age']['old'];
            $newAge = (int) $changes['age']['new'];

            if ($newAge < $oldAge && $oldAge > 0) {
                $warnings[] = [
                    'type' => 'age_decreased',
                    'severity' => 'medium',
                    'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}): Age decreased from {$oldAge} to {$newAge} (possible error?)",
                    'child' => $oldChild,
                ];
            }
        }

        // Check for gender change (unusual)
        if (isset($changes['gender'])) {
            $warnings[] = [
                'type' => 'gender_changed',
                'severity' => 'low',
                'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}): Gender changed from {$changes['gender']['old']} to {$changes['gender']['new']}",
                'child' => $oldChild,
            ];
        }

        return $warnings;
    }

    /**
     * Get all current children from database
     *
     * @return array<int, array<string, mixed>> Current children data
     */
    private static function getCurrentChildren(): array
    {
        return Connection::fetchAll("
            SELECT c.*, f.family_number
            FROM children c
            LEFT JOIN families f ON c.family_id = f.id
            ORDER BY CAST(f.family_number AS UNSIGNED), c.child_letter
        ");
    }

    /**
     * Apply import with sponsorship preservation
     *
     * @param string $csvPath Path to CSV file
     * @param array<string, mixed> $options Import options
     * @return array<string, mixed> Import results
     */
    public static function applyImportWithPreservation(string $csvPath, array $options = []): array
    {
        $importMode = $options['import_mode'] ?? 'replace'; // 'replace', 'append', 'update'

        // Store current sponsorship statuses for all modes
        $sponsorships = Connection::fetchAll("
            SELECT c.id, c.family_id, c.child_letter, c.status, f.family_number
            FROM children c
            LEFT JOIN families f ON c.family_id = f.id
            WHERE c.status IN ('sponsored', 'pending')
        ");

        $sponsorshipLookup = [];
        foreach ($sponsorships as $child) {
            $key = $child['family_number'] . '_' . $child['child_letter'];
            $sponsorshipLookup[$key] = $child['status'];
        }

        // Parse CSV to get new children data
        $handler = new CSVHandler();
        $parseResult = $handler->parseCSVForPreview($csvPath);

        if (! $parseResult['success']) {
            return $parseResult;
        }

        $newChildren = $parseResult['children'];

        // Apply import based on mode
        switch ($importMode) {
            case 'replace':
                return self::applyReplaceMode($csvPath, $sponsorshipLookup, $options);

            case 'append':
                return self::applyAppendMode($newChildren, $sponsorshipLookup);

            case 'update':
                return self::applyUpdateMode($newChildren, $sponsorshipLookup);

            default:
                return ['success' => false, 'message' => 'Invalid import mode'];
        }
    }

    /**
     * Replace mode: Delete all, insert new (current behavior)
     *
     * @param array<string, string> $sponsorshipLookup Existing sponsorships (family_number_childLetter => status)
     * @param array<string, mixed> $options Import options (keep_inactive, etc.)
     * @return array<string, mixed> Result array with success status and counts
     *
     * @psalm-return array{success: mixed, sponsorships_preserved?: int|mixed, import_mode?: 'replace'|mixed,...}
     */
    private static function applyReplaceMode(string $csvPath, array $sponsorshipLookup, array $options): array
    {
        $keepInactive = $options['keep_inactive'] ?? true;

        // Clear existing data (unless keeping inactive)
        if (! $keepInactive) {
            Connection::query('DELETE FROM children WHERE 1=1');
            Connection::query('DELETE FROM families WHERE 1=1');
        }

        // Import new data
        $handler = new CSVHandler();
        $result = $handler->importChildren($csvPath, ['dry_run' => false]);

        if (! $result['success']) {
            return $result;
        }

        // Restore sponsorship statuses
        $restored = self::restoreSponsorshipStatuses($sponsorshipLookup);
        $result['sponsorships_preserved'] = $restored;
        $result['import_mode'] = 'replace';

        return $result;
    }

    /**
     * Append mode: Only insert children that don't exist
     *
     * @param array<int, array<string, mixed>> $newChildren Array of new child records to import
     * @param array<string, string> $sponsorshipLookup Existing sponsorships (family_number_childLetter => status)
     * @return array<string, mixed> Result array with success status and counts
     *
     * @psalm-return array{success: bool, imported: int<0, max>, skipped: int<0, max>, errors: list{0?: string,...}, message: string, import_mode: 'append'}
     */
    private static function applyAppendMode(array $newChildren, array $sponsorshipLookup): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Get existing children lookup
        $existing = Connection::fetchAll("
            SELECT c.id, f.family_number, c.child_letter
            FROM children c
            JOIN families f ON c.family_id = f.id
        ");

        $existingLookup = [];
        foreach ($existing as $child) {
            $key = $child['family_number'] . '_' . $child['child_letter'];
            $existingLookup[$key] = true;
        }

        // Insert only new children
        foreach ($newChildren as $childData) {
            $key = $childData['family_id'] . '_' . $childData['child_letter'];

            if (isset($existingLookup[$key])) {
                $skipped++;

                continue; // Skip existing children
            }

            // Ensure family exists
            $familyId = self::ensureFamilyExists($childData);
            if (! $familyId) {
                $errors[] = "Failed to create family for {$childData['name']}";

                continue;
            }

            // Insert child
            try {
                $childId = self::insertChild($childData, $familyId);
                if ($childId) {
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to insert {$childData['name']}: " . $e->getMessage();
            }
        }

        return [
            'success' => $errors === [],
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "Appended {$imported} new children ({$skipped} existing children skipped)",
            'import_mode' => 'append',
        ];
    }

    /**
     * Update mode: Update existing children, insert new ones
     *
     * @param array<int, array<string, mixed>> $newChildren Array of new child records to import
     * @param array<string, string> $sponsorshipLookup Existing sponsorships (family_number_childLetter => status)
     * @return array<string, mixed> Result array with success status and counts
     *
     * @psalm-return array{success: bool, imported: int<0, max>, inserted: int<0, max>, updated: int<0, max>, errors: list{0?: string,...}, message: string, import_mode: 'update'}
     */
    private static function applyUpdateMode(array $newChildren, array $sponsorshipLookup): array
    {
        $inserted = 0;
        $updated = 0;
        $errors = [];

        // Get existing children
        $existing = Connection::fetchAll("
            SELECT c.*, f.family_number
            FROM children c
            JOIN families f ON c.family_id = f.id
        ");

        $existingLookup = [];
        foreach ($existing as $child) {
            $key = $child['family_number'] . '_' . $child['child_letter'];
            $existingLookup[$key] = $child;
        }

        // Process each child from CSV
        foreach ($newChildren as $childData) {
            $key = $childData['family_id'] . '_' . $childData['child_letter'];

            // Ensure family exists
            $familyId = self::ensureFamilyExists($childData);
            if (! $familyId) {
                $errors[] = "Failed to create family for {$childData['name']}";

                continue;
            }

            if (isset($existingLookup[$key])) {
                // Update existing child
                try {
                    $existingChild = $existingLookup[$key];
                    $preserveStatus = isset($sponsorshipLookup[$key]); // Don't overwrite sponsored status

                    self::updateChild($existingChild['id'], $childData, $familyId, $preserveStatus);
                    $updated++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to update {$childData['name']}: " . $e->getMessage();
                }
            } else {
                // Insert new child
                try {
                    $childId = self::insertChild($childData, $familyId);
                    if ($childId) {
                        $inserted++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to insert {$childData['name']}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => $errors === [],
            'imported' => $inserted + $updated,
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors,
            'message' => "Updated {$updated} existing children, inserted {$inserted} new children",
            'import_mode' => 'update',
        ];
    }

    /**
     * Restore sponsorship statuses for matching children
     *
     * @param array<string, string> $sponsorshipLookup Existing sponsorships (family_number_childLetter => status)
     * @return int Number of sponsorships restored
     *
     * @psalm-return int<0, max>
     */
    private static function restoreSponsorshipStatuses(array $sponsorshipLookup): int
    {
        $restored = 0;
        foreach ($sponsorshipLookup as $key => $status) {
            [$familyNumber, $childLetter] = explode('_', $key);

            $affectedRows = Connection::execute("
                UPDATE children c
                JOIN families f ON c.family_id = f.id
                SET c.status = ?
                WHERE f.family_number = ? AND c.child_letter = ?
            ", [$status, $familyNumber, $childLetter]);

            if ($affectedRows > 0) {
                $restored++;
            }
        }

        return $restored;
    }

    /**
     * Ensure family exists, return family DB ID
     *
     * @param array<string, mixed> $childData Child data including family_id
     * @return int|null Family database ID or null on error
     */
    private static function ensureFamilyExists(array $childData): ?int
    {
        $familyNumber = (string) $childData['family_id'];

        // Check if family exists
        $existing = Connection::fetchRow(
            "SELECT id FROM families WHERE family_number = ?",
            [$familyNumber]
        );

        if ($existing) {
            return (int) $existing['id'];
        }

        // Create new family
        try {
            return Connection::insert('families', [
                'family_number' => $familyNumber,
                'notes' => $childData['family_situation'] ?? '',
            ]);
        } catch (\Exception $e) {
            error_log("Failed to create family {$familyNumber}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Insert a new child
     *
     * @param array<string, mixed> $childData Child data from CSV
     * @return int Inserted child ID
     */
    private static function insertChild(array $childData, int $familyId): int
    {
        return Connection::insert('children', [
            'family_id' => $familyId,
            'child_letter' => $childData['child_letter'],
            'name' => $childData['name'],
            'age_months' => $childData['age_months'],
            'gender' => $childData['gender'],
            'grade' => '', // Not in CSV - calculated from age
            'school' => '',
            'shirt_size' => $childData['shirt_size'] ?? '',
            'pant_size' => $childData['pant_size'] ?? '',
            'shoe_size' => $childData['shoe_size'] ?? '',
            'jacket_size' => $childData['jacket_size'] ?? '',
            'interests' => $childData['greatest_need'] ?? '',
            'wishes' => ($childData['interests'] ?? '') . (($childData['wish_list'] ?? '') ? '. Wish List: ' . $childData['wish_list'] : ''),
            'special_needs' => $childData['special_needs'] ?? 'None',
            'status' => 'available',
        ]);
    }

    /**
     * Update an existing child
     *
     * @param array<string, mixed> $childData Child data from CSV
     */
    private static function updateChild(int $childId, array $childData, int $familyId, bool $preserveStatus): void
    {
        $updateData = [
            'family_id' => $familyId,
            'child_letter' => $childData['child_letter'],
            'name' => $childData['name'],
            'age_months' => $childData['age_months'],
            'gender' => $childData['gender'],
            'grade' => '',
            'school' => '',
            'shirt_size' => $childData['shirt_size'] ?? '',
            'pant_size' => $childData['pant_size'] ?? '',
            'shoe_size' => $childData['shoe_size'] ?? '',
            'jacket_size' => $childData['jacket_size'] ?? '',
            'interests' => $childData['greatest_need'] ?? '',
            'wishes' => ($childData['interests'] ?? '') . (($childData['wish_list'] ?? '') ? '. Wish List: ' . $childData['wish_list'] : ''),
            'special_needs' => $childData['special_needs'] ?? 'None',
        ];

        // Don't update status if preserving sponsorships
        if (! $preserveStatus) {
            $updateData['status'] = 'available';
        }

        Connection::update('children', $updateData, ['id' => $childId]);
    }
}
