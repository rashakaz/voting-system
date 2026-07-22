<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

verifyAdmin();

$db = getDB();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$totalStudents = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM candidates WHERE status = 'active'");
$totalCandidates = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM votes");
$totalVotes = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM elections WHERE status = 'active'");
$activeElections = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
$unreadMessages = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM candidates WHERE status = 'pending'");
$pendingCandidates = (int)$stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM votes WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$votesLast24h = (int)$stmt->fetchColumn();

$turnout = $totalStudents > 0 ? round(($totalVotes / $totalStudents) * 100, 1) : 0;

$stmt = $db->query("
    SELECT v.id, v.voted_at, u.full_name as voter, c.name as candidate, e.title as election
    FROM votes v
    JOIN users u ON v.user_id = u.id
    JOIN candidates c ON v.candidate_id = c.id
    JOIN elections e ON v.election_id = e.id
    ORDER BY v.voted_at DESC
    LIMIT 10
");
$recentVotes = $stmt->fetchAll();

$stmt = $db->query("
    SELECT c.name, c.party, c.position, c.status, COUNT(v.id) as votes
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY votes DESC
    LIMIT 5
");
$topCandidates = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'stats' => [
        'total_students' => $totalStudents,
        'total_candidates' => $totalCandidates,
        'total_votes' => $totalVotes,
        'active_elections' => $activeElections,
        'turnout' => $turnout,
        'unread_messages' => $unreadMessages,
        'pending_candidates' => $pendingCandidates,
        'votes_last_24h' => $votesLast24h,
    ],
    'recent_votes' => $recentVotes,
    'top_candidates' => $topCandidates,
]);
