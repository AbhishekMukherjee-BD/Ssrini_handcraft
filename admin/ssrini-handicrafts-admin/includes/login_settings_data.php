<?php
/**
 * =========================================================
 * SSRINI HANDCRAFTS - ADMIN LOGIN SETTINGS DATA PROVIDER
 * =========================================================
 */

function resolve_login_image_url(string $path): string
{
    $path = trim($path);
    if (empty($path)) {
        return '';
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, '//') === 0) {
        return $path;
    }
    // If it is an absolute path from web root
    if (strpos($path, '/') === 0) {
        return $path;
    }
    // If it is in uploads/
    if (strpos($path, 'uploads/') === 0) {
        return '../' . $path;
    }
    return $path;
}

function get_admin_login_settings(): array
{
    global $pdo;
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $defaults = [
        'logo_image'        => '/ecom/store/assets/images/logo.png',
        'showcase_image'    => '/ecom/about/assets/hero_brand_founder.jpg',
        'showcase_headline' => 'Secure Access for Authorized Personnel'
    ];

    $settings = $defaults;

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT * FROM admin_login_settings ORDER BY id ASC LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (!empty($row['logo_image'])) {
                    $settings['logo_image'] = trim((string)$row['logo_image']);
                }
                if (!empty($row['showcase_image'])) {
                    $settings['showcase_image'] = trim((string)$row['showcase_image']);
                }
                if (!empty($row['showcase_headline'])) {
                    $settings['showcase_headline'] = trim((string)$row['showcase_headline']);
                }
            }
        } catch (Throwable $e) {
            error_log('Admin login settings fetch error: ' . $e->getMessage());
        }
    }

    $settings['resolved_logo_url']     = resolve_login_image_url($settings['logo_image']);
    $settings['resolved_showcase_url'] = resolve_login_image_url($settings['showcase_image']);

    $cached = $settings;
    return $cached;
}
