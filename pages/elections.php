<?php
/**
 * pages/elections.php
 * Liste des élections disponibles pour l'étudiant.
 */
require_once __DIR__ . '/../config.php';
requireLogin();

if ($_SESSION['role'] === 'admin') {
    header('Location: admin_elections.php');
    exit;
}

$pageTitle = 'Élections en cours';
$userId = $_SESSION['user_id'];

// Toutes les élections + statut "voté ou pas" pour cet étudiant
$stmt = $pdo->prepare("
    SELECT e.*,
           (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id AND v.user_id = :uid) AS has_voted,
           (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS nb_candidates,
           (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS total_votes
    FROM elections e
    WHERE e.status IN ('open', 'closed')
    ORDER BY
        FIELD(e.status, 'open', 'closed'),
        e.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$elections = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
    <header class="page-header">
        <div>
            <p class="eyebrow">Espace étudiant</p>
            <h1>Élections <em>disponibles</em></h1>
        </div>
        <div class="meta">
            <?= count($elections) ?> SCRUTIN(S) AFFICHÉ(S)
        </div>
    </header>

    <?php if (empty($elections)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>Aucune élection</h3>
            <p>Aucune élection n'est ouverte pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="election-grid">
            <?php foreach ($elections as $el): ?>
                <?php
                    $hasVoted = $el['has_voted'] > 0;
                    $isOpen = $el['status'] === 'open';
                ?>
                <div class="election-card">
                    <?php if ($hasVoted): ?>
                        <span class="status status-voted">✓ VOTÉ</span>
                    <?php elseif ($isOpen): ?>
                        <span class="status status-open">OUVERTE</span>
                    <?php else: ?>
                        <span class="status status-closed">CLÔTURÉE</span>
                    <?php endif; ?>

                    <h3><?= e($el['title']) ?></h3>
                    <p><?= e($el['description'] ?? 'Aucune description fournie.') ?></p>

                    <div class="meta-row">
                        <span><i class="bi bi-people"></i> <?= (int)$el['nb_candidates'] ?> candidat(s)</span>
                        <span><i class="bi bi-check2"></i> <?= (int)$el['total_votes'] ?> vote(s)</span>
                    </div>

                    <?php if ($hasVoted): ?>
                        <a href="results.php?id=<?= (int)$el['id'] ?>" class="btn-card btn-outline">Voir les résultats →</a>
                    <?php elseif ($isOpen): ?>
                        <?php if ((int)$el['nb_candidates'] === 0): ?>
                            <button class="btn-card btn-disabled" disabled>Pas de candidats</button>
                        <?php else: ?>
                            <a href="vote.php?id=<?= (int)$el['id'] ?>" class="btn-card">→ Voter</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="results.php?id=<?= (int)$el['id'] ?>" class="btn-card btn-outline">Résultats →</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
