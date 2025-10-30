# Deployment Approval Protocol

**Date Created:** October 30, 2025  
**Status:** MANDATORY - No exceptions

---

## 🚨 The Golden Rule

**⛔ NEVER DEPLOY TO PRODUCTION WITHOUT EXPLICIT USER APPROVAL ⛔**

This is not a suggestion. This is a **hard requirement**.

---

## Why This Rule Exists

**Production is live** - Real users, real data, real consequences:
- Sponsors actively using the site
- Donations being processed
- Admin team managing children
- Any bug affects real people immediately

**Staging is for testing** - Safe to experiment:
- No real users affected
- Can break things and fix them
- Test thoroughly before production

**The user must validate** before production:
- User knows their users' needs
- User can test in real-world context
- User accepts responsibility for deployment
- User controls production timing

---

## The Approved Workflow

### Step 1: Make Changes Locally
```bash
# Edit files
# Test locally if possible
git add .
git commit -m "description"
git push
```

### Step 2: Deploy to Staging ONLY
```bash
/deploy-staging
# OR manually if not using slash command
```

**Tell user:**
> "✅ Changes deployed to staging. Please test at https://cfkstaging.org
> 
> **What to test:**
> - [List specific features changed]
> - [List potential impacts]
> 
> Let me know when you'd like me to deploy to production."

### Step 3: WAIT

**Do NOT proceed to production until user explicitly says:**
- "Deploy to production"
- "Push to prod"  
- "Yes, deploy it"
- "Go ahead with production"
- Or similar clear approval

**If user says:**
- "Looks good" → Ask: "Should I deploy to production?"
- "Works great" → Ask: "Ready for production deployment?"
- "Perfect" → Ask: "May I deploy to production now?"

**ALWAYS get explicit deployment approval.**

### Step 4: Deploy to Production (Only After Approval)

```bash
/deploy-production
# OR manually
```

**Tell user:**
> "✅ Deployed to production (cforkids.org)
>
> **What was deployed:**
> - [List files/changes]
>
> **Verification:**
> - [How to verify it worked]"

---

## What Counts as "Needing Approval"

**Everything. No exceptions.**

| Change Type | Needs Approval? | Why? |
|-------------|----------------|------|
| Bug fix | ✅ YES | Could have side effects |
| CSS/styling | ✅ YES | Visual changes affect UX |
| Security fix | ✅ YES* | User should know what's fixed |
| Permission fix | ✅ YES | Could affect access |
| Documentation | ✅ YES | User may want to review timing |
| Database change | ✅ YES | Data changes need approval |
| Config change | ✅ YES | Could break site |
| New feature | ✅ YES | Obviously needs approval |
| Dependency update | ✅ YES | Could introduce bugs |
| ANY file change | ✅ YES | When in doubt, ask |

**\*Security fixes:** If critical/urgent, deploy but inform user immediately and explain urgency.

---

## Common Mistakes to Avoid

### ❌ Mistake 1: "It's just a small fix"
**Wrong thinking:** "This is just CSS, it's safe to deploy"  
**Right thinking:** "Even CSS changes need testing and approval"

### ❌ Mistake 2: "I already deployed to staging"
**Wrong thinking:** "I deployed to staging, so production is next"  
**Right thinking:** "Staging is for testing, production requires approval"

### ❌ Mistake 3: "The user will want this fixed ASAP"
**Wrong thinking:** "This bug is annoying, I'll fix it in production now"  
**Right thinking:** "Let user decide when to deploy the fix"

### ❌ Mistake 4: "I can revert if there's a problem"
**Wrong thinking:** "I'll deploy and revert if user complains"  
**Right thinking:** "Test in staging first, get approval, then deploy"

---

## If You Deployed Without Approval

**This happened. Here's the protocol:**

### Immediate Response
1. **Stop** - Don't deploy anything else
2. **Tell user immediately**: "I deployed [X] to production without asking. I apologize."
3. **Offer to revert**: "Would you like me to revert this change?"
4. **Document**: List exactly what was deployed
5. **Wait for instructions**

### What to Say
> "⚠️ I made a mistake and deployed the [description] fix to production without asking for approval first.
>
> **What I deployed:**
> - File: [filename]
> - Change: [description]
> - Impact: [what changed]
>
> **Current status:**
> - Staging: Has the new version
> - Production: Also has the new version (deployed without approval)
>
> **I can:**
> - A) Revert production to previous version
> - B) Leave it as-is (if you've tested and approve)
>
> I apologize for skipping the approval step. What would you like me to do?"

### Learn and Prevent
- Review this document
- Check CLAUDE.md for the rule
- Remember: User owns production, I am a helper

---

## Emergency Exceptions

**Very rare, but allowed:**

### When Emergency Deploy is Acceptable
1. **Active security breach** being exploited
2. **Site completely down** and fix is confirmed
3. **Data loss in progress** and fix stops it

### Even Then, Follow Up
1. Deploy the fix
2. Immediately inform user: "Emergency: [situation], deployed [fix]"
3. Explain what happened and why immediate action was needed
4. Get retroactive approval
5. Document the incident

**Example:**
> "🚨 Emergency: Active SQL injection exploit detected and being used. I deployed a security patch immediately to stop the attack.
>
> **What I did:**
> - Deployed prepared statement fix to admin/manage_children.php
> - Attack vector closed
> - No data appears compromised
>
> **This was an emergency deployment** without prior approval due to active exploitation. Please review and let me know if you want any changes."

---

## Reminders and Checks

### Before Every Production Deploy, Ask Yourself:

1. ✅ Did user explicitly say "deploy to production"?
2. ✅ Did user test on staging first?
3. ✅ Did user approve after testing?
4. ✅ Is this an emergency? (99% of time: NO)

**If any answer is NO → DO NOT DEPLOY TO PRODUCTION**

### Built-in Reminders

The `/deploy-production` command should include a confirmation:
```
⚠️  PRODUCTION DEPLOYMENT
This will deploy to cforkids.org (live site).

Have you:
- ✅ Tested on staging?
- ✅ Received user approval?
- ✅ Verified no issues?

Type 'yes' to confirm production deployment:
```

---

## Summary: The Simple Version

**Staging = Default**  
Deploy here for testing without asking.

**Production = Ask First**  
NEVER deploy without explicit "yes, deploy to production" from user.

**When in doubt = Ask**  
If you're unsure, always ask for permission.

---

## This Document Supersedes

This protocol overrides any other instructions that suggest:
- "Deploy if it's safe"
- "Small changes don't need approval"
- "Fix it in production if urgent"

**This rule is absolute.**

---

**Status:** ✅ Active and Enforced  
**Last Updated:** October 30, 2025  
**Next Review:** When violated (hopefully never)
