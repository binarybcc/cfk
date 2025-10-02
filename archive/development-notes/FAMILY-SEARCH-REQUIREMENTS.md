# Family-Based Search Requirements

## Overview
The user-facing sponsorship page needs enhanced search functionality that recognizes family relationships and displays related family members in search results.

## Family Identification System

### Family ID Structure
- **Format**: `[Family Number][Child Letter]`
- **Examples**: 
  - `123A` = Child A of Family 123
  - `123B` = Child B of Family 123  
  - `456C` = Child C of Family 456
- **Database Field**: `family_id` (stored as string like "123A")

### Family Grouping Logic
- **Family Number**: Extract numeric portion (e.g., "123" from "123A")
- **Child Identifier**: Extract letter portion (e.g., "A" from "123A")
- **Siblings**: All children sharing the same family number

## Search Functionality Requirements

### Enhanced Search Features
1. **Individual Search**: When searching returns individual children
2. **Family Context**: Show all family members alongside individual results
3. **Family Grouping**: Option to group results by family
4. **Sibling Indicators**: Visual indication of family relationships

### Search Result Display

#### Individual Child Results
```
[Child Photo] John Doe (8 years old)
Family: 123A
Siblings: Mary Doe (123B), Sarah Doe (123C)
[Sponsor This Child] [View Full Family]
```

#### Family Group Results  
```
Family 123 - The Doe Family (3 children)
├─ 123A: John Doe (8 years old) [Available]
├─ 123B: Mary Doe (6 years old) [Available] 
└─ 123C: Sarah Doe (4 years old) [Sponsored]
[Sponsor Available Children] [View Family Details]
```

## Database Schema Updates

### Required Fields
```sql
ALTER TABLE wp_cfk_children ADD COLUMN family_id VARCHAR(10) NOT NULL;
ALTER TABLE wp_cfk_children ADD COLUMN family_number VARCHAR(5) NOT NULL;
ALTER TABLE wp_cfk_children ADD COLUMN child_letter VARCHAR(2) NOT NULL;
```

### Meta Fields to Add
- `family_id` (e.g., "123A")
- `family_number` (e.g., "123") 
- `child_letter` (e.g., "A")
- `family_name` (e.g., "The Doe Family")

## Frontend Implementation Requirements

### Search Interface Updates
1. **Search by Family ID**: Allow searching "123" to find all Family 123 members
2. **Search by Child ID**: Allow searching "123A" to find specific child
3. **Name Search**: Enhanced to show family context in results
4. **Filter Options**: 
   - Show individual children
   - Show family groups
   - Show only families with available children

### Display Components
1. **Family Badge**: Visual indicator showing family relationships
2. **Sibling List**: Compact display of other family members
3. **Family Modal**: Detailed view of entire family
4. **Sponsorship Options**: 
   - Sponsor individual child
   - Sponsor multiple siblings
   - View family story

## Shortcode Updates

### Enhanced Shortcode Parameters
```php
[cfk_children 
    search="true" 
    family_grouping="true"
    show_siblings="true"
    family_view="individual|grouped|both"
]
```

### Usage Examples
```php
// Show individual children with family context
[cfk_children search="true" show_siblings="true"]

// Show family groups
[cfk_children family_grouping="true" family_view="grouped"]

// Allow both individual and family views
[cfk_children search="true" family_view="both" show_siblings="true"]
```

## User Experience Flow

### Search Process
1. **User enters search** (name, age, family ID, interests)
2. **System finds matches** (individual children)
3. **System identifies families** of matched children
4. **Display results** with family context
5. **User can**:
   - Sponsor individual child
   - View full family details  
   - Sponsor multiple siblings
   - Filter by family availability

### Family Detail View
- **Family Story**: Combined narrative of family situation
- **Individual Profiles**: Each child's details and photo
- **Sponsorship Status**: Visual indicators for each child
- **Sponsor Options**: Flexible sponsorship combinations

## Implementation Phases

### Phase 2.1: Database Schema Updates
- Add family relationship fields to child post type
- Update CSV import to handle family IDs
- Migrate existing data (if any) to include family relationships

### Phase 2.2: Enhanced Search Backend  
- Update search queries to include family context
- Implement family grouping logic
- Add family relationship API endpoints

### Phase 2.3: Frontend Search Interface
- Enhanced search form with family options
- Updated result display with family context
- Family detail modal/page implementation

### Phase 2.4: Shortcode Enhancements
- Add family-based parameters
- Update responsive layouts for family display
- Implement family sponsorship workflows

## Technical Considerations

### Performance
- **Efficient Queries**: Single query to get children + families
- **Caching**: Cache family relationships for repeated searches
- **Pagination**: Handle large families and search results efficiently

### Data Integrity
- **Validation**: Ensure family IDs follow correct format
- **Consistency**: Maintain family relationships during imports/updates
- **Migration**: Safe migration path for existing child data

### User Interface
- **Responsive Design**: Family displays work on mobile
- **Accessibility**: Screen readers understand family relationships
- **Progressive Enhancement**: Works without JavaScript for basic functionality

---

*This enhancement will make the sponsorship system much more family-oriented and user-friendly, allowing sponsors to understand family contexts and make informed sponsorship decisions.*