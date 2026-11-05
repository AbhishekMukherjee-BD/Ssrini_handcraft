<?php
/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN SOCIAL MEDIA LINKS MANAGEMENT
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'Social Media Links';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$successMessage = null;
$errorMessage = null;

if (isset($_SESSION['social_success'])) {
    $successMessage = (string) $_SESSION['social_success'];
    unset($_SESSION['social_success']);
}

if (isset($_SESSION['social_error'])) {
    $errorMessage = (string) $_SESSION['social_error'];
    unset($_SESSION['social_error']);
}

// POST HANDLER: Save Links & Active States
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($postedCsrf)) {
        $_SESSION['social_error'] = 'Security validation failed. Please refresh the page and try again.';
        header('Location: social-links.php');
        exit;
    }
    try {
        $urls = $_POST['urls'] ?? [];
        $activeStates = $_POST['is_active'] ?? [];

        $stmt = $pdo->prepare("
            UPDATE social_links 
            SET url = :url, is_active = :is_active 
            WHERE platform_key = :key
        ");

        foreach ($urls as $key => $rawUrl) {
            $cleanedUrl = trim($rawUrl);
            $isActive = isset($activeStates[$key]) ? 1 : 0;

            $stmt->execute([
                ':url' => $cleanedUrl,
                ':is_active' => $isActive,
                ':key' => $key
            ]);
        }

        $_SESSION['social_success'] = 'Social media links and visibility updated successfully!';
        header('Location: social-links.php');
        exit;

    } catch (Throwable $e) {
        $errorMessage = 'Database update error: ' . $e->getMessage();
    }
}

// FETCH CURRENT SOCIAL LINKS
try {
    $stmt = $pdo->query("SELECT * FROM social_links ORDER BY sort_order ASC");
    $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errorMessage = 'Failed to load social links: ' . $e->getMessage();
    $socialLinks = [];
}

// Compute counts
$totalCount = count($socialLinks);
$activeCount = 0;
$configuredCount = 0;
foreach ($socialLinks as $item) {
    if (!empty($item['is_active'])) $activeCount++;
    if (!empty(trim($item['url']))) $configuredCount++;
}

