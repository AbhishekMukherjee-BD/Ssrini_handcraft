<?php

/**
 * Ssrini Handicrafts
 * Update Product API
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

header('Content-Type: application/json; charset=UTF-8');


/*
|--------------------------------------------------------------------------
| Request Method
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
| Read Form Data
|--------------------------------------------------------------------------
*/

$productId = (int) ($_POST['id'] ?? 0);

$productCode = trim(
    $_POST['product_code'] ?? ''
);

$name = trim(
    $_POST['name'] ?? ''
);

$categoryId = (int) (
    $_POST['category_id'] ?? 0
);

$description = trim(
    $_POST['description'] ?? ''
);

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
| Validation
|--------------------------------------------------------------------------
*/

$errors = [];


if ($productId <= 0) {

    $errors['id'] =
        'Invalid product ID.';
}


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


if (
    $price === '' ||
    !is_numeric($price)
) {

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
| Return Validation Errors
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
| Check Existing Product
|--------------------------------------------------------------------------
*/

$productCheck = $pdo->prepare(
    "
    SELECT
        id,
        image,
        gallery_images
    FROM products
    WHERE id = :id
    LIMIT 1
    "
);

$productCheck->execute([
    ':id' => $productId
]);

$existingProduct =
    $productCheck->fetch(PDO::FETCH_ASSOC);


if (!$existingProduct) {

    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Product not found.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Product Code
|--------------------------------------------------------------------------
*/

$codeCheck = $pdo->prepare(
    "
    SELECT id
    FROM products
    WHERE product_code = :product_code
      AND id != :id
    LIMIT 1
    "
);

$codeCheck->execute([
    ':product_code' => $productCode,
    ':id' => $productId
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
| Check Category
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
| Normalize Values
|--------------------------------------------------------------------------
*/

$price = (float) $price;

$discountPrice =
    $discountPrice === ''
        ? null
        : (float) $discountPrice;

$stockQuantity =
    (int) $stockQuantity;


/*
|--------------------------------------------------------------------------
| Generate Slug
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
| Make Slug Unique
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
          AND id != :id
        LIMIT 1
        "
    );

    $slugCheck->execute([
        ':slug' => $slug,
        ':id' => $productId
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
| IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

$imageName = $existingProduct['image'];


/*
|--------------------------------------------------------------------------
| Check New Image
|--------------------------------------------------------------------------
*/

if (
    isset($_FILES['image']) &&
    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
) {

    $image = $_FILES['image'];


    /*
    |----------------------------------------------------------------------
    | Upload Error
    |----------------------------------------------------------------------
    */

    if (
        $image['error'] !==
        UPLOAD_ERR_OK
    ) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Image upload failed.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | Maximum File Size
    |----------------------------------------------------------------------
    */

    if (
        $image['size'] >
        5 * 1024 * 1024
    ) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' =>
                'Image size must not exceed 5MB.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | Check Image Type
    |----------------------------------------------------------------------
    */

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];


    $finfo = new finfo(
        FILEINFO_MIME_TYPE
    );

    $mimeType =
        $finfo->file(
            $image['tmp_name']
        );


    if (
        !isset(
            $allowedTypes[$mimeType]
        )
    ) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' =>
                'Only JPG, PNG and WEBP images are allowed.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | Upload Directory
    |----------------------------------------------------------------------
    */

    $uploadDirectory =
        __DIR__ .
        '/../assets/uploads/';


    if (
        !is_dir(
            $uploadDirectory
        )
    ) {

        mkdir(
            $uploadDirectory,
            0755,
            true
        );
    }


    /*
    |----------------------------------------------------------------------
    | Generate Unique Image Name
    |----------------------------------------------------------------------
    */

    $imageName =
        uniqid(
            'product_',
            true
        ) .
        '.' .
        $allowedTypes[$mimeType];


    $uploadPath =
        $uploadDirectory .
        $imageName;


    /*
    |----------------------------------------------------------------------
    | Move Uploaded Image
    |----------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $image['tmp_name'],
            $uploadPath
        )
    ) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' =>
                'Unable to save uploaded image.'
        ]);

        exit;
    }


    /*
    |----------------------------------------------------------------------
    | Delete Old Image
    |----------------------------------------------------------------------
    */

    if (
        !empty(
            $existingProduct['image']
        )
    ) {

        $oldImagePath =
            $uploadDirectory .
            $existingProduct['image'];


        if (
            is_file(
                $oldImagePath
            )
        ) {

            unlink(
                $oldImagePath
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| GALLERY IMAGES UPLOAD (NEW)
|--------------------------------------------------------------------------
*/
$galleryImageNames = [];
if (!empty($existingProduct['gallery_images'])) {
    $existingGallery = json_decode($existingProduct['gallery_images'], true);
    if (is_array($existingGallery)) {
        $galleryImageNames = $existingGallery;
    }
}

// Handle removal of specific gallery images
if (isset($_POST['remove_gallery']) && is_array($_POST['remove_gallery'])) {
    $removeList = $_POST['remove_gallery'];
    $galleryImageNames = array_filter($galleryImageNames, function($img) use ($removeList) {
        if (in_array($img, $removeList)) {
            // Delete the file from server
            $filePath = __DIR__ . '/../assets/uploads/' . basename($img);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return false; // Remove from array
        }
        return true; // Keep in array
    });
    // Re-index the array after filtering
    $galleryImageNames = array_values($galleryImageNames);
}

if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
    $galleryFiles = $_FILES['gallery_images'];
    $fileCount = count($galleryFiles['name']);
    $uploadDirectoryG = __DIR__ . '/../assets/uploads/';

    for ($i = 0; $i < $fileCount; $i++) {
        if ($galleryFiles['error'][$i] === UPLOAD_ERR_OK) {
            
            // Check size (5MB)
            if ($galleryFiles['size'][$i] > 5 * 1024 * 1024) continue;

            // Check MIME
            $finfoGallery = new finfo(FILEINFO_MIME_TYPE);
            $mimeTypeG = $finfoGallery->file($galleryFiles['tmp_name'][$i]);
            
            $allowedTypesG = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];
            
            if (!isset($allowedTypesG[$mimeTypeG])) continue;

            $extG = $allowedTypesG[$mimeTypeG];
            $gName = 'gallery_' . uniqid('', true) . '.' . $extG;
            $gPath = $uploadDirectoryG . $gName;

            if (move_uploaded_file($galleryFiles['tmp_name'][$i], $gPath)) {
                $galleryImageNames[] = $gName; // Append to existing
            }
        }
    }
}
$galleryJson = json_encode($galleryImageNames);


/*
|--------------------------------------------------------------------------
| Update Product
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        "
        UPDATE products

        SET
            category_id = :category_id,
            product_code = :product_code,
            name = :name,
            slug = :slug,
            description = :description,
            price = :price,
            discount_price = :discount_price,
            stock_quantity = :stock_quantity,
            image = :image,
            gallery_images = :gallery_images,
            collection_name = :collection_name,
            material = :material,
            badge = :badge,
            craft_hours = :craft_hours,
            origin = :origin,
            length = :length,
            width = :width,
            height = :height,
            weight = :weight,
            accordion_craftsmanship = :accordion_craftsmanship,
            accordion_care = :accordion_care,
            accordion_delivery = :accordion_delivery,
            status = :status

        WHERE id = :id
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
            $status,

        ':id' =>
            $productId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        'success' => true,

        'message' =>
            'Product updated successfully.',

        'product_id' =>
            $productId
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'message' =>
            'Unable to update product.'
    ]);
}