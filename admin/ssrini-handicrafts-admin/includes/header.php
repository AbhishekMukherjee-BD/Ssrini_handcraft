<?php
/**
 * Ssrini Handicrafts
 * Admin Header
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

$pageTitles = [
    'index.php' => 'Dashboard',
    'dashboard.php' => 'Dashboard',
    'activity-logs.php' => 'Activity Logs',
    'customers.php' => 'Customers',
    'customer-details.php' => 'Customer Details',
    'products.php' => 'Products',
    'add-product.php' => 'Add Product',
    'edit-product.php' => 'Edit Product',
    'orders.php' => 'Orders',
    'order-details.php' => 'Order Details',
    'invoices.php' => 'Invoices',
    'invoices-view.php' => 'Invoice View',
    'reviews.php' => 'Reviews',
    'notifications.php' => 'Notifications',
    'filter-configuration.php' => 'Filter Configuration',
    'front-page-content.php' => 'Front Page Content',
    'about-details.php' => 'About Details',
    'analytics.php' => 'Analytics',
    'settings.php' => 'Settings'
];

if (empty($pageTitle) || $pageTitle === 'Dashboard') {
    $pageTitle = $pageTitles[$currentPage] ?? 'Dashboard';
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = ucfirst($_SESSION['admin_role'] ?? 'admin');
?>

<header class="top-header">
    <div class="header-left">
        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <div class="header-right">
        <!-- Single Clean Refresh Button -->
        <button
            type="button"
            class="header-button"
            id="refreshButton"
            title="Refresh page"
            onclick="window.location.reload();"
        >
            🔄
            <span>Refresh</span>
        </button>

        <!-- Profile Card with Ganesh / Original Logo -->
        <div class="profile" title="Signed in as <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>">
            <div class="profile-avatar">
                <img
                    src="../assets/images/logo.jpg"
                    alt="Ssrini Handicrafts"
                    style="border-radius:30%; width:100%; height:100%; object-fit:cover;"
                >
            </div>

            <div class="profile-info">
                <span class="profile-name">
                    <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="profile-role">
                    <?= htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
    </div>
</header>