# Christmas for Kids - Standalone Application Project Plan

## PROJECT GOALS

**Replace WooCommerce-based child sponsorship system with dignified, custom PHP application**

### Core Requirements
- **Maintainable by non-coders** - Simple PHP, clear documentation
- **Nexcess hosting compatible** - No framework conflicts, pure PHP
- **Visitor-focused UX** - Easy browsing, search, filtering
- **Respectful approach** - Children are not "products" - dignified sponsorship connections
- **Zeffy integration** - Donation button integration using provided script

## CURRENT STATUS

### What We Learned from cforkids.org Analysis
- Current site uses WooCommerce (children as "products" - insulting approach we're replacing)
- Key functionality: search, age filtering, family grouping, sponsorship status tracking
- Family numbering system (175A, 175B, 175C for siblings)
- Detailed child profiles with clothing sizes, interests, needs
- Categories: Birth-to-4, Elementary, Middle School, High School

### Previous WordPress Plugin Attempt (FAILED)
- Built complex WordPress plugin with shortcodes
- Shortcode `[cfk_children]` never processed correctly
- Admin interface works but public pages failed
- Too complex, framework-dependent approach

## NEW APPROACH: Standalone PHP Application

### Technology Stack Decision
**Pure PHP + Modern Frontend** chosen because:
- Maintainable by non-coders
- Zero framework conflicts with Nexcess
- Fast, simple, reliable
- Easy Zeffy integration

### Step-by-Step Implementation Plan

#### Phase 1: Foundation (IMMEDIATE)
1. **Database Design**
   - `children` table (id, name, age, gender, grade, school, family_id, status, etc.)
   - `families` table (id, family_number, name, notes)
   - `sponsorships` table (id, child_id, sponsor_info, date, status)

2. **Basic File Structure**
   ```
   cfk-standalone/
   ├── index.php (main entry point)
   ├── config/
   │   ├── config.php (database, settings)
   │   └── database.php (connection)
   ├── pages/
   │   ├── children.php (main listing)
   │   ├── child.php (individual profile)
   │   ├── search.php (search results)
   │   └── admin.php (simple admin)
   ├── includes/
   │   ├── functions.php (helper functions)
   │   └── header.php, footer.php
   ├── assets/
   │   ├── css/styles.css
   │   └── js/search.js
   └── admin/
       ├── login.php
       ├── manage-children.php
       └── add-child.php
   ```

3. **Core Functions**
   - Database connection
   - Child listing with pagination
   - Search functionality
   - Basic styling

#### Phase 2: Core Features
1. **Child Browsing System**
   - Grid layout like current site
   - Age category filtering
   - Family grouping display
   - Sponsorship status indicators

2. **Search & Filtering**
   - Text search across child details
   - Age range filtering
   - Gender filtering
   - Family-based filtering

3. **Individual Child Profiles**
   - Detailed information display
   - Photo support
   - Sponsorship interest form
   - Family context (siblings)

#### Phase 3: Admin Interface (Non-Coder Friendly)
1. **Simple Admin Panel**
   - Add/edit children (forms, not code)
   - Manage families
   - Upload photos
   - Update sponsorship status

2. **Data Management**
   - CSV import functionality
   - Backup system
   - Simple reporting

#### Phase 4: Integration & Polish
1. **Zeffy Integration**
   - Add donation button using provided script
   - Seamless integration on relevant pages

2. **Production Readiness**
   - Security hardening
   - Performance optimization
   - Documentation for maintainers
   - Setup instructions

## KEY DESIGN PRINCIPLES

1. **Dignity First**: Children are individuals seeking support, not products
2. **Simplicity**: Code that any web-savvy person can understand and modify
3. **User Experience**: Fast, intuitive browsing for sponsors
4. **Maintainability**: Clear file structure, commented code, documentation
5. **Compatibility**: Works on standard Nexcess hosting without conflicts

## SUCCESS CRITERIA

- [ ] Non-coder can add/edit child information via web interface
- [ ] Visitors can easily browse and search children
- [ ] Family relationships are clearly displayed
- [ ] Sponsorship process is smooth and respectful
- [ ] Zeffy donation integration works seamlessly
- [ ] System performs well on Nexcess hosting
- [ ] Code is documented and maintainable

## ZEFFY INTEGRATION DETAILS
- Script: `https://zeffy-scripts.s3.ca-central-1.amazonaws.com/embed-form-script.min.js`
- Link: `zeffy-form-link="https://www.zeffy.com/embed/donation-form/donate-to-christmas-for-kids?modal=true"`

## NEXT IMMEDIATE STEPS
1. Create database schema
2. Build basic PHP file structure
3. Implement child listing page
4. Add search functionality
5. Create admin interface

---

**IMPORTANT**: This replaces the failed WordPress plugin approach with a simpler, more maintainable solution that respects the children and serves the sponsors effectively.