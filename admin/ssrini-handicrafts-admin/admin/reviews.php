<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN TESTIMONIALS & GOOGLE REVIEWS MANAGEMENT
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'Testimonials & Reviews';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$successMessage = null;
$errorMessage = null;

if (isset($_SESSION['reviews_success'])) {
    $successMessage = (string) $_SESSION['reviews_success'];
    unset($_SESSION['reviews_success']);
}
if (isset($_SESSION['reviews_error'])) {
    $errorMessage = (string) $_SESSION['reviews_error'];
    unset($_SESSION['reviews_error']);
}

// Ensure reviews uploads folder exists
$uploadDir = __DIR__ . '/../uploads/reviews/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// ---------------------------------------------------------
// POST ACTION ROUTING
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($postedCsrf)) {
        $_SESSION['reviews_error'] = 'Security validation failed. Please refresh the page and try again.';
        header('Location: reviews.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. TOGGLE ACTIVE STATUS
    if ($action === 'toggle_status') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $newStatus = (int) ($_POST['is_active'] ?? 0);
        $enumStatus = $newStatus === 1 ? 'approved' : 'hidden';

        if ($reviewId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE reviews SET is_active = :is_active, status = :status WHERE id = :id");
                $stmt->execute([
                    ':is_active' => $newStatus,
                    ':status'    => $enumStatus,
                    ':id'        => $reviewId
                ]);
                $_SESSION['reviews_success'] = $newStatus ? 'Testimonial is now ACTIVE on the website.' : 'Testimonial is now HIDDEN from the website.';
            } catch (Exception $ex) {
                $_SESSION['reviews_error'] = 'Database error updating status: ' . $ex->getMessage();
            }
        }
        header('Location: reviews.php');
        exit;
    }

    // 2. DELETE TESTIMONIAL
    if ($action === 'delete_review') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
                $stmt->execute([':id' => $reviewId]);
                $_SESSION['reviews_success'] = 'Testimonial card was successfully deleted.';
            } catch (Exception $ex) {
                $_SESSION['reviews_error'] = 'Failed to delete testimonial: ' . $ex->getMessage();
            }
        }
        header('Location: reviews.php');
        exit;
    }

    // 3. SAVE / UPDATE TESTIMONIAL
    if ($action === 'save_review') {
        $reviewId       = (int) ($_POST['review_id'] ?? 0);
        $authorName     = trim($_POST['author_name'] ?? '');
        $authorLocation = trim($_POST['author_location'] ?? '');
        $rating         = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $reviewText     = trim($_POST['review_text'] ?? '');
        $sortOrder      = (int) ($_POST['sort_order'] ?? 0);
        $isActive       = isset($_POST['is_active']) ? 1 : 0;
        $source         = trim($_POST['source'] ?? 'Google Reviews');
        $status         = $isActive === 1 ? 'approved' : 'hidden';

        if (empty($authorName) || empty($reviewText)) {
            $_SESSION['reviews_error'] = 'Please provide both the Author Name and the Testimonial Quote.';
            header('Location: reviews.php');
            exit;
        }

        // Image Handling
        $imagePath = trim($_POST['existing_avatar_image'] ?? '../landing/assets/images/IMG_0685.jpg');

        if (isset($_FILES['avatar_image_file']) && $_FILES['avatar_image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar_image_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowedExts, true)) {
                $uniqueName = 'review_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destPath   = $uploadDir . $uniqueName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $imagePath = 'uploads/reviews/' . $uniqueName;
                }
            }
        } elseif (!empty($_POST['avatar_image_url'])) {
            $imagePath = trim($_POST['avatar_image_url']);
        }

        try {
            if ($reviewId > 0) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE reviews SET
                        author_name     = :author_name,
                        author_location = :author_location,
                        rating          = :rating,
                        review_text     = :review_text,
                        avatar_image    = :avatar_image,
                        sort_order      = :sort_order,
                        is_active       = :is_active,
                        status          = :status,
                        source          = :source
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':author_name'     => $authorName,
                    ':author_location' => $authorLocation,
                    ':rating'          => $rating,
                    ':review_text'     => $reviewText,
                    ':avatar_image'    => $imagePath,
                    ':sort_order'      => $sortOrder,
                    ':is_active'       => $isActive,
                    ':status'          => $status,
                    ':source'          => $source,
                    ':id'              => $reviewId
                ]);
                $_SESSION['reviews_success'] = 'Testimonial card updated successfully!';
            } else {
                // Insert
                $stmt = $pdo->prepare("
                    INSERT INTO reviews
                        (author_name, author_location, rating, review_text, avatar_image, sort_order, is_active, status, source)
                    VALUES
                        (:author_name, :author_location, :rating, :review_text, :avatar_image, :sort_order, :is_active, :status, :source)
                ");
                $stmt->execute([
                    ':author_name'     => $authorName,
                    ':author_location' => $authorLocation,
                    ':rating'          => $rating,
                    ':review_text'     => $reviewText,
                    ':avatar_image'    => $imagePath,
                    ':sort_order'      => $sortOrder,
                    ':is_active'       => $isActive,
                    ':status'          => $status,
                    ':source'          => $source
                ]);
                $_SESSION['reviews_success'] = 'New testimonial card added successfully!';
            }
        } catch (Exception $ex) {
            $_SESSION['reviews_error'] = 'Database error saving review: ' . $ex->getMessage();
        }

        header('Location: reviews.php');
        exit;
    }
}

