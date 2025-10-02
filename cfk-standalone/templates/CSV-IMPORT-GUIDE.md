# Christmas for Kids - CSV Import Guide

## Standard CSV Format

### Required Columns (in exact order):
```csv
name,age,gender,family_id,family_name,grade,shirt_size,pant_size,shoe_size,jacket_size,interests,greatest_need,wish_list,special_needs,notes
```

## Column Specifications

### Basic Information
- **name**: Child's first name only (e.g., "Emma", "Marcus")
- **age**: Numeric age 0-18 (e.g., 8, 16)
- **gender**: "M" or "F" only
- **family_id**: Numeric family identifier (e.g., 175, 176)
- **family_name**: Family display name (e.g., "Johnson Family")

### School Information  
- **grade**: Grade level (e.g., "Pre-K", "K", "1st", "2nd", "3rd"... "12th")

### Clothing Sizes
- **shirt_size**: Shirt/top size (e.g., "Youth M", "4T", "Men's Small")
- **pant_size**: Pants/bottom size (e.g., "8", "4T", "Men's 32")  
- **shoe_size**: Shoe size (e.g., "3", "11", "9")
- **jacket_size**: Coat/jacket size (e.g., "Youth M", "4T", "Men's Large")

### Child Interests & Needs
- **interests**: What the child likes (e.g., "Art, Reading, Animals")
- **greatest_need**: Most important items needed (e.g., "Winter coat", "School supplies")
- **wish_list**: Special items they'd love (e.g., "Art easel and books", "Gaming headphones")
- **special_needs**: Medical/dietary needs or "None" (e.g., "Asthma medication", "None")

### Administrative
- **notes**: Family situation or admin notes (e.g., "Single mother household")

## Data Validation Rules

### Required Fields
- name, age, gender, family_id, family_name

### Data Types
- **age**: Must be integer 0-18
- **gender**: Must be exactly "M" or "F" 
- **family_id**: Must be numeric

### Text Limits
- **name**: 50 characters max
- **interests**: 500 characters max
- **greatest_need**: 200 characters max
- **wish_list**: 500 characters max
- **notes**: 500 characters max

### Size Format Guidelines
- Use standard sizing (Youth S/M/L, Adult S/M/L, numeric sizes)
- Be consistent within each column
- Examples: "4T", "Youth Medium", "Men's Large", "Size 8"

## Family Grouping Rules

### Family IDs
- **Same family_id** = siblings
- **Same family_name** = should have same family_id
- **Unique family_id** = separate families

### Example Family Grouping
```csv
name,age,gender,family_id,family_name,...
"Emma",8,"F","175","Johnson Family",...
"Noah",6,"M","175","Johnson Family",...  
"Lily",4,"F","175","Johnson Family",...
```

## Import Process

### 1. Data Validation
- Check all required fields present
- Validate data types and formats
- Check for duplicate family IDs with different names
- Verify age ranges and realistic sizing

### 2. Family Processing  
- Create family records first
- Group children by family_id
- Generate family display numbers (175A, 175B, 175C)

### 3. Child Creation
- Create child records linked to families
- Auto-assign avatars based on age/gender
- Set status to 'available' by default

### 4. Error Reporting
- List any validation errors
- Show successful imports
- Highlight warnings (unusual sizes, missing data)

## Common Issues & Solutions

### Size Inconsistencies
**Problem**: Mixed formats like "Youth M" and "Medium"
**Solution**: Standardize to "Youth M", "Youth L", etc.

### Missing Family Names
**Problem**: Empty family_name field
**Solution**: Auto-generate from child's name (e.g., "Smith Family")

### Age/Grade Mismatches  
**Problem**: 16-year-old in "5th grade"
**Solution**: System will flag for admin review

### Special Characters
**Problem**: Commas in interests breaking CSV
**Solution**: Always wrap text fields in quotes

## Sample Template

Download: `cfk-import-template.csv`

Contains properly formatted sample data showing:
- Multiple families with siblings
- Various age ranges and sizes
- Proper text formatting
- Different family situations

## Best Practices

1. **Use quotes** around all text fields to handle commas
2. **Be consistent** with sizing formats within each column  
3. **Keep names simple** - first name only for privacy
4. **Group families** logically with same family_id
5. **Test with small batches** before importing large datasets

## Export Format

The system uses the **exact same format** for exports, making it easy to:
- Edit data in spreadsheet programs
- Re-import modified data  
- Share data between systems
- Create backups

This standardized format ensures clean, predictable imports and maintains data integrity throughout the system.