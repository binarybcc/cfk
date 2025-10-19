# Logo Implementation Plan

## 📋 Files Needed

Please save the "Christmas for Kids" logo to:
- **Main logo:** `assets/images/cfk-logo.png` (transparent background, ~800x300px)
- **Favicon:** `assets/images/favicon.ico` (16x16, 32x32, 48x48 sizes)
- **Favicon PNG:** `assets/images/favicon.png` (256x256 square version)

## 🎯 Implementation Locations

### 1. Header Logo (Primary)
**File:** `includes/header.php`
**Current:** Text-based logo
**New:** Image logo with fallback text

### 2. Favicon
**Files:**
- `includes/header.php` (link tags)
- `assets/images/favicon.ico`

### 3. Footer Logo
**File:** `includes/footer.php`
**New:** Smaller logo with copyright

### 4. Email Templates
**Files:**
- `includes/sponsorship_manager.php` (portal email)
- Any other email templates

## 📐 Recommended Sizes

- **Header Logo:** 300px wide x auto height (max 100px height)
- **Footer Logo:** 200px wide x auto height
- **Favicon:** 32x32px (standard), 16x16px, 48x48px
- **Email Logo:** 400px wide x auto height

## 🎨 Design Requirements

- **Format:** PNG with transparent background
- **Colors:** Match the official logo (green, red, yellow)
- **Quality:** High resolution for retina displays
- **Alt text:** "Christmas for Kids"

## ⚡ Quick Start

1. Save logo as `cfk-logo.png` in this directory
2. I'll create favicon versions automatically
3. I'll update all necessary files
4. Deploy to server

Ready when you are!
