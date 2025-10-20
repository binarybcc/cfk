<?php

declare(strict_types=1);

namespace CFK\Import;

use CFK\Database\Connection;
use CFK\CSV\Handler as CSVHandler;

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
     * @return array<string, mixed> Analysis results
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
                'total_unchanged' => 0
            ]
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

            if (!isset($currentLookup[$key])) {
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
                        'changes' => $changes
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

            if (!isset($newLookup[$key])) {
                $analysis['removed_children'][] = $oldChild;
                $analysis['stats']['total_removed']++;

                // Warn about sponsored children being removed
                if ($oldChild['status'] === 'sponsored' || $oldChild['status'] === 'pending') {
                    $analysis['warnings'][] = [
                        'type' => 'sponsored_child_removed',
                        'severity' => 'high',
                        'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}) is {$oldChild['status']} but not in new upload",
                        'child' => $oldChild
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
     * @return array<string, array<string, mixed>> Changes detected
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
                    'new' => $newValue
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
     * @return array<int, array<string, mixed>> Warnings
     */
    private static function checkForWarnings(array $oldChild, array $newChild, array $changes): array
    {
        $warnings = [];

        // Check for data becoming blank
        foreach ($changes as $field => $change) {
            if (!empty($change['old']) && empty($change['new'])) {
                $warnings[] = [
                    'type' => 'data_loss',
                    'severity' => 'medium',
                    'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}): {$field} will be cleared (was: {$change['old']})",
                    'child' => $oldChild,
                    'field' => $field
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
                    'child' => $oldChild
                ];
            }
        }

        // Check for gender change (unusual)
        if (isset($changes['gender'])) {
            $warnings[] = [
                'type' => 'gender_changed',
                'severity' => 'low',
                'message' => "Child {$oldChild['name']} (Family {$oldChild['family_id']}{$oldChild['child_letter']}): Gender changed from {$changes['gender']['old']} to {$changes['gender']['new']}",
                'child' => $oldChild
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
            ORDER BY f.family_number, c.child_letter
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
        // Store current sponsorship statuses
        $sponsorships = Connection::fetchAll("
            SELECT c.id, c.family_id, c.child_letter, c.status, f.family_number
            FROM children c
            LEFT JOIN families f ON c.family_id = f.id
            WHERE c.status IN ('sponsored', 'pending')
        ");

        $sponsorshipLookup = [];
        foreach ($sponsorships as $child) {
            $key = $child['family_id'] . '_' . $child['child_letter'];
            $sponsorshipLookup[$key] = $child['status'];
        }

        // Handle removed sponsored children based on options
        $keepInactive = $options['keep_inactive'] ?? true;

        // Clear existing data (unless keeping inactive)
        if (!$keepInactive) {
            Connection::query('DELETE FROM children WHERE 1=1');
            Connection::query('DELETE FROM families WHERE 1=1');
        }

        // Import new data using namespaced CSVHandler
        $handler = new CSVHandler();
        $result = $handler->importChildren($csvPath, ['dry_run' => false]);

        if (!$result['success']) {
            return $result;
        }

        // Restore sponsorship statuses for matching children
        $restored = 0;
        foreach ($sponsorshipLookup as $key => $status) {
            [$familyId, $childLetter] = explode('_', $key);

            $updated = Connection::query("
                UPDATE children c
                JOIN families f ON c.family_id = f.id
                SET c.status = ?
                WHERE f.family_number = ? AND c.child_letter = ?
            ", [$status, $familyId, $childLetter]);

            if ($updated) {
                $restored++;
            }
        }

        $result['sponsorships_preserved'] = $restored;

        return $result;
    }
}
