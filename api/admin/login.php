<?php
require_once __DIR__ . '/../../config/database.php';

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

$token = generateToken(['admin_id' => $admin['id'], 'type' => 'admin']);

jsonResponse([
    'success' => true,
    'message' => 'Admin login successful',
    'admin' => [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'email' => $admin['email'],
        'full_name' => $admin['full_name'],
    ],
    'token' => $token,
]);
