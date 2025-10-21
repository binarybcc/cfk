<?php

/**
 * Page Header Component
 * Reusable page header with gradient background and optional description
 *
 * Required variables:
 * - $title: Page title (string)
 *
 * Optional variables:
 * - $description: Page description (string)
 * - $additionalClasses: Additional CSS classes (string)
 * - $additionalContent: Additional HTML content to display in header (string)
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

$title ??= '';
$description ??= '';
$additionalClasses ??= '';
$additionalContent ??= '';
?>

<div class="page-header <?php echo sanitizeString($additionalClasses); ?>">
    <h1><?php echo sanitizeString($title); ?></h1>
    <?php if ($description) : ?>
        <p class="page-description"><?php echo sanitizeString($description); ?></p>
    <?php endif; ?>
    <?php if ($additionalContent) : ?>
        <?php echo $additionalContent; ?>
    <?php endif; ?>
</div>

<style>
/* Page Header Component Styles */
.page-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    border-radius: 8px;
}

.page-header h1 {
    margin-bottom: 1rem;
    font-size: 2.5rem;
    color: white;
}

.page-description {
    font-size: 1.1rem;
    max-width: 800px;
    margin: 0 auto;
    opacity: 0.95;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page-header {
        padding: 2rem 1rem;
    }

    .page-header h1 {
        font-size: 2rem;
    }

    .page-description {
        font-size: 1rem;
    }
}
</style>
