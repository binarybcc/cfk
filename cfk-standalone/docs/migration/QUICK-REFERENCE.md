# Admin Panel Migration - Quick Reference

**For Developers**: Fast lookup guide for the migrated admin panel.

---

## URL Mapping

### Old → New Routes

| Legacy URL | New Slim Route | HTTP Method |
|-----------|----------------|-------------|
| `admin/index.php` | `/admin/dashboard` | GET |
| `admin/login.php` | `/admin/login` | GET |
| `admin/logout.php` | `/admin/logout` | GET |
| `admin/manage_children.php` | `/admin/children` | GET |
| `admin/manage_children.php?action=add` | `/admin/children/add` | GET |
| `admin/manage_sponsorships.php` | `/admin/sponsorships` | GET |
| `admin/manage_admins.php` | `/admin/users` | GET |
| `admin/import_csv.php` | `/admin/import` | GET |
| `admin/year_end_reset.php` | `/admin/archive` | GET |
| `admin/reports.php` | `/admin/reports` | GET |

---

## File Locations

### Controllers
```
src/Controller/
├── AdminController.php              # Dashboard, Reports
├── AdminChildController.php         # Children CRUD
├── AdminSponsorshipController.php   # Sponsorships
├── AdminImportController.php        # CSV Import/Export
├── AdminArchiveController.php       # Year-End Reset
├── AdminUserController.php          # User Management
└── AdminAuthController.php          # Login/Logout
```

### Templates
```
templates/
├── layouts/admin.twig               # Main layout
└── admin/
    ├── dashboard.twig
    ├── reports.twig
    ├── children/*.twig
    ├── sponsorships/*.twig
    ├── import/*.twig
    ├── archive/*.twig
    ├── users/*.twig
    └── auth/*.twig
```

### Configuration
```
config/slim/
├── container.php    # DI container registration
└── routes.php       # Route definitions
```

---

## Common Operations

### Add a New Admin Route

1. **Create Controller Method**
   ```php
   // src/Controller/AdminFooController.php
   public function bar(Request $request, Response $response): Response
   {
       return $this->view->render($response, 'admin/foo/bar.twig', [
           'pageTitle' => 'Bar'
       ]);
   }
   ```

2. **Register Controller in Container**
   ```php
   // config/slim/container.php
   $container->register(CFK\Controller\AdminFooController::class)
       ->addArgument(new Reference('twig'))
       ->setPublic(true);
   ```

3. **Add Route**
   ```php
   // config/slim/routes.php
   use CFK\Controller\AdminFooController;

   $app->get('/admin/foo/bar', [AdminFooController::class, 'bar']);
   ```

4. **Create Template**
   ```twig
   {# templates/admin/foo/bar.twig #}
   {% extends "layouts/admin.twig" %}

   {% block content %}
       <h1>{{ pageTitle }}</h1>
   {% endblock %}
   ```

---

## Authentication

### Check if User is Logged In

```php
// In any admin page
if (!isLoggedIn()) {
    header('Location: ' . baseUrl('/admin/login'));
    exit;
}
```

### Check Admin Role

```php
// Only allow admins
if (($_SESSION['cfk_admin_role'] ?? '') !== 'admin') {
    $_SESSION['error_message'] = 'Access denied.';
    return $response->withHeader('Location', baseUrl('/admin/dashboard'))->withStatus(302);
}
```

### Session Variables

```php
$_SESSION['cfk_admin_id']       // Admin user ID
$_SESSION['cfk_admin_email']    // Admin email
$_SESSION['cfk_admin_username'] // Admin username
$_SESSION['cfk_admin_role']     // 'admin' or 'editor'
$_SESSION['login_time']         // Unix timestamp
$_SESSION['login_ip']           // IP address
```

---

## CSRF Protection

### Generate Token (in Controller)

```php
$csrfToken = generateCsrfToken();

return $this->view->render($response, 'template.twig', [
    'csrfToken' => $csrfToken
]);
```

### Add to Form (in Template)

```twig
<form method="POST">
    <input type="hidden" name="csrf_token" value="{{ csrfToken }}">
    <!-- form fields -->
</form>
```

### Verify Token (in Controller POST)

```php
$data = $request->getParsedBody();

if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Security token invalid.';
    return $response->withHeader('Location', baseUrl('/admin/foo'))->withStatus(302);
}
```

---

## Flash Messages

### Set Message (Redirect Source)

