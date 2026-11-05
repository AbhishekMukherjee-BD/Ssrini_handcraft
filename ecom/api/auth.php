<?php
require_once __DIR__ . '/../includes/session_config.php';
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/cors.php';
set_cors_headers(['POST', 'OPTIONS']);

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// Connect to Database
$dbPath = __DIR__ . '/../../admin/ssrini-handicrafts-admin/config/database.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database configuration missing.']);
    exit;
}
require_once $dbPath;

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload.']);
    exit;
}

$action = $data['action'];

try {
    if ($action === 'login') {
        $email = strtolower(trim($data['email'] ?? ''));
        $password = trim($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required.");
        }

        // Rate Limiter Check (10 attempts per 15 minutes, loopback excluded from lockout)
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $isLocalhost = in_array($ipAddress, ['::1', '127.0.0.1', 'localhost'], true);
        $timeLimit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        
        if (!$isLocalhost) {
            $stmtAttempts = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
            $stmtAttempts->execute([$ipAddress, $timeLimit]);
            $attemptsResult = $stmtAttempts->fetch();
            
            if ($attemptsResult && $attemptsResult['attempt_count'] >= 10) {
                http_response_code(429);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Too many failed login attempts. Please try again in 15 minutes.',
                    'message' => 'Too many failed login attempts. Please try again in 15 minutes.'
                ]);
                exit;
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password'])) {
            // Login Success -> Clear failed attempts for this IP
            $stmtClear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmtClear->execute([$ipAddress]);

            session_regenerate_id(true);

            // Update last login
            $updateStmt = $pdo->prepare("UPDATE customers SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$customer['id']]);

            // Set session
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email'] = $customer['email'];

            echo json_encode([
                'success' => true, 
                'message' => 'Logged in successfully',
                'customer_name' => $customer['name']
            ]);
        } else {
            // Login Failed -> Record attempt for rate limiting
            $stmtLog = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
            $stmtLog->execute([$ipAddress]);

            throw new Exception("Invalid email or password.");
        }

    } elseif ($action === 'register') {
        $name = trim(strip_tags($data['name'] ?? ''));
        $email = strtolower(trim($data['email'] ?? ''));
        $password = trim($data['password'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception("All fields are required.");
        }
        
        // Strong password check
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters.");
        }

        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT id, password FROM customers WHERE LOWER(email) = ? LIMIT 1");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if (!empty($existing['password'])) {
                throw new Exception("An account with this email already exists. Please log in.");
            } else {
                // Customer exists from an old order but never set a password. Let's update them!
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE customers SET name = ?, password = ?, last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$name, $hashed, $existing['id']]);
                
                $_SESSION['customer_id'] = $existing['id'];
                $_SESSION['customer_name'] = $name;
                $_SESSION['customer_email'] = $email;
            }
        } else {
            // New customer
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO customers (name, email, password, status, created_at, updated_at, last_login) VALUES (?, ?, ?, 'active', NOW(), NOW(), NOW())");
            $insertStmt->execute([$name, $email, $hashed]);
            
            $newId = $pdo->lastInsertId();
            $_SESSION['customer_id'] = $newId;
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
        }

        // Clear any old failed attempts on successful registration
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmtClear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmtClear->execute([$ipAddress]);

        echo json_encode([
            'success' => true, 
            'message' => 'Account created successfully!',
            'customer_name' => $name
        ]);

    } elseif ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out.']);
    } elseif ($action === 'session_status') {
        if (isset($_SESSION['customer_id'])) {
            echo json_encode([
                'loggedIn' => true,
                'customer' => [
                    'id' => $_SESSION['customer_id'],
                    'name' => $_SESSION['customer_name'],
                    'email' => $_SESSION['customer_email']
                ]
            ]);
        } else {
            echo json_encode(['loggedIn' => false]);
        }
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    error_log('Auth API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'An authentication error occurred. Please try again.',
        'message' => 'An authentication error occurred. Please try again.'
    ]);
}
