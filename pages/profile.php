<?php
/**
 * pages/profile.php
 * Profil de l'utilisateur (lecture seule + changement de mot de passe).
 */
require_once __DIR__ . '/../config.php';
requireLogin();

$pageTitle = 'Mon profil';
$userId = $_SESSION['user_id'];
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && checkCsrf($_POST['csrf_token'] ?? null)) {
    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd     = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    if ($currentPwd === '' || $newPwd === '' || $confirmPwd === '') {
        $err = 'Veuillez remplir tous les champs.';
    } elseif (strlen($newPwd) < 6) {
        $err = 'Le nouveau mot de passe doit faire au moins 6 caractères.';
    } elseif ($newPwd !== $confirmPwd) {
        $err = 'La confirmation ne correspond pas.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPwd, $user['password'])) {
            $err = 'Mot de passe actuel incorrect.';
        } else {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
            $stmt->execute([':p' => $hash, ':id' => $userId]);
            $msg = 'Mot de passe modifié avec succès.';
        }
    }
}

// Stats utilisateur
$stmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE user_id = :u');
$stmt->execute([':u' => $userId]);
$voteCount = (int)$stmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="dashboard-main">
    <header class="page-header">
        <div>
            <p class="eyebrow">Compte</p>
            <h1>Mon <em>profil</em></h1>
        </div>
        <div class="meta">ID #<?= $userId ?></div>
    </header>

    <div class="card-x">
        <span class="card-eyebrow">Informations</span>
        <h2>Coordonnées</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">
            <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:0.15em;color:var(--muted);text-transform:uppercase">Nom complet</div>
                <div style="font-family:'Fraunces',serif;font-size:22px;margin-top:4px"><?= e($_SESSION['name']) ?></div>
            </div>
            <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:0.15em;color:var(--muted);text-transform:uppercase">Email</div>
                <div style="font-family:'Fraunces',serif;font-size:22px;margin-top:4px"><?= e($_SESSION['email']) ?></div>
            </div>
            <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:0.15em;color:var(--muted);text-transform:uppercase">Rôle</div>
                <div style="font-family:'Fraunces',serif;font-size:22px;margin-top:4px"><?= $_SESSION['role'] === 'admin' ? 'Administrateur' : 'Étudiant' ?></div>
            </div>
            <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:0.15em;color:var(--muted);text-transform:uppercase">Bulletins exprimés</div>
                <div style="font-family:'Fraunces',serif;font-size:22px;margin-top:4px;color:var(--gold-deep)"><em><?= $voteCount ?></em></div>
            </div>
        </div>
    </div>

    <div class="card-x">
        <span class="card-eyebrow">Sécurité</span>
        <h2>Changer de mot de passe</h2>

        <?php if ($msg): ?><div class="alert alert-success" style="margin-bottom:20px"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error" style="margin-bottom:20px"><?= e($err) ?></div><?php endif; ?>

        <form method="POST" action="profile.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="form-grid">
                <div class="full">
                    <label>Mot de passe actuel</label>
                    <input type="password" name="current_password" class="input-x" required>
                </div>
                <div>
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="input-x" required>
                </div>
                <div>
                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" class="input-x" required>
                </div>
                <div class="full" style="margin-top:10px">
                    <button type="submit" class="btn-card">Mettre à jour</button>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