// Platform visual metadata
$platformMeta = [
    'instagram' => [
        'badge_bg' => 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)',
        'icon' => '📷',
        'placeholder' => 'https://www.instagram.com/ssrini.handicrafts',
        'desc' => 'Shown in Website Footer, About Hero, and Contact Arc Menu'
    ],
    'whatsapp' => [
        'badge_bg' => '#25D366',
        'icon' => '💬',
        'placeholder' => 'https://wa.me/919836081994',
        'desc' => 'Used for customer chat, Mobile Menu, and Checkout Concierge'
    ],
    'location' => [
        'badge_bg' => '#EA4335',
        'icon' => '📍',
        'placeholder' => 'https://maps.app.goo.gl/...',
        'desc' => 'Direct Google Maps pin to Ssrini Atelier in Jaipur/Kolkata'
    ],
    'facebook' => [
        'badge_bg' => '#1877F2',
        'icon' => '👥',
        'placeholder' => 'https://www.facebook.com/ssrini.handicrafts',
        'desc' => 'Shown in Website Footer and Contact Arc Menu'
    ],
    'threads' => [
        'badge_bg' => '#101010',
        'icon' => '🧵',
        'placeholder' => 'https://www.threads.com/@ssrini.handicrafts',
        'desc' => 'Shown in Website Footer, About Hero, and Contact Arc Menu'
    ],
    'twitter' => [
        'badge_bg' => '#0f1419',
        'icon' => '𝕏',
        'placeholder' => 'https://x.com/ssrini_handicraft',
        'desc' => 'Official X (formerly Twitter) brand presence'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Ssrini Handcrafts</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .social-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 24px;
            margin-top: 20px;
        }
        @media (max-width: 1024px) {
            .social-grid { grid-template-columns: 1fr; }
        }
        .social-card {
            background: #fff;
            border: 1px solid #eeeaf2;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .social-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-badge {
            background: #faf8fd;
            border: 1px solid #e8e0f5;
            border-radius: 10px;
            padding: 14px 18px;
        }
        .stat-badge-num {
            font-size: 22px;
            font-weight: 700;
            color: #7627c9;
            margin: 0;
        }
        .stat-badge-label {
            font-size: 11px;
            font-weight: 600;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .platform-row {
            background: #faf9fd;
            border: 1px solid #e9e4f0;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .platform-row:hover {
            border-color: #d1c0eb;
            box-shadow: 0 4px 12px rgba(118, 39, 201, 0.05);
        }
        .platform-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .platform-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .platform-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }
        .platform-name {
            font-size: 14px;
            font-weight: 700;
            color: #25212d;
            margin: 0;
        }
        .platform-desc {
            font-size: 11px;
            color: #888;
            margin: 2px 0 0;
        }
        .toggle-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 22px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #7627c9;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(18px);
        }
        .url-input-wrap {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 8px;
        }
        .social-url-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #dcd5e7;
            border-radius: 8px;
            font-size: 13px;
            color: #25212d;
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .social-url-input:focus {
            border-color: #7627c9;
            box-shadow: 0 0 0 3px rgba(118, 39, 201, 0.12);
        }
        .test-link-btn {
            padding: 9px 14px;
            background: #f0eaf8;
            color: #7627c9;
            border: 1px solid #d9caee;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .test-link-btn:hover {
            background: #7627c9;
            color: #fff;
            border-color: #7627c9;
        }
        .btn-save-social {
            background: linear-gradient(135deg, #7627c9 0%, #58189c 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(118, 39, 201, 0.25);
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 10px;
        }
        .btn-save-social:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(118, 39, 201, 0.35);
        }
        /* PREVIEW STYLING */
        .preview-footer-mockup {
            background: #110d17;
            border: 1px solid #2d243a;
            border-radius: 12px;
            padding: 24px 20px;
            color: #fff;
            text-align: center;
            margin-top: 15px;
        }
        .preview-footer-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #cca43b;
            margin: 0 0 16px;
        }
        .preview-icons-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .preview-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(204, 164, 59, 0.3);
            background: rgba(255,255,255,0.05);
            color: #e5cf87;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .preview-icon-circle.inactive {
            opacity: 0.25;
            filter: grayscale(1);
            border-color: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content" style="padding:24px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px;">
                <div>
                    <h1 style="font-size:22px; font-weight:800; color:#25212d; margin:0 0 6px;">🌐 Social Media & Channel Links</h1>
                    <p style="font-size:13px; color:#666; margin:0;">
                        Manage all official atelier social accounts and contact channels. All links update dynamically across the entire website.
                    </p>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div style="background:#edf9f1; color:#267346; border:1px solid #ccebd6; padding:14px; border-radius:10px; margin-bottom:20px; font-size:13px; font-weight:600;">
                    ✓ <?= e($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div style="background:#fff2f2; color:#c53030; border:1px solid #ffd3d3; padding:14px; border-radius:10px; margin-bottom:20px; font-size:13px; font-weight:600;">
                    ⚠ <?= e($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- STATS BADGES -->
            <div class="social-stats-row">
                <div class="stat-badge">
                    <div class="stat-badge-num"><?= $totalCount ?></div>
                    <div class="stat-badge-label">Available Channels</div>
                </div>
                <div class="stat-badge">
                    <div class="stat-badge-num" style="color:#25d366;"><?= $activeCount ?></div>
                    <div class="stat-badge-label">Active on Website</div>
                </div>
                <div class="stat-badge">
                    <div class="stat-badge-num" style="color:#c58e17;"><?= $configuredCount ?></div>
                    <div class="stat-badge-label">Configured with URLs</div>
                </div>
            </div>

            <div class="social-grid">
                <!-- FORM COLUMN -->
                <section class="social-card">
                    <h2 style="font-size:16px; font-weight:700; color:#25212d; margin:0 0 20px;">Platform Configuration</h2>

                    <form method="POST" id="socialLinksForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($socialLinks as $link): 
                            $key = $link['platform_key'];
                            $meta = $platformMeta[$key] ?? [
                                'badge_bg' => '#7627c9',
                                'icon' => '🔗',
                                'placeholder' => 'https://...',
                                'desc' => 'Social Channel Link'
                            ];
                            $isActive = (bool) ($link['is_active'] ?? 1);
                            $urlValue = $link['url'] ?? '';
                        ?>
                            <div class="platform-row" id="row-<?= e($key) ?>">
                                <div class="platform-header">
                                    <div class="platform-title-wrap">
                                        <div class="platform-icon-badge" style="background: <?= $meta['badge_bg'] ?>;">
                                            <?= $meta['icon'] ?>
                                        </div>
                                        <div>
                                            <h3 class="platform-name"><?= e($link['platform_name']) ?></h3>
                                            <p class="platform-desc"><?= e($meta['desc']) ?></p>
                                        </div>
                                    </div>

                                    <label class="toggle-wrap">
                                        <span style="color: <?= $isActive ? '#257a44' : '#888' ?>;" id="label-status-<?= e($key) ?>">
                                            <?= $isActive ? 'Active' : 'Hidden' ?>
                                        </span>
                                        <span class="toggle-switch">
                                            <input type="checkbox" name="is_active[<?= e($key) ?>]" value="1" <?= $isActive ? 'checked' : '' ?> onchange="updateLivePreview('<?= e($key) ?>', this.checked)">
                                            <span class="toggle-slider"></span>
                                        </span>
                                    </label>
                                </div>

                                <div class="url-input-wrap">
                                    <input 
                                        type="text" 
                                        name="urls[<?= e($key) ?>]" 
                                        id="input-url-<?= e($key) ?>"
                                        class="social-url-input" 
                                        value="<?= e($urlValue) ?>" 
                                        placeholder="<?= e($meta['placeholder']) ?>"
                                        oninput="updateTestLink('<?= e($key) ?>', this.value)"
                                    >
                                    <a 
                                        href="<?= !empty($urlValue) ? e($urlValue) : '#' ?>" 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        class="test-link-btn"
                                        id="test-btn-<?= e($key) ?>"
                                        style="<?= empty($urlValue) ? 'pointer-events:none; opacity:0.4;' : '' ?>"
                                    >
                                        Test Link ↗
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn-save-social">
                            💾 Save All Social Links
                        </button>
                    </form>
                </section>

                <!-- LIVE PREVIEW COLUMN -->
                <aside class="social-card">
                    <h2 style="font-size:16px; font-weight:700; color:#25212d; margin:0 0 10px;">Storefront Live Preview</h2>
                    <p style="font-size:12px; color:#666; margin:0 0 15px;">
                        This preview reflects how active icons will render for visitors in the website footer and mobile radial menus:
                    </p>

                    <div class="preview-footer-mockup">
                        <div class="preview-footer-title">Connect with Atelier</div>
                        <div class="preview-icons-row" id="previewIconsContainer">
                            <?php foreach ($socialLinks as $link): 
                                $key = $link['platform_key'];
                                $meta = $platformMeta[$key] ?? ['icon' => '🔗'];
                                $isActive = (bool) ($link['is_active'] ?? 1);
                                $hasUrl = !empty(trim($link['url'] ?? ''));
                            ?>
                                <div 
                                    class="preview-icon-circle <?= ($isActive && $hasUrl) ? '' : 'inactive' ?>" 
                                    id="preview-circle-<?= e($key) ?>"
                                    title="<?= e($link['platform_name']) ?> (<?= ($isActive && $hasUrl) ? 'Visible' : 'Hidden/No URL' ?>)"
                                >
                                    <?= $meta['icon'] ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:18px; font-size:11px; color:#888;">
                            Icons grayed out are currently hidden or have no link set.
                        </div>
                    </div>

                    <div style="margin-top:24px; padding:16px; background:#fbf9fe; border:1px solid #e8e0f5; border-radius:10px;">
                        <h4 style="font-size:12px; font-weight:700; color:#4a1e6d; margin:0 0 6px;">💡 Quick Handover Tip:</h4>
                        <p style="font-size:11px; color:#666; margin:0; line-height:1.5;">
                            When handing over to the client, they can paste their own Instagram, WhatsApp, or Facebook profiles here at any time. The database immediately publishes the links site-wide without needing any code modifications.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>

<script>
function updateTestLink(key, val) {
    const btn = document.getElementById('test-btn-' + key);
    const circle = document.getElementById('preview-circle-' + key);
    const trimmed = val.trim();

    if (btn) {
        if (trimmed.length > 0) {
            btn.href = trimmed;
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
        } else {
            btn.href = '#';
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.4';
        }
    }

    if (circle) {
        const checkbox = document.querySelector('input[name="is_active[' + key + ']"]');
        const isActive = checkbox ? checkbox.checked : false;
        if (isActive && trimmed.length > 0) {
            circle.classList.remove('inactive');
        } else {
            circle.classList.add('inactive');
        }
    }
}

function updateLivePreview(key, isChecked) {
    const label = document.getElementById('label-status-' + key);
    const circle = document.getElementById('preview-circle-' + key);
    const input = document.getElementById('input-url-' + key);
    const hasUrl = input ? input.value.trim().length > 0 : false;

    if (label) {
        label.textContent = isChecked ? 'Active' : 'Hidden';
        label.style.color = isChecked ? '#257a44' : '#888';
    }

    if (circle) {
        if (isChecked && hasUrl) {
            circle.classList.remove('inactive');
        } else {
            circle.classList.add('inactive');
        }
    }
}
</script>

</body>
</html>
