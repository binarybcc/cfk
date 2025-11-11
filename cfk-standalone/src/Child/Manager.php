<?php

declare(strict_types=1);

namespace CFK\Child;

use Database;
use Exception;

/**
 * Child Manager
 *
 * Handles business logic for child CRUD operations.
 * Consolidated from manage_children.php and ajax_handler.php.
 */
class Manager
{
    /**
     * Add a new child record
     *
     * @param array<string, mixed> $data Child data
     * @return array{success: bool, message: string} Result with success status and message
     */
    public static function addChild(array $data): array
    {
        try {
            // Validate required fields
            $validation = self::validateChildData($data);
            if (! $validation['valid']) {
                return ['success' => false, 'message' => 'Please fix the following errors: ' . implode(', ', $validation['errors'])];
            }

            $assignedFamilyNumber = null;
            $assignedChildLetter = null;

            // Handle new family creation - AUTO-ASSIGN family number
            if ($data['family_id'] === 'new') {
                // Find the next available family number
                $maxFamilyResult = Database::fetchRow("SELECT MAX(CAST(family_number AS UNSIGNED)) as max_num FROM families");
                $nextFamilyNumber = ($maxFamilyResult['max_num'] ?? 99) + 1;

                $assignedFamilyNumber = (string) $nextFamilyNumber;

                // Create new family with auto-assigned number
                $familyId = Database::insert('families', [
                    'family_number' => $assignedFamilyNumber,
                ]);

                if ($familyId === 0) {
                    return ['success' => false, 'message' => 'Failed to create new family'];
                }

                // Use the newly created family ID
                $data['family_id'] = $familyId;

                // For new families, always start with 'A'
                $assignedChildLetter = 'A';
                $data['child_letter'] = $assignedChildLetter;
            } else {
                // For existing families, auto-assign next available child letter
                $existingLettersResult = Database::fetchAll(
                    "SELECT child_letter FROM children WHERE family_id = ? ORDER BY child_letter",
                    [$data['family_id']]
                );

                $existingLetters = array_column($existingLettersResult, 'child_letter');

                // Find the next available letter
                $letters = range('A', 'Z');
                foreach ($letters as $letter) {
                    if (! in_array($letter, $existingLetters)) {
                        $assignedChildLetter = $letter;
                        $data['child_letter'] = $assignedChildLetter;

                        break;
                    }
                }

                if (! $assignedChildLetter) {
                    return ['success' => false, 'message' => 'No available child letters for this family (maximum 26 children per family)'];
                }
            }

            // Check if family exists
            $family = Database::fetchRow("SELECT id, family_number FROM families WHERE id = ?", [$data['family_id']]);
            if (! $family) {
                return ['success' => false, 'message' => 'Selected family does not exist'];
            }

            // Final safety check - ensure letter is unique within family
            $existing = Database::fetchRow(
                "SELECT id FROM children WHERE family_id = ? AND child_letter = ?",
                [$data['family_id'], $data['child_letter']]
            );
            if ($existing) {
                return ['success' => false, 'message' => 'Child letter ' . $data['child_letter'] . ' already exists in this family'];
            }

            // Convert age to months if years provided
            $ageMonths = sanitizeInt($data['age_months'] ?? 0);
            $ageYears = sanitizeInt($data['age_years'] ?? 0);

            if ($ageYears > 0) {
                $ageMonths = $ageYears * 12;
            }

            // Generate default name from family number + child letter (for database NOT NULL requirement)
            $defaultName = $family['family_number'] . $assignedChildLetter;

            $childId = Database::insert('children', [
                'family_id' => sanitizeInt($data['family_id']),
                'child_letter' => sanitizeString($data['child_letter']),
                'name' => $defaultName, // Required NOT NULL field
                'age_months' => $ageMonths,
                'grade' => sanitizeString($data['grade']),
                'gender' => sanitizeString($data['gender']),
                'school' => sanitizeString($data['school'] ?? ''),
                'shirt_size' => sanitizeString($data['shirt_size'] ?? ''),
                'pant_size' => sanitizeString($data['pant_size'] ?? ''),
                'shoe_size' => sanitizeString($data['shoe_size'] ?? ''),
                'jacket_size' => sanitizeString($data['jacket_size'] ?? ''),
                'interests' => sanitizeString($data['interests'] ?? ''),
                'wishes' => sanitizeString($data['wishes'] ?? ''),
                'special_needs' => sanitizeString($data['special_needs'] ?? ''),
                'status' => 'available',
            ]);

            // Build success message with assigned family/child ID
            $displayId = $family['family_number'] . $assignedChildLetter;
            $successMessage = "Child {$displayId} added successfully";

            if ($assignedFamilyNumber) {
                $successMessage .= " (New family {$assignedFamilyNumber} created)";
            }

            return ['success' => true, 'message' => $successMessage];
        } catch (Exception $e) {
            error_log('Failed to add child: ' . $e->getMessage());

            return ['success' => false, 'message' => 'System error occurred. Please try again.'];
        }
    }

