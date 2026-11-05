<?php
/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN LOGIN GATEWAY ASSETS & CUSTOMIZATION
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/login_settings_data.php';

requireAdminLogin();

$pageTitle = 'Login Customization';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$successMessage = null;
$errorMessage = null;

if (isset($_SESSION['login_settings_success'])) {
    $successMessage = (string) $_SESSION['login_settings_success'];
    unset($_SESSION['login_settings_success']);
}
if (isset($_SESSION['login_settings_error'])) {
    $errorMessage = (string) $_SESSION['login_settings_error'];
    unset($_SESSION['login_settings_error']);
}

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_login_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        logo_image VARCHAR(500) NOT NULL DEFAULT '/ecom/store/assets/images/logo.png',
        showcase_image VARCHAR(500) NOT NULL DEFAULT '/ecom/about/assets/hero_brand_founder.jpg',
        showcase_headline VARCHAR(255) DEFAULT 'Secure Access for Authorized Personnel',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (Throwable $e) {
    // Ignore if already created
}

// POST HANDLER: Save or Reset Login Assets
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($postedCsrf)) {
        $_SESSION['login_settings_error'] = 'Security validation failed. Please refresh the page and try again.';
        header('Location: login-settings.php');
        exit;
    }

    $action = $_POST['action'] ?? 'update_settings';

    if ($action === 'reset_defaults') {
        try {
            $stmt = $pdo->prepare("UPDATE admin_login_settings SET 
                logo_image = '/ecom/store/assets/images/logo.png',
                showcase_image = '/ecom/about/assets/hero_brand_founder.jpg',
                showcase_headline = 'Secure Access for Authorized Personnel'
                WHERE id = 1");
            $stmt->execute();

            $_SESSION['login_settings_success'] = 'Reset login assets to default e-commerce logo and About section founder photo successfully!';
            header('Location: login-settings.php');
            exit;
        } catch (Throwable $e) {
            $errorMessage = 'Failed to reset settings: ' . $e->getMessage();
        }
    } elseif ($action === 'update_settings') {
        try {
            $currentSettings = get_admin_login_settings();
            $logoImage = trim($_POST['logo_image_url'] ?? '');
            $showcaseImage = trim($_POST['showcase_image_url'] ?? '');
            $showcaseHeadline = trim($_POST['showcase_headline'] ?? 'Secure Access for Authorized Personnel');

            $uploadDir = dirname(__DIR__) . '/uploads/login/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            // Handle Pic 1: Logo file upload
            if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions, true)) {
                    $filename = 'login_logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $destPath)) {
                        $logoImage = 'uploads/login/' . $filename;
                    }
                } else {
                    $errorMessage = 'Invalid logo format. Allowed: ' . implode(', ', $allowedExtensions);
                }
            }

            // Handle Pic 2: Showcase photo file upload
            if (!empty($_FILES['showcase_file']['name']) && $_FILES['showcase_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['showcase_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions, true)) {
                    $filename = 'login_showcase_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['showcase_file']['tmp_name'], $destPath)) {
                        $showcaseImage = 'uploads/login/' . $filename;
                    }
                } else {
                    $errorMessage = 'Invalid showcase photo format. Allowed: ' . implode(', ', $allowedExtensions);
                }
            }

            // If empty, keep current values
            if (empty($logoImage)) {
                $logoImage = $currentSettings['logo_image'];
            }
            if (empty($showcaseImage)) {
                $showcaseImage = $currentSettings['showcase_image'];
            }

            if (empty($errorMessage)) {
                // Ensure a row exists
                $stmtCheck = $pdo->query("SELECT COUNT(*) FROM admin_login_settings");
                if ((int)$stmtCheck->fetchColumn() === 0) {
                    $stmtInsert = $pdo->prepare("INSERT INTO admin_login_settings (id, logo_image, showcase_image, showcase_headline) VALUES (1, ?, ?, ?)");
                    $stmtInsert->execute([$logoImage, $showcaseImage, $showcaseHeadline]);
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE admin_login_settings SET 
                        logo_image = :logo,
                        showcase_image = :showcase,
                        showcase_headline = :headline
                        WHERE id = 1");
                    $stmtUpdate->execute([
                        ':logo'     => $logoImage,
                        ':showcase' => $showcaseImage,
                        ':headline' => $showcaseHeadline
                    ]);
                }

                $_SESSION['login_settings_success'] = 'Admin Login visual assets updated successfully!';
                header('Location: login-settings.php');
                exit;
            }

        } catch (Throwable $e) {
            $errorMessage = 'Database update error: ' . $e->getMessage();
        }
    }
}

