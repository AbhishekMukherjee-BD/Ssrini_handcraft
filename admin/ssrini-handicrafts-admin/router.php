<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Strip /ssrini-handicrafts-admin prefix if present
if (strpos($uri, '/ssrini-handicrafts-admin') === 0) {
    $uri = substr($uri, strlen('/ssrini-handicrafts-admin'));
    if ($uri === '' || $uri === '/') {
        header('Location: /admin/login.php');
        exit;
    }
}

if ($uri === '/' || $uri === '') {
    header('Location: /admin/login.php');
    exit;
}

$file = __DIR__ . $uri;

if (is_file($file)) {
    return false;
}

if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    include rtrim($file, '/') . '/index.php';
    exit;
}

return false;
