<?php
/**
 * pages/admin_results.php
 * Résultats temps réel — vue admin avec polling AJAX.
 *
 * Oualid Mokrane & Hajar Errahmouni — MGSI
 */
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Résultats temps réel';
$electionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// === Si pas d'élection sélectionnée, afficher la liste ===
if ($electionId <= 0) {
    $stmt = $pdo->query('
        SELECT e.*,
            (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS nb_candidates,
            (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS total_votes
        FROM elections e
        ORDER BY FIELD(e.status, "open", "closed", "archived"), e.created_at DESC
    ');
    $list = $stmt->fetchAll();
}
// === Sinon, charger l'élection sélectionnée + ses résultats ===
else {
    $stmt = $pdo->prepare('SELECT * FROM elections WHERE id = :id');
    $stmt->execute([':id' => $electionId]);
    $election = $stmt->fetch();
    if (!$election) {
        header('Location: ' . BASE_URL . '/pages/admin_results.php');
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.program,
               (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) AS votes
        FROM candidates c
        WHERE c.election_id = :eid
        ORDER BY votes DESC, c.name ASC
    ');
    $stmt->execute([':eid' => $electionId]);
    $results = $stmt->fetchAll();

    $totalVotes = array_sum(array_column($results, 'votes'));

    $totalEligible = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
    $turnout = $totalEligible > 0 ? round(($totalVotes / $totalEligible) * 100, 1) : 0;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">

  <?php if ($electionId <= 0): ?>
    <!-- ============== LISTE DES ÉLECTIONS ============== -->
    <header class="page-header">
      <div>
        <p class="card-eyebrow">— Administration</p>
        <h1>Résultats temps réel</h1>
        <p class="muted">Choisissez une élection pour suivre les votes en direct.</p>
      </div>
    </header>

    <?php if (empty($list)): ?>
      <div class="empty-state">
        <i class="bi bi-bar-chart"></i>
        <p>Aucune élection enregistrée.</p>
      </div>
    <?php else: ?>
      <div class="election-grid">
        <?php foreach ($list as $el): ?>
          <article class="election-card status-<?= e($el['status']) ?>">
            <div class="election-card-head">
              <span class="status status-<?= e($el['status']) ?>"><?= e($el['status']) ?></span>
              <span class="num-badge">#<?= str_pad((string)$el['id'], 3, '0', STR_PAD_LEFT) ?></span>
            </div>
            <h3><?= e($el['title']) ?></h3>
            <p class="muted"><?= e(mb_substr($el['description'] ?? '', 0, 100)) ?>...</p>
            <div class="election-card-meta">
              <span><i class="bi bi-people"></i> <?= (int)$el['nb_candidates'] ?> candidats</span>
              <span><i class="bi bi-check2-square"></i> <?= (int)$el['total_votes'] ?> votes</span>
            </div>
            <a href="?id=<?= (int)$el['id'] ?>" class="btn-cta btn-block">
              <i class="bi bi-graph-up"></i> Voir les résultats
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- ============== RÉSULTATS D'UNE ÉLECTION ============== -->
    <header class="page-header">
      <div>
        <p class="card-eyebrow">— Scrutin n° <?= str_pad((string)$election['id'], 3, '0', STR_PAD_LEFT) ?></p>
        <h1><?= e($election['title']) ?></h1>
        <p class="muted">
          <span class="status status-<?= e($election['status']) ?>"><?= e($election['status']) ?></span>
          <?php if ($election['status'] === 'open'): ?>
            <span class="live-pulse"><span class="live-dot"></span> En direct — actualisation toutes les 5 secondes</span>
          <?php endif; ?>
        </p>
      </div>
      <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/pages/admin_results.php" class="btn-mini">
          <i class="bi bi-arrow-left"></i> Toutes les élections
        </a>
      </div>
    </header>

    <!-- Stats rapides -->
    <section class="stats-cards">
      <article class="stat-card">
        <p class="stat-label">Bulletins exprimés</p>
        <p class="stat-value" id="stat-total"><?= (int)$totalVotes ?></p>
      </article>
      <article class="stat-card">
        <p class="stat-label">Candidats</p>
        <p class="stat-value"><?= count($results) ?></p>
      </article>
      <article class="stat-card">
        <p class="stat-label">Taux de participation</p>
        <p class="stat-value" id="stat-turnout"><?= $turnout ?>%</p>
      </article>
      <article class="stat-card">
        <p class="stat-label">Étudiants éligibles</p>
        <p class="stat-value"><?= $totalEligible ?></p>
      </article>
    </section>

    <?php if (empty($results)): ?>
      <div class="empty-state">
        <i class="bi bi-people"></i>
        <p>Aucun candidat inscrit pour cette élection.</p>
      </div>
    <?php else: ?>
      <div class="dash-grid-2 dashboard-grid-2"
           data-live-election="<?= $election['status'] === 'open' ? (int)$election['id'] : '' ?>">
        <!-- Graphique -->
        <section class="card-x">
          <h2 class="card-eyebrow card-x-title"><i class="bi bi-pie-chart"></i> Répartition des voix</h2>
          <div class="chart-wrapper">
            <canvas id="live-chart"
                    data-chart="doughnut"
                    data-labels='<?= json_encode(array_column($results, "name"), JSON_UNESCAPED_UNICODE) ?>'
                    data-values='<?= json_encode(array_map("intval", array_column($results, "votes"))) ?>'></canvas>
          </div>
        </section>

        <!-- Tableau -->
        <section class="card-x">
          <h2 class="card-eyebrow card-x-title"><i class="bi bi-list-ol"></i> Classement</h2>
          <div class="table-wrap">
            <table class="result-table">
              <thead>
                <tr>
                  <th style="width:60px;">Rang</th>
                  <th>Candidat</th>
                  <th style="width:90px;">Voix</th>
                  <th style="width:140px;">%</th>
                </tr>
              </thead>
              <tbody id="live-results-tbody">
                <?php foreach ($results as $i => $r):
                    $pct = $totalVotes > 0 ? round(($r['votes'] / $totalVotes) * 100, 1) : 0;
                ?>
                  <tr<?= $i === 0 && $r['votes'] > 0 ? ' class="winner-row"' : '' ?>>
                    <td>
                      <span class="rank rank-<?= $i + 1 ?>"><?= $i + 1 ?></span>
                    </td>
                    <td><strong><?= e($r['name']) ?></strong></td>
                    <td><strong><?= (int)$r['votes'] ?></strong></td>
                    <td>
                      <div class="bar-cell">
                        <div class="bar"><span style="width:<?= $pct ?>%"></span></div>
                        <small><?= $pct ?>%</small>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
