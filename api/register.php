<?php
require_once __DIR__ . '/../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $input = getJsonInput();
    $fullName = trim($input['fullname'] ?? '');
    $nationalId = trim($input['nationalId'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $email = trim($input['email'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (strlen($fullName) < 3) {
        jsonResponse(['error' => 'Full name must be at least 3 characters'], 400);
    }
    if (strlen($nationalId) < 5) {
        jsonResponse(['error' => 'National ID must be at least 5 characters'], 400);
    }
    if (strlen($phone) < 8) {
        jsonResponse(['error' => 'Phone number must be at least 8 characters'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Invalid email address'], 400);
    }
    if (strlen($username) < 4) {
        jsonResponse(['error' => 'Username must be at least 4 characters'], 400);
    }
    if (strlen($password) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? OR national_id = ?");
    $stmt->execute([$email, $username, $nationalId]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email, username, or national ID already exists'], 409);
    }

    $studentId = '';
    do {
        $studentId = 'UMMA-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $db->prepare("SELECT id FROM users WHERE student_id = ?");
        $stmt->execute([$studentId]);
    } while ($stmt->fetch());

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $db->prepare("
            INSERT INTO users (full_name, national_id, phone, email, username, password, student_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$fullName, $nationalId, $phone, $email, $username, $hashedPassword, $studentId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            jsonResponse(['error' => 'A user with these details already exists'], 409);
        }
        throw $e;
    }

    $userId = $db->lastInsertId();
    $token = generateToken(['user_id' => $userId, 'type' => 'student']);

    jsonResponse([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $userId,
            'full_name' => $fullName,
            'email' => $email,
            'username' => $username,
            'student_id' => $studentId,
        ],
        'token' => $token,
    ], 201);
} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());

    if ($e instanceof PDOException && $e->getCode() === '23000') {
        jsonResponse(['error' => 'A user with these details already exists'], 409);
    }

    jsonResponse(['error' => 'Registration service failed. Please check the database and try again.'], 500);
}
