<?php

/**
 * Ssrini Handcrafts - Customer Auth API
 * Handles customer registration and login.
 */

require_once __DIR__ . '/../config/database.php';

// Allow CORS for the storefront
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    if ($data['action'] === 'register') {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception("All fields are required.");
        }
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered.");
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $insert = $pdo->prepare("INSERT INTO customers (name, email, password) VALUES (?, ?, ?)");
        $insert->execute([$name, $email, $hash]);
        $customerId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful.',
            'customer' => [
                'id' => $customerId,
                'name' => $name,
                'email' => $email
            ]
        ]);
        
    } elseif ($data['action'] === 'login') {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required.");
        }
        
        $stmt = $pdo->prepare("SELECT id, name, email, password, status FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer || !password_verify($password, $customer['password'])) {
            throw new Exception("Invalid email or password.");
        }
        
        if ($customer['status'] !== 'active') {
            throw new Exception("Your account has been deactivated. Please contact support.");
        }
        
        // Update last login
        $pdo->prepare("UPDATE customers SET last_login = NOW() WHERE id = ?")->execute([$customer['id']]);
        
        // Unset password before returning
        unset($customer['password']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful.',
            'customer' => $customer
        ]);
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
