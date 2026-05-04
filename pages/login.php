<?php
/**
 * pages/login.php
 * Connexion utilisateur (étudiant ou admin).
 * L'admin peut se connecter avec login = "ENSIASD" .
 * Les étudiants se connectent avec leur email.
 */
require_once __DIR__ . '/../config.php';

// Si déjà connecté → dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session invalide. Veuillez réessayer.';
    } else {
        $login    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $emailValue = $login;

        if ($login === '' || $password === '') {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            // Recherche par email OU par "name" si admin (ENSIASD)
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :login OR (role = "admin" AND email = :login2) LIMIT 1');
            $stmt->execute([':login' => $login, ':login2' => $login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Régénérer la session pour éviter le session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Identifiants incorrects.';
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
    <title>Connexion — VoxENSIASD</title>
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
                Le vote n'est pas seulement un droit, c'est aussi un acte de confiance dans l'institution.
            </p>
        </div>
        <div class="auth-meta">
            ENSIASD · TAROUDANT — 2025–2026
        </div>
    </aside>

    <main class="auth-main">
        <form class="auth-form" method="POST" action="login.php" data-context="login" data-allow-login="true">
            <h1>Connexion</h1>
            <p class="form-sub">Accédez à votre espace de vote.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Compte créé avec succès. Vous pouvez vous connecter.</div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="field">
                <label for="email">Email ou identifiant</label>
                <input type="text" id="email" name="email" placeholder="exemple@ensiasd.ma" value="<?= e($emailValue) ?>" required autofocus>
            </div>

            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Se connecter →</button>

            <p class="auth-alt">
                Pas encore de compte ?
                <a href="register.php">Créer un compte</a>
            </p>

            
        </form>
    </main>

    <script src="<?= BASE_URL ?>/js/landing.js"></script>
</body>
</html>
