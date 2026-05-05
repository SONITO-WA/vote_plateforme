<?php
/**
 * config.php
 * ----------
 * Configuration globale + connexion PDO sécurisée à la base de données.
 * Inclure ce fichier en tête de chaque page PHP via require_once.
 *
 * Oualid Mokrane & Hajar Errahmouni — MGSI
 */

// === Configuration session (IMPORTANT pour Fly + HTTPS) ===
$secure = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.gc_maxlifetime', 86400);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Constantes globales ===
define('APP_NAME', 'VoxENSIASD');
define('APP_TAGLINE', 'Plateforme de Vote Électronique');
define('BASE_URL', '');

// === Paramètres BDD ===
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));        // mot de passe XAMPP par défaut (vide)
define('DB_CHARSET', 'utf8mb4');

// === Connexion PDO ===
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Ne pas exposer les détails sensibles en production
    die('Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()));
}

// === Fonctions utilitaires ===

/**
 * Échappe les sorties HTML pour prévenir XSS.
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Vérifie si l'utilisateur est connecté.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur.
 */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Force la connexion — redirige sinon.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

/**
 * Force le rôle admin — redirige sinon.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

/**
 * Génère un token CSRF pour les formulaires.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valide un token CSRF reçu d'un formulaire.
 */
function checkCsrf(?string $token): bool {
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
