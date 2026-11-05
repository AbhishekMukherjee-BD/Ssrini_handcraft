<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session_config.php';
}
require_once __DIR__ . '/../../admin/ssrini-handicrafts-admin/config/database.php';

try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map DB rows to JS format expected by store.js and product.js
    $jsProducts = [];
    foreach($products as $p) {
        
        $images = [];
        if (!empty($p['image'])) {
            $images[] = '/admin/ssrini-handicrafts-admin/assets/uploads/' . $p['image'];
        }
        if (!empty($p['gallery_images'])) {
            $gallery = json_decode($p['gallery_images'], true);
            if (is_array($gallery)) {
                foreach($gallery as $img) {
                    $images[] = '/admin/ssrini-handicrafts-admin/assets/uploads/' . $img;
                }
            }
        }

        $jsProducts[] = [
            'id' => $p['slug'],
            'db_id' => (int)$p['id'],
            'slug' => $p['slug'],
            'title' => $p['name'],
            'collection' => $p['collection_name'] ?: 'All',
            'category' => $p['category_name'] ?: 'All',
            'material' => $p['material'] ?: '',
            'priceINR' => (int)$p['price'],
            'badge' => $p['badge'] ?: '',
            'images' => $images,
            'description' => $p['description'] ?: '',
            'specs' => [
                'craftHours' => $p['craft_hours'] ?: '',
                'origin' => $p['origin'] ?: '',
                'dimensions' => $p['dimensions'] ?: '',
                'weight' => $p['weight'] ?: ''
            ],
            'accordions' => [
                'craftsmanship' => $p['accordion_craftsmanship'] ?: '',
                'care' => $p['accordion_care'] ?: '',
                'delivery' => $p['accordion_delivery'] ?: ''
            ]
        ];
    }
} catch (Exception $e) {
    $jsProducts = [];
}

$userWishlist = [];
if (isset($_SESSION['customer_id'])) {
    try {
        $wStmt = $pdo->prepare("
            SELECT p.slug 
            FROM wishlists w 
            JOIN products p ON w.product_id = p.id 
            WHERE w.customer_id = ?
        ");
        $wStmt->execute([$_SESSION['customer_id']]);
        $userWishlist = $wStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $userWishlist = [];
    }
}