// ---------------------------------------------------------
// FETCH ALL TESTIMONIALS
// ---------------------------------------------------------
$filterStatus = $_GET['filter'] ?? 'all';
$searchQuery  = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM reviews WHERE 1=1";
$params = [];

if ($filterStatus === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($filterStatus === 'hidden') {
    $sql .= " AND is_active = 0";
}

if (!empty($searchQuery)) {
    $sql .= " AND (author_name LIKE :search OR author_location LIKE :search OR review_text LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY sort_order ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$totalCount  = (int) $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$activeCount = (int) $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_active = 1")->fetchColumn();
$hiddenCount = $totalCount - $activeCount;

// Format display path for image preview
function formatAvatarPath(?string $path): string
{
    if (empty($path)) {
        return '../assets/images/placeholder.jpg';
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    if (strpos($path, '../landing/') === 0) {
        return '../../' . substr($path, 3);
    }
    if (strpos($path, 'uploads/') === 0) {
        return '../' . $path;
    }
    return '../../ecom/about/' . ltrim($path, '/');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Ssrini Handcrafts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

<style>
/* Missing CSS vars from dark theme — mapped to light admin palette */
:root {
    --bg-card: #ffffff;
    --bg-secondary: #f5f6fa;
    --border-color: #e8eaf2;
    --text-primary: #15182b;
    --text-secondary: #667085;
    --gold-base: #d4af37;
    --gold-light: #f3e5ab;
    --gold-dark: #aa8c2c;
    --gold-glow: rgba(212, 175, 55, 0.25);
}

.rv-container {
    max-width: 100%;
    margin: 0;
    padding: 0;
}

.rv-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.rv-title-area h1 {
    font-size: 26px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.rv-title-area p {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 0;
}

/* KPI Banner */
.rv-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}

.rv-kpi-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.2s, border-color 0.2s;
}

.rv-kpi-card:hover {
    border-color: var(--gold-base);
    transform: translateY(-2px);
}

.rv-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(212, 175, 55, 0.1);
    color: var(--gold-base);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.rv-kpi-num {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

.rv-kpi-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Actions Toolbar */
.rv-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 20px;
    margin-bottom: 24px;
}

.rv-filters {
    display: flex;
    gap: 8px;
    align-items: center;
}

.rv-filter-pill {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    color: var(--text-secondary);
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    transition: all 0.2s;
}

.rv-filter-pill:hover,
.rv-filter-pill.active {
    background: var(--gold-base);
    color: #111;
    border-color: var(--gold-base);
}

.rv-btn-gold {
    background: linear-gradient(135deg, var(--gold-base), var(--gold-dark));
    color: #000;
    font-weight: 700;
    font-size: 14px;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px var(--gold-glow);
    transition: all 0.2s;
    text-decoration: none;
}

.rv-btn-gold:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px var(--gold-glow);
    color: #000;
}

/* Cards Grid */
.rv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.rv-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transition: all 0.25s ease;
}

.rv-card:hover {
    border-color: rgba(212, 175, 55, 0.4);
    box-shadow: 0 8px 24px rgba(0,0,0,0.22);
}

