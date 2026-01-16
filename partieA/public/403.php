<?php
/**
 * 403 Forbidden Error Page
 * Displayed when user tries to access a resource they don't own (owner-check)
 */

// Ensure 403 status code is set
if (http_response_code() !== 403) {
    http_response_code(403);
}

$pageTitle = '403 Forbidden - Mini E-Commerce';

// Only include header if not already included
if (!defined('HEADER_INCLUDED')) {
    require_once __DIR__ . '/../src/templates/header.php';
    define('HEADER_INCLUDED', true);
}
?>

<div class="error-page">
    <h1>403</h1>
    <h2>Access Forbidden</h2>
    <p class="lead">You are not allowed to access this resource.</p>
    <p class="text-muted">
        This page belongs to another user. You can only view your own orders and resources.
    </p>
    
    <div class="alert alert-danger d-inline-block">
        <i class="bi bi-shield-exclamation"></i>
        <strong>Owner-Check Failed:</strong> You do not have permission to view this content.
    </div>
    
    <div class="mt-4">
        <a href="/" class="btn btn-primary">
            <i class="bi bi-house"></i> Go to Homepage
        </a>
        <a href="/orders.php" class="btn btn-outline-primary">
            <i class="bi bi-list-check"></i> View My Orders
        </a>
    </div>
</div>

<?php
if (!defined('FOOTER_INCLUDED')) {
    require_once __DIR__ . '/../src/templates/footer.php';
    define('FOOTER_INCLUDED', true);
}
?>
