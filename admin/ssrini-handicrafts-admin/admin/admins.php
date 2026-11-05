<?php
/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * ADMINISTRATORS MANAGEMENT PAGE
 * =========================================================
 *
 * File: admin/admins.php
 * Purpose: Manage admin accounts — list, add, toggle status.
 * Design: Uses admin.css design system (matches customers.php)
 * =========================================================
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if(function_exists('requireAdminLogin')) {
    requireAdminLogin();
}

$csrfToken = function_exists('generateCsrfToken') ? generateCsrfToken() : '';

if(!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if(!function_exists('getInitials')) {
    function getInitials(string $name): string {
        $name = trim($name);
        if($name === '') return 'A';
        $parts = preg_split('/\s+/', $name);
        if(count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

if(!function_exists('formatDateTime')) {
    function formatDateTime($value): string {
        if(empty($value)) return '—';
        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d M Y', $timestamp) : e($value);
    }
}

$pageTitle = 'Administrators';
$admins = [];
$totalAdmins = 0;
$errorMessage = null;

try {
    $stmt = $pdo->query("SELECT id, name, email, role, status, created_at FROM admins ORDER BY name ASC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalAdmins = count($admins);
} catch (PDOException $ex) {
    error_log('Admins page database error: ' . $ex->getMessage());
    $errorMessage = 'Unable to load administrator data. Please check the database connection.';
} catch (Throwable $ex) {
    error_log('Admins page error: ' . $ex->getMessage());
    $errorMessage = 'An unexpected error occurred while loading administrators.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Administrators Management - Ssrini Handicrafts Admin Panel">
    <title><?=e($pageTitle)?> | Ssrini Handicrafts Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <!-- Favicon Suite -->
    <link rel="shortcut icon" href="../../../favicon.ico?v=2">
    <link rel="icon" type="image/x-icon" href="../../../favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="../../../ecom/assets/favicon/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="../../../ecom/assets/favicon/favicon-16x16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="../../../ecom/assets/favicon/apple-touch-icon.png?v=2">

    <style>
        /* ── Admins-specific styles that supplement admin.css ── */
        .admins-container{width:100%;max-width:1500px;margin:0 auto}
        .admins-header{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;margin-bottom:22px}
        .admins-title h1{margin:0;font-size:24px;font-weight:700;color:var(--text-primary,#15182b)}
        .admins-title p{margin:4px 0 0;color:var(--text-muted,#98a0b3);font-size:13px}

        .admins-controls{background:var(--surface,#fff);border:1px solid var(--border-light,#f0f1f6);border-radius:var(--radius-lg,12px);padding:16px;display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;margin-bottom:20px;box-shadow:0 4px 12px rgba(0,0,0,.03)}
        .admins-count-badge{font-size:13px;color:var(--text-muted,#98a0b3);font-weight:600}

        .btn-add-admin{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:10px;border:none;background:var(--brand-gradient,linear-gradient(135deg,#7357e8,#ef4f91));color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(115,87,232,.25);transition:transform .2s,box-shadow .2s}
        .btn-add-admin:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(115,87,232,.35)}
        .btn-add-admin:active{transform:translateY(1px)}
        .btn-add-admin svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round}

        .admins-card{background:var(--surface,#fff);border:1px solid var(--border-light,#f0f1f6);border-radius:var(--radius-lg,14px);box-shadow:0 5px 20px rgba(0,0,0,.04);overflow:hidden}
        .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}

        .admins-table{width:100%;border-collapse:collapse;text-align:left;min-width:750px}
        .admins-table th{padding:14px 18px;background:var(--surface-soft,#fbfbfe);color:var(--text-muted,#98a0b3);font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700;border-bottom:1px solid var(--border-light,#f0f1f6);white-space:nowrap}
        .admins-table td{padding:16px 18px;border-bottom:1px solid var(--border-light,#f0f1f6);color:var(--text-secondary,#667085);font-size:13px;vertical-align:middle}
        .admins-table tbody tr{transition:background .15s}
        .admins-table tbody tr:hover{background:var(--surface-hover,#f9f7ff)}
        .admins-table tbody tr:last-child td{border-bottom:none}

        .admin-info-cell{display:flex;align-items:center;gap:12px}
        .admin-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#7357e8,#ef4f91);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;letter-spacing:.5px}
        .admin-name-box{display:flex;flex-direction:column;min-width:0}
        .admin-name{color:var(--text-primary,#15182b);font-weight:700;font-size:14px}
        .admin-id-sub{font-size:11px;color:var(--text-muted,#98a0b3)}

        .role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
        .role-admin{background:var(--primary-light,#f0ecff);color:var(--primary,#7357e8)}
        .role-manager{background:var(--accent-blue-light,#edf4ff);color:var(--accent-blue,#5b8def)}

        .status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em}
        .status-active{background:var(--success-bg,#eafaf3);color:var(--success,#21a875)}
        .status-inactive{background:var(--danger-bg,#fff0f1);color:var(--danger,#e84d5b)}
        .status-dot{width:6px;height:6px;border-radius:50%;display:inline-block}
        .status-active .status-dot{background:var(--success,#21a875)}
        .status-inactive .status-dot{background:var(--danger,#e84d5b)}

        .admins-empty-state{padding:60px 20px;text-align:center;color:var(--text-muted,#98a0b3)}
        .admins-empty-state h3{margin:0 0 8px;color:var(--text-primary,#15182b);font-size:18px}
        .admins-empty-state p{margin:0;font-size:13px}

        .error-alert{background:var(--danger-bg,#fff0f1);border:1px solid var(--danger-border,#fecaca);color:var(--danger,#e84d5b);padding:14px 18px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px}

        .actions-cell{display:flex;align-items:center;gap:8px;white-space:nowrap}
        .btn-action-toggle{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:6px;font-weight:600;font-size:12px;border:none;cursor:pointer;transition:all .2s}
        .btn-deactivate{background:rgba(232,77,91,.1);color:#e84d5b}
        .btn-deactivate:hover{background:#e84d5b;color:#fff}
        .btn-activate{background:rgba(33,168,117,.1);color:#21a875}
        .btn-activate:hover{background:#21a875;color:#fff}

        /* ── Modal form submit button ── */
        .btn-create-admin{width:100%;min-height:42px;border:none;border-radius:8px;background:var(--brand-gradient,linear-gradient(135deg,#7357e8,#ef4f91));color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(115,87,232,.2);transition:transform .2s,box-shadow .2s}
        .btn-create-admin:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(115,87,232,.3)}
        .btn-create-admin:disabled{opacity:.6;cursor:not-allowed;transform:none}

        /* ── Toast notification ── */
        .toast{position:fixed;top:20px;right:20px;z-index:9999;padding:14px 20px;border-radius:10px;font-size:13px;font-weight:600;color:#fff;box-shadow:0 8px 30px rgba(0,0,0,.15);transform:translateX(120%);transition:transform .35s cubic-bezier(.22,1,.36,1);max-width:360px}
        .toast.show{transform:translateX(0)}
        .toast-success{background:var(--success,#21a875)}
        .toast-error{background:var(--danger,#e84d5b)}

        @media(max-width:768px){
            .admins-header{flex-direction:column;align-items:flex-start}
            .admins-controls{flex-direction:column;align-items:flex-start}
        }
    </style>
</head>

<body>
<div class="admin-wrapper">

    <?php
    $sidebarFile = dirname(__DIR__) . '/includes/sidebar.php';
    if(file_exists($sidebarFile)) require_once $sidebarFile;
    ?>

    <main class="main-area">

        <?php
        $headerFile = dirname(__DIR__) . '/includes/header.php';
        if(file_exists($headerFile)) require_once $headerFile;
        ?>

        <div class="page-content">
            <div class="admins-container">

                <!-- ── Page Header ── -->
                <div class="admins-header">
                    <div class="admins-title">
                        <h1>Administrators</h1>
                        <p>Manage admin accounts, roles and access privileges.</p>
                    </div>
                    <button class="btn-add-admin" id="openAddAdminModal">
                        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add New Admin
                    </button>
                </div>

                <!-- ── Error Alert ── -->
                <?php if($errorMessage !== null): ?>
                    <div class="error-alert">⚠️ <?=e($errorMessage)?></div>
                <?php endif; ?>

                <!-- ── Controls Bar ── -->
                <div class="admins-controls">
                    <div class="admins-count-badge">
                        Total Administrators: <strong><?=e($totalAdmins)?></strong>
                    </div>
                </div>

                <!-- ── Data Card ── -->
                <div class="admins-card">
                    <div class="table-responsive">
                        <table class="admins-table">
                            <thead>
                                <tr>
                                    <th>Administrator</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($admins)): ?>
                                    <?php foreach($admins as $adm): ?>
                                        <?php
                                        $adminId   = (int)($adm['id'] ?? 0);
                                        $adminName = trim((string)($adm['name'] ?? 'Admin')) ?: 'Admin';
                                        $adminRole = strtolower(trim((string)($adm['role'] ?? 'admin')));
                                        $adminStatus = strtolower(trim((string)($adm['status'] ?? 'active')));
                                        $roleCls   = $adminRole === 'manager' ? 'role-manager' : 'role-admin';
                                        $roleLabel = $adminRole === 'manager' ? 'Store Manager' : 'Master Admin';
                                        $statusCls = $adminStatus === 'active' ? 'status-active' : 'status-inactive';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="admin-info-cell">
                                                    <div class="admin-avatar"><?=e(getInitials($adminName))?></div>
                                                    <div class="admin-name-box">
                                                        <span class="admin-name"><?=e($adminName)?></span>
                                                        <span class="admin-id-sub">ID: #<?=e($adminId)?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?=e($adm['email'] ?? '—')?></td>
                                            <td>
                                                <span class="role-badge <?=e($roleCls)?>"><?=e($roleLabel)?></span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?=e($statusCls)?>">
                                                    <span class="status-dot"></span>
                                                    <?=e(ucfirst($adminStatus))?>
                                                </span>
                                            </td>
                                            <td><?=e(formatDateTime($adm['created_at'] ?? ''))?></td>
                                            <td>
                                                <div class="actions-cell">
                                                    <?php if($adminStatus === 'active'): ?>
                                                        <button class="btn-action-toggle btn-deactivate" data-id="<?=$adminId?>" data-action="deactivate" title="Deactivate Admin">Deactivate</button>
                                                    <?php else: ?>
                                                        <button class="btn-action-toggle btn-activate" data-id="<?=$adminId?>" data-action="activate" title="Activate Admin">Activate</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="admins-empty-state">
                                                <h3>No Administrators Found</h3>
                                                <p>There are no admin accounts yet. Click "Add New Admin" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- ══════════════════════════════════════════════════════════
     ADD ADMIN MODAL  (uses admin.css modal system)
     ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="addAdminModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Add New Administrator</span>
            <button class="modal-close" id="closeAddAdminModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addAdminForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Ssrini Kumar">
                </div>

                <div class="form-group">
                    <label class="form-label">Email / Username</label>
                    <input type="text" name="email" class="form-control" required placeholder="ssrini@admin.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••" minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control">
                        <option value="admin">Master Admin</option>
                        <option value="manager">Store Manager</option>
                    </select>
                </div>

                <button type="submit" class="btn-create-admin" id="submitAdminBtn">Create Administrator</button>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     TOAST NOTIFICATION
     ══════════════════════════════════════════════════════════ -->
<div class="toast" id="toast"></div>

<script>
/* ── Toast helper ── */
function showToast(message, type) {
    var t = document.getElementById('toast');
    t.textContent = message;
    t.className = 'toast toast-' + (type || 'success') + ' show';
    setTimeout(function(){ t.classList.remove('show'); }, 3500);
}

/* ── Modal open / close ── */
document.getElementById('openAddAdminModal').addEventListener('click', function() {
    document.getElementById('addAdminModal').classList.add('show');
});

document.getElementById('closeAddAdminModal').addEventListener('click', function() {
    document.getElementById('addAdminModal').classList.remove('show');
});

document.getElementById('addAdminModal').addEventListener('click', function(ev) {
    if(ev.target === this) this.classList.remove('show');
});

/* ── Create Admin form ── */
document.getElementById('addAdminForm').addEventListener('submit', async function(ev) {
    ev.preventDefault();
    var btn = document.getElementById('submitAdminBtn');
    btn.textContent = 'Creating...';
    btn.disabled = true;

    var formData = new FormData(ev.target);
    var data = Object.fromEntries(formData.entries());

    try {
        var res = await fetch('../api/admins-create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        var result = await res.json();

        if (result.success) {
            showToast('Administrator created successfully!', 'success');
            setTimeout(function(){ window.location.reload(); }, 1200);
        } else {
            showToast('Error: ' + (result.message || 'Unknown error'), 'error');
            btn.textContent = 'Create Administrator';
            btn.disabled = false;
        }
    } catch (err) {
        showToast('Network error — please try again.', 'error');
        btn.textContent = 'Create Administrator';
        btn.disabled = false;
    }
});

/* ── Toggle admin status (activate / deactivate) ── */
document.querySelectorAll('.btn-action-toggle').forEach(function(btn) {
    btn.addEventListener('click', async function() {
        var adminId = this.dataset.id;
        var action  = this.dataset.action;
        var label   = action === 'activate' ? 'Activate' : 'Deactivate';

        if(!confirm('Are you sure you want to ' + action + ' this administrator?')) return;

        this.textContent = 'Saving...';
        this.disabled = true;

        try {
            var res = await fetch('../api/admins-toggle-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: adminId, action: action, csrf_token: '<?= e($csrfToken) ?>' })
            });
            var result = await res.json();

            if(result.success) {
                showToast('Admin ' + action + 'd successfully!', 'success');
                setTimeout(function(){ window.location.reload(); }, 1200);
            } else {
                showToast('Error: ' + (result.message || 'Unknown error'), 'error');
                this.textContent = label.charAt(0).toUpperCase() + label.slice(1);
                this.disabled = false;
            }
        } catch(err) {
            showToast('Network error — please try again.', 'error');
            this.textContent = label.charAt(0).toUpperCase() + label.slice(1);
            this.disabled = false;
        }
    });
});
</script>
</body>
</html>
