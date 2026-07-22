<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $key = $_GET['key'] ?? '';

        if (!empty($key)) {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $setting = $stmt->fetch();
            jsonResponse(['success' => true, 'setting' => $setting]);
        } else {
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll();
            $result = [];
            foreach ($settings as $s) {
                $result[$s['setting_key']] = $s['setting_value'];
            }
            jsonResponse(['success' => true, 'settings' => $result]);
        }
        break;

    case 'PUT':
        $input = getJsonInput();
        $key = $input['key'] ?? '';
        $value = $input['value'] ?? '';

        if (empty($key)) {
            jsonResponse(['error' => 'Setting key is required'], 400);
        }

        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);

        jsonResponse(['success' => true, 'message' => 'Setting updated successfully']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
