<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $electionId = $_GET['election_id'] ?? 0;
        $userId = $_GET['user_id'] ?? 0;

        if ($electionId) {
            $stmt = $db->prepare("
                SELECT v.id, v.voted_at, v.candidate_id, v.user_id, v.position,
                       c.name as candidate_name, c.party, c.position as candidate_position,
                       u.full_name as voter_name
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                JOIN users u ON v.user_id = u.id
                WHERE v.election_id = ?
                ORDER BY v.voted_at DESC
            ");
            $stmt->execute([$electionId]);
        } elseif ($userId) {
            $stmt = $db->prepare("
                SELECT v.id, v.voted_at, v.candidate_id, v.election_id, v.position,
                       c.name as candidate_name, c.party, c.position as candidate_position,
                       e.title as election_title
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                JOIN elections e ON v.election_id = e.id
                WHERE v.user_id = ?
                ORDER BY v.voted_at DESC
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->prepare("
                SELECT v.id, v.voted_at, v.candidate_id, v.user_id, v.election_id, v.position,
                       c.name as candidate_name, c.party, c.position as candidate_position,
                       u.full_name as voter_name,
                       e.title as election_title
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                JOIN users u ON v.user_id = u.id
                JOIN elections e ON v.election_id = e.id
                ORDER BY v.voted_at DESC
                LIMIT 50
            ");
            $stmt->execute();
        }

        $votes = $stmt->fetchAll();
        jsonResponse(['success' => true, 'votes' => $votes]);
        break;

    case 'POST':
        $input = getJsonInput();
        $userId = $input['user_id'] ?? 0;
        $candidateId = $input['candidate_id'] ?? 0;
        $electionId = $input['election_id'] ?? 0;

        if (!$userId || !$candidateId || !$electionId) {
            jsonResponse(['error' => 'User ID, candidate ID, and election ID are required'], 400);
        }

        $stmt = $db->prepare("SELECT id, status, max_votes_per_student FROM elections WHERE id = ?");
        $stmt->execute([$electionId]);
        $election = $stmt->fetch();

        if (!$election) {
            jsonResponse(['error' => 'Election not found'], 404);
        }

        if ($election['status'] !== 'active') {
            jsonResponse(['error' => 'Election is not active'], 400);
        }

        $stmt = $db->prepare("SELECT id FROM elections WHERE id = ? AND NOW() BETWEEN start_date AND end_date");
        $stmt->execute([$electionId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Election is not currently open'], 400);
        }

        $stmt = $db->prepare("SELECT id, status, position FROM candidates WHERE id = ? AND status = 'active'");
        $stmt->execute([$candidateId]);
        $candidate = $stmt->fetch();
        if (!$candidate) {
            jsonResponse(['error' => 'Candidate not found or not active'], 400);
        }

        $position = $candidate['position'];

        $stmt = $db->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ? AND election_id = ?");
        $stmt->execute([$userId, $electionId]);
        if ((int)$stmt->fetchColumn() >= (int)$election['max_votes_per_student']) {
            jsonResponse(['error' => 'You have reached the maximum number of votes for this election'], 409);
        }

        $stmt = $db->prepare("SELECT id FROM votes WHERE user_id = ? AND election_id = ? AND position = ?");
        $stmt->execute([$userId, $electionId, $position]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'You have already voted for ' . $position], 409);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO votes (user_id, candidate_id, election_id, position) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $candidateId, $electionId, $position]);

            $stmt = $db->prepare("UPDATE users SET voted = 1 WHERE id = ?");
            $stmt->execute([$userId]);

            $db->commit();
            jsonResponse([
                'success' => true,
                'message' => 'Vote cast successfully',
                'id' => $db->lastInsertId(),
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Failed to cast vote'], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
