<?php
/**
 * SSRINI Handcrafts - Social Links Dynamic Data Provider
 * Fetches all dynamic social media URLs and active statuses from the database.
 */

if (!isset($pdo)) {
    $dbPath = dirname(__DIR__, 2) . '/admin/ssrini-handicrafts-admin/config/database.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
    }
}

/**
 * Fetch all social links as an associative array keyed by platform_key
 * @return array<string, array{name: string, url: string, is_active: bool}>
 */
function get_social_links_data(): array
{
    global $pdo;
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $links = [];
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT platform_key, platform_name, url, is_active FROM social_links ORDER BY sort_order ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $links[$row['platform_key']] = [
                    'name' => (string) ($row['platform_name'] ?? ''),
                    'url' => trim((string) ($row['url'] ?? '')),
                    'is_active' => (bool) ($row['is_active'] ?? 0)
                ];
            }
        } catch (Throwable $e) {
            error_log('Social links query error: ' . $e->getMessage());
        }
    }
    $cached = $links;
    return $cached;
}

/**
 * Get active URL for a platform. Returns $default if inactive or empty.
 */
function get_social_url(string $platformKey, string $default = '#'): string
{
    $data = get_social_links_data();
    if (isset($data[$platformKey]) && $data[$platformKey]['is_active'] && !empty($data[$platformKey]['url'])) {
        return $data[$platformKey]['url'];
    }
    return $default;
}

/**
 * Check if a platform has an active, non-empty URL configured.
 */
function is_social_active(string $platformKey): bool
{
    $data = get_social_links_data();
    return isset($data[$platformKey]) && $data[$platformKey]['is_active'] && !empty($data[$platformKey]['url']);
}
