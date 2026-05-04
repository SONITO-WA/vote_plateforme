<?php
/**
 * pages/results.php
 * Affichage des résultats d'une (ou de toutes les) élection(s).
 * L'étudiant ne peut voir les résultats d'une élection que s'il a déja vote
 * OU si l'élection est clôturée/archivée.
 */
require_once __DIR__ . '/../config.php';
requireLogin();

$userId     = $_SESSION['user_id'];
$electionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$justVoted  = isset($_GET['voted']);

// Si pas d'ID, lister les élections terminées + celles où l'utilisateur a déjà voté
if ($electionId <= 0) {
    $pageTitle = 'Résultats';
    $stmt = $pdo->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS total_votes
        FROM elections e
        WHERE e.status IN ('closed', 'archived')
           OR e.id IN (SELECT election_id FROM votes WHERE user_id = :uid)
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $list = $stmt->fetchAll();

    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/navbar.php';
    ?>
    <main class="dashboard-main">
        <header class="page-header">
            <div>
                <p class="eyebrow">Espace étudiant</p>
                <h1>Résultats des <em>scrutins</em></h1>
            </div>
            <div class="meta"><?= count($list) ?> ÉLECTION(S) CONSULTABLE(S)</div>
        </header>

        <?php if (empty($list)): ?>
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h3>Aucun résultat consultable</h3>
                <p>Vous pourrez voir les résultats des élections après avoir voté ou lorsqu'elles seront clôturées.</p>
            </div>
        <?php else: ?>
            <div class="election-grid">
                <?php foreach ($list as $el): ?>
                    <div class="election-card">
                        <span class="status status-<?= e($el['status']) ?>"><?= strtoupper(e($el['status'])) ?></span>
                        <h3><?= e($el['title']) ?></h3>
                        <p><?= e($el['description'] ?? '') ?></p>
                        <div class="meta-row">
                            <span><i class="bi bi-check2"></i> <?= (int)$el['total_votes'] ?> votes</span>
                            <span><i class="bi bi-calendar3"></i> <?= date('d.m.Y', strtotime($el['created_at'])) ?></span>
                        </div>
                        <a href="results.php?id=<?= (int)$el['id'] ?>" class="btn-card">Voir les résultats →</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// === Vue détaillée d'une élection ===
$stmt = $pdo->prepare('SELECT * FROM elections WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $electionId]);
$election = $stmt->fetch();
if (!$election) {
    header('Location: results.php');
    exit;
}

// Vérification d'accès (sauf admin)
if ($_SESSION['role'] !== 'admin') {
    $stmt = $pdo->prepare('SELECT id FROM votes WHERE user_id = :u AND election_id = :e LIMIT 1');
    $stmt->execute([':u' => $userId, ':e' => $electionId]);
    $hasVoted = (bool)$stmt->fetch();

    if (!$hasVoted && $election['status'] === 'open') {
        header('Location: vote.php?id=' . $electionId);
        exit;
    }
}

// Récupérer les résultats
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.program, COUNT(v.id) AS votes
    FROM candidates c
    LEFT JOIN votes v ON v.candidate_id = c.id
    WHERE c.election_id = :e
    GROUP BY c.id
    ORDER BY votes DESC, c.name ASC
");
$stmt->execute([':e' => $electionId]);
$results = $stmt->fetchAll();

$totalVotes = array_sum(array_column($results, 'votes'));

$pageTitle = 'Résultats — ' . $election['title'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
    <header class="page-header">
        <div>
            <p class="eyebrow">Résultats officiels</p>
            <h1><?= e($election['title']) ?></h1>
        </div>
        <div class="meta">
            STATUT : <?= strtoupper(e($election['status'])) ?> · <?= $totalVotes ?> VOTES
        </div>
    </header>

    <?php if ($justVoted): ?>
        <div class="alert alert-success" style="margin-bottom:30px">
            ✓ Votre vote a bien été enregistré. Merci de votre participation !
        </div>
    <?php endif; ?>

    <?php if (empty($results) || $totalVotes === 0): ?>
        <div class="empty-state">
            <i class="bi bi-bar-chart"></i>
            <h3>Aucun vote pour le moment</h3>
            <p>Soyez le premier à participer à cette élection.</p>
        </div>
    <?php else: ?>

        <!-- Graphique -->
        <div class="chart-wrapper">
            <h3>Répartition des votes</h3>
            <p class="chart-meta">Total : <?= $totalVotes ?> bulletin(s) exprimé(s)</p>
            <div class="chart-canvas-wrap">
                <canvas
                    data-chart="doughnut"
                    data-labels='<?= json_encode(array_column($results, 'name')) ?>'
                    data-values='<?= json_encode(array_map('intval', array_column($results, 'votes'))) ?>'>
                </canvas>
            </div>
        </div>

        <!-- Tableau classement -->
        <div class="card-x">
            <span class="card-eyebrow">Classement officiel</span>
            <h2>Détail par candidat</h2>

            <table class="result-table">
                <thead>
                    <tr>
                        <th style="width:60px">Rang</th>
                        <th>Candidat</th>
                        <th>Score</th>
                        <th style="text-align:right;width:80px">Votes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $i => $r):
                        $pct = $totalVotes > 0 ? ($r['votes'] / $totalVotes * 100) : 0;
                        $isWinner = $i === 0 && $r['votes'] > 0;
                    ?>
                        <tr>
                            <td class="rank"><?= $i + 1 ?></td>
                            <td>
                                <div class="name"><?= e($r['name']) ?></div>
                                <div style="font-size:13px;color:var(--muted);margin-top:2px"><?= e($r['program'] ?? '') ?></div>
                            </td>
                            <td>
                                <div class="result-bar-wrap">
                                    <div class="result-bar <?= $isWinner ? 'winner' : '' ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <span class="percentage"><?= number_format($pct, 1) ?>%</span>
                            </td>
                            <td class="votes"><?= (int)$r['votes'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <div style="margin-top:30px">
        <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_elections.php' : 'elections.php' ?>" class="btn-card btn-outline">← Retour aux élections</a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
