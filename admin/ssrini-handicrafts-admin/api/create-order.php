<?php

/**
 * Ssrini Handcrafts - Create Order API
 * Handles storefront checkout submissions.
 */

require_once __DIR__ . '/../config/database.php';

// Allow CORS for the storefront
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

// Get JSON payload
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Handle Customer
    // Check if customer exists by email
    $customerEmail = trim($data['email'] ?? '');
    $customerName = trim($data['name'] ?? '');
    $customerPhone = trim($data['phone'] ?? '');
    
    if (empty($customerEmail) || empty($customerName)) {
        throw new Exception("Name and email are required.");
    }

    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$customerEmail]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $customerId = $customer['id'];
        // Update customer details if they changed
        $updateStmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
        $updateStmt->execute([
            $customerName, $customerPhone, 
            $data['shipping']['address'] ?? '',
            $data['shipping']['city'] ?? '',
            $data['shipping']['state'] ?? '',
            $data['shipping']['pincode'] ?? '',
            $customerId
        ]);
    } else {
        // Create new customer
        $insertCust = $pdo->prepare("INSERT INTO customers (name, email, phone, address, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertCust->execute([
            $customerName, $customerEmail, $customerPhone,
            $data['shipping']['address'] ?? '',
            $data['shipping']['city'] ?? '',
            $data['shipping']['state'] ?? '',
            $data['shipping']['pincode'] ?? ''
        ]);
        $customerId = $pdo->lastInsertId();
    }

    // 2. Create Order
    $orderNumber = 'SSR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
    $subtotal = 0;
    
    // Calculate total from DB to prevent client-side tampering
    $items = $data['items'] ?? [];
    if (empty($items)) {
        throw new Exception("Order must contain items.");
    }

    $orderItemsData = [];
    foreach ($items as $item) {
        $prodStmt = $pdo->prepare("SELECT id, name, price, discount_price, stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $prodStmt->execute([$item['id']]);
        $product = $prodStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product ID {$item['id']} not found or inactive.");
        }
        
        $qty = (int) ($item['quantity'] ?? 1);
        if ($product['stock_quantity'] < $qty) {
            throw new Exception("Insufficient stock for product: {$product['name']}. Available: {$product['stock_quantity']}");
        }

        $price = $product['discount_price'] > 0 ? $product['discount_price'] : $product['price'];
        $subtotal += ($price * $qty);
        
        $orderItemsData[] = [
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'quantity' => $qty,
            'price' => $price
        ];
    }

    $shippingCost = (float) ($data['shipping_cost'] ?? 0);
    $discountAmount = (float) ($data['discount_amount'] ?? 0);
    $totalAmount = $subtotal + $shippingCost - $discountAmount;

    $insertOrder = $pdo->prepare("
        INSERT INTO orders (
            order_number, customer_id, customer_name, customer_email, customer_phone,
            subtotal, shipping_cost, discount_amount, total_amount,
            payment_method, payment_status, order_status,
            shipping_address, shipping_city, shipping_state, shipping_pincode, order_notes
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $insertOrder->execute([
        $orderNumber, $customerId, $customerName, $customerEmail, $customerPhone,
        $subtotal, $shippingCost, $discountAmount, $totalAmount,
        $data['payment_method'] ?? 'cod',
        ($data['payment_method'] === 'online' ? 'paid' : 'pending'), // Simplified for now
        'pending',
        $data['shipping']['address'] ?? '',
        $data['shipping']['city'] ?? '',
        $data['shipping']['state'] ?? '',
        $data['shipping']['pincode'] ?? '',
        $data['order_notes'] ?? ''
    ]);
    
    $orderId = $pdo->lastInsertId();

    // 3. Insert Order Items & Deduct Stock
    $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

    foreach ($orderItemsData as $item) {
        $insertItem->execute([
            $orderId, $item['product_id'], $item['product_name'], $item['quantity'], $item['price']
        ]);
        $updateStock->execute([$item['quantity'], $item['product_id']]);
    }

    // 4. Create Invoice
    $invoiceNumber = 'INV-' . $orderNumber;
    $insertInvoice = $pdo->prepare("INSERT INTO invoices (invoice_number, order_id, customer_id, total_amount, payment_method) VALUES (?, ?, ?, ?, ?)");
    $insertInvoice->execute([
        $invoiceNumber, $orderId, $customerId, $totalAmount, $data['payment_method'] ?? 'cod'
    ]);

    // 5. Create Notification for Admin
    $insertNotif = $pdo->prepare("INSERT INTO notifications (type, title, message, reference_id) VALUES (?, ?, ?, ?)");
    $insertNotif->execute([
        'new_order',
        "New Order Received: $orderNumber",
        "$customerName placed an order for ₹" . number_format($totalAmount, 2),
        $orderId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully.',
        'order_id' => $orderId,
        'order_number' => $orderNumber
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
