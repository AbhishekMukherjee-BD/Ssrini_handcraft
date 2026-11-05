<?php
/**
 * =========================================================
 * SSRINI HANDCRAFTS — COLLECTION PAGE IMAGE DATA PROVIDER
 * =========================================================
 * Fetches hero centerpiece and orbit/rows image pool
 * from the collection_images table with fallback to
 * the hardcoded asset paths.
 */

if (!isset($pdo)) {
    $dbPath = dirname(__DIR__, 2) . '/admin/ssrini-handicrafts-admin/config/database.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
    }
}

/**
 * Resolve a stored image path to a URL accessible from any ecom page.
 */
function resolve_collection_image_url(string $path): string {
    $path = trim($path);
    if (empty($path)) return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
    // Admin uploads go via /admin/ssrini-handicrafts-admin/
    if (str_starts_with($path, 'uploads/')) {
        return '/admin/ssrini-handicrafts-admin/' . $path;
    }
    // Paths stored as ecom/collection/... → prepend slash
    return '/' . ltrim($path, '/');
}

/**
 * Get the hero centerpiece image (zone = 'hero').
 * Returns the DB row or a hardcoded fallback.
 */
function get_collection_hero_image(): array {
    global $pdo;

    $fallback = [
        'id'          => 0,
        'zone'        => 'hero',
        'image_path'  => 'ecom/collection/assets/images/craftsmanship/necklace-hero.webp',
        'alt_text'    => 'Royal Polki & Pearl Heritage Collar',
        'title'       => 'Royal Polki & Pearl Heritage Collar',
        'description' => 'A magnificent royal statement piece crafted in 22kt hallmarked gold.',
        'sort_order'  => 0,
        'is_active'   => 1,
    ];

    if (!isset($pdo)) return $fallback;

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM collection_images WHERE zone = 'hero' AND is_active = 1 LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: $fallback;
    } catch (Throwable) {
        return $fallback;
    }
}

/**
 * Get the orbit + rows image pool (zone = 'orbit').
 * Returns array of DB rows or hardcoded fallback set.
 */
function get_collection_orbit_images(): array {
    global $pdo;

    $fallback = [
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/1.webp', 'title' => 'Heritage Filigree Solitaire Band',       'alt_text' => 'Heritage Filigree Solitaire Band',       'description' => 'Precision hand-engraved gold filigree band centered around a certified brilliant-cut diamond solitaire.'],
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/2.webp', 'title' => 'Imperial Jadau Choker & Cascades',        'alt_text' => 'Imperial Jadau Choker & Cascades',        'description' => 'Intricate closed-back Kundan setting featuring floral meenakari enameling.'],
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/3.webp', 'title' => 'Nizam Heritage Polki Kada',               'alt_text' => 'Nizam Heritage Polki Kada',               'description' => 'Sculpted solid gold kada with screw clasp, lined with natural unheated rubies.'],
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/4.webp', 'title' => 'Temple Architecture Jhumka Pendants',     'alt_text' => 'Temple Architecture Jhumka Pendants',     'description' => 'Intricately articulated chandelier earrings inspired by Dravidian temple carvings.'],
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/5.webp', 'title' => 'Peacock Motif Navratna Choker',           'alt_text' => 'Peacock Motif Navratna Choker',           'description' => 'Featuring nine sacred astrological gemstones arranged in cosmic alignment.'],
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/6.webp', 'title' => 'Mughal Blossom Royal Collar',             'alt_text' => 'Mughal Blossom Royal Collar',             'description' => 'A flexible mesh gold collar woven with micro-pearls and rose-cut diamonds.'],
        ['image_path' => 'ecom/collection/assets/images/craftsmanship/7.webp', 'title' => 'Artisan Hand-Engraved Wedding Band',      'alt_text' => 'Artisan Hand-Engraved Wedding Band',      'description' => 'Subtle high-polish brushed gold with hand-chiseled millgrain edges.'],
    ];

    if (!isset($pdo)) return $fallback;

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM collection_images
             WHERE zone = 'orbit' AND is_active = 1
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return !empty($rows) ? $rows : $fallback;
    } catch (Throwable) {
        return $fallback;
    }
}
