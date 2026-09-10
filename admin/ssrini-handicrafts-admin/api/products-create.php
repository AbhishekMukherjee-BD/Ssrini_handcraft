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