    /**
     * Edit an existing child record
     *
     * @param array<string, mixed> $data Child data
     * @return array{success: bool, message: string} Result with success status and message
     */
    public static function editChild(array $data): array
    {
        try {
            $childId = sanitizeInt($data['child_id'] ?? 0);
            if (! $childId) {
                return ['success' => false, 'message' => 'Invalid child ID'];
            }

            // Validate data
            $validation = self::validateChildData($data);
            if (! $validation['valid']) {
                return ['success' => false, 'message' => 'Please fix the following errors: ' . implode(', ', $validation['errors'])];
            }

            // Check if child exists
            $child = Database::fetchRow("SELECT id, family_id, child_letter FROM children WHERE id = ?", [$childId]);
            if (! $child) {
                return ['success' => false, 'message' => 'Child not found'];
            }

            // Convert age to months if years provided
            $ageMonths = sanitizeInt($data['age_months'] ?? 0);
            $ageYears = sanitizeInt($data['age_years'] ?? 0);

            if ($ageYears > 0) {
                $ageMonths = $ageYears * 12;
            }

            // Get updated family info for name generation
            $updatedFamily = Database::fetchRow("SELECT family_number FROM families WHERE id = ?", [sanitizeInt($data['family_id'])]);
            $defaultName = $updatedFamily['family_number'] . $child['child_letter'];

            Database::update('children', [
                'family_id' => sanitizeInt($data['family_id']),
                'child_letter' => $child['child_letter'], // Use existing letter (not editable)
                'name' => $defaultName, // Update name if family changed
                'age_months' => $ageMonths,
                'grade' => sanitizeString($data['grade']),
                'gender' => sanitizeString($data['gender']),
                'school' => sanitizeString($data['school'] ?? ''),
                'shirt_size' => sanitizeString($data['shirt_size'] ?? ''),
                'pant_size' => sanitizeString($data['pant_size'] ?? ''),
                'shoe_size' => sanitizeString($data['shoe_size'] ?? ''),
                'jacket_size' => sanitizeString($data['jacket_size'] ?? ''),
                'interests' => sanitizeString($data['interests'] ?? ''),
                'wishes' => sanitizeString($data['wishes'] ?? ''),
                'special_needs' => sanitizeString($data['special_needs'] ?? ''),
            ], ['id' => $childId]);

            return ['success' => true, 'message' => 'Child updated successfully'];
        } catch (Exception $e) {
            error_log('Failed to edit child: ' . $e->getMessage());

            return ['success' => false, 'message' => 'System error occurred. Please try again.'];
        }
    }

    /**
     * Delete a child record
     *
     * @param int $childId Child ID to delete
     * @return array{success: bool, message: string} Result with success status and message
     */
    public static function deleteChild(int $childId): array
    {
        try {
            if (! $childId) {
                return ['success' => false, 'message' => 'Invalid child ID'];
            }

            // Check if child has any active sponsorships
            $sponsorships = Database::fetchRow(
                "SELECT COUNT(*) as count FROM sponsorships WHERE child_id = ? AND status IN ('pending', 'confirmed', 'logged')",
                [$childId]
            );

            if (($sponsorships['count'] ?? 0) > 0) {
                return ['success' => false, 'message' => 'Cannot delete child with active sponsorships'];
            }

            Database::delete('children', ['id' => $childId]);

            return ['success' => true, 'message' => 'Child deleted successfully'];
        } catch (Exception $e) {
            error_log('Failed to delete child: ' . $e->getMessage());

            return ['success' => false, 'message' => 'System error occurred. Please try again.'];
        }
    }

    /**
     * Toggle child status
     *
     * @param int $childId Child ID
     * @return array{success: bool, message: string, new_status?: string} Result with success status and message
     */
    public static function toggleChildStatus(int $childId): array
    {
        try {
            $child = Database::fetchRow("SELECT status FROM children WHERE id = ?", [$childId]);

            if (! $child) {
                return ['success' => false, 'message' => 'Child not found'];
            }

            $newStatus = $child['status'] === 'available' ? 'inactive' : 'available';

            $success = Database::execute(
                "UPDATE children SET status = ? WHERE id = ?",
                [$newStatus, $childId]
            );

            if ($success !== 0) {
                return [
                    'success' => true,
                    'message' => 'Child status updated to ' . $newStatus,
                    'new_status' => $newStatus,
                ];
            }

            return ['success' => false, 'message' => 'Failed to update child status'];
        } catch (Exception $e) {
            error_log('Failed to toggle child status: ' . $e->getMessage());

            return ['success' => false, 'message' => 'System error occurred. Please try again.'];
        }
    }

    /**
     * Validate child data
     *
     * @param array<string, mixed> $data Child data to validate
     * @return array{valid: bool, errors: array<int, string>} Validation result with valid flag and errors array
     */
    public static function validateChildData(array $data): array
    {
        $errors = [];

        // Age validation - require exactly one of months or years
        $ageMonths = sanitizeInt($data['age_months'] ?? 0);
        $ageYears = sanitizeInt($data['age_years'] ?? 0);

        if ($ageMonths === 0 && $ageYears === 0) {
            $errors[] = 'Age is required (enter either months or years)';
        } elseif ($ageMonths > 0 && $ageYears > 0) {
            $errors[] = 'Please enter age in either months OR years, not both';
        } elseif ($ageMonths > 0) {
            if ($ageMonths < 0 || $ageMonths > 24) {
                $errors[] = 'Age in months must be between 0 and 24';
            }
        } elseif ($ageYears > 0) {
            if ($ageYears < 0 || $ageYears > 18) {
                $errors[] = 'Age in years must be between 0 and 18';
            }
        }

        if (in_array(trim($data['gender'] ?? ''), ['', '0'], true) || ! in_array($data['gender'], ['M', 'F'])) {
            $errors[] = 'Valid gender is required';
        }

        if (empty($data['family_id']) || ($data['family_id'] !== 'new' && empty(sanitizeInt($data['family_id'])))) {
            $errors[] = 'Family selection is required';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * Get child by ID with family information
     *
     * @param int $childId Child ID
     * @return array<string, mixed>|null Child data with family info or null if not found
     */
    public static function getChildById(int $childId): ?array
    {
        $child = Database::fetchRow(
            "SELECT c.*, f.family_number
             FROM children c
             JOIN families f ON c.family_id = f.id
             WHERE c.id = ?",
            [$childId]
        );

        return $child ?: null;
    }
}
