<?php

declare(strict_types=1);

/**
 * Manage Admin Users
 * Add, edit, and manage administrator accounts
 */

// Security constant
define('CFK_APP', true);

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Only admins can manage other admins
if ($_SESSION['cfk_admin_role'] !== 'admin') {
    die('Access denied. Only administrators can manage admin users.');
}

$pageTitle = 'Manage Administrators';
$message = '';
$error = '';

// Handle form submissions
if ($_POST !== []) {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } elseif (isset($_POST['add_admin'])) {
        // Add new admin
        $username = sanitizeString($_POST['username'] ?? '');
        $email = sanitizeEmail($_POST['email'] ?? '');
        $fullName = sanitizeString($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = sanitizeString($_POST['role'] ?? 'editor');
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Username, email, and password are required.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen((string) $password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!in_array($role, ['admin', 'editor'])) {
            $error = 'Invalid role selected.';
        } else {
            // Check if username already exists
            $existing = Database::fetchRow(
                "SELECT id FROM admin_users WHERE username = ?",
                [$username]
            );

            if ($existing) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Create new admin user
                $passwordHash = password_hash((string) $password, PASSWORD_DEFAULT);

                Database::insert('admin_users', [
                    'username' => $username,
                    'password_hash' => $passwordHash,
                    'email' => $email,
                    'full_name' => $fullName,
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $message = "Administrator '{$username}' has been created successfully.";
                error_log("CFK Admin: New admin user created: $username by " . $_SESSION['cfk_admin_username']);
            }
        }
    } elseif (isset($_POST['edit_admin'])) {
        $adminId = sanitizeInt($_POST['admin_id'] ?? 0);
        $email = sanitizeEmail($_POST['email'] ?? '');
        $fullName = sanitizeString($_POST['full_name'] ?? '');
        $role = sanitizeString($_POST['role'] ?? 'editor');
        $newPassword = $_POST['new_password'] ?? '';

        if ($adminId <= 0 || empty($email)) {
            $error = 'Invalid admin ID or email.';
        } elseif (!in_array($role, ['admin', 'editor'])) {
            $error = 'Invalid role selected.';
        } else {
            $updateData = [
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Update password if provided
            if (!empty($newPassword)) {
                if (strlen((string) $newPassword) < 8) {
                    $error = 'New password must be at least 8 characters long.';
                } else {
                    $updateData['password_hash'] = password_hash((string) $newPassword, PASSWORD_DEFAULT);
                }
            }

            if ($error === '' || $error === '0') {
                Database::update('admin_users', $updateData, ['id' => $adminId]);
                $message = 'Administrator updated successfully.';
                error_log("CFK Admin: Admin user updated: ID $adminId by " . $_SESSION['cfk_admin_username']);
            }
        }
    } elseif (isset($_POST['delete_admin'])) {
        $adminId = sanitizeInt($_POST['admin_id'] ?? 0);

        // Prevent deleting yourself
        if ($adminId == $_SESSION['cfk_admin_id']) {
            $error = 'You cannot delete your own account.';
        } elseif ($adminId <= 0) {
            $error = 'Invalid admin ID.';
        } else {
            // Get admin details before deleting
            $admin = Database::fetchRow("SELECT username FROM admin_users WHERE id = ?", [$adminId]);

            Database::delete('admin_users', ['id' => $adminId]);
            $message = "Administrator '{$admin['username']}' has been deleted.";
            error_log("CFK Admin: Admin user deleted: {$admin['username']} by " . $_SESSION['cfk_admin_username']);
        }
    }
}

// Get all admin users
$admins = Database::fetchAll("
    SELECT id, username, email, full_name, role, last_login, created_at
    FROM admin_users
    ORDER BY created_at DESC
");

// Generate CSP nonce for inline scripts
$cspNonce = bin2hex(random_bytes(16));
include __DIR__ . '/includes/admin_header.php';
?>

<div class="content-header">
    <h1><?php echo $pageTitle; ?></h1>
    <p class="subtitle">Manage administrator accounts and permissions</p>
</div>

<?php if ($message !== '' && $message !== '0') : ?>
    <div class="alert alert-success">
        ✓ <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error !== '' && $error !== '0') : ?>
    <div class="alert alert-error">
        ✗ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Add New Admin Button -->
<div class="action-bar">
        <button id="show-add-admin-btn" class="btn btn-primary">
            ➕ Add New Administrator
        </button>
    </div>

    <!-- Add Admin Form (Hidden by default) -->
    <div id="addAdminForm" class="form-panel" style="display: none;">
        <div class="panel-header">
            <h2>Add New Administrator</h2>
            <button id="hide-add-admin-x" class="btn-close">✕</button>
        </div>

        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="username" class="required">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           required
                           autocomplete="off"
                           pattern="[a-zA-Z0-9_-]+"
                           title="Letters, numbers, underscore, and hyphen only">
                    <small>Letters, numbers, underscore, and hyphen only</small>
                </div>

                <div class="form-group">
                    <label for="email" class="required">Email Address</label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control"
                           required
                           autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text"
                       id="full_name"
                       name="full_name"
                       class="form-control"
                       autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="required">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           required
                           minlength="8"
                           autocomplete="new-password">
                    <small>Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="required">Confirm Password</label>
                    <input type="password"
                           id="confirm_password"
                           name="confirm_password"
                           class="form-control"
                           required
                           minlength="8"
                           autocomplete="new-password">
                </div>
            </div>

            <div class="form-group">
                <label for="role" class="required">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="editor">Editor - Can manage children and sponsorships</option>
                    <option value="admin">Administrator - Full access including user management</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="add_admin" class="btn btn-primary">
                    Create Administrator
                </button>
                <button type="button" id="cancel-add-admin-btn" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Admins List -->
    <div class="data-table-container">
        <h2>Current Administrators</h2>

        <?php if ($admins === []) : ?>
            <p class="no-data">No administrators found.</p>
        <?php else : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin) : ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) $admin['username']); ?></strong>
                                <?php if ($admin['id'] == $_SESSION['cfk_admin_id']) : ?>
                                    <span class="badge badge-primary">You</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="mailto:<?php echo htmlspecialchars((string) $admin['email']); ?>"><?php echo htmlspecialchars((string) $admin['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($admin['full_name'] ?? '—'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $admin['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                    <?php echo ucfirst((string) $admin['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $admin['last_login'] ? date('M j, Y g:i A', strtotime((string) $admin['last_login'])) : 'Never'; ?></td>
                            <td><?php echo date('M j, Y', strtotime((string) $admin['created_at'])); ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-edit btn-edit-admin"
                                        data-admin-id="<?php echo $admin['id']; ?>"
                                        title="Edit">
                                    ✏️ Edit
                                </button>
                                <?php if ($admin['id'] != $_SESSION['cfk_admin_id']) : ?>
                                    <button class="btn btn-sm btn-delete btn-delete-admin"
                                            data-admin-id="<?php echo $admin['id']; ?>"
                                            data-username="<?php echo htmlspecialchars((string) $admin['username'], ENT_QUOTES); ?>"
                                            title="Delete">
                                        🗑️ Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Edit Form (Hidden) -->
                        <tr id="editForm<?php echo $admin['id']; ?>" class="edit-row" style="display: none;">
                            <td colspan="7">
                                <form method="POST" action="" class="inline-edit-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">

                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Username</label>
                                            <input type="text"
                                                   value="<?php echo htmlspecialchars((string) $admin['username']); ?>"
                                                   class="form-control"
                                                   disabled>
                                            <small>Username cannot be changed</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Email Address *</label>
                                            <input type="email"
                                                   name="email"
                                                   value="<?php echo htmlspecialchars((string) $admin['email']); ?>"
                                                   class="form-control"
                                                   required>
                                        </div>

                                        <div class="form-group">
                                            <label>Full Name</label>
                                            <input type="text"
                                                   name="full_name"
                                                   value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>"
                                                   class="form-control">
                                        </div>

                                        <div class="form-group">
                                            <label>Role *</label>
                                            <select name="role" class="form-control" required>
                                                <option value="editor" <?php echo $admin['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                                <option value="admin" <?php echo $admin['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>New Password</label>
                                            <input type="password"
                                                   name="new_password"
                                                   class="form-control"
                                                   minlength="8"
                                                   autocomplete="new-password">
                                            <small>Leave blank to keep current password</small>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" name="edit_admin" class="btn btn-primary">
                                            Save Changes
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-cancel-edit" data-admin-id="<?php echo $admin['id']; ?>">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Form (Hidden) -->
    <form id="deleteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="admin_id" id="deleteAdminId">
        <input type="hidden" name="delete_admin" value="1">
    </form>

<style>

.content-header {
    margin-bottom: 2rem;
}

.content-header h1 {
    font-size: 2rem;
    color: #2c5530;
    margin-bottom: 0.5rem;
}

.subtitle {
    color: #666;
    font-size: 1rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.action-bar {
    margin-bottom: 2rem;
}

.form-panel {
    background: white;
    border: 2px solid #2c5530;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #eee;
}

.panel-header h2 {
    margin: 0;
    color: #2c5530;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 30px;
    height: 30px;
    line-height: 1;
}

.btn-close:hover {
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #333;
}

.form-group label.required::after {
    content: ' *';
    color: #d32f2f;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #2c5530;
}

.form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #2c5530;
    color: white;
}

.btn-primary:hover {
    background: #1e3a21;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
}

.btn-edit {
    background: #ffc107;
    color: #000;
}

.btn-edit:hover {
    background: #ffb300;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

.data-table-container {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table-container h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: #2c5530;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    background: #f8f9fa;
    padding: 0.07rem 0.5rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    line-height: 1.2;
}

.data-table tbody td {
    padding: 0.07rem 0.5rem;
    border-bottom: 1px solid #dee2e6;
    line-height: 1.2;
}

/* Zebra Striping */
.data-table tbody tr:nth-child(even) {
    background: #e9ecef;
}

.data-table tbody tr:nth-child(odd) {
    background: white;
}

.data-table tbody tr:hover {
    background: #e7f1ff !important;
    transition: background-color 0.15s ease;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
}

.badge-primary {
    background: #007bff;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.badge-info {
    background: #17a2b8;
    color: white;
}

.actions {
    white-space: nowrap;
}

.actions .btn {
    margin-right: 0.5rem;
}

.edit-row td {
    background: #f8f9fa !important;
    padding: 2rem !important;
}

.inline-edit-form {
    background: white;
    padding: 1.5rem;
    border-radius: 6px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.no-data {
    text-align: center;
    padding: 3rem;
    color: #666;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .form-row,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .data-table {
        font-size: 0.875rem;
    }

    .data-table thead th,
    .data-table tbody td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<script nonce="<?php echo $cspNonce; ?>">
// CSP-compliant event listeners for manage_admins.php
document.addEventListener('DOMContentLoaded', function() {
    const addAdminForm = document.getElementById('addAdminForm');
    const usernameInput = document.getElementById('username');
    const deleteForm = document.getElementById('deleteForm');
    const deleteAdminId = document.getElementById('deleteAdminId');

    // Helper function to show add admin form
    function showAddAdminForm() {
        addAdminForm.style.display = 'block';
        usernameInput.focus();
    }

    // Helper function to hide add admin form
    function hideAddAdminForm() {
        addAdminForm.style.display = 'none';
        // Reset form
        const form = addAdminForm.querySelector('form');
        if (form) {
            form.reset();
        }
    }

    // Helper function to edit admin
    function editAdmin(adminId) {
        // Hide all other edit forms
        document.querySelectorAll('.edit-row').forEach(row => {
            row.style.display = 'none';
        });

        // Show this edit form
        const editRow = document.getElementById('editForm' + adminId);
        if (editRow) {
            editRow.style.display = 'table-row';

            // Scroll to form
            editRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Helper function to cancel edit
    function cancelEdit(adminId) {
        const editForm = document.getElementById('editForm' + adminId);
        if (editForm) {
            editForm.style.display = 'none';
        }
    }

    // Helper function to delete admin
    function deleteAdmin(adminId, username) {
        if (confirm(`Are you sure you want to delete administrator "${username}"?\n\nThis action cannot be undone.`)) {
            deleteAdminId.value = adminId;
            deleteForm.submit();
        }
    }

    // Show Add Admin Form button
    const showAddBtn = document.getElementById('show-add-admin-btn');
    if (showAddBtn) {
        showAddBtn.addEventListener('click', showAddAdminForm);
    }

    // Hide Add Admin Form buttons
    const hideAddX = document.getElementById('hide-add-admin-x');
    if (hideAddX) {
        hideAddX.addEventListener('click', hideAddAdminForm);
    }

    const cancelAddBtn = document.getElementById('cancel-add-admin-btn');
    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', hideAddAdminForm);
    }

    // Edit admin buttons (event delegation)
    document.querySelectorAll('.btn-edit-admin').forEach(button => {
        button.addEventListener('click', function() {
            const adminId = this.getAttribute('data-admin-id');
            editAdmin(adminId);
        });
    });

    // Cancel edit buttons (event delegation)
    document.querySelectorAll('.btn-cancel-edit').forEach(button => {
        button.addEventListener('click', function() {
            const adminId = this.getAttribute('data-admin-id');
            cancelEdit(adminId);
        });
    });

    // Delete admin buttons (event delegation)
    document.querySelectorAll('.btn-delete-admin').forEach(button => {
        button.addEventListener('click', function() {
            const adminId = this.getAttribute('data-admin-id');
            const username = this.getAttribute('data-username');
            deleteAdmin(adminId, username);
        });
    });

    // Form validation
    const addForm = addAdminForm.querySelector('form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                e.preventDefault();
                return false;
            }

            if (password.length < 8) {
                alert('Password must be at least 8 characters long!');
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
