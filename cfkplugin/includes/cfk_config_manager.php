<?php
/**
 * Configuration Management Class
 * Centralized, type-safe configuration management following Universal Methodology standards
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Centralized configuration management with type safety and validation
 */
class CFK_Config_Manager {
    
    /**
     * Configuration schema with types, defaults, and validation rules
     */
    private const CONFIG_SCHEMA = [
        // General Settings
        'sponsorships_open' => [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Whether sponsorship system is currently accepting new sponsorships'
        ],
        'deadline_date' => [
            'type' => 'string',
            'default' => '',
            'validation' => 'validate_date',
            'description' => 'Gift drop-off deadline date (Y-m-d format)'
        ],
        'selection_timeout' => [
            'type' => 'integer',
            'default' => 2,
            'min' => 1,
            'max' => 24,
            'description' => 'Hours before child selection expires'
        ],
        'drop_off_locations' => [
            'type' => 'array',
            'default' => [],
            'description' => 'Available gift drop-off locations'
        ],
        'average_sponsorship_value' => [
            'type' => 'integer',
            'default' => 100,
            'min' => 10,
            'max' => 1000,
            'description' => 'Average expected sponsorship value in dollars'
        ],
        
        // Email Settings
        'email_from_name' => [
            'type' => 'string',
            'default' => 'Christmas for Kids',
            'description' => 'Name displayed in outgoing emails'
        ],
        'email_from_email' => [
            'type' => 'string',
            'default' => null, // Will be computed
            'validation' => 'validate_email',
            'description' => 'Email address for outgoing emails'
        ],
        'admin_email' => [
            'type' => 'string',
            'default' => null, // Will use WP admin email
            'validation' => 'validate_email',
            'description' => 'Administrator email for notifications'
        ],
        'email_footer_text' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Footer text for all emails'
        ],
        
        // System Settings
        'max_children_per_sponsor' => [
            'type' => 'integer',
            'default' => 0, // 0 = unlimited
            'min' => 0,
            'max' => 50,
            'description' => 'Maximum children per sponsor (0 for unlimited)'
        ],
        'allow_duplicate_emails' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Allow sponsors to use the same email for multiple children'
        ],
        'enable_logging' => [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Enable system activity logging'
        ],
        'require_phone' => [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Require phone number for sponsorship'
        ]
    ];
    
    private const OPTION_PREFIX = 'cfk_';
    private static ?array $cache = null;
    
    /**
     * Get configuration value with type safety
     */
    public static function get(string $key, mixed $default = null): mixed {
        if (!isset(self::CONFIG_SCHEMA[$key])) {
            error_log("CFK Config: Unknown configuration key: {$key}");
            return $default;
        }
        
        $schema = self::CONFIG_SCHEMA[$key];
        $option_name = self::OPTION_PREFIX . $key;
        
        // Use cache if available
        if (self::$cache !== null && array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        
        // Get value from database
        $value = get_option($option_name, $schema['default'] ?? $default);
        
        // Handle computed defaults
        if ($value === null) {
            $value = match($key) {
                'email_from_email' => 'noreply@' . parse_url(home_url(), PHP_URL_HOST),
                'admin_email' => get_option('admin_email'),
                default => $schema['default'] ?? $default
            };
        }
        
        // Type casting and validation
        $value = self::cast_and_validate($key, $value, $schema);
        
        // Cache the result
        if (self::$cache === null) {
            self::$cache = [];
        }
        self::$cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Set configuration value with validation
     */
    public static function set(string $key, mixed $value): bool {
        if (!isset(self::CONFIG_SCHEMA[$key])) {
            error_log("CFK Config: Unknown configuration key: {$key}");
            return false;
        }
        
        $schema = self::CONFIG_SCHEMA[$key];
        
        // Validate and cast value
        try {
            $value = self::cast_and_validate($key, $value, $schema);
        } catch (InvalidArgumentException $e) {
            error_log("CFK Config: Validation failed for {$key}: " . $e->getMessage());
            return false;
        }
        
        $option_name = self::OPTION_PREFIX . $key;
        $result = update_option($option_name, $value);
        
        // Update cache
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
        
        return $result;
    }
    
    /**
     * Get all configuration values as array
     */
    public static function get_all(): array {
        $config = [];
        foreach (array_keys(self::CONFIG_SCHEMA) as $key) {
            $config[$key] = self::get($key);
        }
        return $config;
    }
    
    /**
     * Set multiple configuration values
     */
    public static function set_multiple(array $values): bool {
        $success = true;
        foreach ($values as $key => $value) {
            if (!self::set($key, $value)) {
                $success = false;
            }
        }
        
        // Clear cache after bulk update
        self::clear_cache();
        
        return $success;
    }
    
    /**
     * Get configuration schema information
     */
    public static function get_schema(string $key = null): array {
        if ($key !== null) {
            return self::CONFIG_SCHEMA[$key] ?? [];
        }
        return self::CONFIG_SCHEMA;
    }
    
    /**
     * Clear configuration cache
     */
    public static function clear_cache(): void {
        self::$cache = null;
    }
    
    /**
     * Cast value to correct type and validate
     */
    private static function cast_and_validate(string $key, mixed $value, array $schema): mixed {
        // Type casting
        $value = match($schema['type']) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [],
            default => $value
        };
        
        // Range validation for integers
        if ($schema['type'] === 'integer') {
            if (isset($schema['min']) && $value < $schema['min']) {
                $value = $schema['min'];
            }
            if (isset($schema['max']) && $value > $schema['max']) {
                $value = $schema['max'];
            }
        }
        
        // Custom validation
        if (isset($schema['validation'])) {
            $validation_method = $schema['validation'];
            if (method_exists(self::class, $validation_method)) {
                if (!self::$validation_method($value)) {
                    throw new InvalidArgumentException("Validation failed for {$key}");
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Validate date string
     */
    private static function validate_date(string $date): bool {
        if (empty($date)) {
            return true; // Empty dates are valid
        }
        
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
    
    /**
     * Validate email address
     */
    private static function validate_email(string $email): bool {
        if (empty($email)) {
            return true; // Empty emails handled by defaults
        }
        
        return is_email($email) !== false;
    }
    
    /**
     * Initialize default values
     */
    public static function initialize_defaults(): void {
        foreach (self::CONFIG_SCHEMA as $key => $schema) {
            $option_name = self::OPTION_PREFIX . $key;
            if (get_option($option_name) === false) {
                $default = $schema['default'];
                
                // Handle computed defaults
                if ($default === null) {
                    $default = match($key) {
                        'email_from_email' => 'noreply@' . parse_url(home_url(), PHP_URL_HOST),
                        'admin_email' => get_option('admin_email'),
                        default => null
                    };
                }
                
                if ($default !== null) {
                    update_option($option_name, $default);
                }
            }
        }
    }
    
    /**
     * Export configuration for backup/migration
     */
    public static function export(): array {
        return [
            'version' => CFK_PLUGIN_VERSION,
            'timestamp' => current_time('mysql'),
            'config' => self::get_all()
        ];
    }
    
    /**
     * Import configuration from backup
     */
    public static function import(array $data): bool {
        if (!isset($data['config']) || !is_array($data['config'])) {
            return false;
        }
        
        return self::set_multiple($data['config']);
    }
}