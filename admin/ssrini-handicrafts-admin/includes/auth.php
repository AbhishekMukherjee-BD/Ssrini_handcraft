<?php
/**
 * Ssrini HandiCrafts
 * Authentication Guard
 */

if(session_status() === PHP_SESSION_NONE) {
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