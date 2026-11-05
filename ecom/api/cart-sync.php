<?php
/**
 * SSRINI Handcrafts - Cart Synchronization API
 * 
 * Securely syncs the user's local cart with the MySQL `cart_items` table.
 */
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../includes/session_config.php';
}
header('Content-Type: application/json');

// Ensure user is authenticated before accessing or mutating cart data
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Please log in to sync cart.']);
    exit;
}

// Path to the database configuration
$dbConfigPath = __DIR__ . '/../../admin/ssrini-handicrafts-admin/config/database.php';

if (!file_exists($dbConfigPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database configuration not found.']);
    exit;
}

require_once $dbConfigPath; // Provides $pdo

// Read JSON Input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

$action = $data['action'];
$customerId = (int) $_SESSION['customer_id']; // Strictly bound to authenticated session

try {
    if ($action === 'pull') {
        // PULL: Retrieve the cart for the user
        $stmt = $pdo->prepare("SELECT product_id as id, title, price, image, size, weight, qty FROM cart_items WHERE customer_id = ? ORDER BY id ASC");
        $stmt->execute([$customerId]);
        $items = $stmt->fetchAll();
        
        // Ensure numeric fields are cast properly
        foreach ($items as &$item) {
            $item['price'] = (float) $item['price'];
            $item['qty'] = (int) $item['qty'];
        }
        
        echo json_encode(['success' => true, 'cart' => $items]);
        
    } elseif ($action === 'push') {
        // PUSH: Overwrite the user's cart in the DB
        $cart = isset($data['cart']) && is_array($data['cart']) ? $data['cart'] : [];
        
        $pdo->beginTransaction();
        
        // 1. Clear existing cart for this user
        $delStmt = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ?");
        $delStmt->execute([$customerId]);
        
        // 2. Insert new cart items
        if (!empty($cart)) {
            $insertStmt = $pdo->prepare("INSERT INTO cart_items (customer_id, product_id, title, price, image, size, weight, qty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($cart as $item) {
                // Defensive checks for missing fields
                $pId = $item['id'] ?? 'unknown';
                $title = $item['title'] ?? 'Unknown Item';
                $price = $item['price'] ?? 0;
                $image = $item['image'] ?? '';
                $size = $item['size'] ?? 'free';
                $weight = $item['weight'] ?? '1 LBS';
                $qty = $item['qty'] ?? 1;
                
                $insertStmt->execute([$customerId, $pId, $title, $price, $image, $size, $weight, $qty]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cart synchronized.']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Cart sync error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while syncing your cart. Please try again.']);
}
