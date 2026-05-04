<?php
/**
 * pages/admin_archive.php
 * Archives — élections clôturées ou archivées avec leurs résultats finaux.
 *
 * Oualid Mokrane & Hajar Errahmouni — MGSI
 */
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Archives des scrutins';
$msg = '';

// === Action : archiver / réouvrir ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkCsrf($_POST['csrf_token'] ?? null)) {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'archive') {
            $stmt = $pdo->prepare('UPDATE elections SET status = "archived", closed_at = COALESCE(closed_at, NOW()) WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $msg = 'Élection archivée.';
        } elseif ($action === 'reopen') {
            $stmt = $pdo->prepare('UPDATE elections SET status = "closed" WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $msg = 'Élection désarchivée (statut : clôturée).';
        }
    }
}

// === Liste des élections clôturées + archivées ===
$stmt = $pdo->query('
    SELECT e.*,
        (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS nb_candidates,
        (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS total_votes,
        (SELECT c.name FROM candidates c
            LEFT JOIN votes v2 ON v2.candidate_id = c.id
            WHERE c.election_id = e.id
            GROUP BY c.id
            ORDER BY COUNT(v2.id) DESC, c.id ASC
            LIMIT 1) AS winner_name
    FROM elections e
    WHERE e.status IN ("closed", "archived")
    ORDER BY FIELD(e.status, "archived", "closed"), e.closed_at DESC
');
$archives = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
  <header class="page-header">
    <div>
      <p class="card-eyebrow">— Mémoire institutionnelle</p>
      <h1>Archives des scrutins</h1>
      <p class="muted">Tous les scrutins clôturés et leurs résultats définitifs.</p>
    </div>
  </header>

  <?php if ($msg): ?>
    <div class="alert-x alert-success"><i class="bi bi-check-circle"></i> <?= e($msg) ?></div>
  <?php endif; ?>

  <?php if (empty($archives)): ?>
    <div class="empty-state">
      <i class="bi bi-archive"></i>
      <p>Aucun scrutin archivé pour le moment.</p>
      <p class="muted">Les élections clôturées apparaîtront ici.</p>
    </div>
  <?php else: ?>
    <section class="card-x">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:60px;">#</th>
              <th>Titre du scrutin</th>
              <th style="width:110px;">Statut</th>
              <th style="width:100px;">Candidats</th>
              <th style="width:90px;">Votes</th>
              <th>Vainqueur</th>
              <th style="width:130px;">Clôturée le</th>
              <th style="width:240px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($archives as $a): ?>
              <tr>
                <td><span class="num-badge"><?= str_pad((string)$a['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                <td>
                  <strong><?= e($a['title']) ?></strong>
                  <br><small class="muted"><?= e(mb_substr($a['description'] ?? '', 0, 80)) ?></small>
                </td>
                <td><span class="status status-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                <td><?= (int)$a['nb_candidates'] ?></td>
                <td><strong><?= (int)$a['total_votes'] ?></strong></td>
                <td>
                  <?php if ($a['winner_name'] && (int)$a['total_votes'] > 0): ?>
                    <span class="winner-tag"><i class="bi bi-trophy-fill"></i> <?= e($a['winner_name']) ?></span>
                  <?php else: ?>
                    <em class="muted">—</em>
                  <?php endif; ?>
                </td>
                <td class="muted">
                  <?= $a['closed_at'] ? date('d/m/Y', strtotime($a['closed_at'])) : '—' ?>
                </td>
                <td>
                  <a class="btn-mini btn-edit"
                     href="<?= BASE_URL ?>/pages/admin_results.php?id=<?= (int)$a['id'] ?>">
                    <i class="bi bi-graph-up"></i> Détails
                  </a>
                  <?php if ($a['status'] === 'closed'): ?>
                    <form method="post" style="display:inline" data-confirm="Archiver définitivement cette élection ?">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="action" value="archive">
                      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                      <button class="btn-mini btn-success" type="submit">
                        <i class="bi bi-archive"></i> Archiver
                      </button>
                    </form>
                  <?php else: ?>
                    <form method="post" style="display:inline" data-confirm="Désarchiver cette élection (statut : clôturée) ?">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="action" value="reopen">
                      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                      <button class="btn-mini" type="submit">
                        <i class="bi bi-arrow-counterclockwise"></i> Désarchiver
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