.rv-card.inactive {
    opacity: 0.65;
    border-style: dashed;
}

.rv-card-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}

.rv-avatar {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    border: 1.5px solid var(--gold-base);
    box-shadow: 2px 2px 0px rgba(212, 175, 55, 0.4);
    background: #222;
}

.rv-author-info {
    flex: 1;
    min-width: 0;
}

.rv-author-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rv-author-byline {
    font-size: 12px;
    color: var(--gold-base);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rv-stars {
    display: flex;
    gap: 3px;
    color: var(--gold-base);
    font-size: 14px;
    margin-bottom: 12px;
}

.rv-quote {
    font-size: 13.5px;
    line-height: 1.55;
    color: var(--text-secondary);
    margin: 0 0 16px 0;
    flex-grow: 1;
    position: relative;
    font-style: italic;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.rv-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--border-color);
    margin-top: auto;
}

.rv-badge-order {
    font-size: 11px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    padding: 3px 8px;
    border-radius: 6px;
    color: var(--text-secondary);
}

.rv-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rv-btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    color: var(--text-primary);
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.rv-btn-icon:hover {
    border-color: var(--gold-base);
    color: var(--gold-base);
}

.rv-btn-icon.danger:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

/* Switch */
.rv-switch {
    position: relative;
    display: inline-block;
    width: 38px;
    height: 22px;
}
.rv-switch input { opacity: 0; width: 0; height: 0; }
.rv-slider {
    position: absolute; cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #374151;
    transition: .3s;
    border-radius: 22px;
}
.rv-slider:before {
    position: absolute; content: "";
    height: 16px; width: 16px;
    left: 3px; bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
input:checked + .rv-slider { background-color: var(--gold-base); }
input:checked + .rv-slider:before { transform: translateX(16px); }

/* Location Badges */
.rv-loc-badge {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 3px 8px;
    border-radius: 4px;
    background: rgba(212, 175, 55, 0.15);
    color: var(--gold-base);
    border: 1px solid rgba(212, 175, 55, 0.3);
    margin-left: 8px;
}

/* Modal Overlay */
.rv-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.rv-modal-overlay.active {
    display: flex;
}

.rv-modal {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    width: 100%;
    max-width: 760px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 16px 40px rgba(0,0,0,0.4);
    padding: 28px;
    position: relative;
}

.rv-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 16px;
    margin-bottom: 20px;
}

.rv-modal-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rv-modal-close {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-size: 22px;
    cursor: pointer;
}

.rv-modal-close:hover {
    color: var(--gold-base);
}

/* Modal Form Controls */
.rv-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.rv-form-group {
    margin-bottom: 18px;
}

.rv-label {
    display: flex;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 6px;
}

.rv-input, .rv-textarea, .rv-select {
    width: 100%;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
    color: var(--text-primary);
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.2s;
}

.rv-input:focus, .rv-textarea:focus, .rv-select:focus {
    border-color: var(--gold-base);
    box-shadow: 0 0 0 2px var(--gold-glow);
}

.rv-hint {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 4px;
}

/* Live Card Preview in Modal */
.rv-preview-box {
    background: #0b0f17;
    border: 1px dashed rgba(212, 175, 55, 0.4);
    border-radius: 12px;
    padding: 18px;
    margin-top: 14px;
    margin-bottom: 24px;
}

