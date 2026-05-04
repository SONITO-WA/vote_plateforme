<?php
/**
 * includes/navbar.php
 * -------------------
 * Barre latérale du tableau de bord, adaptée selon le rôle de l'utilisateur.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['name'] ?? 'Utilisateur';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>/index.php" class="brand-link">
            <span class="brand-mark">◉</span>
            <span class="brand-text">Vox<em>ENSIASD</em></span>
        </a>
        <p class="brand-sub">Plateforme de Vote</p>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
        <div class="user-info">
            <span class="user-name"><?= e($userName) ?></span>
            <span class="user-role"><?= $role === 'admin' ? 'Administrateur' : 'Étudiant' ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section">Navigation</span>

        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i><span>Tableau de bord</span>
        </a>

        <?php if ($role === 'student'): ?>
            <a href="<?= BASE_URL ?>/pages/elections.php" class="nav-link <?= $currentPage === 'elections.php' ? 'active' : '' ?>">
                <i class="bi bi-list-check"></i><span>Élections en cours</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/results.php" class="nav-link <?= $currentPage === 'results.php' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i><span>Résultats</span>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/admin_elections.php" class="nav-link <?= $currentPage === 'admin_elections.php' ? 'active' : '' ?>">
                <i class="bi bi-collection"></i><span>Gérer élections</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/admin_candidates.php" class="nav-link <?= $currentPage === 'admin_candidates.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i><span>Candidats</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/admin_results.php" class="nav-link <?= $currentPage === 'admin_results.php' ? 'active' : '' ?>">
                <i class="bi bi-graph-up-arrow"></i><span>Résultats temps réel</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/admin_archive.php" class="nav-link <?= $currentPage === 'admin_archive.php' ? 'active' : '' ?>">
                <i class="bi bi-archive"></i><span>Archives</span>
            </a>
        <?php endif; ?>

        <span class="nav-section">Compte</span>
        <a href="<?= BASE_URL ?>/pages/profile.php" class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person"></i><span>Mon profil</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/logout.php" class="nav-link nav-logout">
            <i class="bi bi-box-arrow-right"></i><span>Déconnexion</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <span>ENSIASD · MGSI</span>
        <span>2025–2026</span>
    </div>
</aside>

<!-- Bouton mobile pour ouvrir la sidebar -->
<button class="sidebar-toggle d-md-none" onclick="document.querySelector('.sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
</button>
