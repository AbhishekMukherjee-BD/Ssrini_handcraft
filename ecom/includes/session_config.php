<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header_remove('X-Powered-By');
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https://*; connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com;");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

if(session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'), // Secure flag for direct HTTPS and reverse-proxy setups
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>
