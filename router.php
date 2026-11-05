<?php
/**
 * SSRINI Handcrafts - Development Router & Security Firewall
 * Used with PHP built-in server: php -S localhost:8000 router.php
 * Prevents directory traversal, sensitive file disclosure (.git, .env, .sql, .bak),
 * enforces security headers, and properly dispatches application requests.
 */

// 1. Remove identifying headers and set baseline security headers
header_remove('X-Powered-By');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 2. Block Hidden Files & Dotfiles (e.g., /.git, /.env, /.user.ini, /.htaccess, /.DS_Store)
if (preg_match('#(^|/)\.[a-zA-Z0-9]#', $uri)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "403 Forbidden: Direct access to hidden files and repositories is denied.";
    exit;
}

// 3. Block Sensitive Extensions and Configurations
$blockedPatterns = [
    '/\.(sql|bak|backup|log|ini|sh|py|md|git|tar|gz|zip)$/i',
    '/(composer\.(json|lock)|package\.(json|lock)|phpunit\.xml)$/i',
    '/database\.sql$/i'
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $uri)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "403 Forbidden: Access to sensitive file type is denied.";
        exit;
    }
}

// 4. Resolve local file path
$filePath = __DIR__ . $uri;

// 5. If it's a real file that isn't a PHP script, serve with immutable cache headers
if (is_file($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext !== 'php') {
        if (basename($filePath) === 'sw.js') {
            header('Content-Type: application/javascript; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Service-Worker-Allowed: /');
            readfile($filePath);
            exit;
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'woff2', 'woff', 'ttf', 'ico'], true)) {
            header('Cache-Control: public, max-age=31536000, immutable');
        } elseif (in_array($ext, ['css', 'js'], true)) {
            header('Cache-Control: public, max-age=604800, stale-while-revalidate=86400');
        }
        return false; // Serve static file as-is
    }
    // Dynamic API bypass for PHP endpoints
    if (strpos($uri, '/api/') !== false) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
    // PHP file directly requested
    require $filePath;
    exit;
}

// 6. If it's a directory, check for index.php or index.html
if (is_dir($filePath)) {
    if (rtrim($uri, '/') !== '' && substr($uri, -1) !== '/') {
        $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        header("Location: " . $uri . "/" . $queryString, true, 301);
        exit;
    }
    $dirIndexPhp = rtrim($filePath, '/') . '/index.php';
    $dirIndexHtml = rtrim($filePath, '/') . '/index.html';
    if (is_file($dirIndexPhp)) {
        require $dirIndexPhp;
        exit;
    }
    if (is_file($dirIndexHtml)) {
        return false;
    }
}

// 7. Root fallback
if ($uri === '/' || $uri === '') {
    if (file_exists(__DIR__ . '/index.php')) {
        require __DIR__ . '/index.php';
        exit;
    }
}

// 8. 404 Not Found
http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo "404 Not Found";
exit;
