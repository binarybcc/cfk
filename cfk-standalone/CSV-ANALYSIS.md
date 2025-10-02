# CSV Data Analysis and Issues

## Current CSV Structure Issues Identified

### 1. **Family ID System Problems**
- **Issue**: Using alphanumeric family IDs (123A, 123B, 456A) instead of proper family grouping
- **Our System**: Uses separate `family_id` (numeric) and `child_letter` (A,B,C) fields  
- **Fix Needed**: Split family IDs into numeric family ID + letter

### 2. **Missing/Inconsistent Family Names**
- **Issue**: Row 9 (Christopher Wilson) has empty family_name field
- **Impact**: Breaks family grouping display
- **Fix Needed**: Generate family name from child's last name or mark as individual

### 3. **Column Mapping Differences**
```csv
CSV Columns → Our Database Columns
name → name ✓
age → age ✓
gender → gender ✓
family_id → needs splitting into family_id + child_letter
family_name → family.family_name ✓
shirt_size → shirt_size ✓
pants_size → pant_size (different column name)
shoe_size → shoe_size ✓
coat_size → jacket_size (different column name)
interests → interests ✓
family_situation → family.notes (different table)
special_needs → special_needs ✓
```

### 4. **Missing Required Fields**
Our system expects but CSV doesn't have:
- `grade` - should be derived from age or left empty
- `school` - not in CSV, optional
- `wishes` - what child wants for Christmas (critical field missing!)
- `status` - will default to 'available'

### 5. **Size Format Inconsistencies**  
- Mix of formats: "Youth M", "2T", "11T", "4Y", "8"
- Some inconsistent with typical sizing conventions
- Need validation/standardization

## Proposed CSV Import Solution

### 1. **Pre-Processing Steps**
```php
function preprocessCsvRow($row) {
    // Split family_id into family_id and child_letter
    if (preg_match('/(\d+)([A-Z])/', $row['family_id'], $matches)) {
        $row['numeric_family_id'] = $matches[1];
        $row['child_letter'] = $matches[2];
    }
    
    // Generate family name if missing
    if (empty($row['family_name'])) {
        $lastName = explode(' ', $row['name']);
        $row['family_name'] = end($lastName) . ' Family';
    }
    
    // Map column names to our schema
    $row['pant_size'] = $row['pants_size'] ?? '';
    $row['jacket_size'] = $row['coat_size'] ?? '';
    
    return $row;
}
```

### 2. **Family Creation Logic**
- Group by numeric family ID
- Create family record first, then children
- Handle family situations in family.notes field

### 3. **Data Validation Rules**
```php
$validationRules = [
    'name' => 'required|max:100',
    'age' => 'required|integer|min:0|max:18', 
    'gender' => 'required|in:M,F',
    'family_id' => 'required|regex:/\d+[A-Z]/',
    // Size validations
    'shirt_size' => 'max:10',
    'shoe_size' => 'max:10'
];
```

### 4. **Improved CSV Template**

```csv
name,age,gender,family_id,family_name,grade,shirt_size,pant_size,shoe_size,jacket_size,interests,wishes,special_needs,family_situation
"John Doe",8,"M","175A","Doe Family","3rd","Youth M","8","4Y","Youth M","Football, Reading","Sports equipment and books","None","Single mother household"
```

## Technical Implementation Plan

### Phase 1: CSV Handler Class
```php
class CFK_CSV_Handler {
    public function importChildren(string $csvPath): array;
    public function exportChildren(array $filters = []): string;
    public function validateRow(array $row): array;
    public function preprocessRow(array $row): array;
}
```

### Phase 2: Import Process
1. **Upload & Validate**: Check file format, required columns
2. **Preprocess**: Fix family IDs, generate missing data  
3. **Create Families**: Group and create family records first
4. **Create Children**: Link to families, validate all data
5. **Report Results**: Success/error summary for admin

### Phase 3: Export Features
- **All Children**: Complete database export
- **Available Only**: Just children needing sponsors
- **By Status**: Pending, sponsored, completed
- **Custom Date Ranges**: For reporting periods

## Questions for Sample Data

1. **Wishes Field**: The CSV lacks what children want for Christmas - should we add this as required?

2. **Grade Information**: Should we auto-derive from age or leave empty for admin to fill?

3. **Family Situations**: Keep in family notes or create separate field?

4. **Size Standardization**: Should we create dropdown lists of valid sizes?

The data looks clean and realistic - ready to build the import system around this structure while fixing the identified issues.