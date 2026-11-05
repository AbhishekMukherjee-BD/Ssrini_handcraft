<?php
/**
 * SSRINI Handcrafts — Centralized CORS Helper
 * Restricts Access-Control-Allow-Origin to whitelisted origins.
 * Falls back to same-origin (no header) if the request origin is not allowed.
 */
function set_cors_headers(array $allowedMethods = ['POST', 'OPTIONS']): void {
    header_remove('X-Powered-By');
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https://*; connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com;");
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    }
    // Load env for any production domain overrides
    require_once __DIR__ . '/env.php';
    $env = get_app_env();

    // Build whitelist: always allow local dev, add production domain from env
    $whitelist = [
        'http://127.0.0.1:8000',
        'http://localhost:8000',
    ];

    $prodDomain = $env['SITE_ORIGIN'] ?? '';
    if (!empty($prodDomain)) {
        $whitelist[] = rtrim($prodDomain, '/');
    }

    // Check request origin against whitelist
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $whitelist, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Vary: Origin');
    }
    // If origin is empty (same-origin request) or not in whitelist, no ACAO header is set

    $methods = implode(', ', $allowedMethods);
    header("Access-Control-Allow-Methods: {$methods}");
    header('Access-Control-Allow-Headers: Content-Type, X-Razorpay-Signature');
}
