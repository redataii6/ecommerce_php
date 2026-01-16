<?php
/**
 * Authentication helper functions
 * Handles user sessions, login, logout, and role checks
 */

require_once __DIR__ . '/db.php';

/**
 * Start session if not already started
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn(): bool {
    initSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId(): ?int {
    initSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser(): ?array {
    initSession();
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, email, name, roles, is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Check if current user is admin
 * @return bool
 */
function isAdmin(): bool {
    initSession();
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $roles = json_decode($user['roles'], true) ?: [];
    return in_array('ROLE_ADMIN', $roles);
}

/**
 * Check if current user has a specific role
 * @param string $role
 * @return bool
 */
function hasRole(string $role): bool {
    initSession();
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $roles = json_decode($user['roles'], true) ?: [];
    return in_array($role, $roles);
}

/**
 * Authenticate user with email and password
 * @param string $email
 * @param string $password
 * @return array|false User data on success, false on failure
 */
function authenticate(string $email, string $password): array|false {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, email, name, password_hash, roles, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    if (!$user['is_active']) {
        return false;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    
    return $user;
}

/**
 * Login user and create session
 * @param array $user
 */
function loginUser(array $user): void {
    initSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_roles'] = json_decode($user['roles'], true) ?: [];
}

/**
 * Logout user and destroy session
 */
function logout(): void {
    initSession();
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Require user to be logged in, redirect to login if not
 * @param string $redirectUrl
 */
function requireLogin(string $redirectUrl = '/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Require user to be admin, show 403 if not
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include __DIR__ . '/../public/403.php';
        exit;
    }
}

/**
 * Check if current user owns a resource (owner-check)
 * @param int $ownerId The owner ID of the resource
 * @return bool
 */
function isOwner(int $ownerId): bool {
    $currentUserId = getCurrentUserId();
    return $currentUserId !== null && $currentUserId === $ownerId;
}

/**
 * Require ownership or admin access, show 403 if not
 * @param int $ownerId
 */
function requireOwnerOrAdmin(int $ownerId): void {
    requireLogin();
    if (!isOwner($ownerId) && !isAdmin()) {
        http_response_code(403);
        include __DIR__ . '/../public/403.php';
        exit;
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCsrfToken(): string {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCsrfToken(string $token): bool {
    initSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 * @return string
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}
