<?php

/**
 * Ssrini Handicrafts
 * Create Product API
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

header('Content-Type: application/json; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Security token invalid or missing. Please refresh and try again.'
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| READ FORM DATA
|--------------------------------------------------------------------------
*/

$productCode = trim($_POST['product_code'] ?? '');

$name = trim($_POST['name'] ?? '');

$categoryId = (int) ($_POST['category_id'] ?? 0);

$description = trim($_POST['description'] ?? '');

$price = $_POST['price'] ?? '';

$discountPrice = $_POST['discount_price'] ?? '';

$stockQuantity = $_POST['stock_quantity'] ?? '';

$status = $_POST['status'] ?? 'active';

/* NEW METADATA FIELDS */
$collectionName = trim($_POST['collection_name'] ?? '');
$material = trim($_POST['material'] ?? '');
$badge = trim($_POST['badge'] ?? '');
$craftHours = trim($_POST['craft_hours'] ?? '');
$origin = trim($_POST['origin'] ?? '');
$length = trim($_POST['length'] ?? '');
$width = trim($_POST['width'] ?? '');
$height = trim($_POST['height'] ?? '');
$weight = trim($_POST['weight'] ?? '');

$accCraft = trim($_POST['accordion_craftsmanship'] ?? '');
$accCare = trim($_POST['accordion_care'] ?? '');
$accDelivery = trim($_POST['accordion_delivery'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

$errors = [];


if ($productCode === '') {

    $errors['product_code'] =
        'Product code is required.';
}


if ($name === '') {

    $errors['name'] =
        'Product name is required.';
}


if ($categoryId <= 0) {

    $errors['category_id'] =
        'Please select a category.';
}


if ($price === '' || !is_numeric($price)) {

    $errors['price'] =
        'Please enter a valid price.';
}


if (
    $discountPrice !== '' &&
    !is_numeric($discountPrice)
) {

    $errors['discount_price'] =
        'Please enter a valid discount price.';
}


if (
    $discountPrice !== '' &&
    is_numeric($price) &&
    (float) $discountPrice >= (float) $price
) {

    $errors['discount_price'] =
        'Discount price must be lower than the regular price.';
}


if (
    $stockQuantity === '' ||
    !filter_var(
        $stockQuantity,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 0
            ]
        ]
    )
) {

    $errors['stock_quantity'] =
        'Stock quantity must be 0 or greater.';
}


if (
    !in_array(
        $status,
        ['active', 'inactive'],
        true
    )
) {

    $errors['status'] =
        'Invalid product status.';
}


/*
|--------------------------------------------------------------------------
| RETURN VALIDATION ERRORS
|--------------------------------------------------------------------------
*/

if (!empty($errors)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please correct the form errors.',
        'errors' => $errors
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| NORMALIZE VALUES
|--------------------------------------------------------------------------
*/

$price = (float) $price;

$discountPrice =
    $discountPrice === ''
        ? null
        : (float) $discountPrice;

$stockQuantity = (int) $stockQuantity;


/*
|--------------------------------------------------------------------------
| GENERATE SLUG
|--------------------------------------------------------------------------
*/

$slug = strtolower(
    trim(
        preg_replace(
            '/[^A-Za-z0-9-]+/',
            '-',
            $name
        ),
        '-'
    )
);


/*
|--------------------------------------------------------------------------
| MAKE SLUG UNIQUE
|--------------------------------------------------------------------------
*/

$baseSlug = $slug;

$counter = 1;

while (true) {

    $slugCheck = $pdo->prepare(
        "
        SELECT id
        FROM products
        WHERE slug = :slug
        LIMIT 1
        "
    );

    $slugCheck->execute([
        ':slug' => $slug
    ]);

    if (!$slugCheck->fetch()) {

        break;
    }

    $slug =
        $baseSlug . '-' . $counter;

    $counter++;
}


/*
|--------------------------------------------------------------------------
| CHECK PRODUCT CODE
|--------------------------------------------------------------------------
*/

$codeCheck = $pdo->prepare(
    "
    SELECT id
    FROM products
    WHERE product_code = :product_code
    LIMIT 1
    "
);

$codeCheck->execute([
    ':product_code' => $productCode
]);


if ($codeCheck->fetch()) {

    http_response_code(409);

    echo json_encode([
        'success' => false,
        'message' =>
            'A product with this product code already exists.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK CATEGORY
|--------------------------------------------------------------------------
*/

$categoryCheck = $pdo->prepare(
    "
    SELECT id
    FROM categories
    WHERE id = :category_id
      AND status = 'active'
    LIMIT 1
    "
);

$categoryCheck->execute([
    ':category_id' => $categoryId
]);


if (!$categoryCheck->fetch()) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' =>
            'Selected category does not exist or is inactive.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

$imageName = null;

$uploadedImagePath = null;


if (
    isset($_FILES['image']) &&
    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
) {

    $image = $_FILES['image'];


    /*
    |----------------------------------------------------------------------
    | CHECK UPLOAD ERROR
    |----------------------------------------------------------------------
    */

    if ($image['error'] !== UPLOAD_ERR_OK) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Image upload failed.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | CHECK FILE SIZE
    |----------------------------------------------------------------------
    */

    if ($image['size'] > 5 * 1024 * 1024) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Image size must be 5MB or less.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | CHECK REAL MIME TYPE
    |----------------------------------------------------------------------
    */

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeType = $finfo->file(
        $image['tmp_name']
    );


    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];


    if (!isset($allowedTypes[$mimeType])) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' =>
                'Invalid image type. Only JPG, PNG and WEBP are allowed.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | CREATE UPLOAD DIRECTORY
    |----------------------------------------------------------------------
    */

    $uploadDirectory =
        __DIR__ . '/../assets/uploads/';


    if (!is_dir($uploadDirectory)) {

        if (!mkdir(
            $uploadDirectory,
            0755,
            true
        )) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Unable to create upload directory.'
            ]);

            exit;
        }
    }


    /*
    |----------------------------------------------------------------------
    | GENERATE UNIQUE IMAGE NAME
    |----------------------------------------------------------------------
    */

    $extension =
        $allowedTypes[$mimeType];


    $imageName =
        'product_' .
        uniqid('', true) .
        '.' .
        $extension;


    /*
    |----------------------------------------------------------------------
    | FINAL IMAGE PATH
    |----------------------------------------------------------------------
    */

    $uploadedImagePath =
        $uploadDirectory . $imageName;


    /*
    |----------------------------------------------------------------------
    | MOVE IMAGE
    |----------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $image['tmp_name'],
            $uploadedImagePath
        )
    ) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' =>
                'Unable to save product image.'
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| GALLERY IMAGES UPLOAD (NEW)
|--------------------------------------------------------------------------
*/
$galleryImageNames = [];
$uploadedGalleryPaths = [];

if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
    $galleryFiles = $_FILES['gallery_images'];
    $fileCount = count($galleryFiles['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        if ($galleryFiles['error'][$i] === UPLOAD_ERR_OK) {
            
            // Check size (5MB)
            if ($galleryFiles['size'][$i] > 5 * 1024 * 1024) continue;

            // Check MIME
            $finfoGallery = new finfo(FILEINFO_MIME_TYPE);
            $mimeTypeG = $finfoGallery->file($galleryFiles['tmp_name'][$i]);
            
            if (!isset($allowedTypes[$mimeTypeG])) continue;

            $extG = $allowedTypes[$mimeTypeG];
            $gName = 'gallery_' . uniqid('', true) . '.' . $extG;
            $gPath = $uploadDirectory . $gName;

            if (move_uploaded_file($galleryFiles['tmp_name'][$i], $gPath)) {
                $galleryImageNames[] = $gName;
                $uploadedGalleryPaths[] = $gPath;
            }
        }
    }
}
$galleryJson = json_encode($galleryImageNames);


