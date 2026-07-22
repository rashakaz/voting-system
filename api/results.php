<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$db = getDB();
$electionId = $_GET['election_id'] ?? 0;

if (!$electionId) {
    $stmt = $db->prepare("SELECT id, title FROM elections WHERE status = 'active' OR status = 'completed' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $election = $stmt->fetch();

    if (!$election) {
        jsonResponse(['error' => 'No elections found'], 404);
    }
    $electionId = $election['id'];
}

$stmt = $db->prepare("SELECT id, title, status, start_date, end_date FROM elections WHERE id = ?");
$stmt->execute([$electionId]);
$election = $stmt->fetch();

if (!$election) {
    jsonResponse(['error' => 'Election not found'], 404);
}

$stmt = $db->prepare("
    SELECT c.id, c.name, c.party, c.position, c.photo,
           COUNT(v.id) as vote_count
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id AND v.election_id = ?
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY c.position ASC, vote_count DESC
");
$stmt->execute([$electionId]);
$candidates = $stmt->fetchAll();

$totalVotes = 0;
$positionTotals = [];
foreach ($candidates as $c) {
    $votes = (int)$c['vote_count'];
    $position = $c['position'] ?: 'Other Position';
    $totalVotes += $votes;
    $positionTotals[$position] = ($positionTotals[$position] ?? 0) + $votes;
}

$results = [];
foreach ($candidates as &$candidate) {
    $position = $candidate['position'] ?: 'Other Position';
    $positionTotal = $positionTotals[$position] ?? 0;
    $percentage = $positionTotal > 0 ? round(($candidate['vote_count'] / $positionTotal) * 100, 1) : 0;
    $results[] = [
        'id' => $candidate['id'],
        'name' => $candidate['name'],
        'party' => $candidate['party'],
        'position' => $candidate['position'],
        'vote_count' => (int)$candidate['vote_count'],
        'percentage' => $percentage,
    ];
}

$stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as total_voters FROM votes WHERE election_id = ?");
$stmt->execute([$electionId]);
$voterCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'");
$stmt->execute();
$totalRegistered = $stmt->fetchColumn();

jsonResponse([
    'success' => true,
    'election' => $election,
    'results' => $results,
    'total_votes' => $totalVotes,
    'total_voters' => (int)$voterCount,
    'total_registered' => (int)$totalRegistered,
    'turnout' => $totalRegistered > 0 ? round(($voterCount / $totalRegistered) * 100, 1) : 0,
]);
