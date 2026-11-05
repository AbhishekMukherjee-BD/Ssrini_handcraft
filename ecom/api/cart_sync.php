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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$localCart = $input['cart'] ?? [];

$customerId = $_SESSION['customer_id'];

try {
    $pdo->beginTransaction();

    // Clear existing DB cart for user (simplest way to sync, overwrite with new state)
    // Or, merge them. Here we merge by upserting.
    // For simplicity, we just clear and insert the latest local cart, assuming local is the source of truth during the session.
    $dStmt = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ?");
    $dStmt->execute([$customerId]);

    $iStmt = $pdo->prepare("INSERT INTO cart_items (customer_id, product_id, quantity) VALUES (?, ?, ?)");

    foreach ($localCart as $item) {
        $pStmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
        $pStmt->execute([$item['id']]);
        $product = $pStmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $iStmt->execute([$customerId, $product['id'], $item['qty']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
