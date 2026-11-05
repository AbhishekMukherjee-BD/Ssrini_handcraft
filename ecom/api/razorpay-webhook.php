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
    exit;
}
require_once $dbPath;

// 2. Load Keys
require_once __DIR__ . '/../includes/env.php';
$env = get_app_env();
$webhookSecret = $env['RAZORPAY_WEBHOOK_SECRET'] ?? '';

if (empty($webhookSecret)) {
    error_log("Razorpay webhook error: RAZORPAY_WEBHOOK_SECRET not configured in .env");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Webhook secret not configured.']);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$postBody = file_get_contents('php://input');
$razorpaySignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($razorpaySignature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing X-Razorpay-Signature header.']);
    exit;
}

$rzpKeyId = $env['RAZORPAY_KEY_ID'] ?? '';
$rzpKeySecret = $env['RAZORPAY_KEY_SECRET'] ?? '';
$api = new Api($rzpKeyId, $rzpKeySecret);

try {
    // 3. Verify Webhook Signature (HMAC-SHA256 over raw postBody)
    $api->utility->verifyWebhookSignature($postBody, $razorpaySignature, $webhookSecret);
} catch (SignatureVerificationError $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid webhook signature.']);
    exit;
}

// 4. Process Webhook Payload
$payload = json_decode($postBody, true);
$event = $payload['event'] ?? '';

try {
    if ($event === 'payment.captured' || $event === 'order.paid') {
        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
        $razorpayPaymentId = $paymentEntity['id'] ?? '';
        $razorpayOrderId = $paymentEntity['order_id'] ?? '';

        if ($razorpayPaymentId && $razorpayOrderId) {
            $pdo->beginTransaction();

            // Check if this exact payment event has already been recorded (Idempotency Guard)
            $checkEvent = $pdo->prepare("SELECT id FROM payment_events WHERE razorpay_payment_id = ? AND event_type = ? LIMIT 1");
            $checkEvent->execute([$razorpayPaymentId, $event]);
            $alreadyProcessed = $checkEvent->fetch();

            if (!$alreadyProcessed) {
                // Log the event safely
                $eventStmt = $pdo->prepare("
                    INSERT INTO payment_events (payment_id, razorpay_payment_id, event_type, payload, created_at)
                    VALUES ((SELECT id FROM payments WHERE razorpay_order_id = ? LIMIT 1), ?, ?, ?, NOW())
                ");
                $eventStmt->execute([$razorpayOrderId, $razorpayPaymentId, $event, $postBody]);
            }

            // Update Payments (idempotent - only updates if not already captured/paid)
            $updatePayment = $pdo->prepare("
                UPDATE payments 
                SET status = 'captured', razorpay_payment_id = ?, updated_at = NOW() 
                WHERE razorpay_order_id = ? AND status NOT IN ('captured', 'paid')
            ");
            $updatePayment->execute([$razorpayPaymentId, $razorpayOrderId]);

            if ($updatePayment->rowCount() > 0) {
                // Update Orders
                $updateOrder = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid', order_status = 'processing', updated_at = NOW() 
                    WHERE id = (SELECT order_id FROM payments WHERE razorpay_order_id = ? LIMIT 1)
                ");
                $updateOrder->execute([$razorpayOrderId]);

                // Decrement Stock Inventory Atomically
                $stmtGetOrder = $pdo->prepare("SELECT order_id FROM payments WHERE razorpay_order_id = ? LIMIT 1");
                $stmtGetOrder->execute([$razorpayOrderId]);
                $internalOrderId = $stmtGetOrder->fetchColumn();

                if ($internalOrderId) {
                    $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmtItems->execute([$internalOrderId]);
                    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
                    foreach ($items as $item) {
                        $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
                    }

                    // Send Email Notification
                    $stmtOrderDetails = $pdo->prepare("SELECT o.order_number, o.customer_email, o.customer_name, o.total_amount, i.invoice_number FROM orders o LEFT JOIN invoices i ON o.id = i.order_id WHERE o.id = ? LIMIT 1");
                    $stmtOrderDetails->execute([$internalOrderId]);
                    $orderDetails = $stmtOrderDetails->fetch(PDO::FETCH_ASSOC);

                    if ($orderDetails && !empty($orderDetails['customer_email'])) {
                        require_once __DIR__ . '/../includes/mailer.php';
                        $subject = "Order Confirmation - {$orderDetails['invoice_number']}";
                        $body = "
                            <h2>Thank you for your order, {$orderDetails['customer_name']}!</h2>
                            <p>Your order (<strong>{$orderDetails['order_number']}</strong>) has been successfully placed and payment is confirmed.</p>
                            <p><strong>Total Amount:</strong> ₹" . number_format($orderDetails['total_amount'], 2) . "</p>
                            <p><strong>Payment Method:</strong> Online (Razorpay)</p>
                            <p>We will notify you once your order is shipped.</p>
                        ";
                        sendEmailNotification($orderDetails['customer_email'], $subject, $body);
                    }
                }
            }

            $pdo->commit();
        }
    }
    
    // Acknowledge receipt to Razorpay
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Razorpay webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal webhook processing error.']);
}