.rv-preview-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gold-base);
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.rv-mockup-card {
    background: #1e293b;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3), 0 4px 0 2px rgba(212,175,55,0.4);
    border: 1px solid #d4af37;
    color: #fff;
    max-width: 340px;
    margin: 0 auto;
}
</style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content" style="padding:24px;">
        <div class="rv-container">

    <!-- HEADER -->
    <div class="rv-header">
        <div class="rv-title-area">
            <h1>
                <span>Voices of Bengal Testimonials</span>
                <span class="rv-loc-badge">Storefront: About Page Carousel</span>
            </h1>
            <p>Manage customer testimonials and Google Reviews displayed in the signature 3D staggered card carousel on the About page.</p>
        </div>
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <a href="http://127.0.0.1:8000/ecom/about/index.php#testimonials" target="_blank" rel="noopener"
               style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#64748b; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.2s;"
               onmouseover="this.style.borderColor='#d4af37';this.style.color='#aa8c2c';"
               onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';">
                <span>👁️</span>
                <span>Preview on About Page</span>
            </a>
            <button type="button" class="rv-btn-gold" onclick="openAddModal()">
                <span style="font-size:18px; line-height:1;">＋</span>
                <span>Add Testimonial Card</span>
            </button>
        </div>
    </div>

    <!-- ALERTS -->
    <?php if ($successMessage): ?>
        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #4ade80; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span>✓</span>
            <span><?= e($successMessage) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span>⚠</span>
            <span><?= e($errorMessage) ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI STATS -->
    <div class="rv-kpi-grid">
        <div class="rv-kpi-card">
            <div class="rv-kpi-icon">💬</div>
            <div>
                <div class="rv-kpi-num"><?= $totalCount ?></div>
                <div class="rv-kpi-label">Total Testimonials</div>
            </div>
        </div>
        <div class="rv-kpi-card">
            <div class="rv-kpi-icon">✨</div>
            <div>
                <div class="rv-kpi-num" style="color:#22c55e;"><?= $activeCount ?></div>
                <div class="rv-kpi-label">Active on About Page</div>
            </div>
        </div>
        <div class="rv-kpi-card">
            <div class="rv-kpi-icon">👁️‍🗨️</div>
            <div>
                <div class="rv-kpi-num" style="color:#eab308;"><?= $hiddenCount ?></div>
                <div class="rv-kpi-label">Hidden / Drafts</div>
            </div>
        </div>
        <div class="rv-kpi-card">
            <div class="rv-kpi-icon">⭐</div>
            <div>
                <div class="rv-kpi-num" style="color:var(--gold-base);">5.0 ★</div>
                <div class="rv-kpi-label">Google Verified Average</div>
            </div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="rv-toolbar">
        <div class="rv-filters">
            <span style="font-size:12px; color:var(--text-secondary); text-transform:uppercase; margin-right:4px;">Filter:</span>
            <a href="reviews.php?filter=all" class="rv-filter-pill <?= $filterStatus === 'all' ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
            <a href="reviews.php?filter=active" class="rv-filter-pill <?= $filterStatus === 'active' ? 'active' : '' ?>">Active (<?= $activeCount ?>)</a>
            <a href="reviews.php?filter=hidden" class="rv-filter-pill <?= $filterStatus === 'hidden' ? 'active' : '' ?>">Hidden (<?= $hiddenCount ?>)</a>
        </div>

        <form method="GET" action="reviews.php" style="display:flex; gap:8px;">
            <input type="hidden" name="filter" value="<?= e($filterStatus) ?>">
            <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Search by patron or quote..." class="rv-input" style="padding:6px 12px; width:220px; font-size:12px;">
            <button type="submit" class="rv-btn-icon" title="Search">🔍</button>
            <?php if (!empty($searchQuery)): ?>
                <a href="reviews.php?filter=<?= e($filterStatus) ?>" class="rv-btn-icon" title="Clear Search">✕</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- CARDS GRID -->
    <?php if (empty($reviews)): ?>
        <div style="background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 12px; padding: 48px; text-align: center;">
            <div style="font-size: 36px; margin-bottom: 12px;">📭</div>
            <h3 style="color: var(--text-primary); margin: 0 0 8px 0;">No Testimonials Found</h3>
            <p style="color: var(--text-secondary); margin: 0 0 20px 0;">No reviews match your current filter or search criteria.</p>
            <button type="button" class="rv-btn-gold" onclick="openAddModal()">＋ Add Your First Testimonial</button>
        </div>
    <?php else: ?>
        <div class="rv-grid">
            <?php foreach ($reviews as $rv): ?>
                <?php
                    $isActive = (int) ($rv['is_active'] ?? 1);
                    $rating   = (int) ($rv['rating'] ?? 5);
                    $avatar   = formatAvatarPath($rv['avatar_image']);
                ?>
                <div class="rv-card <?= $isActive ? '' : 'inactive' ?>" id="reviewCard_<?= $rv['id'] ?>">
                    <!-- TOP: AVATAR & AUTHOR -->
                    <div>
                        <div class="rv-card-top">
                            <img src="<?= e($avatar) ?>" alt="<?= e($rv['author_name']) ?>" class="rv-avatar" onerror="this.src='../assets/images/placeholder.jpg'">
                            <div class="rv-author-info">
                                <h4 class="rv-author-name"><?= e($rv['author_name']) ?></h4>
                                <p class="rv-author-byline"><?= e($rv['author_location'] ?: 'Verified Patron') ?></p>
                            </div>
                        </div>

                        <!-- STARS -->
                        <div class="rv-stars">
                            <?php for ($s = 0; $s < $rating; $s++): ?>
                                <span>★</span>
                            <?php endfor; ?>
                            <?php for ($s = $rating; $s < 5; $s++): ?>
                                <span style="color:#4b5563;">★</span>
                            <?php endfor; ?>
                            <span style="font-size:11px; color:var(--text-secondary); margin-left:6px; font-weight:600;"><?= $rating ?>.0</span>
                        </div>

                        <!-- QUOTE -->
                        <p class="rv-quote">“<?= e($rv['review_text']) ?>”</p>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="rv-card-footer">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <!-- Active Toggle Switch -->
                            <form method="POST" style="margin:0; display:inline-flex; align-items:center;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="review_id" value="<?= $rv['id'] ?>">
                                <input type="hidden" name="is_active" value="<?= $isActive ? '0' : '1' ?>">
                                <label class="rv-switch" title="<?= $isActive ? 'Active on website. Click to Hide' : 'Hidden from website. Click to Activate' ?>">
                                    <input type="checkbox" <?= $isActive ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span class="rv-slider"></span>
                                </label>
                            </form>
                            <span style="font-size:11px; color:<?= $isActive ? '#22c55e' : '#9ca3af' ?>; font-weight:600;">
                                <?= $isActive ? 'Live' : 'Hidden' ?>
                            </span>
                        </div>

                        <div class="rv-actions">
                            <span class="rv-badge-order" title="Sort Order">#<?= (int) ($rv['sort_order'] ?? 0) ?></span>

                            <!-- EDIT BUTTON -->
                            <button type="button" class="rv-btn-icon" title="Edit Testimonial" onclick='openEditModal(<?= json_encode($rv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                ✏️
                            </button>

                            <!-- DELETE BUTTON -->
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this testimonial from <?= e($rv['author_name']) ?>?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?= $rv['id'] ?>">
                                <button type="submit" class="rv-btn-icon danger" title="Delete Testimonial">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- =========================================================
     MODAL: ADD / EDIT TESTIMONIAL CARD
     ========================================================= -->
