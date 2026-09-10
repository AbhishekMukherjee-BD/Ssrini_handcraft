<?php
/**
 * Ssrini Handicrafts
 * Admin Sidebar
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$currentStatus = $_GET['status'] ?? '';

/**
 * Helper function to check if current page matches target page(s)
 */
function isActivePage($pages, $currentPage) {
    if (is_array($pages)) {
        return in_array($currentPage, $pages, true);
    }
    return $currentPage === $pages;
}

$isOrdersPage = isActivePage(['orders.php', 'order-view.php', 'order-details.php', 'order-edit.php'], $currentPage);
?>

<!-- =================================================
     MOBILE SIDEBAR TOGGLE & OVERLAY (Universal across all pages)
     ================================================= -->
<button type="button" class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Open Navigation Sidebar">☰</button>
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>

<aside class="sidebar" id="adminSidebar" aria-label="Admin Navigation"> 
    
    <!-- BRAND --> 
    <div class="sidebar-brand"> 
        <a href="index.php" style="display:flex; align-items:center; gap:12px; text-decoration:none; color:inherit;">
            <img src="../assets/images/logo.jpg" alt="Ssrini Handicrafts" style="width:38px; height:38px; border-radius:10px; object-fit:cover;">
            <div>
                <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0; line-height:1.2;">Ssrini Handcrafts</h2> 
                <span style="font-size:11px; color:var(--primary); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Admin Panel</span>
            </div>
        </a>
    </div> 

    <!-- NAVIGATION --> 
    <nav class="sidebar-nav"> 
        <!-- Dashboard --> 
        <div class="nav-section"> 
            <a href="index.php" class="nav-item <?= isActivePage(['index.php', 'dashboard.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">📊</span> 
                <span>Dashboard</span> 
            </a> 
        </div> 

        <!-- SALES --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Sales</div> 
            
            <a href="orders.php" class="nav-item <?= $isOrdersPage ? 'active' : '' ?>"> 
                <span class="nav-icon">📦</span> 
                <span class="nav-label">Orders</span> 
            </a> 

            <!-- SUBMENU: Auto-opens when on orders page -->
            <div class="nav-submenu <?= $isOrdersPage ? 'open' : '' ?>"> 
                <a href="orders.php?status=new" class="<?= ($currentPage === 'orders.php' && $currentStatus === 'new') ? 'sub-active' : '' ?>">New Orders</a> 
                <a href="orders.php" class="<?= ($currentPage === 'orders.php' && empty($currentStatus)) ? 'sub-active' : '' ?>">All Orders</a> 
            </div> 
        </div> 

        <!-- CATALOGUE --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Catalogue</div> 
            
            <a href="products.php" class="nav-item <?= isActivePage(['products.php', 'product-add.php', 'add-product.php', 'product-edit.php', 'edit-product.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🛍️</span> 
                <span>Products</span> 
            </a> 

            <a href="invoices.php" class="nav-item <?= isActivePage(['invoices.php', 'invoices-view.php', 'invoice-view.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🧾</span> 
                <span>Invoices</span> 
            </a> 
        </div> 

        <!-- WEBSITE --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Website</div> 

            <a href="filter-configuration.php" class="nav-item <?= isActivePage(['filter-configuration.php', 'filter-config.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🔍</span> 
                <span>Filter Configuration</span> 
            </a> 

            <a href="front-page-content.php" class="nav-item <?= isActivePage(['front-page-content.php', 'front-content.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🏠</span> 
                <span>Front Page Content</span> 
            </a> 

            <a href="about-details.php" class="nav-item <?= isActivePage(['about-details.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">ℹ️</span> 
                <span>About Details</span> 
            </a> 
        </div> 

        <!-- MANAGEMENT --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Management</div> 

            <a href="customers.php" class="nav-item <?= isActivePage(['customers.php', 'customer-details.php', 'customer-view.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">👥</span> 
                <span>Customers</span> 
            </a> 

            <a href="reviews.php" class="nav-item <?= isActivePage(['reviews.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">⭐</span> 
                <span>Reviews</span> 
            </a> 

            <a href="notifications.php" class="nav-item <?= isActivePage(['notifications.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🔔</span> 
                <span>Notifications</span> 
            </a> 

            <a href="analytics.php" class="nav-item <?= isActivePage(['analytics.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">📈</span> 
                <span>Analytics</span> 
            </a> 

            <a href="activity-logs.php" class="nav-item <?= isActivePage(['activity-logs.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">📝</span> 
                <span>Activity Logs</span> 
            </a> 

            <a href="settings.php" class="nav-item <?= isActivePage(['settings.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">⚙️</span> 
                <span>Settings</span> 
            </a> 
        </div> 
    </nav> 

    <!-- LOGOUT --> 
    <div class="sidebar-footer"> 
        <a href="logout.php" class="nav-item logout-link" style="color:var(--danger, #e84d5b); justify-content:center;"> 
            <span>🚪 Logout</span> 
        </a> 
    </div> 
