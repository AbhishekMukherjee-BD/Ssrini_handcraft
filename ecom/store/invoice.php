<?php
require_once __DIR__ . "/../includes/products_data.php";

$isAdmin = isset($_SESSION['admin_id']);
$isCustomer = isset($_SESSION['customer_id']);

if (!$isAdmin && !$isCustomer) {
    header('Location: /ecom/auth/index.php?redirect=' . urlencode('/ecom/store/account.php'));
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$orderId) {
    die("Invalid Order ID");
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found");
}

// Authorization check (cast both to int to prevent type-coercion bypass)
if ($isCustomer && !$isAdmin && (int)$order['customer_id'] !== (int)$_SESSION['customer_id']) {
    http_response_code(403);
    die("Unauthorized access to this order.");
}

// Fetch invoice details
$invStmt = $pdo->prepare("SELECT * FROM invoices WHERE order_id = ?");
$invStmt->execute([$orderId]);
$invoice = $invStmt->fetch(PDO::FETCH_ASSOC);

$invoiceNumber = $invoice ? $invoice['invoice_number'] : ('INV-PENDING-' . $orderId);
$invoiceDate = $invoice ? $invoice['created_at'] : $order['created_at'];

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= htmlspecialchars($invoiceNumber) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #111; margin: 0; padding: 20px; background: #f8f8f8; }
        .invoice-box { max-width: 800px; margin: auto; padding: 40px; border: 1px solid #eee; background: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 20px; margin-bottom: 30px; }
        .logo-sec h1 { font-family: 'Cinzel', serif; margin: 0; font-size: 28px; letter-spacing: 2px; }
        .logo-sec p { margin: 5px 0; font-size: 13px; color: #555; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; font-size: 32px; font-weight: 700; font-family: 'Cinzel', serif; color: #333; }
        .invoice-title p { margin: 5px 0 0 0; font-size: 14px; font-weight: 500; }
        .billing-sec { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .billing-sec div { width: 48%; }
        .billing-sec h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .billing-sec p { margin: 4px 0; font-size: 14px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f4f4f4; border-bottom: 2px solid #ddd; padding: 12px; text-align: left; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #333; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        td.right, th.right { text-align: right; }
        .totals-table { width: 50%; float: right; margin-bottom: 40px; }
        .totals-table th { background: none; border-bottom: none; border-top: 1px solid #eee; }
        .totals-table td { border-bottom: none; border-top: 1px solid #eee; font-weight: 600; }
        .totals-table .grand-total th, .totals-table .grand-total td { font-size: 18px; border-top: 2px solid #111; padding-top: 15px; }
        .clear { clear: both; }
        .footer { text-align: center; color: #666; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
        .print-btn-container { text-align: center; margin-bottom: 20px; }
        .print-btn { display: inline-block; padding: 10px 20px; background: #111; color: #fff; text-align: center; border-radius: 4px; text-decoration: none; font-weight: 500; cursor: pointer; }
        .print-btn:hover { background: #333; }
        @media print { body { background: #fff; padding: 0; } .invoice-box { box-shadow: none; border: none; padding: 0; } .print-btn-container { display: none; } }
    </style>
</head>
<body>

<div class="print-btn-container">
    <a class="print-btn" onclick="window.print()">Print Invoice</a>
</div>

<div class="invoice-box">
    <div class="invoice-header">
        <div class="logo-sec">
            <h1>SSRINI</h1>
            <p>Atelier & Co. • Heritage Edition</p>
            <p>123 Royal Street, Jaipur, RJ 302001</p>
            <p>Email: bespoke@ssrini.com | Phone: +91 98765 43210</p>
            <p>GSTIN: 08AABCU9603R1ZM</p>
        </div>
        <div class="invoice-title">
            <h2>INVOICE</h2>
            <p>#<?= htmlspecialchars($invoiceNumber) ?></p>
            <p>Date: <?= date('d M Y', strtotime($invoiceDate)) ?></p>
            <p>Order: #<?= htmlspecialchars($order['order_number']) ?></p>
        </div>
    </div>

    <div class="billing-sec">
        <div>
            <h3>Bill To</h3>
            <p><strong><?= htmlspecialchars($order['customer_name'] ?: 'Customer') ?></strong></p>
            <p><?= htmlspecialchars($order['customer_email']) ?></p>
            <p><?= htmlspecialchars($order['customer_phone']) ?></p>
        </div>
        <div>
            <h3>Ship To</h3>
            <p><strong><?= htmlspecialchars($order['customer_name'] ?: 'Customer') ?></strong></p>
            <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            <p><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> - <?= htmlspecialchars($order['shipping_pincode']) ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Description</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="right"><?= htmlspecialchars($item['quantity']) ?></td>
                <td class="right">₹<?= number_format($item['price'], 2) ?></td>
                <td class="right">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <th>Subtotal</th>
            <td class="right">₹<?= number_format($order['subtotal'], 2) ?></td>
        </tr>
        <?php if ($order['discount_amount'] > 0): ?>
        <tr>
            <th>Discount</th>
            <td class="right">- ₹<?= number_format($order['discount_amount'], 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Shipping</th>
            <td class="right">₹<?= number_format($order['shipping_cost'], 2) ?></td>
        </tr>
        <tr class="grand-total">
            <th>Total Amount</th>
            <td class="right">₹<?= number_format($order['total_amount'], 2) ?></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td class="right"><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></td>
        </tr>
        <tr>
            <th>Payment Status</th>
            <td class="right"><?= strtoupper(htmlspecialchars($order['payment_status'])) ?></td>
        </tr>
    </table>
    <div class="clear"></div>

    <div class="footer">
        <p>This is a computer generated invoice and does not require a physical signature.</p>
        <p>Thank you for choosing Ssrini Handcrafts.</p>
    </div>
</div>

</body>
</html>
