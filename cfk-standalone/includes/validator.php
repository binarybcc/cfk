<?php

declare(strict_types=1);

/**
 * Centralized Validation Class
 * Provides consistent validation across the application
 */

// Prevent direct access
if (! defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

class Validator
{
    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * Validate required fields
     * @param array<int, string> $fields
     */
    public function required(array $fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? null;
            if ($value === null || $value === '' || ($value === [])) {
                $this->errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Must be a valid email address';
        }

        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min): self
    {
        $value = $this->data[$field] ?? '';
        if (strlen((string)$value) < $min) {
            $this->errors[$field][] = "Must be at least $min characters";
        }

        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max): self
    {
        $value = $this->data[$field] ?? '';
        if (strlen((string)$value) > $max) {
            $this->errors[$field][] = "Must not exceed $max characters";
        }

        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && ! is_numeric($value)) {
            $this->errors[$field][] = 'Must be a number';
        }

        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && ! filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = 'Must be a whole number';
        }

        return $this;
    }

    /**
     * Validate value is in array
     * @param array<int|string, mixed> $allowed
     */
    public function in(string $field, array $allowed): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && ! in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            $this->errors[$field][] = "Must be one of: $allowedStr";
        }

        return $this;
    }

    /**
     * Validate minimum numeric value
     */
    public function min(string $field, int|float $min): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && is_numeric($value) && $value < $min) {
            $this->errors[$field][] = "Must be at least $min";
        }

        return $this;
    }

    /**
     * Validate maximum numeric value
     */
    public function max(string $field, int|float $max): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && is_numeric($value) && $value > $max) {
            $this->errors[$field][] = "Must not exceed $max";
        }

        return $this;
    }

    /**
     * Validate regex pattern
     */
    public function pattern(string $field, string $pattern, string $message = 'Invalid format'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value && ! preg_match($pattern, (string)$value)) {
            $this->errors[$field][] = $message;
        }

        return $this;
    }

    /**
     * Validate phone number (basic US format)
     */
    public function phone(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value) {
            // Remove common formatting characters
            $cleaned = preg_replace('/[\s\-\(\)\.]/', '', (string)$value);
            if (! preg_match('/^[\+]?[1]?\d{10,15}$/', (string) $cleaned)) {
                $this->errors[$field][] = 'Must be a valid phone number';
            }
        }

        return $this;
    }

    /**
     * Validate URL format
     */
    public function url(string $field): self
    {
        $value = $this->data[$field] ?? null;
        if ($value && ! filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = 'Must be a valid URL';
        }

        return $this;
    }

    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value) {
            $d = DateTime::createFromFormat($format, (string)$value);
            if (! $d || $d->format($format) !== $value) {
                $this->errors[$field][] = "Must be a valid date ($format)";
            }
        }

        return $this;
    }

    /**
     * Validate that field matches another field
     */
    public function matches(string $field, string $matchField): self
    {
        $value = $this->data[$field] ?? null;
        $matchValue = $this->data[$matchField] ?? null;
        if ($value !== $matchValue) {
            $this->errors[$field][] = "Must match " . str_replace('_', ' ', $matchField);
        }

        return $this;
    }

    /**
     * Custom validation with callback
     */
    public function custom(string $field, callable $callback, string $message = 'Invalid value'): self
    {
        $value = $this->data[$field] ?? null;
        if (! $callback($value, $this->data)) {
            $this->errors[$field][] = $message;
        }

        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Get all errors
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for a field
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all errors as flat array
     * @return array<int, string>
     */
    public function allErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $flat[] = $message;
            }
        }

        return $flat;
    }

    /**
     * Get validated data (only fields that passed validation)
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $validated = [];
        foreach ($this->data as $key => $value) {
            if (! isset($this->errors[$key])) {
                $validated[$key] = $value;
            }
        }

        return $validated;
    }

    /**
     * Static factory method
     */
    public static function make(array $data): self
    {
        return new self($data);
    }
}

/**
 * Helper function for quick validation
 */
function validate(array $data, array $rules): Validator
{
    $validator = Validator::make($data);

    foreach ($rules as $field => $ruleString) {
        $fieldRules = explode('|', (string) $ruleString);

        foreach ($fieldRules as $rule) {
            // Parse rule with parameters
            if (str_contains($rule, ':')) {
                [$ruleName, $params] = explode(':', $rule, 2);
                $params = explode(',', $params);
            } else {
                $ruleName = $rule;
                $params = [];
            }

            // Apply rule
            switch ($ruleName) {
                case 'required':
                    $validator->required([$field]);

                    break;
                case 'email':
                    $validator->email($field);

                    break;
                case 'min':
                    $validator->minLength($field, (int)$params[0]);

                    break;
                case 'max':
                    $validator->maxLength($field, (int)$params[0]);

                    break;
                case 'numeric':
                    $validator->numeric($field);

                    break;
                case 'integer':
                    $validator->integer($field);

                    break;
                case 'in':
                    $validator->in($field, $params);

                    break;
                case 'phone':
                    $validator->phone($field);

                    break;
                case 'url':
                    $validator->url($field);

                    break;
                case 'date':
                    $validator->date($field, $params[0] ?? 'Y-m-d');

                    break;
            }
        }
    }

    return $validator;
}
