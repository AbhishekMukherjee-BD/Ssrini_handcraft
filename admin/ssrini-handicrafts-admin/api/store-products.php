<?php

/**
 * Ssrini Handcrafts - Public Storefront API
 * Handles public product catalog and product details.
 */

require_once __DIR__ . '/../config/database.php';

// Allow CORS for the storefront
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    /* GET PARAMETERS */
    $search = trim($_GET['search'] ?? '');
    $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
    $slug = trim($_GET['slug'] ?? '');
    $productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $sort = $_GET['sort'] ?? 'newest';

    /* BASE QUERY - ONLY ACTIVE PRODUCTS */
    $sql = "
        SELECT
            p.id, p.category_id, p.product_code, p.name, p.slug,
            p.description, p.price, p.price_usd, p.discount_price, p.stock_quantity,
            p.image, p.gallery_images, p.material, p.badge, p.craft_hours,
            p.origin, p.dimensions, p.weight, p.collection_name, p.status,
            p.created_at, p.updated_at,
            c.name AS category_name, c.slug AS category_slug
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'active'
    ";

    $params = [];

    /* FETCH SINGLE PRODUCT BY ID */
    if ($productId > 0) {
        $sql .= " AND p.id = :id";
        $params[':id'] = $productId;
    }

    /* FETCH SINGLE PRODUCT BY SLUG */
    if ($slug !== '') {
        $sql .= " AND p.slug = :slug";
        $params[':slug'] = $slug;
    }

    /* SEARCH */
    if ($search !== '' && $productId === 0 && $slug === '') {
        $sql .= " AND (p.name LIKE :s1 OR p.product_code LIKE :s2 OR c.name LIKE :s3)";
        $searchTerm = '%' . $search . '%';
        $params[':s1'] = $searchTerm;
        $params[':s2'] = $searchTerm;
        $params[':s3'] = $searchTerm;
    }

    /* CATEGORY FILTER */
    if ($categoryId > 0) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryId;
    }

    /* SORTING */
    switch ($sort) {
        case 'oldest':
            $sql .= " ORDER BY p.created_at ASC";
            break;
        case 'price_low':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'name_asc':
            $sql .= " ORDER BY p.name ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY p.name DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY p.created_at DESC";
            break;
    }

    /* EXECUTE QUERY */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    /* FORMAT PRODUCT DATA */
    foreach ($products as &$product) {
        $product['id'] = (int) $product['id'];
        $product['category_id'] = (int) $product['category_id'];
        $product['price'] = (float) $product['price'];
        $product['price_usd'] = $product['price_usd'] !== null ? (float) $product['price_usd'] : null;
        $product['discount_price'] = $product['discount_price'] !== null ? (float) $product['discount_price'] : null;
        $product['stock_quantity'] = (int) $product['stock_quantity'];
        
        // Parse JSON for gallery images
        if (!empty($product['gallery_images'])) {
            $product['gallery_images'] = json_decode($product['gallery_images'], true);
        } else {
            $product['gallery_images'] = [];
        }
    }
    unset($product);

    /* SUCCESS RESPONSE */
    echo json_encode([
        'success' => true,
        'count' => count($products),
        'data' => $products
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    /* ERROR RESPONSE */
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load products.'
    ], JSON_UNESCAPED_UNICODE);
}
