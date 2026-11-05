<?php require_once __DIR__ . '/../includes/session_config.php'; echo json_encode(['loggedIn' => isset($_SESSION['customer_id'])]); ?>
