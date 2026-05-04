<?php
/**
 * pages/admin_candidates.php
 * Gestion des candidats par élection (CRUD).
 *
 * Oualid Mokrane & Hajar Errahmouni — MGSI
 */
require_once __DIR__ . '/../config.php';
requireAdmin();

$pageTitle = 'Gérer les candidats';
$msg = '';
$err = '';

// === Élection sélectionnée ===
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

// === Actions POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkCsrf($_POST['csrf_token'] ?? null)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name    = trim($_POST['name'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $eid     = (int)($_POST['election_id'] ?? 0);

        if ($name === '' || $eid <= 0) {
            $err = 'Le nom du candidat et l\'élection sont obligatoires.';
        } else {
            // Vérifier que l'élection existe et n'a pas encore de votes
            $check = $pdo->prepare('SELECT e.id, (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS nb FROM elections e WHERE e.id = :id');
            $check->execute([':id' => $eid]);
            $row = $check->fetch();
            if (!$row) {
                $err = 'Élection introuvable.';
            } elseif ((int)$row['nb'] > 0) {
                $err = 'Impossible d\'ajouter un candidat : des votes ont déjà été enregistrés pour cette élection.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO candidates (name, program, election_id) VALUES (:n, :p, :e)');
                $stmt->execute([':n' => $name, ':p' => $program, ':e' => $eid]);
                $msg = 'Candidat ajouté avec succès.';
                $electionId = $eid;
            }
        }
    } elseif ($action === 'update') {
        $id      = (int)($_POST['id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $program = trim($_POST['program'] ?? '');

        if ($id <= 0 || $name === '') {
            $err = 'Données invalides.';
        } else {
            $stmt = $pdo->prepare('UPDATE candidates SET name = :n, program = :p WHERE id = :id');
            $stmt->execute([':n' => $name, ':p' => $program, ':id' => $id]);
            $msg = 'Candidat mis à jour.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Empêcher la suppression si des votes existent déjà
        $check = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE candidate_id = :id');
        $check->execute([':id' => $id]);
        if ((int)$check->fetchColumn() > 0) {
            $err = 'Impossible de supprimer : ce candidat a déjà reçu des votes.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM candidates WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $msg = 'Candidat supprimé.';
        }
    }
}

// === Liste des élections (pour le sélecteur) ===
$elections = $pdo->query('SELECT id, title, status FROM elections ORDER BY created_at DESC')->fetchAll();

// === Élection courante + ses candidats ===
$currentElection = null;
$candidates = [];
if ($electionId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM elections WHERE id = :id');
    $stmt->execute([':id' => $electionId]);
    $currentElection = $stmt->fetch();

    if ($currentElection) {
        $stmt = $pdo->prepare('
            SELECT c.*, (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) AS nb_votes
            FROM candidates c
            WHERE c.election_id = :eid
            ORDER BY c.id ASC
        ');
        $stmt->execute([':eid' => $electionId]);
        $candidates = $stmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
  <header class="page-header">
    <div>
      <p class="card-eyebrow">— Administration</p>
      <h1>Candidats</h1>
      <p class="muted">Définissez la liste des candidats pour chaque scrutin.</p>
    </div>
    <div class="page-header-actions">
      <a href="<?= BASE_URL ?>/pages/admin_elections.php" class="btn-mini">
        <i class="bi bi-arrow-left"></i> Retour aux élections
      </a>
    </div>
  </header>

  <?php if ($msg): ?><div class="alert-x alert-success"><i class="bi bi-check-circle"></i> <?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert-x alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div><?php endif; ?>

  <!-- Sélecteur d'élection -->
  <section class="card-x">
    <h2 class="card-eyebrow card-x-title">Choisir une élection</h2>
    <form method="get" class="form-inline-x">
      <select name="election_id" class="form-select-x" onchange="this.form.submit()">
        <option value="0">— Sélectionner une élection —</option>
        <?php foreach ($elections as $el): ?>
          <option value="<?= (int)$el['id'] ?>" <?= $electionId === (int)$el['id'] ? 'selected' : '' ?>>
            <?= e($el['title']) ?> (<?= e($el['status']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn-mini btn-edit" type="submit">Afficher</button></noscript>
    </form>
  </section>

  <?php if ($currentElection): ?>

    <!-- Formulaire d'ajout -->
    <?php if ($currentElection['status'] !== 'archived'): ?>
    <section class="card-x">
      <h2 class="card-eyebrow card-x-title">
        <i class="bi bi-person-plus"></i> Ajouter un candidat à « <?= e($currentElection['title']) ?> »
      </h2>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="election_id" value="<?= (int)$electionId ?>">

        <div class="field">
          <label for="name">Nom complet du candidat</label>
          <input id="name" name="name" type="text" required maxlength="100" placeholder="ex. Hajar Errahmouni">
        </div>

        <div class="field">
          <label for="program">Programme / slogan (optionnel)</label>
          <textarea id="program" name="program" rows="2" maxlength="500" placeholder="Quelques lignes sur la vision du candidat..."></textarea>
        </div>

        <div class="field">
          <button type="submit" class="btn-cta">
            <i class="bi bi-check2"></i> Ajouter le candidat
          </button>
        </div>
      </form>
    </section>
    <?php endif; ?>

    <!-- Liste des candidats -->
    <section class="card-x">
      <h2 class="card-eyebrow card-x-title">
        Candidats inscrits — <?= count($candidates) ?>
      </h2>

      <?php if (empty($candidates)): ?>
        <div class="empty-state">
          <i class="bi bi-people"></i>
          <p>Aucun candidat pour le moment.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:60px;">N°</th>
                <th>Nom</th>
                <th>Programme</th>
                <th style="width:90px;">Votes</th>
                <th style="width:200px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($candidates as $i => $c): ?>
                <tr>
                  <td><span class="num-badge"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span></td>
                  <td><strong><?= e($c['name']) ?></strong></td>
                  <td class="muted"><?= e($c['program'] ?? '') ?: '<em>—</em>' ?></td>
                  <td><strong><?= (int)$c['nb_votes'] ?></strong></td>
                  <td>
                    <button type="button"
                      class="btn-mini btn-edit"
                      onclick="toggleEdit(<?= (int)$c['id'] ?>)">
                      <i class="bi bi-pencil"></i> Modifier
                    </button>
                    <?php if ((int)$c['nb_votes'] === 0): ?>
                    <form method="post" style="display:inline" data-confirm="Supprimer ce candidat ?">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button class="btn-mini btn-danger" type="submit">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <!-- Ligne d'édition cachée -->
                <tr id="edit-<?= (int)$c['id'] ?>" class="edit-row" hidden>
                  <td colspan="5">
                    <form method="post" class="form-grid">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <div class="field">
                        <label>Nom</label>
                        <input name="name" type="text" required value="<?= e($c['name']) ?>">
                      </div>
                      <div class="field">
                        <label>Programme</label>
                        <textarea name="program" rows="2"><?= e($c['program'] ?? '') ?></textarea>
                      </div>
                      <div class="field" style="display:flex;gap:.5rem;">
                        <button class="btn-cta" type="submit"><i class="bi bi-save"></i> Enregistrer</button>
                        <button class="btn-mini" type="button" onclick="toggleEdit(<?= (int)$c['id'] ?>)">Annuler</button>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-arrow-up"></i>
      <p>Choisissez une élection ci-dessus pour gérer ses candidats.</p>
    </div>
  <?php endif; ?>
</main>

<script>
function toggleEdit(id) {
  const row = document.getElementById('edit-' + id);
  if (row) row.hidden = !row.hidden;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
