<?php 
/**
 * Ssrini Handicrafts - Master Admin Dashboard
 * Direct Database Connection & Authentic Store Data
 */

require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../includes/auth.php'; 

requireAdminLogin(); 

$pageTitle = 'Dashboard'; 
$totalOrders = 0; 
$totalProducts = 0; 
$totalCustomers = 0; 
$totalRevenue = 0; 
$recentOrders = [];

try {
    $db = $pdo ?? $conn ?? null;
    if ($db) {
        $orderQuery = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as revenue FROM orders");
        if ($orderData = $orderQuery->fetch(PDO::FETCH_ASSOC)) { 
            $totalOrders = $orderData['total_orders']; 
            $totalRevenue = $orderData['revenue']; 
        }
        
        $prodQuery = $db->query("SELECT COUNT(*) as total_products FROM products");
        if ($prodData = $prodQuery->fetch(PDO::FETCH_ASSOC)) { 
            $totalProducts = $prodData['total_products']; 
        }
        
        $custQuery = $db->query("SELECT COUNT(*) as total_customers FROM customers");
        if ($custData = $custQuery->fetch(PDO::FETCH_ASSOC)) { 
            $totalCustomers = $custData['total_customers']; 
        }
        
        $recQuery = $db->query("SELECT orders.*, customers.name AS joined_customer_name FROM orders LEFT JOIN customers ON orders.customer_id = customers.id ORDER BY orders.created_at DESC LIMIT 5");
        $recentOrders = $recQuery->fetchAll(PDO::FETCH_ASSOC);

    }
} catch (Exception $e) {
    // Database exception handling
}
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <meta name="description" content="Ssrini Handicrafts Admin Panel"> 
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Ssrini Handicrafts</title> 
    <link rel="stylesheet" href="../assets/css/admin.css"> 
    <style>
    /* ================= GLOBAL & BASE RESET ================= */
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    } 
    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* ================= TABLE WRAPPER ================= */
    .table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 14px;
    }

    .admin-table {
        width: 100%;
        min-width: 650px;
        border-collapse: separate;
        border-spacing: 0;
    }

    /* ================= STATS & CARDS ================= */
    .stat-card {
        padding: 20px;
        background: var(--surface, #ffffff);
        border: 1px solid var(--border-light, #e8eaf2);
        border-radius: var(--radius-md, 12px);
        transition: all 0.25s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        border-color: var(--border-hover, #ddd9ee);
    }

    /* ================= MOBILE RESPONSIVE ================= */
    @media (max-width: 768px) {
        .main-area { width: 100% !important; margin-left: 0 !important; } 
        .top-header { padding-left: 68px !important; } 
        .page-content { padding: 18px 14px !important; }

        .stats-grid { grid-template-columns: 1fr !important; gap: 14px !important; } 
        .page-content > section[style*="grid-template-columns"] {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }
    }
    </style>
</head> 
<body> 

<div class="admin-wrapper"> 
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?> 

    <!-- MAIN AREA -->
    <main class="main-area"> 
        <!-- HEADER -->
        <?php require_once __DIR__ . '/../includes/header.php'; ?> 

        <div class="page-content" style="padding: 24px 30px;"> 
            
            <!-- HERO BANNER -->
            <section class="craft-hero">
                <div class="craft-hero-content">
                    <div class="craft-hero-eyebrow">✦ Welcome back, Admin</div>
                    <h1>SSRINI HANDICRAFTS</h1>
                    <p>Empowering rural artisans of Bengal with premium digital management.</p>
                    <div class="craft-hero-divider"><span></span>✦<span></span></div>
                </div>
                <div class="craft-hero-art">
                    <img src="../assets/images/folk-art.png" alt="Ssrini Handicrafts Folk Art" onerror="this.src='../assets/images/logo.jpg'">
                </div>
            </section>

            <!-- STATS GRID WITH REAL DATABASE DATA -->
            <section class="stats-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;margin-bottom:24px;"> 
                
                <div class="card stat-card card-hover"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">📦</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Total Orders</div> 
                    <div class="stat-value" id="totalOrders" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;"><?= number_format($totalOrders) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">All store orders</div> 
                </div> 

                <div class="card stat-card card-hover"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">🛍️</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Products</div> 
                    <div class="stat-value" id="totalProducts" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;"><?= number_format($totalProducts) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">Active inventory</div> 
                </div> 

                <div class="card stat-card card-hover"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">👥</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Customers</div> 
                    <div class="stat-value" id="totalCustomers" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;"><?= number_format($totalCustomers) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">Registered accounts</div> 
                </div> 

                <div class="card stat-card card-hover"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">₹</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Revenue</div> 
                    <div class="stat-value" id="totalRevenue" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;">₹<?= number_format($totalRevenue, 2) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">Gross merchandise value</div> 
                </div> 

            </section> 

            <!-- RECENT ORDERS & QUICK ACTIONS -->
            <section style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:20px;"> 
                
                <div class="table-card card" style="background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);overflow:hidden;"> 
                    <div class="table-header" style="padding:20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-light);"> 
                        <div> 
                            <div class="table-title" style="font-size:16px;font-weight:600;color:var(--text-primary);">Recent Orders</div> 
                            <p style="margin-top:4px;color:var(--text-muted);font-size:11px;">Latest transactions from your store</p> 
                        </div> 
                        <a href="orders.php" class="btn btn-secondary" style="text-decoration:none;padding:6px 14px;font-size:12px;border:1px solid var(--border-light);border-radius:6px;background:var(--surface-soft);color:var(--text-primary);font-weight:500;">View All</a> 
                    </div> 
                    <div class="table-wrapper"> 
                        <table class="admin-table"> 
                            <thead> 
                                <tr style="text-align:left;background:var(--surface-soft);font-size:12px;color:var(--text-secondary);"> 
                                    <th style="padding:12px 16px;">Order ID</th>
                                    <th style="padding:12px 16px;">Customer</th>
                                    <th style="padding:12px 16px;">Amount</th>
                                    <th style="padding:12px 16px;">Status</th>
                                    <th style="padding:12px 16px;">Date</th> 
                                </tr> 
                            </thead> 
                            <tbody> 
                                <?php if (!empty($recentOrders)): foreach ($recentOrders as $order): ?>
                                <tr style="border-bottom:1px solid var(--border-light);font-size:13px;"> 
                                    <td style="padding:12px 16px;font-weight:500;">#<?= htmlspecialchars($order['id'] ?? $order['order_id'] ?? 'N/A') ?></td> 
                                    <td style="padding:12px 16px;"><?= htmlspecialchars($order['customer_name'] ?? $order['joined_customer_name'] ?? $order['shipping_name'] ?? 'Guest Customer') ?></td> 
                                    <td style="padding:12px 16px;font-weight:600;">₹<?= number_format($order['total_amount'] ?? 0, 2) ?></td> 
                                    <td style="padding:12px 16px;"><span class="badge" style="padding:3px 8px;font-size:11px;border-radius:4px;background:var(--surface-soft);border:1px solid var(--border-light);"><?= htmlspecialchars($order['status'] ?? 'Pending') ?></span></td> 
                                    <td style="padding:12px 16px;color:var(--text-muted);"><?= htmlspecialchars($order['created_at'] ?? 'N/A') ?></td> 
                                </tr> 
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" style="padding:0;"><div class="empty-state" style="text-align:center;padding:40px 20px;"><div class="empty-state-icon" style="font-size:32px;margin-bottom:10px;">📦</div><h3 style="font-size:16px;font-weight:600;color:var(--text-primary);margin-bottom:4px;">No orders yet</h3><p style="color:var(--text-muted);font-size:12px;">New customer orders will appear here automatically.</p></div></td></tr> 
                                <?php endif; ?>
                            </tbody> 
                        </table> 
                    </div> 
                </div> 

                <!-- QUICK ACTIONS -->
                <div class="card" style="background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);height:fit-content;"> 
                    <div style="padding:20px;border-bottom:1px solid var(--border-light);"> 
                        <div class="table-title" style="font-size:16px;font-weight:600;color:var(--text-primary);">Quick Actions</div> 
                        <p style="margin-top:4px;color:var(--text-muted);font-size:11px;">Frequently used admin shortcuts</p> 
                    </div> 
                    <div style="padding:16px;display:flex;flex-direction:column;gap:10px;"> 
                        <button type="button" class="btn btn-primary" onclick="window.location.href='products.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:none;background:var(--primary);color:#fff;font-weight:500;">🛍️ Add Product</button> 
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='orders.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:1px solid var(--border-light);background:var(--surface-soft);color:var(--text-primary);font-weight:500;">📦 Manage Orders</button> 
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='customers.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:1px solid var(--border-light);background:var(--surface-soft);color:var(--text-primary);font-weight:500;">👥 View Customers</button> 
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='invoices.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:1px solid var(--border-light);background:var(--surface-soft);color:var(--text-primary);font-weight:500;">📄 Create Invoice</button> 
                    </div> 
                </div> 

            </section> 

            <!-- STORE STATUS -->
            <section style="margin-top:20px;"> 
                <div class="card" style="background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);"> 
                    <div style="padding:20px;"> 
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;"> 
                            <div>
                                <div class="table-title" style="font-size:16px;font-weight:600;color:var(--text-primary);">Store Status</div>
                                <p style="margin-top:5px;color:var(--text-secondary);font-size:12px;">Your current store configuration and gateway status.</p>
                            </div> 
                            <span class="badge badge-success" style="padding:4px 10px;font-size:12px;border-radius:6px;background:rgba(33,168,117,0.1);color:#21a875;font-weight:600;">● Store Active</span> 
                        </div> 
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:20px;"> 
                            <div style="padding:15px;border:1px solid var(--border-light);border-radius:var(--radius-md,8px);background:var(--surface-soft);"><div style="font-size:20px;margin-bottom:8px;">💵</div><strong style="font-size:13px;color:var(--text-primary);">Cash on Delivery</strong><div style="margin-top:5px;font-size:11px;color:#21a875;font-weight:500;">Available</div></div> 
                            <div style="padding:15px;border:1px solid var(--border-light);border-radius:var(--radius-md,8px);background:var(--surface-soft);"><div style="font-size:20px;margin-bottom:8px;">💳</div><strong style="font-size:13px;color:var(--text-primary);">Online Payment</strong><div style="margin-top:5px;font-size:11px;color:#f59e0b;font-weight:500;">Coming Soon</div></div> 
                            <div style="padding:15px;border:1px solid var(--border-light);border-radius:var(--radius-md,8px);background:var(--surface-soft);"><div style="font-size:20px;margin-bottom:8px;">🌐</div><strong style="font-size:13px;color:var(--text-primary);">Website</strong><div style="margin-top:5px;font-size:11px;color:#21a875;font-weight:500;">Connected & Live</div></div> 
                        </div> 
                    </div> 
                </div> 
            </section> 

        </div> 
    </main> 
</div>

</body> 
</html>