<?php
require_once __DIR__ . '/../../config/database.php';

verifyAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT u.id, u.full_name, u.national_id, u.phone, u.email, u.username,
                   u.student_id, u.status, u.voted, u.created_at, MAX(v.voted_at) AS last_vote_at
            FROM users u
            LEFT JOIN votes v ON u.id = v.user_id
            WHERE 1=1
        ";
        $countSql = "SELECT COUNT(*) FROM users u WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.full_name LIKE ? OR u.student_id LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
            $countSql .= " AND (u.full_name LIKE ? OR u.student_id LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($status)) {
            $sql .= " AND u.status = ?";
            $countSql .= " AND u.status = ?";
            $params[] = $status;
        }

        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $voters = $stmt->fetchAll();

        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $totalActive = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE voted = 1");
        $totalVoted = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'");
        $totalInactive = (int)$stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'voters' => $voters,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
            'stats' => [
                'total' => $totalUsers,
                'active' => $totalActive,
                'voted' => $totalVoted,
                'not_voted' => $totalActive - $totalVoted,
                'inactive' => $totalInactive,
            ],
        ]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            jsonResponse(['error' => 'User ID is required'], 400);
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true, 'message' => 'Voter deleted successfully']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
