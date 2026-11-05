<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/login_settings_data.php';

$loginSettings = get_admin_login_settings();

if(isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$csrfToken = generateCsrfToken();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $postedCsrf = $_POST['csrf_token'] ?? '';

    // CSRF Validation
    if (!verifyCsrfToken($postedCsrf)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } 
    // Basic Validation
    elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } 
    else {
        // Rate Limiter Check
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $timeLimit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        
        $stmtAttempts = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
        $stmtAttempts->execute([$ipAddress, $timeLimit]);
        $attemptsResult = $stmtAttempts->fetch();
        
        if ($attemptsResult && $attemptsResult['attempt_count'] >= 5) {
            $error = 'Too many failed login attempts. Please try again in 15 minutes.';
        } else {
            // Proceed with login check
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password, role, status
                FROM admins
                WHERE email = :email
                LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch();

            if(
                $admin &&
                $admin['status'] === 'active' &&
                password_verify($password, $admin['password'])
            ) {
                // Login Success -> Clear rate limit records for this IP
                $stmtClear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                $stmtClear->execute([$ipAddress]);

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                header('Location: index.php');
                exit;
            } else {
                // Login Failed -> Log attempt
                $stmtLog = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
                $stmtLog->execute([$ipAddress]);
                
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | SSRINI Handcrafts</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Syne:wght@500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/auth.css">
  <!-- Favicon Suite -->
  <link rel="shortcut icon" href="../../../favicon.ico?v=2">
  <link rel="icon" type="image/x-icon" href="../../../favicon.ico?v=2">
  <link rel="icon" type="image/png" sizes="32x32" href="../../../ecom/assets/favicon/favicon-32x32.png?v=2">
  <link rel="icon" type="image/png" sizes="16x16" href="../../../ecom/assets/favicon/favicon-16x16.png?v=2">
  <link rel="apple-touch-icon" sizes="180x180" href="../../../ecom/assets/favicon/apple-touch-icon.png?v=2">
  <style>
    /* Admin specific overrides */
    .error-alert {
      background: rgba(220, 38, 38, 0.1);
      color: #dc2626;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 14px;
      margin-bottom: 24px;
      border-left: 4px solid #dc2626;
      font-family: 'Inter', sans-serif;
    }
    .auth-mode-toggle { display: none; } /* Hide sign up toggle */

    /* Floating Brand Logo Just Below Return to Store */
    .login-brand-logo-top {
      position: fixed;
      top: 76px;
      left: 28px;
      z-index: 99;
      display: inline-flex;
      align-items: center;
      padding: 7px 16px;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--auth-border, #e5e7eb);
      border-radius: 999px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
      transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .login-brand-logo-top:hover {
      background: #FFFFFF;
      border-color: var(--auth-gold, #C5A059);
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(197, 160, 89, 0.18);
    }
    .login-brand-logo-top .login-top-logo-img {
      height: 24px;
      width: auto;
      object-fit: contain;
      display: block;
    }
    @media (max-width: 900px) {
      .login-brand-logo-top {
        top: 68px;
        left: 20px;
        padding: 5px 12px;
      }
      .login-brand-logo-top .login-top-logo-img {
        height: 20px;
      }
    }

    .showcase-brand-header {
      margin-top: 100px !important;
      margin-bottom: 24px !important;
    }
  </style>
</head>
<body>

  <!-- Floating Return Navigation -->
  <a href="/ecom/collection/index.php" class="back-home-pill" id="backHomeBtn">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="19" y1="12" x2="5" y2="12"></line>
      <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    <span>Return to Store</span>
  </a>

  <!-- Dynamic Brand Logo Attached Just Below Return Button -->
  <div class="login-brand-logo-top" id="loginBrandLogoTop">
    <a href="/ecom/index.php" title="SSRINI Handcrafts Home">
      <img src="<?= htmlspecialchars($loginSettings['resolved_logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="SSRINI Handcrafts Logo" class="login-top-logo-img">
    </a>
  </div>

  <!-- Split-Screen Grid Layout -->
  <div class="auth-page-wrapper">

    <!-- LEFT PANEL: INTERACTIVE SHOWCASE -->
    <aside class="auth-showcase-panel" aria-label="SSRINI Admin Showcase">
      <div class="showcase-brand-header">
        <div class="showcase-brand-text">
          <h2>Ssrini Handcrafts</h2>
          <span>Administrative Control Panel</span>
        </div>
      </div>

      <div class="showcase-stage">
        <div class="showcase-image-viewport" id="showcaseViewport">
          <div class="showcase-slide active">
            <img src="<?= htmlspecialchars($loginSettings['resolved_showcase_url'], ENT_QUOTES, 'UTF-8') ?>" alt="SSRINI Craftsmanship Showcase" loading="eager" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
          </div>
        </div>
      </div>

      <div class="showcase-footer">
        <h3 class="showcase-headline" id="showcaseHeadline">
          <?= htmlspecialchars($loginSettings['showcase_headline'], ENT_QUOTES, 'UTF-8') ?>
        </h3>
      </div>
    </aside>

    <!-- RIGHT PANEL: CLEAN LUXURY WHITE FORM -->
    <main class="auth-form-panel">
      <div class="auth-form-container">
        
        <div class="form-brand-top">
          <a href="/ecom/index.php" class="form-emblem-badge" title="SSRINI Handcrafts">
            <img src="<?= htmlspecialchars($loginSettings['resolved_logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="SSRINI" style="max-height: 24px; width: auto; object-fit: contain;">
            <span>Ssrini Admin</span>
          </a>
        </div>

        <div class="auth-header-text">
          <h1 id="authTitle">Admin Gateway</h1>
          <p id="authSubtitle">Enter your master credentials to access the administrative dashboard.</p>
        </div>

        <?php if($error): ?>
          <div class="error-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form Element -->
        <form class="auth-form" method="POST" action="login.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

          <!-- Email Input Field -->
          <div class="solace-input-field">
            <input type="email" id="emailInput" name="email" placeholder="admin@domain.com" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <span class="inline-field-label">Admin Email</span>
          </div>

          <!-- Password Input Field -->
          <div class="solace-input-field">
            <input type="password" id="passwordInput" name="password" placeholder="••••••••••••" required autocomplete="current-password">
            <span class="inline-field-label">Password</span>
          </div>

          <div class="auth-extra-row" id="signInExtras">
            <label class="auth-checkbox-label">
              <input type="checkbox" id="rememberMeCheckbox" checked>
              <span>Remember me</span>
            </label>
          </div>

          <button type="submit" class="auth-primary-btn">
            <span>Access Dashboard</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>

        </form>

      </div>
    </main>
  </div>
</body>
</html>