// Fetch current active settings
$settings = get_admin_login_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page Customization | SSRINI Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        :root {
            --luxury-gold: #c5a059;
            --luxury-gold-hover: #b38f48;
            --luxury-gold-soft: rgba(197, 160, 89, 0.12);
            --luxury-dark: #0f172a;
            --luxury-card: #ffffff;
            --luxury-border: #e2e8f0;
            --luxury-bg: #f8fafc;
            --luxury-muted: #64748b;
        }

        body {
            background-color: var(--luxury-bg);
            font-family: 'Inter', sans-serif;
            color: var(--luxury-dark);
            margin: 0;
            padding: 0;
        }

        .login-config-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 24px 80px 24px;
        }

        /* Banner Header */
        .page-intro-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 32px 36px;
            color: #ffffff;
            margin-bottom: 32px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.18);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .page-intro-banner::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(197, 160, 89, 0.28) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .banner-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            z-index: 2;
        }
        .banner-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: rgba(197, 160, 89, 0.2);
            color: #d4af37;
            border: 1px solid rgba(197, 160, 89, 0.4);
            margin-bottom: 12px;
        }
        .banner-title {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #ffffff;
            letter-spacing: 0.02em;
        }
        .banner-desc {
            font-size: 14px;
            color: #94a3b8;
            max-width: 680px;
            margin: 0;
            line-height: 1.55;
        }
        .banner-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-view-gateway {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .btn-view-gateway:hover {
            background: #ffffff;
            color: #0f172a;
            border-color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
        }

        /* Flash Alerts */
        .custom-alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* 2-Column Grid for Assets */
        .assets-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            margin-bottom: 32px;
        }
        @media (max-width: 960px) {
            .assets-grid {
                grid-template-columns: 1fr;
            }
        }

        .asset-card {
            background: var(--luxury-card);
            border: 1px solid var(--luxury-border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
        }
        .asset-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
        }
        .card-header-tag {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .card-tag-pill {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 10px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
        }
        .card-num-badge {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: var(--luxury-gold);
            font-size: 14px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 6px 0;
            color: #0f172a;
        }
        .card-subtitle {
            font-size: 13px;
            color: var(--luxury-muted);
            margin: 0 0 20px 0;
            line-height: 1.5;
        }

        /* Preview Containers */
        .preview-box {
            width: 100%;
            border-radius: 12px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .logo-preview-box {
            height: 140px;
            background-color: #ffffff;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 12px 12px;
        }
        .logo-preview-box img {
            max-height: 52px;
            max-width: 85%;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.06));
            transition: transform 0.25s ease;
        }
        .showcase-preview-box {
            height: 240px;
            background: #0f172a;
        }
        .showcase-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }
        .preview-badge-overlay {
            position: absolute;
            bottom: 10px;
            right: 12px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(6px);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Form Inputs */
        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 14px;
            font-size: 13px;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
            background: #ffffff;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: var(--luxury-gold);
            box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.18);
        }
        .file-upload-box {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 12px;
        }
        .file-upload-box:hover {
            border-color: var(--luxury-gold);
            background: var(--luxury-gold-soft);
        }
        .file-upload-box input[type="file"] {
            display: none;
        }
        .file-upload-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .dimension-hint {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Simulation Card */
        .simulation-card {
            background: #ffffff;
            border: 1px solid var(--luxury-border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 32px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        }
        .sim-browser-bar {
            background: #f1f5f9;
            padding: 8px 14px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-bottom: none;
        }
        .sim-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .sim-dot-red { background: #ef4444; }
        .sim-dot-yellow { background: #f59e0b; }
        .sim-dot-green { background: #10b981; }
        .sim-url-bar {
            background: #ffffff;
            border-radius: 6px;
            padding: 3px 12px;
            font-size: 11px;
            color: #64748b;
            flex-grow: 1;
            margin-left: 10px;
            border: 1px solid #e2e8f0;
            font-family: monospace;
        }

        /* Scaled Mockup of Login Page */
        .sim-stage {
            border: 1px solid #e2e8f0;
            border-radius: 0 0 12px 12px;
            height: 340px;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .sim-left-panel {
            background: #0f172a;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
            overflow: hidden;
        }
        .sim-left-bg-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.88;
        }
        .sim-left-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15,23,42,0.4) 0%, rgba(15,23,42,0.85) 100%);
            z-index: 1;
        }
        .sim-left-content {
            position: relative;
            z-index: 2;
        }
        .sim-return-pill {
            position: absolute;
            top: 14px;
            left: 16px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(8px);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 10;
        }
        .sim-logo-fixed {
            position: absolute;
            top: 46px;
            left: 16px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            padding: 4px 10px;
            border-radius: 8px;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .sim-logo-fixed img {
            height: 16px;
            width: auto;
            display: block;
        }
        .sim-showcase-title {
            position: absolute;
            bottom: 16px;
            left: 20px;
            right: 20px;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            z-index: 2;
            line-height: 1.4;
        }
        .sim-right-panel {
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            border-left: 1px solid #e2e8f0;
        }
        .sim-form-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 12px;
        }
        .sim-form-badge img {
            height: 14px;
            width: auto;
        }
        .sim-form-dummy-input {
            width: 75%;
            height: 24px;
            background: #f1f5f9;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .sim-form-dummy-btn {
            width: 75%;
            height: 28px;
            background: #0f172a;
            border-radius: 6px;
            margin-top: 4px;
        }

        /* Bottom Action Bar */
        .bottom-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 28px;
            background: #ffffff;
            border: 1px solid var(--luxury-border);
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            flex-wrap: wrap;
            gap: 16px;
        }
        .btn-save-master {
            background: linear-gradient(135deg, var(--luxury-gold) 0%, #b38f48 100%);
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(197, 160, 89, 0.3);
        }
        .btn-save-master:hover {
            background: linear-gradient(135deg, #d4af37 0%, #a07e3a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(197, 160, 89, 0.4);
        }
        .btn-reset-defaults {
            background: transparent;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-reset-defaults:hover {
            color: #0f172a;
            background: #f8fafc;
            border-color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN AREA -->
    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="login-config-container">

            <!-- INTRO HERO BANNER -->
            <div class="page-intro-banner">
                <div class="banner-flex">
                    <div>
                        <span class="banner-pill">🔐 Portal Customization</span>
                        <h1 class="banner-title">Admin Login Gateway Assets</h1>
                        <p class="banner-desc">
                            Manage the two dynamic visual cornerstones of the master login gateway: 
                            the <strong>Brand Logo</strong> attached under the <em>Return to Store</em> button, 
                            and the <strong>Interactive Showcase Photo</strong> on the left portal stage.
                        </p>
                    </div>
                    <div class="banner-actions">
                        <a href="login.php" target="_blank" class="btn-view-gateway" title="Open Admin Login in New Tab">
                            <span>Preview Login Screen</span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- SUCCESS & ERROR NOTIFICATIONS -->
            <?php if ($successMessage): ?>
                <div class="custom-alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?= e($successMessage) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="custom-alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= e($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- MAIN EDIT FORM -->
            <form method="POST" action="login-settings.php" enctype="multipart/form-data" id="loginSettingsForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="update_settings">

                <div class="assets-grid">

                    <!-- CARD 1: PIC 1 - BRAND LOGO -->
                    <div class="asset-card">
                        <div>
                            <div class="card-header-tag">
                                <span class="card-tag-pill">Pic 1 · Brand Logo</span>
                                <span class="card-num-badge">TOP SHELF & EMBLEM</span>
                            </div>
                            <h2 class="card-title">Login Page Brand Logo</h2>
                            <p class="card-subtitle">
                                Attached right below the <em>Return to Store</em> button at the top-left, and rendered on the form emblem badge.
                            </p>

                            <!-- Live Logo Preview Box -->
                            <div class="preview-box logo-preview-box">
                                <img src="<?= e($settings['resolved_logo_url']) ?>" alt="Current Brand Logo" id="logoPreviewImg">
                                <span class="preview-badge-overlay">Active Logo</span>
                            </div>

                            <!-- File Upload Field -->
                            <div class="form-group">
                                <label class="form-label">Upload New Logo (PNG / SVG / WEBP)</label>
                                <label class="file-upload-box">
                                    <input type="file" name="logo_file" id="logoFileInput" accept=".png,.svg,.webp,.jpg,.jpeg" onchange="previewLocalImage(this, 'logoPreviewImg')">
                                    <span class="file-upload-label">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        <span>Choose Logo File...</span>
                                    </span>
                                </label>
                            </div>

                            <!-- Direct URL Fallback -->
                            <div class="form-group">
                                <label class="form-label" for="logoUrlInput">Or Image Path / Remote URL</label>
                                <input type="text" class="form-control" name="logo_image_url" id="logoUrlInput" value="<?= e($settings['logo_image']) ?>" placeholder="/ecom/store/assets/images/logo.png">
                                <div class="dimension-hint">
                                    <span>💡 Suggested: Transparent PNG or SVG (~200–400px width).</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: PIC 2 - SHOWCASE PHOTO -->
                    <div class="asset-card">
                        <div>
                            <div class="card-header-tag">
                                <span class="card-tag-pill">Pic 2 · Showcase Visual</span>
                                <span class="card-num-badge">LEFT STAGE HERO</span>
                            </div>
                            <h2 class="card-title">Left Panel Showcase Visual</h2>
                            <p class="card-subtitle">
                                The high-resolution hero photo showcased on the interactive left panel (currently set to the About section founder photo).
                            </p>

                            <!-- Live Showcase Preview Box -->
                            <div class="preview-box showcase-preview-box">
                                <img src="<?= e($settings['resolved_showcase_url']) ?>" alt="Current Showcase Visual" id="showcasePreviewImg">
                                <span class="preview-badge-overlay">Active Visual</span>
                            </div>

                            <!-- File Upload Field -->
                            <div class="form-group">
                                <label class="form-label">Upload Showcase Visual (JPG / WEBP / PNG)</label>
                                <label class="file-upload-box">
                                    <input type="file" name="showcase_file" id="showcaseFileInput" accept=".jpg,.jpeg,.webp,.png" onchange="previewLocalImage(this, 'showcasePreviewImg')">
                                    <span class="file-upload-label">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        <span>Choose Photo File...</span>
                                    </span>
                                </label>
                            </div>

                            <!-- Direct URL Fallback -->
                            <div class="form-group">
                                <label class="form-label" for="showcaseUrlInput">Or Image Path / Remote URL</label>
                                <input type="text" class="form-control" name="showcase_image_url" id="showcaseUrlInput" value="<?= e($settings['showcase_image']) ?>" placeholder="/ecom/about/assets/hero_brand_founder.jpg">
                                <div class="dimension-hint">
                                    <span>💡 Suggested: High-res portrait or atelier photo (e.g. 1200×1600).</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- CARD 3: SHOWCASE HEADLINE TEXT -->
                <div class="asset-card" style="margin-bottom: 32px;">
                    <div>
                        <div class="card-header-tag">
                            <span class="card-tag-pill">Showcase Typography</span>
                            <span class="card-num-badge">PORTAL HEADLINE</span>
                        </div>
                        <h2 class="card-title">Left Panel Showcase Headline</h2>
                        <p class="card-subtitle">
                            The security and assurance subtitle displayed at the bottom of the left interactive showcase panel.
                        </p>
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="text" class="form-control" name="showcase_headline" value="<?= e($settings['showcase_headline']) ?>" placeholder="Secure Access for Authorized Personnel">
                        </div>
                    </div>
                </div>

                <!-- MINIATURE LIVE SIMULATION BOX -->
                <div class="simulation-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 4px 0;">Real-Time Scaled Mockup</h3>
                            <p style="font-size: 12px; color: #64748b; margin: 0;">Simulates exact placement of Pic 1 (Logo) and Pic 2 (Showcase) on <code>login.php</code></p>
                        </div>
                        <span style="font-size: 11px; font-weight: 600; color: #059669; background: #ecfdf5; padding: 4px 10px; border-radius: 999px;">Live Simulation</span>
                    </div>

                    <div class="sim-browser-bar">
                        <span class="sim-dot sim-dot-red"></span>
                        <span class="sim-dot sim-dot-yellow"></span>
                        <span class="sim-dot sim-dot-green"></span>
                        <span class="sim-url-bar">https://ssrini.com/admin/login.php</span>
                    </div>

                    <div class="sim-stage">
                        <!-- Left Panel Showcase Mock -->
                        <div class="sim-left-panel">
                            <!-- Floating Return button -->
                            <div class="sim-return-pill">← Return to Store</div>
                            <!-- Brand Logo under Return button -->
                            <div class="sim-logo-fixed" title="Pic 1 (Logo)">
                                <img src="<?= e($settings['resolved_logo_url']) ?>" alt="Logo Mock" id="simLogoImg">
                            </div>

                            <!-- Showcase Hero Background (Pic 2) -->
                            <img src="<?= e($settings['resolved_showcase_url']) ?>" alt="Showcase Mock" class="sim-left-bg-img" id="simShowcaseImg">
                            <div class="sim-left-overlay"></div>

                            <div class="sim-showcase-title">
                                <?= e($settings['showcase_headline']) ?>
                            </div>
                        </div>

                        <!-- Right Panel Form Mock -->
                        <div class="sim-right-panel">
                            <div class="sim-form-badge">
                                <img src="<?= e($settings['resolved_logo_url']) ?>" alt="Logo Badge" id="simBadgeImg">
                                <span>Ssrini Admin</span>
                            </div>
                            <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 12px; font-family: 'Cinzel', serif;">Admin Gateway</div>
                            <div class="sim-form-dummy-input"></div>
                            <div class="sim-form-dummy-input"></div>
                            <div class="sim-form-dummy-btn"></div>
                        </div>
                    </div>
                </div>

                <!-- SAVE & RESET ACTIONS -->
                <div class="bottom-action-bar">
                    <button type="submit" class="btn-save-master">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Save & Apply Visual Assets</span>
                    </button>
            </form>

                    <!-- Reset to Defaults Form -->
                    <form method="POST" action="login-settings.php" onsubmit="return confirm('Are you sure you want to reset both the logo and showcase visual to original e-commerce defaults?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="reset_defaults">
                        <button type="submit" class="btn-reset-defaults">
                            ↺ Reset to E-commerce Defaults
                        </button>
                    </form>
                </div>

        </div>
    </main>
</div>

    <!-- Client-side Image Live Preview Script -->
    <script>
    function previewLocalImage(input, previewImgId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const targetImg = document.getElementById(previewImgId);
                if (targetImg) {
                    targetImg.src = e.target.result;
                }
                // Also update simulation
                if (previewImgId === 'logoPreviewImg') {
                    const simLogo = document.getElementById('simLogoImg');
                    const simBadge = document.getElementById('simBadgeImg');
                    if (simLogo) simLogo.src = e.target.result;
                    if (simBadge) simBadge.src = e.target.result;
                } else if (previewImgId === 'showcasePreviewImg') {
                    const simShowcase = document.getElementById('simShowcaseImg');
                    if (simShowcase) simShowcase.src = e.target.result;
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Live URL field update
    document.getElementById('logoUrlInput')?.addEventListener('input', function(e) {
        const url = e.target.value.trim();
        if (url) {
            document.getElementById('logoPreviewImg').src = url;
            const simLogo = document.getElementById('simLogoImg');
            const simBadge = document.getElementById('simBadgeImg');
            if (simLogo) simLogo.src = url;
            if (simBadge) simBadge.src = url;
        }
    });

    document.getElementById('showcaseUrlInput')?.addEventListener('input', function(e) {
        const url = e.target.value.trim();
        if (url) {
            document.getElementById('showcasePreviewImg').src = url;
            const simShowcase = document.getElementById('simShowcaseImg');
            if (simShowcase) simShowcase.src = url;
        }
    });
    </script>
</body>
</html>
