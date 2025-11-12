<?php

declare(strict_types=1);

namespace CFK\Controller;

use CFK\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * Admin User Management Controller
 *
 * Handles administrator account management including:
 * - Listing admin users
 * - Creating new administrators
 * - Editing admin details and roles
 * - Deleting admin accounts
 * - Password management
 *
 * Week 8 Part 2 Phase 7 Migration
 * Migrated from: admin/manage_admins.php
 */
class AdminUserController
{
    private Twig $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display Admin Users Management Page
     *
     * Route: GET /admin/users
     * Access: Admin role only
     */
    public function index(Request $request, Response $response): Response
    {
        // Check admin role (only admins can manage users)
        if (($_SESSION['cfk_admin_role'] ?? '') !== 'admin') {
            $_SESSION['error_message'] = 'Access denied. Only administrators can manage admin users.';
            return $response
                ->withHeader('Location', baseUrl('/admin/dashboard'))
                ->withStatus(302);
        }

        // Get all admin users
        $admins = Database::fetchAll("
            SELECT id, username, email, full_name, role, last_login, created_at
            FROM admin_users
            ORDER BY created_at DESC
        ");

        // Get flash messages
        $message = $_SESSION['success_message'] ?? '';
        $error = $_SESSION['error_message'] ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        return $this->view->render($response, 'admin/users/index.twig', [
            'pageTitle' => 'Manage Administrators',
            'admins' => $admins,
            'message' => $message,
            'error' => $error,
            'currentAdminId' => $_SESSION['cfk_admin_id'] ?? 0,
            'csrfToken' => generateCsrfToken(),
        ]);
    }

    /**
     * Create New Administrator
     *
     * Route: POST /admin/users
     * Access: Admin role only
     */
    public function create(Request $request, Response $response): Response
    {
        // Check admin role
        if (($_SESSION['cfk_admin_role'] ?? '') !== 'admin') {
            $_SESSION['error_message'] = 'Access denied.';
            return $response
                ->withHeader('Location', baseUrl('/admin/dashboard'))
                ->withStatus(302);
        }

        $data = $request->getParsedBody();

        // Validate CSRF token
        if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Security token invalid. Please try again.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        // Get form data
        $username = sanitizeString($data['username'] ?? '');
        $email = sanitizeEmail($data['email'] ?? '');
        $fullName = sanitizeString($data['full_name'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $role = sanitizeString($data['role'] ?? 'editor');

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['error_message'] = 'Username, email, and password are required.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        if ($password !== $confirmPassword) {
            $_SESSION['error_message'] = 'Passwords do not match.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        if (strlen($password) < 8) {
            $_SESSION['error_message'] = 'Password must be at least 8 characters long.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        if (!in_array($role, ['admin', 'editor'], true)) {
            $_SESSION['error_message'] = 'Invalid role selected.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        // Check if username already exists
        $existing = Database::fetchRow(
            "SELECT id FROM admin_users WHERE username = ?",
            [$username]
        );

        if ($existing) {
            $_SESSION['error_message'] = 'Username already exists. Please choose a different username.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        // Create new admin user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        Database::insert('admin_users', [
            'username' => $username,
            'password_hash' => $passwordHash,
            'email' => $email,
            'full_name' => $fullName,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['success_message'] = "Administrator '{$username}' has been created successfully.";
        error_log("CFK Admin: New admin user created: $username by " . ($_SESSION['cfk_admin_username'] ?? 'unknown'));

        return $response
            ->withHeader('Location', baseUrl('/admin/users'))
            ->withStatus(302);
    }

    /**
     * Update Existing Administrator
     *
     * Route: POST /admin/users/{id}
     * Access: Admin role only
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        // Check admin role
        if (($_SESSION['cfk_admin_role'] ?? '') !== 'admin') {
            $_SESSION['error_message'] = 'Access denied.';
            return $response
                ->withHeader('Location', baseUrl('/admin/dashboard'))
                ->withStatus(302);
        }

        $adminId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Validate CSRF token
        if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Security token invalid. Please try again.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        // Get form data
        $email = sanitizeEmail($data['email'] ?? '');
        $fullName = sanitizeString($data['full_name'] ?? '');
        $role = sanitizeString($data['role'] ?? 'editor');
        $newPassword = $data['new_password'] ?? '';

        // Validation
        if ($adminId <= 0 || empty($email)) {
            $_SESSION['error_message'] = 'Invalid admin ID or email.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        if (!in_array($role, ['admin', 'editor'], true)) {
            $_SESSION['error_message'] = 'Invalid role selected.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        $updateData = [
            'email' => $email,
            'full_name' => $fullName,
            'role' => $role,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Update password if provided
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $_SESSION['error_message'] = 'New password must be at least 8 characters long.';
                return $response
                    ->withHeader('Location', baseUrl('/admin/users'))
                    ->withStatus(302);
            }
            $updateData['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        Database::update('admin_users', $updateData, ['id' => $adminId]);

        $_SESSION['success_message'] = 'Administrator updated successfully.';
        error_log("CFK Admin: Admin user updated: ID $adminId by " . ($_SESSION['cfk_admin_username'] ?? 'unknown'));

        return $response
            ->withHeader('Location', baseUrl('/admin/users'))
            ->withStatus(302);
    }

    /**
     * Delete Administrator
     *
     * Route: POST /admin/users/{id}/delete
     * Access: Admin role only
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        // Check admin role
        if (($_SESSION['cfk_admin_role'] ?? '') !== 'admin') {
            $_SESSION['error_message'] = 'Access denied.';
            return $response
                ->withHeader('Location', baseUrl('/admin/dashboard'))
                ->withStatus(302);
        }

        $adminId = (int) $args['id'];
        $data = $request->getParsedBody();

        // Validate CSRF token
        if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
            $_SESSION['error_message'] = 'Security token invalid. Please try again.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        // Prevent deleting yourself
        if ($adminId === ($_SESSION['cfk_admin_id'] ?? 0)) {
            $_SESSION['error_message'] = 'You cannot delete your own account.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        if ($adminId <= 0) {
            $_SESSION['error_message'] = 'Invalid admin ID.';
            return $response
                ->withHeader('Location', baseUrl('/admin/users'))
                ->withStatus(302);
        }

        // Get admin details before deleting
        $admin = Database::fetchRow("SELECT username FROM admin_users WHERE id = ?", [$adminId]);

        Database::delete('admin_users', ['id' => $adminId]);

        $_SESSION['success_message'] = "Administrator '" . ($admin['username'] ?? 'Unknown') . "' has been deleted.";
        error_log("CFK Admin: Admin user deleted: " . ($admin['username'] ?? 'Unknown') . " by " . ($_SESSION['cfk_admin_username'] ?? 'unknown'));

        return $response
            ->withHeader('Location', baseUrl('/admin/users'))
            ->withStatus(302);
    }
}
