<?php 
/**
 * Ssrini Handcrafts
 * Database Configuretion
 * 
 * Creates a reusable PDO connection to MySQL/MariaDB.
 */


require_once dirname(__DIR__, 3) . '/ecom/includes/env.php';
$env = get_app_env();
$host = $env['DB_HOST'] ?? '127.0.0.1';
$dbname = $env['DB_NAME'] ?? 'ssrinihandicrafts';
$username = $env['DB_USER'] ?? 'ssrinihandicrafts';
$password = $env['DB_PASS'] ?? 'IuE7HzxtQKszKPk1H6Nx';

$charset = 'utf8mb4';
$dsn = "mysql:host={$host};dbname={$dbname}; charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   =>false,

    ];

try {
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );

} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('Service temporarily unavailable. Please try again later.');
}