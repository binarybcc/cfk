<?php

declare(strict_types=1);

namespace CFK\Validators;

/**
 * Validator for child data
 */
class ChildValidator
{
    private array $errors = [];

    /**
     * Validate child data for creation/update
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        $this->validateFamilyId($data['family_id'] ?? '');
        $this->validateName($data['name'] ?? '');
        $this->validateAge($data['age'] ?? null);
        $this->validateGender($data['gender'] ?? '');
        $this->validateGrade($data['grade'] ?? '');
        $this->validateInterests($data['interests'] ?? '');

        return empty($this->errors);
    }

    /**
     * Validate CSV import data
     */
    public function validateCsvRow(array $row, int $rowNumber): bool
    {
        $this->errors = [];
        $prefix = "Row {$rowNumber}: ";

        if (!$this->validateFamilyId($row['family_id'] ?? '', $prefix)) {
            return false;
        }

        if (!$this->validateName($row['name'] ?? '', $prefix)) {
            return false;
        }

        if (!$this->validateAge($row['age'] ?? null, $prefix)) {
            return false;
        }

        if (!$this->validateGender($row['gender'] ?? '', $prefix)) {
            return false;
        }

        $this->validateGrade($row['grade'] ?? '', $prefix);
        $this->validateInterests($row['interests'] ?? '', $prefix);

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Validate family ID
     */
    private function validateFamilyId(string $familyId, string $prefix = ''): bool
    {
        if (empty($familyId)) {
            $this->errors[] = $prefix . 'Family ID is required';
            return false;
        }

        if (!preg_match('/^\d+[A-Z]$/', $familyId)) {
            $this->errors[] = $prefix . 'Family ID must be in format: number + letter (e.g., 123A)';
            return false;
        }

        if (strlen($familyId) > 10) {
            $this->errors[] = $prefix . 'Family ID is too long (max 10 characters)';
            return false;
        }

        return true;
    }

    /**
     * Validate child name
     */
    private function validateName(string $name, string $prefix = ''): bool
    {
        $name = trim($name);

        if (empty($name)) {
            $this->errors[] = $prefix . 'Name is required';
            return false;
        }

        if (strlen($name) < 2) {
            $this->errors[] = $prefix . 'Name must be at least 2 characters long';
            return false;
        }

        if (strlen($name) > 100) {
            $this->errors[] = $prefix . 'Name is too long (max 100 characters)';
            return false;
        }

        if (!preg_match('/^[a-zA-Z\s\-\'.]+$/u', $name)) {
            $this->errors[] = $prefix . 'Name contains invalid characters';
            return false;
        }

        return true;
    }

    /**
     * Validate child age
     */
    private function validateAge($age, string $prefix = ''): bool
    {
        if ($age === null || $age === '') {
            $this->errors[] = $prefix . 'Age is required';
            return false;
        }

        $age = (int) $age;

        if ($age < 1) {
            $this->errors[] = $prefix . 'Age must be a positive number';
            return false;
        }

        if ($age > 25) {
            $this->errors[] = $prefix . 'Age cannot be greater than 25';
            return false;
        }

        return true;
    }

    /**
     * Validate gender
     */
    private function validateGender(string $gender, string $prefix = ''): bool
    {
        $gender = strtolower(trim($gender));

        if (empty($gender)) {
            $this->errors[] = $prefix . 'Gender is required';
            return false;
        }

        $validGenders = ['male', 'female', 'm', 'f', 'boy', 'girl'];
        
        if (!in_array($gender, $validGenders, true)) {
            $this->errors[] = $prefix . 'Gender must be: male, female, m, f, boy, or girl';
            return false;
        }

        return true;
    }

    /**
     * Validate grade
     */
    private function validateGrade(string $grade, string $prefix = ''): bool
    {
        $grade = trim($grade);

        if (empty($grade)) {
            // Grade is optional
            return true;
        }

        if (strlen($grade) > 50) {
            $this->errors[] = $prefix . 'Grade is too long (max 50 characters)';
            return false;
        }

        return true;
    }

    /**
     * Validate interests
     */
    private function validateInterests(string $interests, string $prefix = ''): bool
    {
        if (strlen($interests) > 500) {
            $this->errors[] = $prefix . 'Interests description is too long (max 500 characters)';
            return false;
        }

        return true;
    }

    /**
     * Sanitize and normalize child data
     */
    public static function sanitize(array $data): array
    {
        return [
            'family_id' => strtoupper(trim($data['family_id'] ?? '')),
            'name' => trim($data['name'] ?? ''),
            'age' => (int) ($data['age'] ?? 0),
            'gender' => self::normalizeGender($data['gender'] ?? ''),
            'grade' => trim($data['grade'] ?? ''),
            'interests' => trim($data['interests'] ?? ''),
            'status' => $data['status'] ?? 'available'
        ];
    }

    /**
     * Normalize gender values
     */
    private static function normalizeGender(string $gender): string
    {
        $gender = strtolower(trim($gender));

        return match ($gender) {
            'm', 'male', 'boy' => 'male',
            'f', 'female', 'girl' => 'female',
            default => $gender
        };
    }
}