<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $status = $_GET['status'] ?? '';

        $sql = "SELECT e.*, 
                (SELECT COUNT(*) FROM votes WHERE election_id = e.id) as total_votes,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as total_voters
                FROM elections e WHERE 1=1";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY e.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $elections = $stmt->fetchAll();

        foreach ($elections as &$election) {
            $total = $election['total_voters'] ?: 1;
            $election['turnout'] = round(($election['total_votes'] / $total) * 100, 1);
        }

        jsonResponse(['success' => true, 'elections' => $elections]);
        break;

    case 'POST':
        $input = getJsonInput();
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $startDate = $input['start_date'] ?? '';
        $endDate = $input['end_date'] ?? '';
        $maxVotes = $input['max_votes_per_student'] ?? 1;

        if (empty($title) || empty($startDate) || empty($endDate)) {
            jsonResponse(['error' => 'Title, start date, and end date are required'], 400);
        }

        $stmt = $db->prepare("INSERT INTO elections (title, description, start_date, end_date, max_votes_per_student, status) VALUES (?, ?, ?, ?, ?, 'upcoming')");
        $stmt->execute([$title, $description, $startDate, $endDate, $maxVotes]);

        jsonResponse([
            'success' => true,
            'message' => 'Election created successfully',
            'id' => $db->lastInsertId(),
        ], 201);
        break;

    case 'PUT':
        $input = getJsonInput();
        $id = $input['id'] ?? 0;

        if (!$id) {
            jsonResponse(['error' => 'Election ID is required'], 400);
        }

        $fields = [];
        $params = [];
        foreach (['title', 'description', 'start_date', 'end_date', 'max_votes_per_student', 'status'] as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($fields)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $sql = "UPDATE elections SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'message' => 'Election updated successfully']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            jsonResponse(['error' => 'Election ID is required'], 400);
        }

        $stmt = $db->prepare("DELETE FROM elections WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Election deleted successfully']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
