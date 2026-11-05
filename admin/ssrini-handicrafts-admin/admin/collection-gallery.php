<?php
/**
 * =========================================================
 * SSRINI HANDCRAFTS — ADMIN
 * COLLECTION PAGE GALLERY MANAGER
 * =========================================================
 * Manages two zones:
 *   hero  → 1 centerpiece image (the expanding necklace card)
 *   orbit → pool of images used in the 25-card radial orbit
 *           AND the 3 infinite-scroll spread rows
 *
 * Table: collection_images
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'Collection Gallery';

function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ── Ensure table exists (idempotent) ── */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS collection_images (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        zone        ENUM('hero','orbit') NOT NULL DEFAULT 'orbit',
        image_path  VARCHAR(500) NOT NULL,
        alt_text    VARCHAR(255) NOT NULL DEFAULT '',
        title       VARCHAR(255) NOT NULL DEFAULT '',
        description TEXT,
        sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        is_active   TINYINT(1) NOT NULL DEFAULT 1,
        created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable) {}

/* ── Upload helper ── */
$uploadDir = dirname(__DIR__) . '/uploads/collection/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$allowedExts = ['jpg','jpeg','png','webp','gif'];

function handleUpload(array $file, string $uploadDir, array $allowedExts): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) return false;
    if ($file['size'] > 12 * 1024 * 1024) return false;
    $filename = 'col_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return 'uploads/collection/' . $filename;
    }
    return false;
}

/* ── Flash messages ── */
$success = $_SESSION['cg_success'] ?? null; unset($_SESSION['cg_success']);
$error   = $_SESSION['cg_error']   ?? null; unset($_SESSION['cg_error']);

