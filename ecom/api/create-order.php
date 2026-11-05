<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../includes/session_config.php';
}
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/cors.php';
set_cors_headers(['POST', 'OPTIONS']);

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Only POST is accepted.']);
    exit;
}

// SEC-10: Session-based rate limiter for order creation (max 10 per 60 seconds)
$now = time();
$orderWindow = 60;
if (!isset($_SESSION['order_rate_log'])) {
    $_SESSION['order_rate_log'] = [];
}
$_SESSION['order_rate_log'] = array_filter($_SESSION['order_rate_log'], function($t) use ($now, $orderWindow) {
    return ($now - $t) < $orderWindow;
});
if (count($_SESSION['order_rate_log']) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many order requests. Please wait a moment and try again.']);
    exit;
}
$_SESSION['order_rate_log'][] = $now;

// 1. Connect to Database
$dbPath = __DIR__ . '/../../admin/ssrini-handicrafts-admin/config/database.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database configuration missing.']);
    exit;
}
require_once $dbPath;

// 2. Parse Incoming Order JSON
$rawInput = file_get_contents('php://input');
$order = json_decode($rawInput, true);

if (!$order || !isset($order['order_number'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order payload.']);
    exit;
}

$orderNumber = htmlspecialchars($order['order_number']);
$customer = isset($order['customer']) ? $order['customer'] : [];
$customerName = htmlspecialchars(isset($customer['name']) ? $customer['name'] : '');
$customerPhone = htmlspecialchars(isset($customer['phone']) ? $customer['phone'] : '');
$customerEmail = htmlspecialchars(isset($customer['email']) ? $customer['email'] : '');
$customerAddress = htmlspecialchars(isset($customer['address']) ? $customer['address'] : '');
$customerCity = htmlspecialchars(isset($customer['city']) ? $customer['city'] : '');
$customerState = htmlspecialchars(isset($customer['state']) ? $customer['state'] : '');
$customerPincode = htmlspecialchars(isset($customer['pincode']) ? $customer['pincode'] : '');
$customerNotes = htmlspecialchars(isset($customer['notes']) ? $customer['notes'] : '');

$paymentMethod = isset($order['payment_method']) ? $order['payment_method'] : 'cod';
$paymentStatus = isset($order['payment_status']) ? $order['payment_status'] : 'pending';
$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';

$items = isset($order['items']) ? $order['items'] : [];

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order must contain at least one item.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 3. Handle Customer
    $customerId = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
    if ($customerId) {
        $updateStmt = $pdo->prepare("UPDATE customers SET name = COALESCE(NULLIF(?, ''), name), address = COALESCE(NULLIF(?, ''), address), city = COALESCE(NULLIF(?, ''), city), state = COALESCE(NULLIF(?, ''), state), pincode = COALESCE(NULLIF(?, ''), pincode), phone = COALESCE(NULLIF(?, ''), phone), updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$customerName, $customerAddress, $customerCity, $customerState, $customerPincode, $customerPhone, $customerId]);
    } else if (!empty($customerPhone) || !empty($customerEmail)) {
        // Try to find existing customer by email or phone
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$customerEmail, $customerPhone]);
        $existingCustomer = $stmt->fetch();

        if ($existingCustomer) {
            $customerId = $existingCustomer['id'];
            $updateStmt = $pdo->prepare("UPDATE customers SET name=?, address=?, city=?, state=?, pincode=?, updated_at=NOW() WHERE id=?");
            $updateStmt->execute([$customerName, $customerAddress, $customerCity, $customerState, $customerPincode, $customerId]);
        } else {
            // Insert new customer
            $insertStmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, city, state, pincode, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())");
            $insertStmt->execute([$customerName, $customerEmail, $customerPhone, $customerAddress, $customerCity, $customerState, $customerPincode]);
            $customerId = $pdo->lastInsertId();
        }
    }

    // 3.5 Calculate Actual Total from DB
    $calculatedTotalInr = 0;
    $processedItems = [];
    foreach ($items as $item) {
        $slug = isset($item['id']) ? $item['id'] : '';
        $qty = (int)(isset($item['qty']) ? $item['qty'] : 1);
        
        if ($qty <= 0) {
            throw new Exception("Invalid quantity for product: " . htmlspecialchars($slug));
        }

        $productStmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE slug = ? AND status = 'active' LIMIT 1 FOR UPDATE");
        $productStmt->execute([$slug]);
        $productRow = $productStmt->fetch();
        
        if ($productRow) {
            $productId = (int)$productRow['id'];
            $title = $productRow['name'];
            $price = (float)$productRow['price'];
            $availableStock = (int)$productRow['stock_quantity'];

            if ($availableStock < $qty) {
                throw new Exception("Insufficient stock for {$title}. Only {$availableStock} available.");
            }
        } else {
            // SECURITY: Reject orders that try to purchase non-existent or inactive items
            throw new Exception("Product not found or unavailable: " . htmlspecialchars($slug));
        }
        
        $calculatedTotalInr += ($price * $qty);
        $processedItems[] = [
            'product_id' => $productId,
            'title' => $title,
            'qty' => $qty,
            'price' => $price
        ];
    }
    
    // SECURITY: Override any client-provided total with the server-calculated total
    $totalInr = $calculatedTotalInr;

    // 4. Insert Order
    $orderStmt = $pdo->prepare("INSERT INTO orders (order_number, customer_id, customer_name, customer_email, customer_phone, subtotal, shipping_cost, discount_amount, total_amount, payment_method, payment_status, order_status, shipping_address, shipping_city, shipping_state, shipping_pincode, order_notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $orderStmt->execute([
        $orderNumber,
        $customerId ?: 0, // Fallback if no customer created
        $customerName,
        $customerEmail,
        $customerPhone,
        $totalInr,
        0.00, // Shipping cost placeholder
        0.00, // Discount placeholder
        $totalInr,
        $paymentMethod,
        $paymentStatus,
        $orderStatus,
        $customerAddress,
        $customerCity,
        $customerState,
        $customerPincode,
        $customerNotes
    ]);
    $orderId = $pdo->lastInsertId();

    // 5. Insert Order Items & Deduct stock for COD
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
    
    foreach ($processedItems as $pItem) {
        $itemStmt->execute([$orderId, $pItem['product_id'], $pItem['title'], $pItem['qty'], $pItem['price']]);
        if ($paymentMethod === 'cod') {
            $stockStmt->execute([$pItem['qty'], $pItem['product_id']]);
        }
    }

    // 6. Generate Invoice
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    $invoiceStmt = $pdo->prepare("INSERT INTO invoices (invoice_number, order_id, customer_id, total_amount, payment_method, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $invoiceStmt->execute([
        $invoiceNumber,
        $orderId,
        $customerId ?: 0,
        $totalInr,
        $paymentMethod
    ]);

    $razorpayOrderId = null;
    $rzpKeyId = null;

    if ($paymentMethod === 'online') {
        // Load env for Razorpay keys
        require_once __DIR__ . '/../includes/env.php';
        $env = get_app_env();
        $rzpKeyId = $env['RAZORPAY_KEY_ID'] ?? '';
        $rzpKeySecret = $env['RAZORPAY_KEY_SECRET'] ?? '';

        if (empty($rzpKeyId) || empty($rzpKeySecret)) {
            $pdo->rollBack();
            http_response_code(503);
            echo json_encode(['success' => false, 'error' => 'Payment Gateway Error: Online payment credentials are not configured.']);
            exit;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';
        
        try {
            $api = new \Razorpay\Api\Api($rzpKeyId, $rzpKeySecret);
            
            // Create Razorpay Order (amount is in paise)
            $orderData = [
                'receipt'         => (string)$orderId,
                'amount'          => intval(round($totalInr * 100)),
                'currency'        => 'INR',
                'payment_capture' => 1
            ];
            $razorpayOrder = $api->order->create($orderData);
            $razorpayOrderId = $razorpayOrder['id'] ?? null;

            if (empty($razorpayOrderId)) {
                throw new \Exception("Razorpay did not return a valid order ID.");
            }

            // Record the payment intent in our payments table
            $paymentStmt = $pdo->prepare("INSERT INTO payments (order_id, razorpay_order_id, amount, status, created_at, updated_at) VALUES (?, ?, ?, 'created', NOW(), NOW())");
            $paymentStmt->execute([$orderId, $razorpayOrderId, $totalInr]);

        } catch (\Exception $e) {
            // Rollback the order if Razorpay creation fails
            $pdo->rollBack();
            error_log("Razorpay Order Creation Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Payment Gateway Error: Unable to initiate payment transaction.']);
            exit;
        }
    }

    $pdo->commit();

    // 7. Send Order Confirmation Email for COD
    if ($paymentMethod === 'cod') {
        require_once __DIR__ . '/../includes/mailer.php';
        $subject = "Order Confirmation - {$invoiceNumber}";
        $body = "
            <h2>Thank you for your order, {$customerName}!</h2>
            <p>Your order (<strong>{$orderNumber}</strong>) has been successfully placed.</p>
            <p><strong>Total Amount:</strong> ₹" . number_format($totalInr, 2) . "</p>
            <p><strong>Payment Method:</strong> Cash on Delivery</p>
            <p>We will notify you once your order is shipped.</p>
        ";
        sendEmailNotification($customerEmail, $subject, $body);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order successfully saved.',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'invoice_number' => $invoiceNumber,
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_key_id' => $rzpKeyId
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order creation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to process your order. Please try again or contact support.'
    ]);
}
