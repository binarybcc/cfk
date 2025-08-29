<?php
/**
 * Children Manager Class - Part 1: Core Structure, Enums & DTOs
 * Handles the Child custom post type and related functionality
 *
 * @package ChristmasForKids
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enums for type safety
enum CFK_Gender: string {
    case MALE = 'Male';
    case FEMALE = 'Female';
    
    public function getIcon(): string {
        return match($this) {
            self::MALE => '♂',
            self::FEMALE => '♀'
        };
    }
    
    public function getLabel(): string {
        return match($this) {
            self::MALE => __('Male', 'cfk-sponsorship'),
            self::FEMALE => __('Female', 'cfk-sponsorship')
        };
    }
}

enum CFK_AgeRange: string {
    case INFANT = 'Infant';
    case ELEMENTARY = 'Elementary';
    case MIDDLE_SCHOOL = 'Middle School';
    case HIGH_SCHOOL = 'High School';
    
    public function getAgeGroup(): array {
        return match($this) {
            self::INFANT => ['min' => 0, 'max' => 2],
            self::ELEMENTARY => ['min' => 3, 'max' => 10],
            self::MIDDLE_SCHOOL => ['min' => 11, 'max' => 13],
            self::HIGH_SCHOOL => ['min' => 14, 'max' => 18]
        };
    }
    
    public function getLabel(): string {
        return match($this) {
            self::INFANT => __('Infant', 'cfk-sponsorship'),
            self::ELEMENTARY => __('Elementary', 'cfk-sponsorship'),
            self::MIDDLE_SCHOOL => __('Middle School', 'cfk-sponsorship'),
            self::HIGH_SCHOOL => __('High School', 'cfk-sponsorship')
        };
    }
}

enum CFK_ColumnType: string {
    case PHOTO = 'child_photo';
    case CHILD_ID = 'child_id';
    case AGE_GENDER = 'age_gender';
    case FAMILY_ID = 'family_id';
    case AGE_RANGE = 'age_range';
    case SPONSORED = 'sponsored';
}

// Readonly data transfer objects
readonly class CFK_ChildDetails {
    public function __construct(
        public string $id,
        public string $name,
        public int $age,
        public CFK_Gender $gender,
        public string $family_id,
        public CFK_AgeRange $age_range,
        public string $clothing_info,
        public string $gift_requests,
        public bool $sponsored,
        public ?string $avatar_url,
        public string $edit_url
    ) {}
    
    public function to_array(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender->value,
            'family_id' => $this->family_id,
            'age_range' => $this->age_range->value,
            'clothing_info' => $this->clothing_info,
            'gift_requests' => $this->gift_requests,
            'sponsored' => $this->sponsored,
            'avatar_url' => $this->avatar_url,
            'edit_url' => $this->edit_url
        ];
    }
    
    public function get_display_name(): string {
        return $this->name ?: "Child {$this->id}";
    }
}

readonly class CFK_SponsorshipStats {
    public function __construct(
        public int $total_children,
        public int $sponsored_children,
        public int $available_children,
        public int $total_families,
        public float $sponsorship_percentage
    ) {}
    
    public function to_array(): array {
        return [
            'total_children' => $this->total_children,
            'sponsored_children' => $this->sponsored_children,
            'available_children' => $this->available_children,
            'total_families' => $this->total_families,
            'sponsorship_percentage' => $this->sponsorship_percentage
        ];
    }
}

readonly class CFK_ChildQuery {
    public function __construct(
        public ?CFK_AgeRange $age_range = null,
        public ?CFK_Gender $gender = null,
        public ?bool $sponsored = null,
        public int $per_page = 12,
        public bool $exclude_selected = true,
        public string $search_term = '',
        public string $family_id = '',
        public string $order_by = 'date',
        public string $order = 'DESC'
    ) {}
    
    public function to_wp_query_args(): array {
        $query_args = [
            'post_type' => 'child',
            'post_status' => 'publish',
            'posts_per_page' => $this->per_page,
            'meta_query' => ['relation' => 'AND']
        ];
        
        // Add search term
        if ($this->search_term !== '') {
            $query_args['s'] = $this->search_term;
        }
        
        // Add meta query filters
        if ($this->age_range) {
            $query_args['meta_query'][] = [
                'key' => '_child_age_range',
                'value' => $this->age_range->value,
                'compare' => '='
            ];
        }
        
        if ($this->gender) {
            $query_args['meta_query'][] = [
                'key' => '_child_gender',
                'value' => $this->gender->value,
                'compare' => '='
            ];
        }
        
        if ($this->family_id !== '') {
            $query_args['meta_query'][] = [
                'key' => '_child_family_id',
                'value' => $this->family_id,
                'compare' => '='
            ];
        }
        
        if ($this->sponsored !== null) {
            $query_args['meta_query'][] = match($this->sponsored) {
                true => [
                    'key' => '_child_sponsored',
                    'value' => '1',
                    'compare' => '='
                ],
                false => [
                    'relation' => 'OR',
                    [
                        'key' => '_child_sponsored',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_child_sponsored',
                        'value' => '1',
                        'compare' => '!='
                    ]
                ]
            };
        }
        
        // Add ordering
        if ($this->order_by !== 'date') {
            $meta_key_mapping = [
                'age' => '_child_age',
                'child_id' => '_child_id',
                'family_id' => '_child_family_id',
                'sponsored' => '_child_sponsored'
            ];
            
            if (isset($meta_key_mapping[$this->order_by])) {
                $query_args['meta_key'] = $meta_key_mapping[$this->order_by];
                $query_args['orderby'] = 'meta_value';
                if ($this->order_by === 'age') {
                    $query_args['orderby'] = 'meta_value_num';
                }
            }
        }
        
        $query_args['order'] = $this->order;
        
        return $query_args;
    }
}

// Core Children Manager Class
class CFK_Children_Manager {
    private array $meta_fields_config;
    
    public function __construct() {
        $this->init_meta_fields_config();
        $this->register_hooks();
    }
    
    private function init_meta_fields_config(): void {
        $this->meta_fields_config = [
            'child_id' => [
                'sanitize' => 'sanitize_text_field', 
                'required' => true,
                'type' => 'string'
            ],
            'child_age' => [
                'sanitize' => 'intval', 
                'required' => true,
                'type' => 'number',
                'min' => 0,
                'max' => 18
            ],
            'child_gender' => [
                'sanitize' => 'sanitize_text_field', 
                'required' => true,
                'type' => 'enum',
                'enum_class' => CFK_Gender::class
            ],
            'child_family_id' => [
                'sanitize' => 'sanitize_text_field', 
                'required' => false,
                'type' => 'string'
            ],
            'child_age_range' => [
                'sanitize' => 'sanitize_text_field', 
                'required' => true,
                'type' => 'enum',
                'enum_class' => CFK_AgeRange::class
            ],
            'child_clothing_info' => [
                'sanitize' => 'sanitize_textarea_field', 
                'required' => false,
                'type' => 'textarea'
            ],
            'child_gift_requests' => [
                'sanitize' => 'sanitize_textarea_field', 
                'required' => false,
                'type' => 'textarea'
            ],
            'child_sponsored' => [
                'sanitize' => 'sanitize_text_field', 
                'required' => false,
                'type' => 'checkbox'
            ]
        ];
    }
    
    private function register_hooks(): void {
        add_action('init', $this->register_child_post_type(...));
        add_action('add_meta_boxes', $this->add_child_meta_boxes(...));
        add_action('save_post', $this->save_child_meta(...));
        add_action('manage_child_posts_columns', $this->custom_child_columns(...));
        add_action('manage_child_posts_custom_column', $this->custom_child_column_content(...), 10, 2);
        add_filter('manage_edit-child_sortable_columns', $this->sortable_child_columns(...));
        add_action('pre_get_posts', $this->child_custom_orderby(...));
        
        // AJAX handlers
        add_action('wp_ajax_cfk_export_children', $this->handle_ajax_export(...));
        add_action('wp_ajax_cfk_bulk_update_status', $this->handle_ajax_bulk_update(...));
        add_action('wp_ajax_cfk_validate_child_id', $this->handle_ajax_validate_child_id(...));
    }
    
    public function get_meta_fields_config(): array {
        return $this->meta_fields_config;
    }
    
    public function validate_meta_field(string $field_name, mixed $value): array {
        $config = $this->meta_fields_config[$field_name] ?? null;
        $errors = [];
        
        if (!$config) {
            $errors[] = sprintf(__('Unknown field: %s', 'cfk-sponsorship'), $field_name);
            return $errors;
        }
        
        // Required field check
        if ($config['required'] && empty($value)) {
            $field_label = str_replace(['child_', '_'], ['', ' '], $field_name);
            $errors[] = sprintf(__('%s is required', 'cfk-sponsorship'), ucwords($field_label));
            return $errors;
        }
        
        // Type-specific validation
        match($config['type']) {
            'number' => $this->validate_number_field($value, $config, $errors),
            'enum' => $this->validate_enum_field($value, $config, $errors),
            'string' => $this->validate_string_field($value, $config, $errors),
            default => null
        };
        
        return $errors;
    }
    
    private function validate_number_field(mixed $value, array $config, array &$errors): void {
        $num_value = intval($value);
        
        if (isset($config['min']) && $num_value < $config['min']) {
            $errors[] = sprintf(__('Value must be at least %d', 'cfk-sponsorship'), $config['min']);
        }
        
        if (isset($config['max']) && $num_value > $config['max']) {
            $errors[] = sprintf(__('Value must be no more than %d', 'cfk-sponsorship'), $config['max']);
        }
    }
    
    private function validate_enum_field(mixed $value, array $config, array &$errors): void {
        if (empty($value)) {
            return;
        }
        
        $enum_class = $config['enum_class'];
        try {
            $enum_class::from($value);
        } catch (ValueError) {
            $errors[] = sprintf(__('Invalid value for %s', 'cfk-sponsorship'), $enum_class);
        }
    }
    
    private function validate_string_field(mixed $value, array $config, array &$errors): void {
        if (isset($config['max_length']) && strlen($value) > $config['max_length']) {
            $errors[] = sprintf(__('Value must be no more than %d characters', 'cfk-sponsorship'), $config['max_length']);
        }
    }
}

?>