<div class="rv-modal-overlay" id="reviewModalOverlay">
    <div class="rv-modal">
        <div class="rv-modal-header">
            <h3 id="modalTitle">
                <span>＋ Add Testimonial Card</span>
            </h3>
            <button type="button" class="rv-modal-close" onclick="closeModal()">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="reviewForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_review">
            <input type="hidden" name="review_id" id="modalReviewId" value="0">
            <input type="hidden" name="existing_avatar_image" id="modalExistingImage" value="">

            <div class="rv-form-grid">
                <!-- 1. AUTHOR NAME -->
                <div class="rv-form-group">
                    <label class="rv-label">
                        <span>Author Name</span>
                        <span class="rv-loc-badge">Card: Title</span>
                    </label>
                    <input type="text" name="author_name" id="modalAuthorName" class="rv-input" placeholder="e.g. Ananya Chatterjee" required oninput="updateLivePreview()">
                    <p class="rv-hint">Primary name of the patron or customer.</p>
                </div>

                <!-- 2. LOCATION & ROLE BYLINE -->
                <div class="rv-form-group">
                    <label class="rv-label">
                        <span>Location & Byline</span>
                        <span class="rv-loc-badge">Card: Subtitle</span>
                    </label>
                    <input type="text" name="author_location" id="modalAuthorLocation" class="rv-input" placeholder="e.g. Kolkata (Verified Patron)" oninput="updateLivePreview()">
                    <p class="rv-hint">Location and verification label displayed beside author name.</p>
                </div>
            </div>

            <!-- 3. TESTIMONIAL QUOTE -->
            <div class="rv-form-group">
                <label class="rv-label">
                    <span>Testimonial Quote</span>
                    <span class="rv-loc-badge">Card: Main Body</span>
                </label>
                <textarea name="review_text" id="modalReviewText" class="rv-textarea" rows="4" placeholder="Enter the patron's exact review or words about their experience with Ssrini..." required oninput="updateLivePreview()"></textarea>
                <p class="rv-hint">The main quotation rendered inside decorative quotes in the 3D staggered card stack.</p>
            </div>

            <div class="rv-form-grid">
                <!-- 4. STAR RATING -->
                <div class="rv-form-group">
                    <label class="rv-label">
                        <span>Star Rating</span>
                        <span class="rv-loc-badge">Card: 5 Stars</span>
                    </label>
                    <select name="rating" id="modalRating" class="rv-select" onchange="updateLivePreview()">
                        <option value="5" selected>★★★★★ (5 Stars - Exceptional)</option>
                        <option value="4">★★★★☆ (4 Stars - Great)</option>
                        <option value="3">★★★☆☆ (3 Stars - Good)</option>
                        <option value="2">★★☆☆☆ (2 Stars - Fair)</option>
                        <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                    </select>
                </div>

                <!-- 5. SORT ORDER -->
                <div class="rv-form-group">
                    <label class="rv-label">
                        <span>Sort Order</span>
                        <span class="rv-loc-badge">Carousel Sequence</span>
                    </label>
                    <input type="number" name="sort_order" id="modalSortOrder" class="rv-input" value="0">
                    <p class="rv-hint">Lower numbers appear first in the card cycle.</p>
                </div>
            </div>

            <!-- 6. AVATAR PHOTOGRAPH -->
            <div class="rv-form-group">
                <label class="rv-label">
                    <span>Avatar Photograph</span>
                    <span class="rv-loc-badge">Card: Top-Left Thumbnail</span>
                </label>
                <div style="display:flex; gap:16px; align-items:center;">
                    <img src="../assets/images/placeholder.jpg" id="modalPreviewThumb" style="width:58px; height:58px; border-radius:10px; object-fit:cover; border:1.5px solid var(--gold-base);" onerror="this.src='../assets/images/placeholder.jpg'">
                    <div style="flex:1;">
                        <input type="file" name="avatar_image_file" class="rv-input" accept="image/*" onchange="previewAvatar(this)">
                        <div style="margin-top:6px;">
                            <span style="font-size:11px; color:#888;">Or existing path / URL:</span>
                            <input type="text" name="avatar_image_url" id="modalImageUrl" class="rv-input" style="padding:6px 10px; font-size:12px; margin-top:4px;" placeholder="../landing/assets/images/IMG_0685.jpg" oninput="updateAvatarFromUrl(this.value)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. ACTIVE TOGGLE -->
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                <input type="checkbox" name="is_active" id="modalIsActive" value="1" checked style="width:18px; height:18px; accent-color:var(--gold-base);">
                <label for="modalIsActive" style="font-size:13.5px; font-weight:600; color:var(--text-primary); cursor:pointer;">
                    Active on Website (Show this card in the About page staggered carousel)
                </label>
            </div>

            <!-- LIVE CARD PREVIEW SIMULATION -->
            <div class="rv-preview-box">
                <div class="rv-preview-title">
                    <span>👁️ Live Storefront Card Simulation</span>
                </div>
                <div class="rv-mockup-card" id="mockupCard">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                        <img src="../assets/images/placeholder.jpg" id="mockupAvatar" style="width:48px; height:48px; border-radius:8px; object-fit:cover; border:1px solid #d4af37; box-shadow:2px 2px 0px #d4af37;">
                        <div>
                            <div style="font-weight:700; font-size:14px; color:#fff;" id="mockupAuthor">Author Name</div>
                            <div style="font-size:11px; color:#d4af37;" id="mockupByline">Kolkata (Verified Patron)</div>
                        </div>
                    </div>
                    <div style="color:#d4af37; font-size:13px; margin-bottom:10px;" id="mockupStars">★★★★★</div>
                    <div style="font-size:12.5px; line-height:1.5; color:#cbd5e1; font-style:italic;" id="mockupQuote">
                        “The craftsmanship of SSRINI is simply beyond compare...”
                    </div>
                </div>
            </div>

            <!-- SUBMIT BUTTONS -->
            <div style="display:flex; justify-content:flex-end; gap:12px; border-top:1px solid var(--border-color); padding-top:18px;">
                <button type="button" class="rv-filter-pill" onclick="closeModal()" style="padding:10px 20px; font-size:14px;">Cancel</button>
                <button type="submit" class="rv-btn-gold" style="padding:10px 24px;">
                    <span id="modalSubmitText">Save Testimonial</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<span>＋ Add Testimonial Card</span>';
    document.getElementById('modalSubmitText').textContent = 'Add Testimonial';
    document.getElementById('modalReviewId').value = '0';
    document.getElementById('modalAuthorName').value = '';
    document.getElementById('modalAuthorLocation').value = 'Kolkata (Verified Patron)';
    document.getElementById('modalRating').value = '5';
    document.getElementById('modalReviewText').value = '';
    document.getElementById('modalSortOrder').value = '0';
    document.getElementById('modalIsActive').checked = true;
    document.getElementById('modalExistingImage').value = '../landing/assets/images/IMG_0685.jpg';
    document.getElementById('modalImageUrl').value = '../landing/assets/images/IMG_0685.jpg';
    document.getElementById('modalPreviewThumb').src = '../../landing/assets/images/IMG_0685.jpg';
    document.getElementById('mockupAvatar').src = '../../landing/assets/images/IMG_0685.jpg';

    updateLivePreview();
    document.getElementById('reviewModalOverlay').classList.add('active');
}