</aside> 

<!-- =================================================
     SIDEBAR STYLES (SINGLE UNIVERSAL TOGGLE)
     ================================================= -->
<style>
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        text-decoration: none;
        color: var(--text-secondary, #475569);
        font-weight: 500;
        border-radius: 10px;
        transition: all 0.2s ease;
        cursor: pointer !important;
        pointer-events: auto !important;
    }

    .nav-item:hover, .nav-item.active {
        background: rgba(115, 87, 232, 0.1);
        color: var(--primary, #7357e8);
        font-weight: 600;
    }

    .nav-submenu {
        display: none;
        padding-left: 38px;
        flex-direction: column;
        gap: 4px;
        margin-top: 4px;
    }

    .nav-submenu.open {
        display: flex;
    }

    .nav-submenu a {
        padding: 6px 12px;
        font-size: 13px;
        color: var(--text-muted, #64748b);
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .nav-submenu a:hover,
    .nav-submenu a.sub-active {
        color: var(--primary, #7357e8);
        background: rgba(115, 87, 232, 0.08);
        font-weight: 600;
    }

    .sidebar-footer {
        padding: 14px 16px;
        border-top: 1px solid var(--border-light, #f0f1f6);
    }

    .logout-link:hover {
        background: rgba(232, 77, 91, 0.1) !important;
        color: var(--danger, #e84d5b) !important;
    }

    /* Dedicated Mobile Sidebar Toggle Button */
    .mobile-sidebar-toggle {
        display: none;
        position: fixed;
        top: 12px;
        left: 14px;
        z-index: 10003;
        width: 44px;
        height: 44px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 12px;
        background: #ffffff;
        color: var(--primary, #7357e8);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        font-size: 22px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        transition: all 0.2s ease;
    }

    .mobile-sidebar-toggle:active {
        transform: scale(0.94);
    }

    .mobile-sidebar-overlay { 
        display: none; 
        position: fixed;
        inset: 0;
        z-index: 10001;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    @media (max-width: 768px) {
        .mobile-sidebar-toggle {
            display: flex !important;
        }

        .top-header {
            padding-left: 68px !important;
        }

        .sidebar {
            position: fixed !important;
            top: 0; 
            left: 0; 
            bottom: 0;
            width: min(290px, 84vw) !important;
            z-index: 10002 !important;
            transform: translateX(-110%) !important;
            transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1) !important;
            overflow-y: auto !important;
            box-shadow: 15px 0 45px rgba(0, 0, 0, 0.22) !important;
            background: #ffffff !important;
        }

        body.sidebar-mobile-open .sidebar,
        .sidebar.mobile-open { 
            transform: translateX(0) !important; 
        }

        .mobile-sidebar-overlay { 
            display: block !important; 
        }

        body.sidebar-mobile-open .mobile-sidebar-overlay,
        .mobile-sidebar-overlay.active { 
            opacity: 1 !important; 
            visibility: visible !important;
            pointer-events: auto !important; 
        }

        body.sidebar-mobile-open { 
            overflow: hidden !important; 
        }

        .main-area,
        .products-main,
        .orders-main { 
            width: 100% !important; 
            margin-left: 0 !important; 
        }
    }
</style>

<!-- =================================================
     SIDEBAR JAVASCRIPT (SINGLE RELIABLE HANDLER)
     ================================================= -->
<script> 
document.addEventListener('DOMContentLoaded', function () { 
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('mobileSidebarOverlay');
    const toggleBtn = document.getElementById('mobileSidebarToggle');

    function openMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('mobile-open');
        document.body.classList.add('sidebar-mobile-open');
        if (overlay) overlay.classList.add('active');
        if (toggleBtn) {
            toggleBtn.innerHTML = '✕';
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
    }

    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('mobile-open');
        document.body.classList.remove('sidebar-mobile-open');
        if (overlay) overlay.classList.remove('active');
        if (toggleBtn) {
            toggleBtn.innerHTML = '☰';
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
    }

    function toggleMobileSidebar() {
        const isOpen = document.body.classList.contains('sidebar-mobile-open') || (sidebar && sidebar.classList.contains('mobile-open'));
        if (isOpen) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMobileSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
    }

    const navigationLinks = document.querySelectorAll('.sidebar a');
    navigationLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeMobileSidebar();
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && (document.body.classList.contains('sidebar-mobile-open') || (sidebar && sidebar.classList.contains('mobile-open')))) {
            closeMobileSidebar();
        }
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileSidebar();
        }
    });
}); 
</script>