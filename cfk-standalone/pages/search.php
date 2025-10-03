<?php
/**
 * Search Results Page
 * Displays search results for children
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Get search query
$searchQuery = sanitizeString($_GET['q'] ?? $_GET['search'] ?? '');

if (empty($searchQuery)) {
    // Redirect to children page if no search query
    header('Location: ' . baseUrl('?page=children'));
    exit;
}

// Redirect to children page with search parameter
header('Location: ' . baseUrl('?page=children&search=' . urlencode($searchQuery)));
exit;
