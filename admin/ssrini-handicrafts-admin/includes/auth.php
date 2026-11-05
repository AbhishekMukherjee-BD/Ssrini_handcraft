<?php
/**
 * Ssrini HandiCrafts
 * Authentication Guard
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header_remove('X-Powered-By');
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if(session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'), // Secure flag for direct HTTPS and reverse-proxy setups
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check whether an admin is logged in.
 */

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Protect admin-only pages.
 */

function requireAdminLogin(): void
{
    // SEC-12: Server-side rolling idle timeout (60 minutes)
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > 3600) {
        session_unset();
        session_destroy();
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        } else {
            header('Location: login.php?expired=1');
        }
        exit;
    }
    $_SESSION['admin_last_activity'] = time();

    if (!isAdminLoggedIn()) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        } else {
            header('Location: login.php');
        }
        exit;
    }
}

/**
 * Generate and return a CSRF token.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token.
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}