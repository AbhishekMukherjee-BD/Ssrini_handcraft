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
$productSlug = $input['product_id'] ?? null;

if (!$productSlug) {
    echo json_encode(['success' => false, 'error' => 'missing_product']);
    exit;
}

try {
    $customerId = $_SESSION['customer_id'];
    
    // Get product ID from slug or numeric ID
    $pStmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? OR id = ?");
    $pStmt->execute([$productSlug, $productSlug]);
    $product = $pStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'product_not_found']);
        exit;
    }
    
    $productId = $product['id'];
    
    // Check if already in wishlist
    $cStmt = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $cStmt->execute([$customerId, $productId]);
    
    if ($cStmt->rowCount() > 0) {
        // Remove from wishlist
        $dStmt = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
        $dStmt->execute([$customerId, $productId]);
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        // Add to wishlist
        $iStmt = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        $iStmt->execute([$customerId, $productId]);
        echo json_encode(['success' => true, 'status' => 'added']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
