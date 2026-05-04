<?php
/**
 * pages/dashboard.php
 * Tableau de bord principal — affichage différent selon le rôle.
 */
require_once __DIR__ . '/../config.php';
requireLogin();

$pageTitle = 'Tableau de bord';
$role = $_SESSION['role'];

// === Données pour le dashboard ===
if ($role === 'admin') {
    $totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $totalElections = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
    $openElections  = (int)$pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'open'")->fetchColumn();
    $totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();

    // Données pour graphique : votes par élection
    $stmt = $pdo->query("
        SELECT e.title, COUNT(v.id) AS nb
        FROM elections e
        LEFT JOIN votes v ON v.election_id = e.id
        GROUP BY e.id
        ORDER BY e.id DESC
        LIMIT 5
    ");
    $electionStats = $stmt->fetchAll();
    $chartLabels = array_map(fn($r) => $r['title'], $electionStats);
    $chartValues = array_map(fn($r) => (int)$r['nb'], $electionStats);

    // Élections récentes
    $recentElections = $pdo->query("SELECT * FROM elections ORDER BY id DESC LIMIT 5")->fetchAll();
} else {
    // Données étudiant
    $userId = $_SESSION['user_id'];

    $openElections  = (int)$pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'open'")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
    $myVotes = (int)$stmt->fetchColumn();

    // Élections où l'étudiant n'a pas encore voté
    $stmt = $pdo->prepare("
        SELECT e.* FROM elections e
        WHERE e.status = 'open'
          AND e.id NOT IN (SELECT election_id FROM votes WHERE user_id = :uid)
        ORDER BY e.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([':uid' => $userId]);
    $availableElections = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="dashboard-main">
    <header class="page-header">
        <div>
            <p class="eyebrow"><?= $role === 'admin' ? 'Espace administrateur' : 'Espace étudiant' ?></p>
            <h1>Bonjour, <em><?= e(explode(' ', $_SESSION['name'])[0]) ?></em>.</h1>
        </div>
        <div class="meta">
            <?= date('d.m.Y · H:i') ?>
        </div>
    </header>

    <?php if ($role === 'admin'): ?>

        <!-- Statistiques admin -->
        <section class="stats-cards">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-people"></i> Étudiants inscrits</div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-trend">comptes actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-collection"></i> Élections totales</div>
                <div class="stat-value"><?= $totalElections ?></div>
                <div class="stat-trend">depuis la création</div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-broadcast"></i> En cours</div>
                <div class="stat-value"><em><?= $openElections ?></em></div>
                <div class="stat-trend">scrutins ouverts</div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-check2-circle"></i> Bulletins</div>
                <div class="stat-value"><?= $totalVotes ?></div>
                <div class="stat-trend">votes exprimés</div>
            </div>
        </section>

        <!-- Graphique participation -->
        <div class="chart-wrapper">
            <h3>Participation par élection</h3>
            <p class="chart-meta">5 derniers scrutins · Nombre de bulletins exprimés</p>
            <div class="chart-canvas-wrap">
                <canvas
                    data-chart="bar"
                    data-labels='<?= htmlspecialchars(json_encode($chartLabels), ENT_QUOTES) ?>'
                    data-values='<?= htmlspecialchars(json_encode($chartValues), ENT_QUOTES) ?>'>
                </canvas>
            </div>
        </div>

        <!-- Élections récentes -->
        <div class="card-x">
            <span class="card-eyebrow">Vue d'ensemble</span>
            <h2>Élections récentes</h2>

            <?php if (empty($recentElections)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Aucune élection</h3>
                    <p>Commencez par créer votre première élection.</p>
                    <a href="admin_elections.php" class="btn-card" style="margin-top:16px">+ Nouvelle élection</a>
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Créée le</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentElections as $e): ?>
                            <tr>
                                <td><strong><?= e($e['title']) ?></strong></td>
                                <td>
                                    <span class="status status-<?= e($e['status']) ?>"><?= strtoupper(e($e['status'])) ?></span>
                                </td>
                                <td><?= date('d.m.Y', strtotime($e['created_at'])) ?></td>
                                <td style="text-align:right">
                                    <a href="admin_results.php?id=<?= (int)$e['id'] ?>" class="btn-mini btn-edit">Résultats</a>
                                    <a href="admin_candidates.php?election_id=<?= (int)$e['id'] ?>" class="btn-mini btn-edit">Candidats</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top:20px">
                    <a href="admin_elections.php" class="btn-card">Voir toutes les élections →</a>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- Vue ÉTUDIANT -->
        <section class="stats-cards">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-broadcast"></i> Scrutins ouverts</div>
                <div class="stat-value"><em><?= $openElections ?></em></div>
                <div class="stat-trend">disponibles maintenant</div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-check2-circle"></i> Mes votes</div>
                <div class="stat-value"><?= $myVotes ?></div>
                <div class="stat-trend">bulletins exprimés</div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-clock-history"></i> En attente</div>
                <div class="stat-value"><?= max(0, count($availableElections)) ?></div>
                <div class="stat-trend">votes restants</div>
            </div>
        </section>

        <div class="card-x">
            <span class="card-eyebrow">À votre attention</span>
            <h2>Élections ouvertes</h2>

            <?php if (empty($availableElections)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle"></i>
                    <h3>Vous êtes à jour</h3>
                    <p>Aucune élection ouverte ne nécessite votre vote pour le moment.</p>
                    <a href="results.php" class="btn-card btn-outline" style="margin-top:16px">Voir les résultats</a>
                </div>
            <?php else: ?>
                <div class="election-grid">
                    <?php foreach ($availableElections as $el): ?>
                        <div class="election-card">
                            <span class="status status-open">OUVERTE</span>
                            <h3><?= e($el['title']) ?></h3>
                            <p><?= e($el['description'] ?? 'Aucune description fournie.') ?></p>
                            <div class="meta-row">
                                <span><i class="bi bi-calendar3"></i> <?= date('d.m.Y', strtotime($el['created_at'])) ?></span>
                                <span><i class="bi bi-clock"></i> En cours</span>
                            </div>
                            <a href="vote.php?id=<?= (int)$el['id'] ?>" class="btn-card">→ Voter maintenant</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:20px">
                    <a href="elections.php" class="btn-card btn-outline">Voir toutes les élections →</a>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
