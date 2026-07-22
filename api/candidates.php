<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $status = $_GET['status'] ?? '';
        $position = $_GET['position'] ?? '';

        $sql = "SELECT id, name, party, position, photo, manifesto, status, created_at FROM candidates WHERE 1=1";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        if (!empty($position)) {
            $sql .= " AND position = ?";
            $params[] = $position;
        }

        $sql .= " ORDER BY id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll();

        jsonResponse(['success' => true, 'candidates' => $candidates]);
        break;

    case 'POST':
        $input = getJsonInput();
        $name = trim($input['name'] ?? '');
        $party = trim($input['party'] ?? '');
        $position = trim($input['position'] ?? 'SRC President');
        $manifesto = trim($input['manifesto'] ?? '');

        if (empty($name) || empty($party)) {
            jsonResponse(['error' => 'Name and party are required'], 400);
        }

        $stmt = $db->prepare("INSERT INTO candidates (name, party, position, manifesto, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $party, $position, $manifesto]);

        jsonResponse([
            'success' => true,
            'message' => 'Candidate added successfully',
            'id' => $db->lastInsertId(),
        ], 201);
        break;

    case 'PUT':
        $input = getJsonInput();
        $id = $input['id'] ?? 0;

        if (!$id) {
            jsonResponse(['error' => 'Candidate ID is required'], 400);
        }

        $fields = [];
        $params = [];
        foreach (['name', 'party', 'position', 'manifesto', 'status'] as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = trim($input[$field]);
            }
        }

        if (empty($fields)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $sql = "UPDATE candidates SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'message' => 'Candidate updated successfully']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            jsonResponse(['error' => 'Candidate ID is required'], 400);
        }

        $stmt = $db->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Candidate deleted successfully']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
