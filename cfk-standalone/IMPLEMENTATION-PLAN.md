# Christmas for Kids - Phase 2 Implementation Plan

## Overview
Building production-ready features for the standalone Christmas for Kids sponsorship system. Focus on dignity, maintainability, and non-coder usability.

## Key Requirements Clarification

### Avatar System (NO Real Photos)
- **7 Avatar Categories**: infant, male toddler, female toddler, male 6-10, female 6-10, teen male, teen female
- **Logic**: Auto-assign based on child's age + gender from database
- **Implementation**: SVG-based avatars for scalability and performance
- **Privacy**: Maintains complete anonymity while providing visual representation

### Core Features to Implement

#### 1. Avatar System
```php
// Age/Gender mapping logic:
// 0-2 years: infant (gender-neutral)
// 3-5 years: toddler (male/female)
// 6-10 years: child (male/female) 
// 11-18 years: teen (male/female)
```

#### 2. Admin Pages (Non-Coder Friendly)
- **Child Management**: Add, edit, delete, bulk operations
- **Sponsorship Processing**: Review requests, confirm, track status
- **Family Management**: Group siblings, edit family info
- **User Management**: Admin accounts and permissions

#### 3. Sponsorship Flow
- **Public Form**: Sponsor selects child, fills contact info
- **Admin Review**: Admin confirms sponsorship details
- **Email Notifications**: Auto-notify sponsors and admins
- **Status Tracking**: Pending → Confirmed → Completed

#### 4. CSV Operations
- **Import**: Bulk add children/families from spreadsheet
- **Export**: Generate reports for printing, backup, analysis
- **Templates**: Provide CSV templates for consistent imports

#### 5. Email System
- **Transactional**: Sponsorship confirmations, status updates
- **Templates**: Pre-written, professional email templates
- **Admin Notifications**: Alert when actions needed
- **Sponsor Communication**: Keep sponsors informed of process

## Implementation Strategy

### Phase 2A: Avatar System
1. Create 7 SVG avatar designs
2. Implement age/gender detection logic
3. Update photo display functions
4. Test across all child profiles

### Phase 2B: Admin Management
1. Child CRUD operations (Create, Read, Update, Delete)
2. Sponsorship management dashboard
3. Family grouping tools
4. User permissions system

### Phase 2C: Sponsorship Processing  
1. Public sponsorship request form
2. Admin approval workflow
3. Status tracking system
4. Sponsor communication tools

### Phase 2D: Data Management
1. CSV import with validation
2. CSV export with filtering
3. Backup and restore functionality
4. Data integrity checks

### Phase 2E: Communication System
1. Email template system
2. SMTP configuration
3. Automated notifications
4. Email logging and tracking

## Technical Specifications

### Avatar System Architecture
```php
class AvatarManager {
    public static function getAvatarForChild(array $child): string;
    private static function determineAvatarCategory(int $age, string $gender): string;
    private static function generateSvgAvatar(string $category): string;
}
```

### Database Extensions
- Add `avatar_category` field to children table for caching
- Email log table for tracking communications
- Admin action log for audit trail

### File Structure Extensions
```
cfk-standalone/
├── admin/
│   ├── manage_children.php (CRUD interface)
│   ├── manage_sponsorships.php (processing)
│   ├── import_csv.php (bulk import)
│   ├── export_data.php (reports)
│   └── email_templates.php (communication)
├── includes/
│   ├── avatar_manager.php (avatar logic)
│   ├── email_manager.php (notifications)
│   └── csv_handler.php (import/export)
└── assets/
    └── avatars/ (SVG avatar files)
```

## Security Considerations

### Data Protection
- No real photos stored or transmitted
- CSRF protection on all admin forms
- Input validation and sanitization
- SQL injection prevention (prepared statements)

### Access Control
- Role-based admin permissions
- Session management
- Login attempt limiting
- Secure password requirements

### Privacy Compliance
- Avatar-only child representation
- Family permission tracking
- Data retention policies
- Audit logging for compliance

## Quality Assurance

### Testing Strategy
- Unit tests for avatar selection logic
- Integration tests for sponsorship flow
- CSV import/export validation
- Email delivery confirmation
- Admin interface usability testing

### Documentation Requirements
- Admin user guide for non-coders
- CSV template instructions
- Email template customization guide
- Troubleshooting documentation

## Success Metrics

### Functionality
- [ ] All 7 avatar categories display correctly
- [ ] Admin can manage children without coding
- [ ] Sponsorship requests process smoothly
- [ ] CSV import handles various data formats
- [ ] Emails deliver reliably

### Usability (Non-Coder Focus)
- [ ] Admin interface requires no technical knowledge
- [ ] Error messages are clear and actionable
- [ ] Common tasks have simple workflows
- [ ] Help text available where needed

### Performance
- [ ] Avatar generation is fast (<100ms)
- [ ] Admin pages load quickly (<2s)
- [ ] CSV operations handle 100+ records
- [ ] Email queue processes efficiently

## Next Steps Questions for Clarification

1. **Email Provider**: Should we use PHP mail() or configure SMTP? Any preferred service?

2. **CSV Format**: Do you have existing CSV templates or should we create standard format?

3. **Avatar Style**: Preference for cartoon-style, minimalist, or realistic SVG avatars?

4. **Admin Roles**: Do you need different permission levels (super admin vs. editor)?

5. **Sponsorship Limits**: Can one sponsor take multiple children? Any restrictions?

6. **Email Frequency**: How often should status update emails be sent?

Ready to proceed with Phase 2A (Avatar System) unless you have other priorities or additional requirements.