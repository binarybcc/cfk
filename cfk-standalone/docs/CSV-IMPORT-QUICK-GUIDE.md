# Quick Guide: CSV Import System

## 📤 How to Upload Children

### Step 1: Prepare Your CSV File
- Keep ONE master CSV file with ALL children
- Update it as needed throughout the year
- Don't worry about removing sponsored children from the file
- Standard format: Family ID, Child Letter, Name, Age, Gender, etc.

### Step 2: Upload & Preview
1. Go to **Admin → Import CSV** (https://cforkids.org/admin/import_csv.php)
2. Click "Choose File" and select your CSV
3. Click "Upload & Preview"
4. **Wait for preview** - shows what will change

### Step 3: Review Warnings
**Red Warnings (Important!):**
- Sponsored children not in new file
- You can choose to keep them as "inactive"

**Orange Warnings (Check These):**
- Data being cleared (was filled, now blank)
- Ages going down (probably a typo)

**Yellow Warnings (FYI):**
- Gender changes (unusual but OK)

### Step 4: Confirm Import
1. Choose whether to keep inactive sponsored children ☑️
2. Click "Confirm Import"
3. Done! Sponsorships are automatically preserved

---

## 🔄 What Happens Automatically

### Before Import:
✅ System creates backup of current children
✅ Backup saved with timestamp
✅ Only keeps last 2 backups

### During Import:
✅ Preserves all sponsorship statuses
✅ Matches children by Family ID + Child Letter
✅ Updates everything else (age, size, interests, etc.)

### After Import:
✅ Shows how many sponsorships were preserved
✅ Links to view all children

---

## 🆘 Need to Restore?

### If Import Went Wrong:
1. Scroll down to "Backup Management"
2. Find the backup you want (shows date and count)
3. Click "Restore This Backup"
4. Confirm
5. Done!

### Backups Show:
- Date created
- Reason (csv_import, pre_restore, etc.)
- How many children
- How many families

---

## 💡 Pro Tips

**Maintaining Your CSV:**
1. Always work from ONE master file
2. Add new children to the end
3. Update existing children's info as needed
4. Don't delete sponsored children (system will warn you)

**Before Each Upload:**
1. Make sure ages increased (or stayed same)
2. Check that sponsored children are still in the file
3. Verify no important data is blank

**Using Warnings:**
- **High priority** = Stop and review
- **Medium priority** = Double-check your CSV
- **Low priority** = Probably OK, just confirming

---

## ❓ Common Questions

**Q: Do I need to remove sponsored children from my CSV?**
A: NO! The system automatically preserves their status.

**Q: What if I upload a file missing a sponsored child?**
A: You'll see a red warning. You can choose to keep them as "inactive" or remove them.

**Q: Can I undo an import?**
A: YES! Use the backup restore feature. Every import creates an automatic backup.

**Q: Do I need to check "update existing" anymore?**
A: NO! That option is gone. System always updates everything intelligently.

**Q: What about "dry run" mode?**
A: Gone! The new preview step shows you what will change BEFORE confirming.

**Q: How many backups are kept?**
A: Last 2 backups are kept automatically. Older ones are deleted.

---

## 🚨 Troubleshooting

**"No file to import. Please upload again."**
- Your session expired. Just re-upload the file.

**"CSV parsing failed"**
- Check your CSV format matches the template
- Download the template to compare

**"Backup creation failed"**
- Check server disk space
- Contact your developer

**Uploaded but nothing happened:**
- Make sure you clicked "Confirm Import" after preview
- Check if there are errors shown in preview

---

## 📞 Need Help?

1. Check the warnings - they explain what's wrong
2. Try restoring from backup
3. Contact your developer with:
   - Screenshot of error/warning
   - What you were trying to do
   - Date/time it happened

---

## ✅ Best Practices Checklist

Before each upload:
- [ ] CSV has all current children
- [ ] Ages are correct (not decreased)
- [ ] Sponsored children are included
- [ ] No important fields are blank
- [ ] File is under 5MB

After upload preview:
- [ ] Review ALL warnings
- [ ] Decide about inactive sponsored children
- [ ] Verify statistics look correct
- [ ] Click Confirm Import

After import:
- [ ] Check success message
- [ ] Note how many sponsorships preserved
- [ ] Spot-check a few children
- [ ] Keep your CSV file safe

---

**Remember:** The system is designed to protect your data. Trust the warnings and use the backups if needed!