function openEditModal(review) {
    document.getElementById('modalTitle').innerHTML = '<span>✏️ Edit Testimonial Card</span>';
    document.getElementById('modalSubmitText').textContent = 'Update Testimonial';
    document.getElementById('modalReviewId').value = review.id;
    document.getElementById('modalAuthorName').value = review.author_name || '';
    document.getElementById('modalAuthorLocation').value = review.author_location || '';
    document.getElementById('modalRating').value = review.rating || '5';
    document.getElementById('modalReviewText').value = review.review_text || '';
    document.getElementById('modalSortOrder').value = review.sort_order || '0';
    document.getElementById('modalIsActive').checked = (parseInt(review.is_active) === 1);
    document.getElementById('modalExistingImage').value = review.avatar_image || '';
    document.getElementById('modalImageUrl').value = review.avatar_image || '';

    var displaySrc = review.avatar_image;
    if (displaySrc && displaySrc.indexOf('../landing/') === 0) {
        displaySrc = '../../' + displaySrc.substring(3);
    } else if (displaySrc && displaySrc.indexOf('uploads/') === 0) {
        displaySrc = '../' + displaySrc;
    }
    document.getElementById('modalPreviewThumb').src = displaySrc || '../assets/images/placeholder.jpg';
    document.getElementById('mockupAvatar').src = displaySrc || '../assets/images/placeholder.jpg';

    updateLivePreview();
    document.getElementById('reviewModalOverlay').classList.add('active');
}

