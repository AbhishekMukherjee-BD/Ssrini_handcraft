<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../includes/session_config.php';
}
require_once __DIR__ . '/../../admin/ssrini-handicrafts-admin/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$customerId = $_SESSION['customer_id'];

// If GET, return list of wishlist products
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.slug, p.price, p.discount_price, p.image, p.badge, w.added_at 
                               FROM wishlists w 
                               JOIN products p ON w.product_id = p.id 
                               WHERE w.customer_id = ? 
                               ORDER BY w.added_at DESC");
        $stmt->execute([$customerId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'wishlist' => $items]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'database_error']);
    }
    exit;
}

// If POST, forward to toggle logic
require_once __DIR__ . '/toggle_wishlist.php';
