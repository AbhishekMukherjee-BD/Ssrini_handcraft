<?php
require_once __DIR__ . "/../includes/products_data.php";

if (!isset($_SESSION['customer_id'])) {
    header('Location: /ecom/auth/index.php?redirect=' . urlencode('/ecom/store/account.php'));
    exit;
}

$customerId = (int)$_SESSION['customer_id'];
$orders = [];
$customer = null;
$wishlistProducts = [];
$orderItemsByOrderId = [];

try {
    // Fetch Customer details
    $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $cStmt->execute([$customerId]);
    $customer = $cStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Vault customer fetch error: " . $e->getMessage());
}

if (!$customer) {
    $customer = [
        'id' => $customerId,
        'name' => $_SESSION['customer_name'] ?? 'Valued Collector',
        'email' => $_SESSION['customer_email'] ?? '',
        'phone' => '',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

try {
    // Fetch Orders
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$customerId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Order Items for all orders
    if (!empty($orders)) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemsStmt = $pdo->prepare("
            SELECT oi.*, p.slug, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.id ASC
        ");
        $itemsStmt->execute($orderIds);
        $rawItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rawItems as $item) {
            $orderItemsByOrderId[$item['order_id']][] = $item;
        }
    }
} catch (Exception $e) {
    error_log("Vault orders fetch error: " . $e->getMessage());
    $orders = [];
}

try {
    // Fetch Wishlist
    $wStmt = $pdo->prepare("
        SELECT p.*, w.added_at 
        FROM wishlists w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.customer_id = ? 
        ORDER BY w.added_at DESC
    ");
    $wStmt->execute([$customerId]);
    $wishlistProducts = $wStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Vault wishlist fetch error: " . $e->getMessage());
    $wishlistProducts = [];
}

$totalSpent = 0;
foreach ($orders as $ord) {
    if ($ord['order_status'] !== 'cancelled') {
        $totalSpent += (float)$ord['total_amount'];
    }
}
$memberSince = !empty($customer['created_at']) ? date('M Y', strtotime($customer['created_at'])) : date('M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Member Vault | SSRINI Handcrafts</title>
  <meta name="description" content="View your bespoke commissions, private order history, and saved heirlooms in the SSRINI Member Vault.">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Syne:wght@500;600;700&display=swap" rel="stylesheet">

  <!-- Favicon Suite -->
  <link rel="shortcut icon" href="../favicon.ico?v=2">
  <link rel="icon" type="image/x-icon" href="../favicon.ico?v=2">
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png?v=2">
  <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png?v=2">
  <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png?v=2">

  <link rel="stylesheet" href="css/store.css">
  <link rel="stylesheet" href="../css/mobile-responsive.css?v=20260914">

  <style>
    :root {
      --vault-gold: #C5A059;
      --vault-gold-light: #DFCA9B;
      --vault-gold-glow: rgba(197, 160, 89, 0.15);
      --vault-dark: #121212;
      --vault-charcoal: #1c1c1c;
      --vault-border: #E8E4DC;
      --vault-bg-subtle: #FAF8F5;
      --vault-font-serif: 'Cinzel', serif;
      --vault-font-sans: 'Inter', sans-serif;
      --vault-font-display: 'Syne', sans-serif;
    }

    body {
      background-color: #FAFAFA;
      color: #1a1a1a;
      font-family: var(--vault-font-sans);
      margin: 0;
      padding: 0;
      -webkit-font-smoothing: antialiased;
    }

    /* Fixed Luxury Navbar */
    .vault-navbar {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 76px;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--vault-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 40px;
      box-sizing: border-box;
      z-index: 1000;
    }
    .vault-brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }
    .vault-brand-logo {
      height: 38px;
      width: auto;
      object-fit: contain;
    }
    .vault-brand-text {
      font-family: var(--vault-font-serif);
      font-size: 17px;
      font-weight: 700;
      letter-spacing: 0.15em;
      color: var(--vault-dark);
      text-transform: uppercase;
    }
    .vault-brand-text span {
      display: block;
      font-size: 9px;
      letter-spacing: 0.2em;
      color: var(--vault-gold);
      font-family: var(--vault-font-sans);
      font-weight: 500;
    }

    .vault-nav-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .vault-return-store-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 18px;
      border: 1px solid var(--vault-border);
      border-radius: 999px;
      background: #FFFFFF;
      color: #1a1a1a;
      text-decoration: none;
      font-size: 13px;
      font-weight: 500;
      transition: all 0.25s ease;
    }
    .vault-return-store-btn:hover {
      border-color: var(--vault-gold);
      background: #FAF8F5;
      color: var(--vault-gold);
      box-shadow: 0 4px 12px var(--vault-gold-glow);
    }

    /* Main Container */
    .vault-wrapper {
      max-width: 1140px;
      margin: 110px auto 80px auto;
      padding: 0 24px;
      box-sizing: border-box;
    }

    /* Hero Banner Profile Header */
    .vault-hero-card {
      background: linear-gradient(135deg, #181818 0%, #111111 100%);
      border-radius: 20px;
      padding: 36px 40px;
      color: #FFFFFF;
      position: relative;
      overflow: hidden;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
      border: 1px solid rgba(197, 160, 89, 0.25);
      margin-bottom: 32px;
    }
    .vault-hero-card::after {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(197, 160, 89, 0.18) 0%, transparent 70%);
      pointer-events: none;
    }
    .vault-hero-content {
      position: relative;
      z-index: 2;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 24px;
    }
    .vault-hero-title {
      font-family: var(--vault-font-serif);
      font-size: 32px;
      font-weight: 600;
      letter-spacing: 0.04em;
      margin: 0 0 6px 0;
      color: #FAF8F5;
    }
    .vault-hero-title span {
      color: var(--vault-gold);
      font-style: italic;
    }
    .vault-hero-sub {
      font-size: 14px;
      color: #A3A3A3;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .vault-hero-sub .patron-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      background: rgba(197, 160, 89, 0.2);
      border: 1px solid rgba(197, 160, 89, 0.4);
      border-radius: 999px;
      color: var(--vault-gold-light);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .vault-hero-stats {
      display: flex;
      gap: 28px;
    }
    .vault-stat-box {
      text-align: right;
    }
    .vault-stat-number {
      font-family: var(--vault-font-display);
      font-size: 24px;
      font-weight: 700;
      color: var(--vault-gold);
      line-height: 1.2;
    }
    .vault-stat-label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: #888;
      margin-top: 2px;
    }

    /* Tabs Navigation */
    .vault-tabs {
      display: flex;
      gap: 12px;
      border-bottom: 1px solid var(--vault-border);
      margin-bottom: 28px;
    }
    .vault-tab-btn {
      background: none;
      border: none;
      padding: 12px 20px;
      font-family: var(--vault-font-sans);
      font-size: 14px;
      font-weight: 600;
      color: #666;
      cursor: pointer;
      position: relative;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .vault-tab-btn:hover {
      color: var(--vault-dark);
    }
    .vault-tab-btn.active {
      color: var(--vault-dark);
    }
    .vault-tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      height: 2px;
      background: var(--vault-gold);
    }
    .vault-tab-badge {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 999px;
      font-size: 11px;
      background: #EEEEEE;
      color: #555;
    }
    .vault-tab-btn.active .vault-tab-badge {
      background: var(--vault-gold-glow);
      color: var(--vault-gold);
      font-weight: 700;
    }

    /* Tab Panes */
    .vault-tab-pane {
      display: none;
    }
    .vault-tab-pane.active {
      display: block;
      animation: vaultFadeIn 0.3s ease;
    }
    @keyframes vaultFadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Orders Section */
    .orders-stack {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }
    .order-card-luxury {
      background: #FFFFFF;
      border: 1px solid var(--vault-border);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
      overflow: hidden;
      transition: all 0.25s ease;
    }
    .order-card-luxury:hover {
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
      border-color: #D8D2C6;
    }
    .order-card-header {
      padding: 20px 28px;
      background: #FCFBFA;
      border-bottom: 1px solid #EFECE6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .order-header-left h3 {
      margin: 0 0 4px 0;
      font-family: var(--vault-font-serif);
      font-size: 17px;
      font-weight: 600;
      color: var(--vault-dark);
      letter-spacing: 0.04em;
    }
    .order-header-left p {
      margin: 0;
      font-size: 13px;
      color: #777;
    }
    .order-header-right {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    /* Status Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }
    .status-pending { background: #FFF9E6; color: #9A6700; border: 1px solid #FFE28A; }
    .status-processing { background: #EDF7FF; color: #0066B2; border: 1px solid #BCE0FD; }
    .status-shipped { background: #EBF9EE; color: #1E7E34; border: 1px solid #B8E8C2; }
    .status-delivered { background: #E8F5E9; color: #155724; border: 1px solid #C3E6CB; }
    .status-cancelled { background: #FDE8E8; color: #C5221F; border: 1px solid #F8B4B4; }

    /* Order Items List */
    .order-items-list {
      padding: 24px 28px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .order-item-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 16px;
      border-bottom: 1px solid #F5F3EF;
      gap: 16px;
    }
    .order-item-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }
    .order-item-thumb-wrapper {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .order-item-thumb {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      object-fit: cover;
      background: #F0EDE6;
      border: 1px solid #EAE6DE;
    }
    .order-item-title {
      font-size: 14px;
      font-weight: 600;
      color: #1a1a1a;
      margin: 0 0 4px 0;
    }
    .order-item-subtitle {
      font-size: 12px;
      color: #777;
      margin: 0;
    }
    .order-item-price-col {
      text-align: right;
      font-size: 14px;
      font-weight: 600;
      color: #1a1a1a;
    }

    /* Order Card Footer */
    .order-card-footer {
      padding: 18px 28px;
      background: #FAFAFA;
      border-top: 1px solid #EFECE6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .order-delivery-address {
      font-size: 12px;
      color: #666;
      max-width: 500px;
    }
    .order-delivery-address strong {
      color: #333;
    }
    .order-total-block {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .order-grand-total {
      font-size: 16px;
      font-weight: 700;
      color: var(--vault-dark);
      font-family: var(--vault-font-display);
    }
    .btn-invoice {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      background: #FFFFFF;
      border: 1px solid #DDD;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
      color: #222;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-invoice:hover {
      border-color: var(--vault-gold);
      color: var(--vault-gold);
      background: #FAF8F5;
    }

    /* Empty States */
    .vault-empty-state {
      text-align: center;
      padding: 70px 20px;
      background: #FFFFFF;
      border-radius: 16px;
      border: 1px dashed var(--vault-border);
    }
    .vault-empty-icon {
      width: 48px;
      height: 48px;
      stroke: var(--vault-gold);
      margin-bottom: 16px;
      opacity: 0.85;
    }
    .vault-empty-state h3 {
      font-family: var(--vault-font-serif);
      font-size: 22px;
      font-weight: 600;
      margin: 0 0 8px 0;
      color: var(--vault-dark);
    }
    .vault-empty-state p {
      font-size: 14px;
      color: #777;
      max-width: 440px;
      margin: 0 auto 24px auto;
      line-height: 1.6;
    }
    .btn-gold-cta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: var(--vault-dark);
      color: #FFFFFF;
      border-radius: 999px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.05em;
      transition: all 0.25s ease;
    }
    .btn-gold-cta:hover {
      background: var(--vault-gold);
      color: #111;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px var(--vault-gold-glow);
    }

    /* Wishlist Grid */
    .wishlist-grid-luxury {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
    }
    .wishlist-card {
      background: #FFFFFF;
      border: 1px solid var(--vault-border);
      border-radius: 14px;
      overflow: hidden;
      transition: all 0.25s ease;
      display: flex;
      flex-direction: column;
    }
    .wishlist-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
      border-color: var(--vault-gold-light);
    }
    .wishlist-img-frame {
      position: relative;
      width: 100%;
      height: 240px;
      background: #FAF8F5;
      overflow: hidden;
    }
    .wishlist-img-frame img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
    .wishlist-card:hover .wishlist-img-frame img {
      transform: scale(1.05);
    }
    .wishlist-remove-btn {
      position: absolute;
      top: 12px;
      right: 12px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(0, 0, 0, 0.08);
      color: #666;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .wishlist-remove-btn:hover {
      background: #FF4D4D;
      color: #FFF;
      border-color: #FF4D4D;
    }
    .wishlist-card-body {
      padding: 20px;
      display: flex;
      flex-direction: column;
      flex: 1;
      justify-content: space-between;
    }
    .wishlist-card-title {
      font-family: var(--vault-font-serif);
      font-size: 16px;
      font-weight: 600;
      color: var(--vault-dark);
      margin: 0 0 6px 0;
    }
    .wishlist-card-meta {
      font-size: 13px;
      color: #888;
      margin: 0 0 14px 0;
    }
    .wishlist-card-price {
      font-family: var(--vault-font-display);
      font-size: 17px;
      font-weight: 700;
      color: var(--vault-dark);
      margin-bottom: 16px;
    }
    .wishlist-card-action {
      display: block;
      width: 100%;
      text-align: center;
      padding: 10px 0;
      background: #F8F8F8;
      border: 1px solid #E8E8E8;
      border-radius: 8px;
      color: #1a1a1a;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.2s;
    }
    .wishlist-card-action:hover {
      background: var(--vault-dark);
      color: #FFFFFF;
      border-color: var(--vault-dark);
    }

    /* Profile Tab Info Grid */
    .profile-card-luxury {
      background: #FFFFFF;
      border: 1px solid var(--vault-border);
      border-radius: 16px;
      padding: 32px 36px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    .profile-card-luxury h3 {
      font-family: var(--vault-font-serif);
      font-size: 20px;
      margin: 0 0 20px 0;
      color: var(--vault-dark);
      border-bottom: 1px solid #F0EDE6;
      padding-bottom: 14px;
    }
    .profile-info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 24px;
      margin-bottom: 30px;
    }
    .profile-field label {
      display: block;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #888;
      margin-bottom: 6px;
    }
    .profile-field div {
      font-size: 15px;
      font-weight: 600;
      color: #1a1a1a;
    }
    .profile-logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: #FFF0F0;
      color: #D32F2F;
      border: 1px solid #FFCDD2;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .profile-logout-btn:hover {
      background: #D32F2F;
      color: #FFF;
      border-color: #D32F2F;
    }

    @media (max-width: 768px) {
      .vault-navbar {
        padding: 0 20px;
        height: 66px;
      }
      .vault-wrapper {
        margin-top: 86px;
        padding: 0 16px;
      }
      .vault-hero-card {
        padding: 24px;
      }
      .vault-hero-stats {
        width: 100%;
        justify-content: space-between;
      }
      .order-card-header, .order-items-list, .order-card-footer {
        padding: 16px;
      }
      .order-item-row {
        flex-direction: column;
        align-items: flex-start;
      }
      .order-item-price-col {
        text-align: left;
      }
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <header class="vault-navbar" role="banner">
    <a href="/ecom/index.php" class="vault-brand" aria-label="SSRINI Handcrafts Home">
      <img src="assets/svgs/logo.svg" alt="SSRINI Handcrafts" class="vault-brand-logo">
      <div class="vault-brand-text">
        SSRINI
        <span>MEMBER VAULT</span>
      </div>
    </a>

    <div class="vault-nav-right">
      <a href="/ecom/store/index.php" class="vault-return-store-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Return to Shop</span>
      </a>

      <!-- Desktop Nav Auth Session Button -->
      <a href="javascript:void(0)" class="nav-auth-btn" id="navAuthBtn" title="Signed in as <?= htmlspecialchars($customer['name']) ?>">
        <svg class="nav-auth-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span><?= htmlspecialchars(explode(' ', $customer['name'])[0]) ?></span>
      </a>
    </div>
  </header>

  <!-- Main Vault Dashboard -->
  <main class="vault-wrapper">

    <!-- Member Welcome Banner -->
    <section class="vault-hero-card">
      <div class="vault-hero-content">
        <div>
          <h1 class="vault-hero-title">The Member <span>Vault</span></h1>
          <p class="vault-hero-sub">
            <span>Welcome back, <?= htmlspecialchars($customer['name']) ?></span>
            <span class="patron-badge">✦ Atelier Patron</span>
          </p>
        </div>
        <div class="vault-hero-stats">
          <div class="vault-stat-box">
            <div class="vault-stat-number"><?= count($orders) ?></div>
            <div class="vault-stat-label">Commissions</div>
          </div>
          <div class="vault-stat-box">
            <div class="vault-stat-number">₹<?= number_format($totalSpent, 0) ?></div>
            <div class="vault-stat-label">Acquired Value</div>
          </div>
          <div class="vault-stat-box">
            <div class="vault-stat-number"><?= $memberSince ?></div>
            <div class="vault-stat-label">Patron Since</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tab Selector -->
    <nav class="vault-tabs" role="tablist" aria-label="Member Vault Sections">
      <button class="vault-tab-btn active" data-tab="tab-orders" role="tab" aria-selected="true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        <span>Order History</span>
        <span class="vault-tab-badge"><?= count($orders) ?></span>
      </button>

      <button class="vault-tab-btn" data-tab="tab-wishlist" role="tab" aria-selected="false">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
        <span>Curated Wishlist</span>
        <span class="vault-tab-badge" id="wishlistCountBadge"><?= count($wishlistProducts) ?></span>
      </button>

      <button class="vault-tab-btn" data-tab="tab-profile" role="tab" aria-selected="false">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Patron Profile</span>
      </button>
    </nav>

    <!-- Tab 1: Orders Stack -->
    <div class="vault-tab-pane active" id="tab-orders">
      <?php if (empty($orders)): ?>
        <div class="vault-empty-state">
          <svg class="vault-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
          <h3>Your Vault has no active orders</h3>
          <p>No bespoke handcrafted jewelry or artisanal orders have been placed under this account yet.</p>
          <a href="/ecom/store/index.php" class="btn-gold-cta">
            <span>Explore the Atelier Shop</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      <?php else: ?>
        <div class="orders-stack">
          <?php foreach ($orders as $order): ?>
            <?php
              $status = strtolower($order['order_status']);
              $statusClass = 'status-pending';
              if ($status === 'processing') $statusClass = 'status-processing';
              if ($status === 'shipped') $statusClass = 'status-shipped';
              if ($status === 'delivered') $statusClass = 'status-delivered';
              if ($status === 'cancelled') $statusClass = 'status-cancelled';

              $orderItems = $orderItemsByOrderId[$order['id']] ?? [];
            ?>
            <article class="order-card-luxury">
              <!-- Order Header -->
              <div class="order-card-header">
                <div class="order-header-left">
                  <h3>Order #<?= htmlspecialchars($order['order_number']) ?></h3>
                  <p>Commissioned on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?> • <?= htmlspecialchars($order['payment_method']) === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?></p>
                </div>
                <div class="order-header-right">
                  <span class="status-badge <?= $statusClass ?>">
                    ● <?= htmlspecialchars(strtoupper($order['order_status'])) ?>
                  </span>
                  <a href="/ecom/store/invoice.php?order_id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn-invoice">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    <span>View Invoice</span>
                  </a>
                </div>
              </div>

              <!-- Order Items List -->
              <?php if (!empty($orderItems)): ?>
                <div class="order-items-list">
                  <?php foreach ($orderItems as $item): ?>
                    <?php
                      $itemImg = !empty($item['image']) ? 'assets/images/' . htmlspecialchars($item['image']) : 'assets/images/placeholder.jpg';
                    ?>
                    <div class="order-item-row">
                      <div class="order-item-thumb-wrapper">
                        <img src="<?= $itemImg ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="order-item-thumb" onerror="this.src='assets/svgs/logo.svg'">
                        <div>
                          <h4 class="order-item-title"><?= htmlspecialchars($item['product_name']) ?></h4>
                          <p class="order-item-subtitle">Qty: <?= (int)$item['quantity'] ?> × ₹<?= number_format($item['price'], 2) ?></p>
                        </div>
                      </div>
                      <div class="order-item-price-col">
                        ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- Order Footer -->
              <div class="order-card-footer">
                <div class="order-delivery-address">
                  <strong>Shipping to:</strong> <?= htmlspecialchars($order['shipping_address'] ?? 'Standard Address') ?>, <?= htmlspecialchars($order['shipping_city'] ?? '') ?> <?= htmlspecialchars($order['shipping_pincode'] ?? '') ?>
                </div>
                <div class="order-total-block">
                  <span style="font-size: 13px; color: #888;">Total:</span>
                  <div class="order-grand-total">₹<?= number_format($order['total_amount'], 2) ?></div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tab 2: Curated Wishlist -->
    <div class="vault-tab-pane" id="tab-wishlist">
      <?php if (empty($wishlistProducts)): ?>
        <div class="vault-empty-state">
          <svg class="vault-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
          </svg>
          <h3>Your Wishlist is Empty</h3>
          <p>You have not saved any heirloom creations to your private gallery yet. Tap the heart icon on any masterpiece in the shop to bookmark it.</p>
          <a href="/ecom/store/index.php" class="btn-gold-cta">
            <span>Browse Masterpiece Store</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      <?php else: ?>
        <div class="wishlist-grid-luxury" id="wishlistGrid">
          <?php foreach ($wishlistProducts as $wProduct): ?>
            <?php
              $wImg = !empty($wProduct['image']) ? 'assets/images/' . htmlspecialchars($wProduct['image']) : 'assets/images/placeholder.jpg';
            ?>
            <div class="wishlist-card" id="wishlist-item-<?= htmlspecialchars($wProduct['slug']) ?>">
              <div class="wishlist-img-frame">
                <img src="<?= $wImg ?>" alt="<?= htmlspecialchars($wProduct['name']) ?>" loading="lazy" onerror="this.src='assets/svgs/logo.svg'">
                <button type="button" class="wishlist-remove-btn" title="Remove from Wishlist" onclick="removeWishlistItem('<?= htmlspecialchars($wProduct['slug']) ?>')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
              </div>
              <div class="wishlist-card-body">
                <div>
                  <h4 class="wishlist-card-title"><?= htmlspecialchars($wProduct['name']) ?></h4>
                  <p class="wishlist-card-meta"><?= htmlspecialchars($wProduct['material'] ?? 'Artisanal Craft') ?></p>
                  <div class="wishlist-card-price">₹<?= number_format($wProduct['price'], 2) ?></div>
                </div>
                <a href="/ecom/store/product.php?id=<?= htmlspecialchars($wProduct['slug']) ?>" class="wishlist-card-action">View Masterpiece</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tab 3: Patron Profile -->
    <div class="vault-tab-pane" id="tab-profile">
      <div class="profile-card-luxury">
        <h3>Member Profile & Preferences</h3>
        <div class="profile-info-grid">
          <div class="profile-field">
            <label>Full Name</label>
            <div><?= htmlspecialchars($customer['name']) ?></div>
          </div>
          <div class="profile-field">
            <label>Registered Email</label>
            <div><?= htmlspecialchars($customer['email']) ?></div>
          </div>
          <div class="profile-field">
            <label>Phone Number</label>
            <div><?= !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'Not specified' ?></div>
          </div>
          <div class="profile-field">
            <label>Membership Status</label>
            <div>Atelier Circle • Active</div>
          </div>
        </div>

        <div style="border-top: 1px solid #F0EDE6; padding-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div style="font-size: 13px; color: #777;">
            Need help or bespoke concierge service? Reach us at <a href="mailto:contact@ssrini.com" style="color: var(--vault-gold); text-decoration: none; font-weight: 500;">contact@ssrini.com</a>
          </div>
          <button type="button" class="profile-logout-btn" id="vaultSignOutBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Sign Out of Account</span>
          </button>
        </div>
      </div>
    </div>

  </main>

  <script>
    // Tab switching
    document.querySelectorAll('.vault-tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.vault-tab-btn').forEach(b => {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.vault-tab-pane').forEach(p => p.classList.remove('active'));

        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        const tabId = btn.getAttribute('data-tab');
        const targetPane = document.getElementById(tabId);
        if (targetPane) targetPane.classList.add('active');
      });
    });

    // Wishlist item removal
    function removeWishlistItem(productSlug) {
      if (!confirm('Remove this item from your private curated wishlist?')) return;
      
      fetch('/ecom/api/toggle_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productSlug })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const card = document.getElementById('wishlist-item-' + productSlug);
          if (card) {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => {
              card.remove();
              const badge = document.getElementById('wishlistCountBadge');
              if (badge) {
                const count = parseInt(badge.textContent, 10) || 0;
                badge.textContent = Math.max(0, count - 1);
              }
              const grid = document.getElementById('wishlistGrid');
              if (grid && grid.children.length === 0) {
                window.location.reload();
              }
            }, 300);
          }
        }
      })
      .catch(err => console.error('Wishlist error:', err));
    }

    // Sign out from profile tab
    const profileSignOutBtn = document.getElementById('vaultSignOutBtn');
    if (profileSignOutBtn) {
      profileSignOutBtn.addEventListener('click', () => {
        fetch('/ecom/api/auth.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'logout' })
        }).then(() => {
          try { localStorage.removeItem('ssrini_auth_user'); } catch(e){}
          window.location.href = '/ecom/index.php';
        });
      });
    }
  </script>

  <script src="../js/auth-session.js"></script>
  <script src="../js/mobile-nav.js?v=20260914"></script>
</body>
</html>
