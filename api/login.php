<?php
require_once __DIR__ . '/../config/database.php';

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

    $stmt = $db->prepare("SELECT id, full_name, email, username, student_id, password, status FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['error' => 'Invalid username or password'], 401);
    }

    if (($user['status'] ?? 'active') === 'inactive') {
        jsonResponse(['error' => 'Account is deactivated. Contact administrator.'], 403);
    }

    $token = generateToken(['user_id' => (int)$user['id'], 'type' => 'student']);

    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => (int)$user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'username' => $user['username'],
            'student_id' => $user['student_id'],
        ],
        'token' => $token,
    ]);
} catch (Throwable $e) {
    error_log('Student login error: ' . $e->getMessage());
    jsonResponse(['error' => 'Login service failed. Please check the database and try again.'], 500);
}
