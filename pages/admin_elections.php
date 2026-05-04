<?php
/**
 * pages/admin_elections.php
 * Gestion des élections (créer, ouvrir, fermer, archiver, supprimer).
 */
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Gérer les élections';
$msg = ''; $err = '';

// === Actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkCsrf($_POST['csrf_token'] ?? null)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        if ($title === '') {
            $err = 'Le titre est obligatoire.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO elections (title, description, status) VALUES (:t, :d, "open")');
            $stmt->execute([':t' => $title, ':d' => $desc]);
            $msg = 'Élection créée avec succès.';
        }
    } elseif ($action === 'change_status') {
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['open', 'closed', 'archived'], true)) {
            $stmt = $pdo->prepare('UPDATE elections SET status = :s, closed_at = CASE WHEN :s2 IN ("closed","archived") THEN NOW() ELSE closed_at END WHERE id = :id');
            $stmt->execute([':s' => $newStatus, ':s2' => $newStatus, ':id' => $id]);
            $msg = 'Statut mis à jour.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM elections WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $msg = 'Élection supprimée.';
    }
}

$elections = $pdo->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS nb_candidates,
           (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS nb_votes
    FROM elections e
    ORDER BY e.id DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
    <header class="page-header">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Gestion des <em>élections</em></h1>
        </div>
        <div class="meta"><?= count($elections) ?> ÉLECTION(S)</div>
    </header>

    <?php if ($msg): ?><div class="alert alert-success" style="margin-bottom:20px"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error" style="margin-bottom:20px"><?= e($err) ?></div><?php endif; ?>

    <!-- Formulaire de création -->
    <div class="card-x">
        <span class="card-eyebrow">Action</span>
        <h2>Créer une nouvelle élection</h2>
        <form method="POST" action="admin_elections.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
                <div class="full">
                    <label>Titre de l'élection</label>
                    <input type="text" name="title" class="input-x" placeholder="Ex : Élection des délégués MGSI 2026" required>
                </div>
                <div class="full">
                    <label>Description</label>
                    <textarea name="description" class="input-x" placeholder="Description courte du scrutin..."></textarea>
                </div>
                <div class="full">
                    <button type="submit" class="btn-card">+ Créer l'élection</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des élections -->
    <div class="card-x">
        <span class="card-eyebrow">Liste</span>
        <h2>Toutes les élections</h2>

        <?php if (empty($elections)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Aucune élection</h3>
                <p>Créez votre première élection ci-dessus.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Candidats</th>
                            <th>Votes</th>
                            <th>Créée</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($elections as $e): ?>
                            <tr>
                                <td>
                                    <strong style="font-family:'Fraunces',serif;font-size:16px"><?= e($e['title']) ?></strong>
                                    <?php if (!empty($e['description'])): ?>
                                        <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= e(mb_strimwidth($e['description'], 0, 80, '…')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status status-<?= e($e['status']) ?>"><?= strtoupper(e($e['status'])) ?></span>
                                </td>
                                <td><?= (int)$e['nb_candidates'] ?></td>
                                <td><strong><?= (int)$e['nb_votes'] ?></strong></td>
                                <td><?= date('d.m.Y', strtotime($e['created_at'])) ?></td>
                                <td style="text-align:right;white-space:nowrap">
                                    <a href="admin_candidates.php?election_id=<?= (int)$e['id'] ?>" class="btn-mini btn-edit">Candidats</a>
                                    <a href="admin_results.php?id=<?= (int)$e['id'] ?>" class="btn-mini btn-edit">Résultats</a>

                                    <?php if ($e['status'] === 'open'): ?>
                                        <form method="POST" action="admin_elections.php" style="display:inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                                            <input type="hidden" name="new_status" value="closed">
                                            <button type="submit" class="btn-mini btn-success">Clôturer</button>
                                        </form>
                                    <?php elseif ($e['status'] === 'closed'): ?>
                                        <form method="POST" action="admin_elections.php" style="display:inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                                            <input type="hidden" name="new_status" value="archived">
                                            <button type="submit" class="btn-mini btn-edit">Archiver</button>
                                        </form>
                                        <form method="POST" action="admin_elections.php" style="display:inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                                            <input type="hidden" name="new_status" value="open">
                                            <button type="submit" class="btn-mini btn-success">Rouvrir</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" action="admin_elections.php" style="display:inline" onsubmit="return confirm('Supprimer définitivement cette élection ? Tous les votes associés seront perdus.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                                        <button type="submit" class="btn-mini btn-danger">Suppr.</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
