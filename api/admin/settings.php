<?php
require_once __DIR__ . '/../../config/database.php';

verifyAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll();
        $result = [];
        foreach ($settings as $s) {
            $result[$s['setting_key']] = $s['setting_value'];
        }

        $stmt = $db->query("SELECT id, username, email, full_name FROM admin_users LIMIT 1");
        $admin = $stmt->fetch();

        jsonResponse([
            'success' => true,
            'settings' => $result,
            'admin' => $admin,
        ]);
        break;

    case 'PUT':
        $input = getJsonInput();

        if (isset($input['settings']) && is_array($input['settings'])) {
            foreach ($input['settings'] as $key => $value) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
        }

        if (isset($input['admin'])) {
            $adminData = $input['admin'];
            $fields = [];
            $params = [];

            if (isset($adminData['full_name'])) {
                $fields[] = "full_name = ?";
                $params[] = $adminData['full_name'];
            }
            if (isset($adminData['email'])) {
                $fields[] = "email = ?";
                $params[] = $adminData['email'];
            }
            if (isset($adminData['password']) && !empty($adminData['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($adminData['password'], PASSWORD_BCRYPT);
            }

            if (!empty($fields)) {
                $stmt = $db->prepare("UPDATE admin_users SET " . implode(', ', $fields) . " WHERE id = 1");
                $stmt->execute($params);
            }
        }

        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $updated = [];
        foreach ($stmt->fetchAll() as $s) {
            $updated[$s['setting_key']] = $s['setting_value'];
        }

        jsonResponse(['success' => true, 'message' => 'Settings saved successfully', 'settings' => $updated]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
