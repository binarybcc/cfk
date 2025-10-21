<?php

/**
 * DEPRECATED: This file is kept for backwards compatibility only.
 * The actual implementation has moved to src/Import/Analyzer.php
 *
 * The CFK_Import_Analyzer class is automatically available via class_alias()
 * defined in config/config.php - no need to define it here.
 */

// Prevent direct access
if (!defined('CFK_APP')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Class is now loaded via Composer autoloader and aliased in config.php
// This file intentionally left empty to maintain backwards compatibility
// with existing require_once statements

return; // Exit early - nothing to do here

// DEPRECATED CODE BELOW - DO NOT USE
// ====================================
class CFK_Import_Analyzer_DEPRECATED
{
    // All functionality moved to CFK\Import\Analyzer
    // This class exists only for reference and should not be used
}
