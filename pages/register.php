<?php
/**
 * pages/register.php
 * Inscription d'un nouvel étudiant.
 */
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session invalide. Veuillez réessayer.';
    } else {
        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $old['name']  = $name;
        $old['email'] = $email;

        if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format d\'email invalide.';
        } elseif (strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            // Vérifier unicité de l'email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'Un compte existe déjà avec cet email.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:n, :e, :p, "student")');
                $stmt->execute([':n' => $name, ':e' => $email, ':p' => $hash]);
                header('Location: login.php?registered=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — VoxENSIASD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,800;1,9..144,400;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body class="auth-body">

    <aside class="auth-aside">
        <div class="brand-block">
            <span class="brand-mark">◉</span>
            <a href="<?= BASE_URL ?>/index.php" style="color:inherit;text-decoration:none">
                <h2>Vox<em>ENSIASD</em></h2>
            </a>
            <p class="lead-quote">
                Rejoignez la communauté électorale étudiante. Un compte, un vote, une voix qui compte.
            </p>
        </div>
        <div class="auth-meta">
            INSCRIPTION SÉCURISÉE — RGPD COMPATIBLE
        </div>
    </aside>

    <main class="auth-main">
        <form class="auth-form" method="POST" action="register.php" data-context="register">
            <h1>Inscription</h1>
            <p class="form-sub">Créez votre compte étudiant pour participer aux élections.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="field">
                <label for="name">Nom complet</label>
                <input type="text" id="name" name="name" placeholder="Prénom Nom" value="<?= e($old['name']) ?>" required>
            </div>

            <div class="field">
                <label for="email">Email institutionnel</label>
                <input type="email" id="email" name="email" placeholder="prenom@ensiasd.ma" value="<?= e($old['email']) ?>" required>
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="6 caractères minimum" required>
                <div class="password-strength"><div class="bar"></div></div>
                <span class="strength-label"></span>
            </div>

            <div class="field">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Créer mon compte →</button>

            <p class="auth-alt">
                Déjà inscrit ?
                <a href="login.php">Se connecter</a>
            </p>

            <p style="font-size:11px;color:var(--muted);margin-top:24px;font-family:'JetBrains Mono',monospace;letter-spacing:0.05em">
                EN CRÉANT UN COMPTE, VOUS ACCEPTEZ NOTRE
                <a href="#" style="color:var(--gold-deep)">POLITIQUE DE CONFIDENTIALITÉ</a>
                ET NOS
                <a href="#" style="color:var(--gold-deep)">CONDITIONS D'UTILISATION</a>.
            </p>
        </form>
    </main>

    <script src="<?= BASE_URL ?>/js/landing.js"></script>
</body>
</html>
