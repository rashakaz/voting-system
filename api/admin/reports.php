<?php
require_once __DIR__ . '/../../config/database.php';

verifyAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();
$type = $_GET['type'] ?? 'summary';

switch ($type) {
    case 'turnout':
        $stmt = $db->query("
            SELECT 
                e.id, e.title, e.start_date, e.end_date, e.status,
                COUNT(DISTINCT v.user_id) as voters,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as registered,
                ROUND((COUNT(DISTINCT v.user_id) / (SELECT COUNT(*) FROM users WHERE status = 'active')) * 100, 1) as turnout_pct
            FROM elections e
            LEFT JOIN votes v ON e.id = v.election_id
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ");
        $data = $stmt->fetchAll();
        break;

    case 'candidates':
        $stmt = $db->query("
            SELECT c.id, c.name, c.party, c.position, c.status,
                   COUNT(v.id) as vote_count,
                   e.title as election_title
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
            LEFT JOIN elections e ON v.election_id = e.id
            GROUP BY c.id
            ORDER BY vote_count DESC
        ");
        $data = $stmt->fetchAll();
        break;

    case 'security':
        $stmt = $db->query("
            SELECT id, user_type, user_id, action, details, created_at
            FROM activity_logs
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $data = $stmt->fetchAll();
        break;

    case 'voters':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $total = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.student_id, u.email, u.phone, u.status, u.created_at,
                   CASE WHEN v.id IS NOT NULL THEN 'Voted' ELSE 'Not Voted' END as voting_status,
                   v.voted_at
            FROM users u
            LEFT JOIN votes v ON u.id = v.user_id
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $data = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
        ]);
        exit;

    case 'summary':
    default:
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM votes");
        $totalVotes = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM candidates");
        $totalCandidates = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM elections");
        $totalElections = (int)$stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM contact_messages");
        $totalMessages = (int)$stmt->fetchColumn();

        $stmt = $db->query("
            SELECT DATE(voted_at) as date, COUNT(*) as count
            FROM votes
            WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            GROUP BY DATE(voted_at)
            ORDER BY date
        ");
        $voteTrend = $stmt->fetchAll();

        $data = [
            'total_users' => $totalUsers,
            'total_votes' => $totalVotes,
            'total_candidates' => $totalCandidates,
            'total_elections' => $totalElections,
            'total_messages' => $totalMessages,
            'vote_trend' => $voteTrend,
        ];
        break;
}

jsonResponse(['success' => true, 'data' => $data, 'type' => $type]);