```php
$_SESSION['success_message'] = 'Operation completed successfully.';
$_SESSION['error_message'] = 'An error occurred.';

return $response->withHeader('Location', baseUrl('/admin/foo'))->withStatus(302);
```

### Display Message (Destination Template)

```twig
{% if message %}
    <div class="alert alert-{{ message.type|default('info') }}">
        {{ message }}
    </div>
{% endif %}

{% if error %}
    <div class="alert alert-error">
        {{ error }}
    </div>
{% endif %}
```

### Clear After Display (in Controller)

```php
$message = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
```

---

## Database Operations

### Fetch Single Row

```php
use CFK\Database\Connection as Database;

$child = Database::fetchRow(
    "SELECT * FROM children WHERE id = ?",
    [$childId]
);
```

### Fetch Multiple Rows

```php
$children = Database::fetchAll(
    "SELECT * FROM children WHERE status = ? ORDER BY first_name",
    ['available']
);
```

### Insert

```php
Database::insert('children', [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'age' => $age,
    'created_at' => date('Y-m-d H:i:s')
]);

$newId = Database::getLastInsertId();
```

### Update

```php
Database::update('children',
    ['status' => 'sponsored'],  // Data to update
    ['id' => $childId]           // WHERE conditions
);
```

### Delete

```php
Database::delete('children', ['id' => $childId]);
```

### Execute (No Result)

```php
Database::execute(
    "UPDATE children SET status = ? WHERE id = ?",
    ['available', $childId]
);
```

---

## Input Sanitization

```php
// Strings
$name = sanitizeString($_POST['name'] ?? '');

// Emails
$email = sanitizeEmail($_POST['email'] ?? '');

// Integers
$id = sanitizeInt($_POST['id'] ?? 0);

// HTML (allows safe tags)
$bio = sanitizeHtml($_POST['bio'] ?? '');
```

---

## Twig Template Helpers

### Functions Available in Templates

```twig
{{ baseUrl('/admin/foo') }}              {# Generate full URL #}
{{ getPhotoUrl(child) }}                 {# Get child photo URL #}
{{ formatAge(age) }}                     {# Format age display #}
{{ getAgeCategory(age, gender) }}        {# Get avatar category #}
{{ sanitizeString(text) }}               {# Sanitize string #}
```

### Common Template Patterns

**Extend Layout:**
```twig
{% extends "layouts/admin.twig" %}

{% block title %}My Page{% endblock %}

{% block content %}
    <!-- page content -->
{% endblock %}
```

**Loop with Alternating Rows:**
```twig
{% for item in items %}
    <tr class="{{ cycle(['even', 'odd'], loop.index0) }}">
        <td>{{ item.name }}</td>
    </tr>
{% endfor %}
```

**Conditional Display:**
```twig
{% if items is empty %}
    <p>No items found.</p>
{% else %}
    <ul>
        {% for item in items %}
            <li>{{ item.name }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

---

## Testing

### Run Smoke Tests

```bash
cd cfk-standalone
./tests/smoke-test-admin-migration.sh
```

### Manual Testing Checklist

1. Login with magic link
2. Navigate all menu items
3. Create/edit/delete a child
4. View sponsorships
5. Upload CSV
6. Create/delete admin user
7. Logout

---

## Debugging

### Enable Debug Mode

```php
// config/config.php
define('APP_DEBUG', true);
```

### Check Error Logs

```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php-fpm/error.log
```

### Common Issues

**Issue:** 404 on admin routes
**Solution:** Verify .htaccess rewrite rules are working

**Issue:** CSRF token invalid
**Solution:** Clear browser cookies and retry

**Issue:** Session not persisting
**Solution:** Check session.save_path permissions

---

## Useful Commands

```bash
# Start dev server
php -S localhost:8080 -t cfk-standalone

# Check PHP syntax
php -l file.php

# Find all TODOs
grep -r "TODO" src/

# Count lines of code
find src/ -name "*.php" | xargs wc -l

# Clear Twig cache
rm -rf cfk-standalone/cache/twig/*
```

---

## Need Help?

- **Test Plan:** `docs/testing/week8-9-migration-test-plan.md`
- **Full Migration Report:** `docs/migration/WEEK8-9-MIGRATION-COMPLETE.md`
- **Legacy Files Reference:** `admin/LEGACY_FILES.md`
- **Project Guidelines:** `CLAUDE.md`

---

**Last Updated:** 2025-11-12
