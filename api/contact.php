<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $db->prepare("SELECT id, name, email, subject, message, is_read, created_at FROM contact_messages ORDER BY created_at DESC");
        $stmt->execute();
        $messages = $stmt->fetchAll();
        jsonResponse(['success' => true, 'messages' => $messages]);
        break;

    case 'POST':
        $input = getJsonInput();
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            jsonResponse(['error' => 'Name must be at least 2 characters'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Invalid email address'], 400);
        }
        if (empty($subject) || strlen($subject) < 3) {
            jsonResponse(['error' => 'Subject must be at least 3 characters'], 400);
        }
        if (empty($message) || strlen($message) < 10) {
            jsonResponse(['error' => 'Message must be at least 10 characters'], 400);
        }

        $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);

        jsonResponse([
            'success' => true,
            'message' => 'Your message has been sent successfully. We will get back to you shortly.',
        ], 201);
        break;

    case 'PUT':
        $input = getJsonInput();
        $id = $input['id'] ?? 0;

        if (!$id) {
            jsonResponse(['error' => 'Message ID is required'], 400);
        }

        $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Message marked as read']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
