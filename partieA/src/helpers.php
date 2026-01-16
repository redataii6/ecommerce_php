<?php
/**
 * Helper functions for the application
 */

/**
 * Set a flash message
 * @param string $type Message type (success, error, warning, info)
 * @param string $message The message
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash messages
 * @return array
 */
function getFlash(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Check if there are flash messages
 * @return bool
 */
function hasFlash(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['flash']);
}

/**
 * Escape HTML output to prevent XSS
 * @param string|null $string
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Get current URL path
 * @return string
 */
function currentPath(): string {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}

/**
 * Check if current path matches
 * @param string $path
 * @return bool
 */
function isCurrentPath(string $path): bool {
    return currentPath() === $path;
}

/**
 * Format price
 * @param float $price
 * @return string
 */
function formatPrice(float $price): string {
    return '€' . number_format($price, 2);
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate required fields
 * @param array $data
 * @param array $required
 * @return array Errors array
 */
function validateRequired(array $data, array $required): array {
    $errors = [];
    foreach ($required as $field) {
        if (empty($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

/**
 * Sanitize string input
 * @param string $input
 * @return string
 */
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Get pagination data
 * @param int $total Total items
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array
 */
function paginate(int $total, int $page = 1, int $perPage = 12): array {
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Generate order status badge HTML
 * @param string $status
 * @return string
 */
function statusBadge(string $status): string {
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(e($status)) . '</span>';
}