function closeModal() {
    document.getElementById('reviewModalOverlay').classList.remove('active');
}

function updateLivePreview() {
    var name = document.getElementById('modalAuthorName').value || 'Author Name';
    var byline = document.getElementById('modalAuthorLocation').value || 'Location (Verified Patron)';
    var quote = document.getElementById('modalReviewText').value || 'Your testimonial quote here...';
    var rating = parseInt(document.getElementById('modalRating').value) || 5;

    document.getElementById('mockupAuthor').textContent = name;
    document.getElementById('mockupByline').textContent = byline;
    document.getElementById('mockupQuote').textContent = '“' + quote + '”';

    var starsStr = '';
    for (var i = 0; i < rating; i++) starsStr += '★';
    for (var j = rating; j < 5; j++) starsStr += '☆';
    document.getElementById('mockupStars').textContent = starsStr;
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('modalPreviewThumb').src = e.target.result;
            document.getElementById('mockupAvatar').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateAvatarFromUrl(val) {
    if (!val) return;
    var displaySrc = val;
    if (displaySrc.indexOf('../landing/') === 0) {
        displaySrc = '../../' + displaySrc.substring(3);
    } else if (displaySrc.indexOf('uploads/') === 0) {
        displaySrc = '../' + displaySrc;
    }
    document.getElementById('modalPreviewThumb').src = displaySrc;
    document.getElementById('mockupAvatar').src = displaySrc;
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

        </div><!-- /.rv-container -->
        </div><!-- /.page-content -->
    </main><!-- /.main-area -->
</div><!-- /.admin-wrapper -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>