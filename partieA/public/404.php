<?php
/**
 * 404 Not Found Error Page
 */

if (http_response_code() !== 404) {
    http_response_code(404);
}

$pageTitle = '404 Not Found - Mini E-Commerce';

if (!defined('HEADER_INCLUDED')) {
    require_once __DIR__ . '/../src/templates/header.php';
    define('HEADER_INCLUDED', true);
}
?>

<div class="error-page">
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p class="lead">The page you're looking for doesn't exist.</p>
    <p class="text-muted">
        The resource may have been moved or deleted.
    </p>
    
    <div class="mt-4">
        <a href="/" class="btn btn-primary">
            <i class="bi bi-house"></i> Go to Homepage
        </a>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Go Back
        </a>
    </div>
</div>

<?php
if (!defined('FOOTER_INCLUDED')) {
    require_once __DIR__ . '/../src/templates/footer.php';
    define('FOOTER_INCLUDED', true);
}
?>