/*
|--------------------------------------------------------------------------
| INSERT PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        "
        INSERT INTO products (
            category_id,
            product_code,
            name,
            slug,
            description,
            price,
            discount_price,
            stock_quantity,
            image,
            gallery_images,
            collection_name,
            material,
            badge,
            craft_hours,
            origin,
            length,
            width,
            height,
            weight,
            accordion_craftsmanship,
            accordion_care,
            accordion_delivery,
            status
        )

        VALUES (
            :category_id,
            :product_code,
            :name,
            :slug,
            :description,
            :price,
            :discount_price,
            :stock_quantity,
            :image,
            :gallery_images,
            :collection_name,
            :material,
            :badge,
            :craft_hours,
            :origin,
            :length,
            :width,
            :height,
            :weight,
            :accordion_craftsmanship,
            :accordion_care,
            :accordion_delivery,
            :status
        )
        "
    );


    $stmt->execute([

        ':category_id' =>
            $categoryId,

        ':product_code' =>
            $productCode,

        ':name' =>
            $name,

        ':slug' =>
            $slug,

        ':description' =>
            $description !== ''
                ? $description
                : null,

        ':price' =>
            $price,

        ':discount_price' =>
            $discountPrice,

        ':stock_quantity' =>
            $stockQuantity,

        ':image' =>
            $imageName,
            
        ':gallery_images' => $galleryJson,
        ':collection_name' => $collectionName ?: null,
        ':material' => $material ?: null,
        ':badge' => $badge ?: null,
        ':craft_hours' => $craftHours ?: null,
        ':origin' => $origin ?: null,
        ':length' => $length !== '' ? $length : null,
        ':width' => $width !== '' ? $width : null,
        ':height' => $height !== '' ? $height : null,
        ':weight' => $weight !== '' ? $weight : null,
        ':accordion_craftsmanship' => $accCraft ?: null,
        ':accordion_care' => $accCare ?: null,
        ':accordion_delivery' => $accDelivery ?: null,

        ':status' =>
            $status
    ]);


    /*
    |--------------------------------------------------------------------------
    | GET NEW PRODUCT ID
    |--------------------------------------------------------------------------
    */

    $productId =
        (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'message' =>
            'Product created successfully.',

        'product_id' =>
            $productId,

        'image' =>
            $imageName
    ]);


} catch (PDOException $e) {


    /*
    |--------------------------------------------------------------------------
    | DELETE IMAGE IF DATABASE INSERT FAILS
    |--------------------------------------------------------------------------
    */

    if (
        $uploadedImagePath !== null &&
        file_exists($uploadedImagePath)
    ) {

        unlink($uploadedImagePath);
    }


    http_response_code(500);

    echo json_encode([

        'success' => false,

        'message' =>
            'Unable to create product.'
    ]);

}

?>