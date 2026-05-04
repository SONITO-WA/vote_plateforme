<?php
/**
 * pages/api_results.php
 * Endpoint JSON pour les résultats temps réel (utilisé par AJAX/polling).
 * GET ?election_id=X
 *
 * Oualid Mokrane & Hajar Errahmouni — MGSI
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// === Authentification requise ===
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
if ($electionId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'election_id requis']);
    exit;
}

try {
    // Vérifier que l'élection existe
    $stmt = $pdo->prepare('SELECT id, title, status FROM elections WHERE id = :id');
    $stmt->execute([':id' => $electionId]);
    $election = $stmt->fetch();

    if (!$election) {
        http_response_code(404);
        echo json_encode(['error' => 'Élection introuvable']);
        exit;
    }

    // === Contrôle d'accès ===
    // - Admin : accès total
    // - Étudiant : doit avoir voté OU élection clôturée/archivée
    if (!isAdmin()) {
        $hasVoted = false;
        if ($election['status'] === 'open') {
            $check = $pdo->prepare('SELECT 1 FROM votes WHERE user_id = :u AND election_id = :e LIMIT 1');
            $check->execute([':u' => $_SESSION['user_id'], ':e' => $electionId]);
            $hasVoted = (bool)$check->fetchColumn();

            if (!$hasVoted) {
                http_response_code(403);
                echo json_encode(['error' => 'Vous devez voter avant de voir les résultats']);
                exit;
            }
        }
    }

    // === Récupération des résultats ===
    $stmt = $pdo->prepare('
        SELECT c.id, c.name,
               (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) AS votes
        FROM candidates c
        WHERE c.election_id = :eid
        ORDER BY votes DESC, c.name ASC
    ');
    $stmt->execute([':eid' => $electionId]);
    $rows = $stmt->fetchAll();

    $results = array_map(function ($r) {
        return [
            'id'    => (int)$r['id'],
            'name'  => $r['name'],
            'votes' => (int)$r['votes'],
        ];
    }, $rows);

    $totalVotes = array_sum(array_column($results, 'votes'));

    // Taux de participation
    $totalEligible = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
    $turnout = $totalEligible > 0 ? round(($totalVotes / $totalEligible) * 100, 1) : 0;

    echo json_encode([
        'election_id' => (int)$election['id'],
        'title'       => $election['title'],
        'status'      => $election['status'],
        'total_votes' => $totalVotes,
        'turnout'     => $turnout,
        'eligible'    => $totalEligible,
        'results'     => $results,
        'timestamp'   => date('c'),
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
