<?php
require_once __DIR__ . '/../../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $input = getJsonInput();
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'Username and password are required'], 400);
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id, username, email, full_name, password FROM admin_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        jsonResponse(['error' => 'Invalid admin credentials'], 401);
    }

    $db->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?")
        ->execute([$admin['id']]);

    $token = generateToken(['admin_id' => (int)$admin['id'], 'type' => 'admin']);

    jsonResponse([
        'success' => true,
        'message' => 'Admin login successful',
        'admin' => [
            'id' => (int)$admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'full_name' => $admin['full_name'],
        ],
        'token' => $token,
    ]);
} catch (Throwable $e) {
    error_log('Admin login error: ' . $e->getMessage());
    jsonResponse(['error' => 'Admin login service failed. Please check the database and try again.'], 500);
}
