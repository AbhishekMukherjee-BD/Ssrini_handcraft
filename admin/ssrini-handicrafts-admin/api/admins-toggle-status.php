<?php
/**
 * SSRINI HANDICRAFTS
 * API: Toggle Administrator Status (Activate / Deactivate)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

// CSRF check
$postedCsrf = $input['csrf_token'] ?? '';
if (!verifyCsrfToken($postedCsrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
    exit;
}

$adminId = (int)($input['id'] ?? 0);
$action  = strtolower(trim((string)($input['action'] ?? '')));

if ($adminId <= 0 || !in_array($action, ['activate', 'deactivate'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid administrator ID or action.']);
    exit;
}

// Security: Prevent self-deactivation
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);
if ($action === 'deactivate' && $adminId === $currentAdminId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own active session account.']);
    exit;
}

$newStatus = $action === 'activate' ? 'active' : 'inactive';

try {
    // Check if target admin exists
    $checkStmt = $pdo->prepare("SELECT id, name, role FROM admins WHERE id = ?");
    $checkStmt->execute([$adminId]);
    $admin = $checkStmt->fetch();

    if (!$admin) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Administrator not found.']);
        exit;
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE admins SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $adminId]);

    echo json_encode([
        'success'    => true,
        'message'    => 'Administrator successfully ' . ($action === 'activate' ? 'activated' : 'deactivated') . '.',
        'new_status' => $newStatus
    ]);

} catch (PDOException $e) {
    error_log('Admins toggle status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error while updating administrator status.']);
}
