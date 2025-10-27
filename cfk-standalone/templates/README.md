# CSV Import Template

## Age Fields - IMPORTANT

**Two age columns are provided: `age_months` and `age_years`**

### Rules:
1. **Fill ONLY ONE column per child** (not both)
2. Use `age_months` for children under 2 years old (0-24 months)
3. Use `age_years` for children 2 years and older

### Valid Ranges:
- `age_months`: 0-24 months
- `age_years`: 0-18 years

### Examples:

| Child | age_months | age_years | Result |
|-------|------------|-----------|--------|
| Baby (18 months) | 18 | (empty) | ✅ Displays as "18 months" |
| Toddler (24 months) | 24 | (empty) | ✅ Displays as "24 months" |
| Preschooler | (empty) | 3 | ✅ Displays as "3 years" |
| Elementary | (empty) | 8 | ✅ Displays as "8 years" |
| INVALID | 18 | 2 | ❌ ERROR: Cannot fill both columns |
| INVALID | (empty) | (empty) | ❌ ERROR: Must fill one column |

## Why This Matters

Toy manufacturers label products by age in months for young children:
- "18 months and older"
- "24 months and older"

This system allows accurate age tracking for toy purchasing and sizing.

## Template Columns

1. **name** - Child identifier (e.g., "001A", "002B")
2. **age_months** - Age in months (0-24) - Fill this OR age_years
3. **age_years** - Age in years (0-18) - Fill this OR age_months
4. **gender** - M or F
5. **shirt_size** - Clothing size
6. **pant_size** - Clothing size
7. **shoe_size** - Shoe size
8. **jacket_size** - Jacket/coat size
9. **interests** - Hobbies, likes, activities
10. **greatest_need** - Most needed items
11. **wish_list** - What they want for Christmas
12. **special_needs** - Any special considerations
13. **family_situation** - Family background notes

## Import Process

1. Download template: `cfk-import-template.csv`
2. Fill in child data (one row per child)
3. Save as CSV file
4. Go to Admin → Import CSV
5. Upload your file
6. Review validation results
7. Confirm import

## Notes

- Family members use same family number with different letters (001A, 001B, 001C)
- Empty cells should be left blank (not "N/A" or "None")
- Use commas within quoted fields for lists
- Special characters are allowed in quoted fields