/* ====================================================================
   POST HANDLER
   ==================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $_SESSION['cg_error'] = 'Security validation failed (CSRF). Please try again.';
        header('Location: collection-gallery.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    /* ── ADD new image ── */
    if ($action === 'add') {
        $zone  = in_array($_POST['zone'] ?? '', ['hero', 'orbit', 'spread'], true) ? $_POST['zone'] : 'orbit';
        $title = trim($_POST['title'] ?? '');
        $alt   = trim($_POST['alt_text'] ?? $title);
        $desc  = trim($_POST['description'] ?? '');
        $path  = trim($_POST['image_url'] ?? '');

        if (!empty($_FILES['image_file']['name'])) {
            $uploaded = handleUpload($_FILES['image_file'], $uploadDir, $allowedExts);
            if ($uploaded) $path = $uploaded;
            else { $_SESSION['cg_error'] = 'Upload failed or invalid file type.'; header('Location: collection-gallery.php'); exit; }
        }

        if (!$path) { $_SESSION['cg_error'] = 'Please provide an image.'; header('Location: collection-gallery.php'); exit; }

        // If adding hero and one already exists, replace it
        if ($zone === 'hero') {
            $count = $pdo->query("SELECT COUNT(*) FROM collection_images WHERE zone='hero'")->fetchColumn();
            if ($count > 0) {
                $stmt = $pdo->prepare("UPDATE collection_images SET image_path=?,alt_text=?,title=?,description=?,updated_at=NOW() WHERE zone='hero' LIMIT 1");
                $stmt->execute([$path, $alt, $title, $desc]);
                $_SESSION['cg_success'] = 'Hero centerpiece image updated!';
                header('Location: collection-gallery.php'); exit;
            }
        }

        $sortStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM collection_images WHERE zone = ?");
        $sortStmt->execute([$zone]);
        $sort = (int)$sortStmt->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO collection_images (zone,image_path,alt_text,title,description,sort_order) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$zone, $path, $alt, $title, $desc, $sort]);
        $_SESSION['cg_success'] = 'Image added successfully!';
        header('Location: collection-gallery.php'); exit;
    }

    /* ── EDIT existing image ── */
    if ($action === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $alt   = trim($_POST['alt_text'] ?? $title);
        $desc  = trim($_POST['description'] ?? '');
        $path  = trim($_POST['image_url'] ?? '');

        if (!empty($_FILES['image_file']['name'])) {
            $uploaded = handleUpload($_FILES['image_file'], $uploadDir, $allowedExts);
            if ($uploaded) $path = $uploaded;
        }

        if ($path) {
            $stmt = $pdo->prepare("UPDATE collection_images SET image_path=?,alt_text=?,title=?,description=?,updated_at=NOW() WHERE id=?");
            $stmt->execute([$path, $alt, $title, $desc, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE collection_images SET alt_text=?,title=?,description=?,updated_at=NOW() WHERE id=?");
            $stmt->execute([$alt, $title, $desc, $id]);
        }
        $_SESSION['cg_success'] = 'Image updated!';
        header('Location: collection-gallery.php'); exit;
    }

    /* ── DELETE ── */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM collection_images WHERE id=?")->execute([$id]);
        $_SESSION['cg_success'] = 'Image removed from gallery.';
        header('Location: collection-gallery.php'); exit;
    }

    /* ── TOGGLE active ── */
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE collection_images SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        header('Location: collection-gallery.php'); exit;
    }

    /* ── REORDER (JSON from drag-drop) ── */
    if ($action === 'reorder') {
        $ids = json_decode($_POST['order'] ?? '[]', true);
        if (is_array($ids)) {
            $stmt = $pdo->prepare("UPDATE collection_images SET sort_order=? WHERE id=?");
            foreach ($ids as $i => $id) $stmt->execute([$i + 1, (int)$id]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
}

/* ── Load all images ── */
$heroRow    = $pdo->query("SELECT * FROM collection_images WHERE zone='hero' LIMIT 1")->fetch() ?: null;
$orbitImages = $pdo->query("SELECT * FROM collection_images WHERE zone='orbit' ORDER BY sort_order ASC")->fetchAll();
$orbitCount  = count($orbitImages);

/* ── Build image preview URL (relative to doc-root) ── */
function imgUrl(string $path): string {
    if (str_starts_with($path, 'http')) return $path;
    return '/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | Ssrini Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
/* ─────────────────────────── PAGE VARS ─────────────────────────── */
:root {
    --gold: #d4af37;
    --gold-dk: #aa8c2c;
    --gold-lt: #f5e6a3;
    --radius: 12px;
    --shadow: 0 2px 12px rgba(0,0,0,.08);
    --border: #e5e7eb;
}

/* ─────────────────────────── LAYOUT ─────────────────────────── */
.cg-container { max-width: 1100px; }
.cg-header {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 16px; margin-bottom: 28px;
}
.cg-title-area h1 { font-size: 22px; font-weight: 700; color: #111; margin: 0; }
.cg-title-area p  { color: #64748b; font-size: 13px; margin: 4px 0 0; }
.cg-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fef9e7; border: 1px solid #f5e6a3;
    color: #92710a; font-size: 11px; font-weight: 700;
    padding: 3px 10px; border-radius: 999px; text-transform: uppercase; letter-spacing: .8px;
}

/* ─────────────────────────── SECTION CARDS ─────────────────────────── */
.cg-section {
    background: #fff; border: 1px solid var(--border);
    border-radius: var(--radius); box-shadow: var(--shadow);
    margin-bottom: 28px; overflow: hidden;
}
.cg-section-head {
    display: flex; justify-content: space-between; align-items: center;
    padding: 18px 24px; border-bottom: 1px solid var(--border);
    background: #fafafa;
}
.cg-section-head h2 { font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; }
.cg-section-head p  { font-size: 12px; color: #64748b; margin: 2px 0 0; }
.cg-section-body { padding: 24px; }

/* ─────────────────────────── HERO ZONE ─────────────────────────── */
.hero-zone {
    display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;
}
.hero-preview {
    flex-shrink: 0; width: 200px; height: 200px;
    border-radius: 10px; overflow: hidden; border: 2px solid var(--gold);
    background: #f8f9fa; display: flex; align-items: center; justify-content: center;
}
.hero-preview img { width: 100%; height: 100%; object-fit: cover; }
.hero-preview .no-img { color: #94a3b8; font-size: 12px; text-align: center; }
.hero-meta { flex: 1; min-width: 240px; }
.hero-meta h3 { font-size: 14px; font-weight: 700; margin: 0 0 4px; color: #111; }
.hero-meta p  { font-size: 12px; color: #64748b; margin: 0 0 12px; }

/* ─────────────────────────── ORBIT GRID ─────────────────────────── */
.orbit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
}
.orbit-card {
    border: 1px solid var(--border); border-radius: 10px;
    overflow: hidden; background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    transition: box-shadow .2s, transform .2s;
    cursor: grab;
    position: relative;
}
.orbit-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.12); transform: translateY(-2px); }
.orbit-card.drag-over { border-color: var(--gold); box-shadow: 0 0 0 2px var(--gold-lt); }
.orbit-card-img {
    width: 100%; aspect-ratio: 1/1; object-fit: cover;
    background: #f1f5f9;
}
.orbit-card-body { padding: 10px; }
.orbit-card-title { font-size: 11px; font-weight: 700; color: #1e293b; margin-bottom: 8px; line-height: 1.3; }
.orbit-card-actions { display: flex; gap: 6px; }
.orbit-sort-handle {
    position: absolute; top: 6px; left: 6px;
    background: rgba(255,255,255,.85); border-radius: 6px;
    padding: 3px 5px; font-size: 12px; cursor: grab; color: #64748b;
}
.orbit-status-badge {
    position: absolute; top: 6px; right: 6px;
    padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 700;
}
.badge-live   { background: #d1fae5; color: #065f46; }
.badge-hidden { background: #fee2e2; color: #991b1b; }
.orbit-sort-num {
    position: absolute; bottom: 6px; left: 6px;
    background: rgba(0,0,0,.45); color: #fff;
    font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px;
}

/* ─────────────────────────── BUTTONS ─────────────────────────── */
.btn-gold {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--gold); color: #fff; font-size: 13px;
    font-weight: 700; border: none; border-radius: 8px;
    padding: 9px 18px; cursor: pointer; transition: background .2s;
    text-decoration: none;
}
.btn-gold:hover { background: var(--gold-dk); }
.btn-sm {
    font-size: 11px; padding: 5px 10px; border-radius: 6px;
    font-weight: 600; border: 1px solid transparent; cursor: pointer; transition: all .15s;
}
.btn-edit  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.btn-edit:hover { background: #dbeafe; }
.btn-del   { background: #fff5f5; color: #dc2626; border-color: #fca5a5; }
.btn-del:hover { background: #fee2e2; }
.btn-toggle-on  { background: #f0fdf4; color: #15803d; border-color: #86efac; }
.btn-toggle-on:hover { background: #dcfce7; }
.btn-toggle-off { background: #fef9c3; color: #854d0e; border-color: #fde047; }
.btn-toggle-off:hover { background: #fef08a; }

/* ─────────────────────────── MODAL ─────────────────────────── */
.cg-modal-bg {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); z-index: 9999;
    align-items: center; justify-content: center;
}
.cg-modal-bg.open { display: flex; }
.cg-modal {
    background: #fff; border-radius: 14px; padding: 28px;
    width: min(520px, 95vw); box-shadow: 0 20px 60px rgba(0,0,0,.2);
    max-height: 90vh; overflow-y: auto;
}
.cg-modal h3 { font-size: 16px; font-weight: 700; margin: 0 0 20px; color: #111; }
.cg-field { margin-bottom: 16px; }
.cg-field label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
.cg-field input[type=text],
.cg-field textarea,
.cg-field select { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 12px; font-size: 13px; color: #111; background: #fafafa; box-sizing: border-box; }
.cg-field textarea { height: 80px; resize: vertical; }
.cg-field input[type=text]:focus,
.cg-field textarea:focus { outline: none; border-color: var(--gold); background: #fff; }
.cg-field-row { display: flex; gap: 12px; }
.cg-field-row .cg-field { flex: 1; }
.upload-preview { width: 100%; height: 140px; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f8fafc; margin-top: 8px; cursor: pointer; position: relative; }
.upload-preview img { width: 100%; height: 100%; object-fit: cover; }
.upload-preview span { color: #94a3b8; font-size: 12px; }
.upload-preview input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.btn-cancel { background: #f1f5f9; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #e2e8f0; }

/* ─────────────────────────── FLASH ─────────────────────────── */
.flash { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
.flash-ok  { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.flash-err { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* ─────────────────────────── MIN IMAGES WARNING ─────────────────────────── */
.cg-info-bar {
    display: flex; align-items: center; gap: 10px;
    background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 8px; padding: 10px 16px; margin-bottom: 16px; font-size: 12px; color: #92400e;
}
.cg-info-bar.ok { background: #f0fdf4; border-color: #86efac; color: #14532d; }
</style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content" style="padding: 24px;">
        <div class="cg-container">

            <!-- HEADER -->
            <div class="cg-header">
                <div class="cg-title-area">
                    <h1>Collection Gallery
                        <span class="cg-badge">Storefront: Collection Page</span>
                    </h1>
                    <p>Manage the hero centerpiece image and the orbit ring / scrolling row image pool for the Collection page.</p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="http://127.0.0.1:8000/ecom/collection/index.php" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;text-decoration:none;"
                       onmouseover="this.style.borderColor='#d4af37';this.style.color='#aa8c2c';"
                       onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';">
                        👁️ Preview Collection Page
                    </a>
                </div>
            </div>

            <?php if ($success): ?><div class="flash flash-ok">✓ <?= e($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="flash flash-err">✗ <?= e($error) ?></div><?php endif; ?>

            <!-- ══════════════════════════════════════════════
                 ZONE 1: HERO CENTERPIECE IMAGE
                 ══════════════════════════════════════════════ -->
            <div class="cg-section">
                <div class="cg-section-head">
                    <div>
                        <h2>🎯 Zone 1 — Hero Centerpiece</h2>
                        <p>The 1 large image that sits in the center before the orbit expands. Also shown as the middle-row anchor when the spread rows open.</p>
                    </div>
                    <button class="btn-gold" onclick="openAddModal('hero')">
                        <span>✎</span> <?= $heroRow ? 'Replace Hero Image' : 'Set Hero Image' ?>
                    </button>
                </div>
                <div class="cg-section-body">
                    <div class="hero-zone">
                        <div class="hero-preview">
                            <?php if ($heroRow): ?>
                                <img src="<?= e(imgUrl($heroRow['image_path'])) ?>" alt="<?= e($heroRow['alt_text']) ?>">
                            <?php else: ?>
                                <div class="no-img">No hero image set</div>
                            <?php endif; ?>
                        </div>
                        <div class="hero-meta">
                            <?php if ($heroRow): ?>
                                <h3><?= e($heroRow['title'] ?: 'Untitled') ?></h3>
                                <p><?= e($heroRow['description'] ?: 'No description') ?></p>
                                <p style="font-size:11px;color:#94a3b8;margin-top:8px;">Path: <?= e($heroRow['image_path']) ?></p>
                                <div style="display:flex;gap:8px;margin-top:12px;">
                                    <button class="btn-sm btn-edit" onclick="openEditModal(<?= $heroRow['id'] ?>, 'hero', <?= htmlspecialchars(json_encode($heroRow), ENT_QUOTES) ?>)">✎ Edit Details</button>
                                    <form method="post" onsubmit="return confirm('Remove hero image?');" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $heroRow['id'] ?>">
                                        <button type="submit" class="btn-sm btn-del">🗑 Remove</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <p style="color:#94a3b8;">No hero image set. Click "Set Hero Image" to add one.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════
                 ZONE 2: ORBIT & ROWS IMAGE POOL
                 ══════════════════════════════════════════════ -->
            <div class="cg-section">
                <div class="cg-section-head">
                    <div>
                        <h2>🔮 Zone 2 — Orbit Ring & Scrolling Rows</h2>
                        <p>Same image pool used for both the 25-card radial orbit AND the 3 infinite-scroll spread rows. Drag cards to reorder. Minimum 8 recommended.</p>
                    </div>
                    <button class="btn-gold" onclick="openAddModal('orbit')">
                        <span style="font-size:18px;line-height:1;">＋</span> Add Image
                    </button>
                </div>
                <div class="cg-section-body">

                    <!-- Min images status bar -->
                    <div class="cg-info-bar <?= $orbitCount >= 8 ? 'ok' : '' ?>">
                        <?php if ($orbitCount >= 8): ?>
                            ✓ <?= $orbitCount ?> images active — meets the 8-image minimum for clean orbit display.
                        <?php else: ?>
                            ⚠ <?= $orbitCount ?>/8 images — at least 8 recommended for the orbit ring to look full without obvious repeats.
                        <?php endif; ?>
                    </div>

                    <?php if (empty($orbitImages)): ?>
                        <p style="color:#94a3b8;text-align:center;padding:40px 0;">No orbit images yet. Add your first image above.</p>
                    <?php else: ?>
                    <div class="orbit-grid" id="orbitGrid">
                        <?php foreach ($orbitImages as $i => $img): ?>
                        <div class="orbit-card" draggable="true" data-id="<?= $img['id'] ?>">
                            <span class="orbit-sort-handle" title="Drag to reorder">⠿</span>
                            <span class="orbit-status-badge <?= $img['is_active'] ? 'badge-live' : 'badge-hidden' ?>">
                                <?= $img['is_active'] ? 'Live' : 'Hidden' ?>
                            </span>
                            <span class="orbit-sort-num">#<?= $i + 1 ?></span>
                            <img class="orbit-card-img" src="<?= e(imgUrl($img['image_path'])) ?>" alt="<?= e($img['alt_text']) ?>" loading="lazy">
                            <div class="orbit-card-body">
                                <div class="orbit-card-title"><?= e($img['title'] ?: 'Untitled') ?></div>
                                <div class="orbit-card-actions">
                                    <button class="btn-sm btn-edit" onclick="openEditModal(<?= $img['id'] ?>, 'orbit', <?= htmlspecialchars(json_encode($img), ENT_QUOTES) ?>)">✎</button>
                                    <form method="post" onsubmit="return confirm('Delete this image?');" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                        <button type="submit" class="btn-sm btn-del">🗑</button>
                                    </form>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                        <button type="submit" class="btn-sm <?= $img['is_active'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>"><?= $img['is_active'] ? '● Live' : '○ Hidden' ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div><!-- /cg-container -->
        </div><!-- /page-content -->
    </main>
</div>

<!-- ══════════════════════════════════════════════════════
     ADD / EDIT MODAL
     ══════════════════════════════════════════════════════ -->
<div class="cg-modal-bg" id="cgModal">
    <div class="cg-modal">
        <h3 id="cgModalTitle">Add Image</h3>
        <form method="post" enctype="multipart/form-data" id="cgModalForm">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <input type="hidden" name="action" id="fAction" value="add">
            <input type="hidden" name="id"     id="fId"     value="">
            <input type="hidden" name="zone"   id="fZone"   value="orbit">

            <div class="cg-field">
                <label>Zone</label>
                <div id="fZoneDisplay" style="font-size:13px;font-weight:600;color:#374151;padding:6px 0;"></div>
            </div>

            <!-- Upload / Preview -->
            <div class="cg-field">
                <label>Image <span style="font-weight:400;color:#94a3b8;">(upload file or paste URL below)</span></label>
                <label class="upload-preview" id="uploadPreviewArea">
                    <img id="uploadPreviewImg" src="" alt="" style="display:none;">
                    <span id="uploadPreviewText">Click to upload image</span>
                    <input type="file" name="image_file" id="fImageFile" accept="image/*" onchange="previewFile(this)">
                </label>
            </div>

            <div class="cg-field">
                <label>Or Paste Image URL</label>
                <input type="text" name="image_url" id="fImageUrl" placeholder="https://... or relative path like ecom/collection/assets/..." oninput="previewUrl(this.value)">
            </div>

            <div class="cg-field">
                <label>Title <span style="font-weight:400;color:#94a3b8;">(shown in lightbox)</span></label>
                <input type="text" name="title" id="fTitle" placeholder="e.g. Royal Polki Heritage Collar">
            </div>

            <div class="cg-field">
                <label>Alt Text <span style="font-weight:400;color:#94a3b8;">(accessibility)</span></label>
                <input type="text" name="alt_text" id="fAltText" placeholder="Brief description of the image">
            </div>

            <div class="cg-field">
                <label>Description <span style="font-weight:400;color:#94a3b8;">(shown in lightbox popup)</span></label>
                <textarea name="description" id="fDesc" placeholder="A short description of this piece..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-gold" id="fSubmitBtn">Save Image</button>
            </div>
        </form>
    </div>
</div>

<script>
/* ── Modal controls ── */
const modal = document.getElementById('cgModal');

function openAddModal(zone) {
    document.getElementById('cgModalTitle').textContent = zone === 'hero' ? 'Set Hero Centerpiece Image' : 'Add Orbit / Row Image';
    document.getElementById('fAction').value = 'add';
    document.getElementById('fId').value = '';
    document.getElementById('fZone').value = zone;
    document.getElementById('fZoneDisplay').textContent = zone === 'hero' ? '🎯 Hero Centerpiece (1 image, centers the orbit before expansion)' : '🔮 Orbit & Rows pool (used in both the ring and the 3 scrolling rows)';
    document.getElementById('fTitle').value = '';
    document.getElementById('fAltText').value = '';
    document.getElementById('fDesc').value = '';
    document.getElementById('fImageUrl').value = '';
    resetPreview();
    document.getElementById('fSubmitBtn').textContent = 'Save Image';
    modal.classList.add('open');
}

function openEditModal(id, zone, data) {
    document.getElementById('cgModalTitle').textContent = 'Edit Image';
    document.getElementById('fAction').value = 'edit';
    document.getElementById('fId').value = id;
    document.getElementById('fZone').value = zone;
    document.getElementById('fZoneDisplay').textContent = zone === 'hero' ? '🎯 Hero Centerpiece' : '🔮 Orbit & Rows pool';
    document.getElementById('fTitle').value = data.title || '';
    document.getElementById('fAltText').value = data.alt_text || '';
    document.getElementById('fDesc').value = data.description || '';
    document.getElementById('fImageUrl').value = data.image_path || '';
    previewUrl('/' + (data.image_path || '').replace(/^\//, ''));
    document.getElementById('fSubmitBtn').textContent = 'Update Image';
    modal.classList.add('open');
}

function closeModal() {
    modal.classList.remove('open');
}

modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

/* ── Image preview ── */
function previewFile(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => showPreview(e.target.result);
    reader.readAsDataURL(file);
    document.getElementById('fImageUrl').value = '';
}

function previewUrl(url) {
    if (!url) { resetPreview(); return; }
    const fullUrl = url.startsWith('http') ? url : '/' + url.replace(/^\//, '');
    showPreview(fullUrl);
}

function showPreview(src) {
    const img = document.getElementById('uploadPreviewImg');
    const txt = document.getElementById('uploadPreviewText');
    img.src = src;
    img.style.display = 'block';
    txt.style.display = 'none';
}

function resetPreview() {
    const img = document.getElementById('uploadPreviewImg');
    const txt = document.getElementById('uploadPreviewText');
    img.src = '';
    img.style.display = 'none';
    txt.style.display = 'block';
}

/* ── Drag-to-reorder ── */
const grid = document.getElementById('orbitGrid');
if (grid) {
    let draggedEl = null;

    grid.addEventListener('dragstart', e => {
        draggedEl = e.target.closest('.orbit-card');
        draggedEl.style.opacity = '0.5';
    });

    grid.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('.orbit-card');
        if (!target || target === draggedEl) return;
        grid.querySelectorAll('.orbit-card').forEach(c => c.classList.remove('drag-over'));
        target.classList.add('drag-over');
        const rect = target.getBoundingClientRect();
        const mid  = rect.left + rect.width / 2;
        if (e.clientX < mid) {
            grid.insertBefore(draggedEl, target);
        } else {
            grid.insertBefore(draggedEl, target.nextSibling);
        }
    });

    grid.addEventListener('dragend', () => {
        if (draggedEl) draggedEl.style.opacity = '1';
        draggedEl = null;
        grid.querySelectorAll('.orbit-card').forEach(c => c.classList.remove('drag-over'));
        saveOrder();
    });

    function saveOrder() {
        const ids = [...grid.querySelectorAll('.orbit-card')].map(c => c.dataset.id);
        /* Update visible numbers */
        grid.querySelectorAll('.orbit-sort-num').forEach((el, i) => el.textContent = '#' + (i + 1));
        fetch('collection-gallery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=reorder&order=' + encodeURIComponent(JSON.stringify(ids)) + '&csrf_token=' + encodeURIComponent('<?= e(generateCsrfToken()) ?>')
        });
    }
}
</script>

</body>
</html>
