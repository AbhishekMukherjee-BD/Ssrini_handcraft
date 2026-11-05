<?php
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
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// 1. Connect to Database
$dbPath = __DIR__ . '/../../admin/ssrini-handicrafts-admin/config/database.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database configuration missing.']);
    exit;
}
require_once $dbPath;

// 2. Parse Incoming Payload
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (
    !$payload ||
    empty($payload['razorpay_payment_id']) ||
    empty($payload['razorpay_order_id']) ||
    empty($payload['razorpay_signature']) ||
    empty($payload['internal_order_id'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payment payload. All fields are required.']);
    exit;
}

$razorpayPaymentId = trim($payload['razorpay_payment_id']);
$razorpayOrderId = trim($payload['razorpay_order_id']);
$razorpaySignature = trim($payload['razorpay_signature']);
$internalOrderId = (int)$payload['internal_order_id'];

// 3. Load Keys
require_once __DIR__ . '/../includes/env.php';
$env = get_app_env();
$rzpKeyId = $env['RAZORPAY_KEY_ID'] ?? '';
$rzpKeySecret = $env['RAZORPAY_KEY_SECRET'] ?? '';

if (empty($rzpKeyId) || empty($rzpKeySecret)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Payment Gateway keys missing.']);
    exit;
}

// 3.5 Security: Verify that internal order ID matches razorpay_order_id in payments table
$stmtCheck = $pdo->prepare("SELECT id, amount, status FROM payments WHERE order_id = ? AND razorpay_order_id = ? LIMIT 1");
$stmtCheck->execute([$internalOrderId, $razorpayOrderId]);
$paymentRow = $stmtCheck->fetch();

if (!$paymentRow) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order and payment identifier mismatch.']);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

try {
    $api = new Api($rzpKeyId, $rzpKeySecret);

    $attributes = array(
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_payment_id' => $razorpayPaymentId,
        'razorpay_signature' => $razorpaySignature
    );

    // 4. Mathematical Cryptographic Verification (HMAC-SHA256)
    $api->utility->verifyPaymentSignature($attributes);

    // 5. Verification Passed! Update Database
    $pdo->beginTransaction();

    // Attempt an idempotent update (locks the row and only updates if not already captured)
    $updatePayment = $pdo->prepare("
        UPDATE payments 
        SET razorpay_payment_id = ?, status = 'captured', updated_at = NOW() 
        WHERE order_id = ? AND razorpay_order_id = ? AND status NOT IN ('captured', 'paid')
    ");
    $updatePayment->execute([$razorpayPaymentId, $internalOrderId, $razorpayOrderId]);
    
    // If rowCount > 0, we were the first to process this payment
    $wasCapturedJustNow = ($updatePayment->rowCount() > 0);

    // Update orders table
    $updateOrder = $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'processing', updated_at = NOW() WHERE id = ?");
    $updateOrder->execute([$internalOrderId]);

    // 6. Atomically Decrement Stock if not already captured
    if ($wasCapturedJustNow) {
        $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$internalOrderId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
        foreach ($items as $item) {
            $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
        }
    }

    $pdo->commit();

    // 7. Dispatch Order Confirmation Email to Buyer
    if ($wasCapturedJustNow) {
        $stmtOrderDetails = $pdo->prepare("SELECT o.order_number, o.customer_email, o.customer_name, o.total_amount, i.invoice_number FROM orders o LEFT JOIN invoices i ON o.id = i.order_id WHERE o.id = ? LIMIT 1");
        $stmtOrderDetails->execute([$internalOrderId]);
        $orderDetails = $stmtOrderDetails->fetch(PDO::FETCH_ASSOC);

        if ($orderDetails && !empty($orderDetails['customer_email'])) {
            require_once __DIR__ . '/../includes/mailer.php';
            $subject = "Order Confirmation - {$orderDetails['invoice_number']}";
            $body = "
                <h2>Thank you for your order, {$orderDetails['customer_name']}!</h2>
                <p>Your order (<strong>{$orderDetails['order_number']}</strong>) has been confirmed and payment was received successfully.</p>
                <p><strong>Total Amount:</strong> ₹" . number_format($orderDetails['total_amount'], 2) . "</p>
                <p><strong>Payment Method:</strong> Online Payment (Razorpay)</p>
                <p>We will notify you once your order is dispatched.</p>
            ";
            sendEmailNotification($orderDetails['customer_email'], $subject, $body);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment successfully verified and captured.'
    ]);

} catch (SignatureVerificationError $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Payment signature verification failed. Possible tampering detected.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error during payment capture: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred during payment processing. Please contact support if your account was charged.'
    ]);
}
