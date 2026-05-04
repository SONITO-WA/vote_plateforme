<?php
/**
 * pages/vote.php
 * Page de vote — l'étudiant choisit son candidat pour une élection donnée.
 * Sécurité : un seul vote par étudiant et par élection.
 */
require_once __DIR__ . '/../config.php';
requireLogin();

if ($_SESSION['role'] === 'admin') {
    header('Location: admin_elections.php');
    exit;
}

$userId     = $_SESSION['user_id'];
$electionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error      = '';

if ($electionId <= 0) {
    header('Location: elections.php');
    exit;
}

// Récupérer l'élection
$stmt = $pdo->prepare('SELECT * FROM elections WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $electionId]);
$election = $stmt->fetch();

if (!$election) {
    header('Location: elections.php');
    exit;
}

// Vérifier que l'élection est ouverte
if ($election['status'] !== 'open') {
    header('Location: results.php?id=' . $electionId);
    exit;
}

// Vérifier que l'étudiant n'a pas déjà voté
$stmt = $pdo->prepare('SELECT id FROM votes WHERE user_id = :u AND election_id = :e LIMIT 1');
$stmt->execute([':u' => $userId, ':e' => $electionId]);
if ($stmt->fetch()) {
    header('Location: results.php?id=' . $electionId);
    exit;
}

// === Traitement du vote ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session invalide. Veuillez réessayer.';
    } else {
        $candidateId = isset($_POST['candidate_id']) ? (int)$_POST['candidate_id'] : 0;

        // Vérifier que le candidat appartient bien à cette élection
        $stmt = $pdo->prepare('SELECT id FROM candidates WHERE id = :c AND election_id = :e LIMIT 1');
        $stmt->execute([':c' => $candidateId, ':e' => $electionId]);
        if (!$stmt->fetch()) {
            $error = 'Candidat invalide.';
        } else {
            // Insertion sécurisée — la contrainte UNIQUE empêche le double vote
            try {
                $stmt = $pdo->prepare('INSERT INTO votes (user_id, candidate_id, election_id) VALUES (:u, :c, :e)');
                $stmt->execute([':u' => $userId, ':c' => $candidateId, ':e' => $electionId]);

                header('Location: results.php?id=' . $electionId . '&voted=1');
                exit;
            } catch (PDOException $ex) {
                // Code 23000 = duplicate key (vote unique)
                if ($ex->getCode() === '23000') {
                    header('Location: results.php?id=' . $electionId);
                    exit;
                }
                $error = 'Erreur lors de l\'enregistrement du vote.';
            }
        }
    }
}

// Récupérer les candidats
$stmt = $pdo->prepare('SELECT * FROM candidates WHERE election_id = :e ORDER BY id ASC');
$stmt->execute([':e' => $electionId]);
$candidates = $stmt->fetchAll();

$pageTitle = 'Voter — ' . $election['title'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
    <div class="vote-container">
        <header class="vote-header">
            <p class="eyebrow">BULLETIN OFFICIEL</p>
            <h1><?= e($election['title']) ?></h1>
            <p style="color:var(--muted);margin:0;max-width:520px;margin:8px auto 0">
                <?= e($election['description'] ?? '') ?>
            </p>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (empty($candidates)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h3>Aucun candidat</h3>
                <p>Aucun candidat n'a encore été enregistré pour cette élection.</p>
                <a href="elections.php" class="btn-card btn-outline">← Retour</a>
            </div>
        <?php else: ?>
            <form method="POST" action="vote.php?id=<?= $electionId ?>" id="vote-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <p style="font-family:'JetBrains Mono',monospace;font-size:12px;letter-spacing:0.15em;color:var(--muted);text-transform:uppercase;margin-bottom:20px">
                    SÉLECTIONNEZ UN CANDIDAT — UN SEUL CHOIX POSSIBLE
                </p>

                <?php foreach ($candidates as $i => $c): ?>
                    <label class="candidate-option">
                        <input type="radio" name="candidate_id" value="<?= (int)$c['id'] ?>" required>
                        <span class="check-mark"></span>
                        <div class="candidate-info">
                            <span class="name"><?= e($c['name']) ?></span>
                            <span class="program"><?= e($c['program'] ?? 'Programme non communiqué.') ?></span>
                        </div>
                        <span class="num-badge">N°<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                    </label>
                <?php endforeach; ?>

                <div class="vote-submit">
                    <p class="vote-warning">⚠ Votre vote est définitif et anonyme.</p>
                    <div style="display:flex;gap:12px">
                        <a href="elections.php" class="btn-card btn-outline">Annuler</a>
                        <button type="submit" class="btn-card">✓ Confirmer mon vote</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
