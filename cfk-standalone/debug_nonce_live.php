<?php
define('CFK_APP', true);
session_start();
require __DIR__ . '/config/config.php';
require __DIR__ . '/includes/functions.php';

// Start output buffering to capture header
ob_start();
include __DIR__ . '/includes/header.php';
$headerHtml = ob_get_clean();

// Output debug info
header('Content-Type: text/html; charset=UTF-8');
echo '<pre style="background:#000;color:#0f0;padding:20px;">';
echo "=== NONCE DEBUG ANALYSIS ===\n\n";
echo "1. Session ID: " . session_id() . "\n";
echo "2. Session nonce in \$_SESSION: " . ($_SESSION['csp_nonce'] ?? 'NOT SET') . "\n";
echo "3. \$cspNonce variable exists: " . (isset($cspNonce) ? 'YES' : 'NO') . "\n";
echo "4. \$cspNonce value: " . ($cspNonce ?? 'NOT SET') . "\n\n";

// Check how many times nonce appears in HTML
$nonceCount = preg_match_all('/nonce="([^"]*)"/', $headerHtml, $matches);
echo "5. Nonce attributes found in HTML: " . $nonceCount . "\n";

if ($nonceCount > 0) {
    echo "6. Nonce values found:\n";
    foreach (array_unique($matches[1]) as $i => $value) {
        $display = $value ? substr($value, 0, 20) . '...' : 'EMPTY STRING';
        echo "   - Value " . ($i+1) . ": " . $display . "\n";
    }
}

// Show first 500 chars of header HTML around first nonce
$firstNoncePos = strpos($headerHtml, 'nonce=');
if ($firstNoncePos !== false) {
    $snippet = substr($headerHtml, max(0, $firstNoncePos - 50), 150);
    echo "\n7. HTML snippet around first nonce:\n";
    echo htmlspecialchars($snippet) . "\n";
}

echo '</pre>';
echo $headerHtml;
?>
