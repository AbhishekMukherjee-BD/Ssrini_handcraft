<?php
/**
 * SSRINI Handcrafts - Automated Dual Email Dispatcher
 * Dispatches Order Alert to Store Owner & Confirmation Receipt to Buyer
 */

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

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
}

// 1. Load .env Configuration
function loadEnv($path) {
    $env = [];
    if (!file_exists($path)) {
        return $env;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    return $env;
}

$envPath = __DIR__ . '/../.env';
$env = loadEnv($envPath);

$storeOwnerEmail = isset($env['STORE_OWNER_EMAIL']) ? $env['STORE_OWNER_EMAIL'] : 'amitguddu2505@gmail.com';
$storeName = isset($env['STORE_NAME']) ? $env['STORE_NAME'] : 'SSRINI Handcrafts Atelier';
$storeContactEmail = isset($env['STORE_CONTACT_EMAIL']) ? $env['STORE_CONTACT_EMAIL'] : 'contact@ssrini.com';
$storeWhatsapp = isset($env['STORE_WHATSAPP']) ? $env['STORE_WHATSAPP'] : '+919836081994';

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
$customerName = htmlspecialchars(isset($customer['name']) ? $customer['name'] : 'Valued Customer');
$customerPhone = htmlspecialchars(isset($customer['phone']) ? $customer['phone'] : 'N/A');
$customerEmail = htmlspecialchars(isset($customer['email']) ? $customer['email'] : '');
$customerAddress = htmlspecialchars(isset($customer['address']) ? $customer['address'] : '');
$customerCity = htmlspecialchars(isset($customer['city']) ? $customer['city'] : '');
$customerState = htmlspecialchars(isset($customer['state']) ? $customer['state'] : '');
$customerPincode = htmlspecialchars(isset($customer['pincode']) ? $customer['pincode'] : '');
$customerNotes = htmlspecialchars(isset($customer['notes']) ? $customer['notes'] : '');

$totalUsd = htmlspecialchars(isset($order['total_amount_usd']) ? $order['total_amount_usd'] : '0.00');
$totalInr = htmlspecialchars(isset($order['total_amount_inr']) ? number_format($order['total_amount_inr']) : '0');
$paymentMethod = (isset($order['payment_method']) && $order['payment_method'] === 'online') ? 'Online Payment (Prepaid)' : 'Cash on Delivery (COD)';
$paymentStatus = (isset($order['payment_status']) && $order['payment_status'] === 'paid') ? 'Paid' : 'Pending';

$items = isset($order['items']) ? $order['items'] : [];

// Build Itemized HTML Rows
$itemsHtml = '';
foreach ($items as $item) {
    $title = htmlspecialchars(isset($item['title']) ? $item['title'] : 'Artifact Piece');
    $qty = (int)(isset($item['qty']) ? $item['qty'] : 1);
    $price = (float)(isset($item['price']) ? $item['price'] : 0);
    $lineTotal = number_format($price * $qty, 2);
    $size = htmlspecialchars(isset($item['size']) ? $item['size'] : 'Free');

    $itemsHtml .= "
    <tr style='border-bottom: 1px solid #eeeeee;'>
      <td style='padding: 12px 8px; font-size: 14px; color: #111111;'>
        <strong>{$title}</strong><br>
        <span style='font-size: 12px; color: #777777;'>Size: {$size} | Qty: {$qty}</span>
      </td>
      <td style='padding: 12px 8px; font-size: 14px; color: #111111; text-align: right;'>
        \${$lineTotal}
      </td>
    </tr>";
}

// 3. Construct Luxury HTML Email Templates

// Email A: Owner Notification
$ownerSubject = "🚨 New Order Alert: [{$orderNumber}] - {$customerName}";
$ownerBody = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <title>New Order Alert</title>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f7f6f4; margin: 0; padding: 24px; color: #111111;'>
  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
    <tr>
      <td align='center'>
        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 8px; border: 1px solid #e5e5e5; overflow: hidden;'>
          <tr>
            <td style='background-color: #111111; padding: 24px; text-align: center;'>
              <h1 style='color: #ffffff; font-size: 20px; letter-spacing: 2px; margin: 0; text-transform: uppercase;'>{$storeName}</h1>
              <p style='color: #c5a059; font-size: 12px; margin: 6px 0 0 0; letter-spacing: 1px; text-transform: uppercase;'>Atelier Order Dispatch Alert</p>
            </td>
          </tr>
          <tr>
            <td style='padding: 32px;'>
              <div style='background-color: #fcfbfa; border: 1px solid #e8e6e1; border-radius: 6px; padding: 16px; margin-bottom: 24px;'>
                <span style='font-size: 12px; color: #777777; text-transform: uppercase; font-weight: bold;'>Order Reference:</span>
                <h2 style='margin: 4px 0 0 0; color: #111111; font-size: 22px;'>{$orderNumber}</h2>
              </div>

              <h3 style='font-size: 15px; text-transform: uppercase; border-bottom: 2px solid #111111; padding-bottom: 6px; margin-bottom: 12px;'>Customer & Delivery Details</h3>
              <p style='font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;'>
                <strong>Recipient Name:</strong> {$customerName}<br>
                <strong>Phone Number:</strong> <a href='tel:{$customerPhone}' style='color: #111111; font-weight: bold;'>{$customerPhone}</a><br>
                <strong>Email Address:</strong> {$customerEmail}<br>
                <strong>Delivery Address:</strong> {$customerAddress}, {$customerCity}, {$customerState} - {$customerPincode}<br>
                " . (!empty($customerNotes) ? "<strong>Special Notes:</strong> <span style='color: #e082a8;'>{$customerNotes}</span><br>" : "") . "
              </p>

              <h3 style='font-size: 15px; text-transform: uppercase; border-bottom: 2px solid #111111; padding-bottom: 6px; margin-bottom: 12px;'>Purchased Artifacts</h3>
              <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-bottom: 20px;'>
                {$itemsHtml}
              </table>

              <table width='100%' border='0' cellspacing='0' cellpadding='0' style='border-top: 2px solid #111111; padding-top: 12px;'>
                <tr>
                  <td style='font-size: 14px; color: #777777;'>Payment Method:</td>
                  <td style='font-size: 14px; font-weight: bold; text-align: right; color: #111111;'>{$paymentMethod}</td>
                </tr>
                <tr>
                  <td style='font-size: 14px; color: #777777;'>Payment Status:</td>
                  <td style='font-size: 14px; font-weight: bold; text-align: right; color: " . ($paymentStatus === 'Paid' ? '#2e7d32' : '#e65100') . ";'>{$paymentStatus}</td>
                </tr>
                <tr>
                  <td style='font-size: 16px; font-weight: bold; color: #111111; padding-top: 10px;'>Total Amount Due:</td>
                  <td style='font-size: 20px; font-weight: bold; text-align: right; color: #111111; padding-top: 10px;'>\${$totalUsd} <span style='font-size: 14px; color: #e082a8;'>(₹{$totalInr})</span></td>
                </tr>
              </table>

              <div style='margin-top: 28px; text-align: center;'>
                <a href='https://wa.me/" . preg_replace('/\D/', '', $customerPhone) . "' style='background-color: #25d366; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; display: inline-block;'>Message Customer on WhatsApp</a>
              </div>
            </td>
          </tr>
          <tr>
            <td style='background-color: #f4f4f4; padding: 16px; text-align: center; font-size: 12px; color: #888888;'>
              Automated Atelier Dispatch Alert · {$storeName}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>";

// Email B: Buyer Confirmation (if customer provided email)
$buyerSubject = "✨ Order Confirmation: [{$orderNumber}] - {$storeName}";
$buyerBody = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <title>Order Confirmation</title>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f7f6f4; margin: 0; padding: 24px; color: #111111;'>
  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
    <tr>
      <td align='center'>
        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 8px; border: 1px solid #e5e5e5; overflow: hidden;'>
          <tr>
            <td style='background-color: #111111; padding: 28px 24px; text-align: center;'>
              <h1 style='color: #ffffff; font-size: 22px; letter-spacing: 2px; margin: 0; text-transform: uppercase;'>{$storeName}</h1>
              <p style='color: #c5a059; font-size: 12px; margin: 8px 0 0 0; letter-spacing: 1px; text-transform: uppercase;'>Certified Bengal Guild Craft</p>
            </td>
          </tr>
          <tr>
            <td style='padding: 32px;'>
              <h2 style='font-size: 20px; color: #111111; margin: 0 0 8px 0;'>Thank You For Supporting Handcrafted Art, {$customerName}!</h2>
              <p style='font-size: 14px; color: #555555; line-height: 1.6; margin: 0 0 24px 0;'>
                Every piece you wear carries the heart and heritage of our Master Artisans. Your bespoke order has been registered and is being prepared for insured dispatch.
              </p>

              <div style='background-color: #fcfbfa; border: 1px solid #e8e6e1; border-radius: 6px; padding: 16px; margin-bottom: 24px;'>
                <span style='font-size: 12px; color: #777777; text-transform: uppercase; font-weight: bold;'>Order Reference:</span>
                <h3 style='margin: 4px 0 0 0; color: #111111; font-size: 20px;'>{$orderNumber}</h3>
              </div>

              <h3 style='font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #dddddd; padding-bottom: 6px; margin-bottom: 12px;'>Order Summary</h3>
              <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-bottom: 20px;'>
                {$itemsHtml}
              </table>

              <table width='100%' border='0' cellspacing='0' cellpadding='0' style='border-top: 1px solid #dddddd; padding-top: 12px; margin-bottom: 24px;'>
                <tr>
                  <td style='font-size: 14px; color: #777777;'>Insured Transit:</td>
                  <td style='font-size: 14px; font-weight: bold; text-align: right; color: #2e7d32;'>FREE</td>
                </tr>
                <tr>
                  <td style='font-size: 14px; color: #777777;'>Payment Method:</td>
                  <td style='font-size: 14px; font-weight: bold; text-align: right; color: #111111;'>{$paymentMethod}</td>
                </tr>
                <tr>
                  <td style='font-size: 16px; font-weight: bold; color: #111111; padding-top: 8px;'>Total:</td>
                  <td style='font-size: 18px; font-weight: bold; text-align: right; color: #111111; padding-top: 8px;'>\${$totalUsd} <span style='font-size: 13px; color: #e082a8;'>(₹{$totalInr})</span></td>
                </tr>
              </table>

              <h3 style='font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #dddddd; padding-bottom: 6px; margin-bottom: 12px;'>Shipping Destination</h3>
              <p style='font-size: 13px; color: #555555; line-height: 1.5; margin: 0 0 28px 0;'>
                {$customerName}<br>
                {$customerAddress}<br>
                {$customerCity}, {$customerState} - {$customerPincode}<br>
                Phone: {$customerPhone}
              </p>

              <div style='background-color: #faf9f7; border: 1px dashed #dcdad5; border-radius: 6px; padding: 18px; text-align: center;'>
                <p style='font-size: 13px; color: #333333; margin: 0 0 10px 0;'>Need help or custom ring resizing? Our Atelier Concierge is at your service.</p>
                <a href='https://wa.me/" . preg_replace('/\D/', '', $storeWhatsapp) . "?text=" . urlencode("Hi SSRINI Atelier! Regarding my order #{$orderNumber}") . "' style='background-color: #25d366; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block;'>Chat with Atelier on WhatsApp</a>
              </div>
            </td>
          </tr>
          <tr>
            <td style='background-color: #f4f4f4; padding: 16px; text-align: center; font-size: 11px; color: #888888; line-height: 1.5;'>
              {$storeName} · Direct Correspondence: {$storeContactEmail}<br>
              Every piece you wear carries the heart of our craft.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>";

// Headers
$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    "From: {$storeName} <{$storeContactEmail}>",
    "Reply-To: {$storeContactEmail}",
    'X-Mailer: PHP/' . phpversion()
];
$headersStr = implode("\r\n", $headers);

// 4. Send Emails via standard mail()
$ownerSent = @mail($storeOwnerEmail, $ownerSubject, $ownerBody, $headersStr);

$buyerSent = false;
if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    $buyerSent = @mail($customerEmail, $buyerSubject, $buyerBody, $headersStr);
}

echo json_encode([
    'success' => true,
    'message' => 'Order received and notifications processed.',
    'order_number' => $orderNumber,
    'owner_alert_sent_to' => $storeOwnerEmail,
    'buyer_confirmation_sent' => $buyerSent ? $customerEmail : 'N/A'
]);
