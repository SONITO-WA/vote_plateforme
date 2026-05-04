<?php
/**
 * index.php — Landing Page
 * VoxENSIASD — Plateforme de Vote Électronique
 */
require_once __DIR__ . '/config.php';

// Si déjà connecté, on peut proposer d'aller directement au dashboard
$loggedIn = isLoggedIn();

// Quelques statistiques pour la section "stats"
try {
    $totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $totalElections = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
    $totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
    $openElections  = (int)$pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'open'")->fetchColumn();
} catch (Exception $e) {
    $totalUsers = $totalElections = $totalVotes = $openElections = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="VoxENSIASD — Plateforme de vote électronique pour l'élection des délégués étudiants. Sécurité, transparence, résultats en temps réel.">
    <meta name="keywords" content="vote électronique, élection, délégués, ENSIASD, étudiants, MGSI">
    <meta name="author" content="Oualid Mokrane &amp; Hajar Errahmouni">
    <title>VoxENSIASD — Plateforme de vote électronique</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,800;1,9..144,400;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body class="landing-body">

<!-- ============== TOPBAR ============== -->
<header class="topbar">
    <a href="#hero" class="brand">
        <span class="dot"></span>
        Vox<em>ENSIASD</em>
    </a>
    <nav>
        <ul class="nav-menu">
            <li><a href="#features">Le service</a></li>
            <li><a href="#how">Fonctionnement</a></li>
            <li><a href="#stats">Chiffres</a></li>
            <li><a href="#testimonials">Témoignages</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
    </nav>
    <?php if ($loggedIn): ?>
        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-vote">Mon espace</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/pages/login.php" class="btn-vote">Se connecter</a>
    <?php endif; ?>
</header>

<!-- ============== HERO ============== -->
<section id="hero" class="hero">
    <div class="hero-text">
        <span class="hero-eyebrow">Édition 2025–2026 · ENSIASD Taroudant</span>
        <h1>
            Le vote<br>
            <em>étudiant</em>,<br>
            <span class="underline">réinventé.</span>
        </h1>
        <p>
            Une plateforme civique conçue pour les écoles. Élisez vos délégués
            en quelques clics, dans un environnement sécurisé, transparent,
            et accessible depuis n'importe quel appareil.
        </p>
        <div class="hero-cta">
            <a href="<?= BASE_URL ?>/pages/<?= $loggedIn ? 'elections.php' : 'register.php' ?>" class="btn-primary-x">
                <span>→ Voter maintenant</span>
            </a>
            <a href="#how" class="btn-secondary-x">En savoir plus</a>
        </div>
    </div>

    <div class="hero-visual">
        <div class="ballot-card">
            <h4>Élection</h4>
            <div class="ballot-title">Délégué MGSI 2026</div>
            <div class="candidate-row selected">
                <div class="checkbox"></div>
                <span class="name">Oussama Charif</span>
            </div>
            <div class="candidate-row">
                <div class="checkbox"></div>
                <span class="name">Narjis Lotfi</span>
            </div>
            <div class="candidate-row">
                <div class="checkbox"></div>
                <span class="name">Souhail Ziane</span>
            </div>
            <div class="ballot-stamp">VOTE<br>VALIDÉ<br>2026</div>
        </div>
    </div>
</section>

<!-- ============== FEATURES ============== -->
<section id="features">
    <span class="section-eyebrow">Ce que nous offrons</span>
    <h2 class="section-title">
        Une infrastructure de vote <em>moderne</em>, taillée pour la vie étudiante.
    </h2>

    <div class="features-grid">
        <div class="feature">
            <div class="icon">◉</div>
            <span class="num">01 / Sécurité</span>
            <h3>Un vote, une voix.</h3>
            <p>Chaque étudiant ne peut voter qu'une seule fois par scrutin. Contrainte unique en base, validation côté serveur.</p>
        </div>
        <div class="feature">
            <div class="icon">⌬</div>
            <span class="num">02 / Transparence</span>
            <h3>Résultats en direct.</h3>
            <p>Les chiffres se mettent à jour en temps réel. Aucune zone d'ombre, aucun délai d'annonce officielle.</p>
        </div>
        <div class="feature">
            <div class="icon">⊞</div>
            <span class="num">03 / Simplicité</span>
            <h3>Conçu pour le mobile.</h3>
            <p>Bootstrap responsive : votez depuis votre téléphone, votre tablette ou un ordinateur de la salle informatique.</p>
        </div>
        <div class="feature">
            <div class="icon">⊕</div>
            <span class="num">04 / Archivage</span>
            <h3>Mémoire institutionnelle.</h3>
            <p>Tous les scrutins passés sont conservés. Une trace consultable des décisions collectives de la promotion.</p>
        </div>
    </div>
</section>

<!-- ============== HOW IT WORKS ============== -->
<section id="how">
    <span class="section-eyebrow">Comment ça marche</span>
    <h2 class="section-title">
        Quatre étapes, <em>quelques minutes</em>.
    </h2>

    <div class="steps">
        <div class="step">
            <span class="step-num">i</span>
            <h4>Inscription</h4>
            <p>Créez un compte avec votre email institutionnel.</p>
        </div>
        <div class="step">
            <span class="step-num">ii</span>
            <h4>Connexion</h4>
            <p>Identifiez-vous sur l'espace réservé aux étudiants.</p>
        </div>
        <div class="step">
            <span class="step-num">iii</span>
            <h4>Choix</h4>
            <p>Consultez les programmes, choisissez votre candidat.</p>
        </div>
        <div class="step">
            <span class="step-num">iv</span>
            <h4>Validation</h4>
            <p>Votre vote est enregistré, anonymement et définitivement.</p>
        </div>
    </div>
</section>

<!-- ============== STATS ============== -->
<section id="stats">
    <span class="section-eyebrow">Chiffres</span>
    <h2 class="section-title">
        L'engagement, <em>en chiffres.</em>
    </h2>

    <div class="stats-row">
        <div class="stat">
            <span class="num" data-count="<?= $totalUsers ?>">0</span>
            <span class="label">Étudiants inscrits</span>
        </div>
        <div class="stat">
            <span class="num" data-count="<?= $totalElections ?>">0</span>
            <span class="label">Scrutins organisés</span>
        </div>
        <div class="stat">
            <span class="num" data-count="<?= $totalVotes ?>">0</span>
            <span class="label">Bulletins exprimés</span>
        </div>
        <div class="stat">
            <span class="num"><em data-count="<?= $openElections ?>">0</em></span>
            <span class="label">Élections en cours</span>
        </div>
    </div>
</section>

<!-- ============== TESTIMONIALS ============== -->
<section id="testimonials">
    <span class="section-eyebrow">Témoignages</span>
    <h2 class="section-title">
        Ce qu'<em>ils en disent</em>.
    </h2>

    <div class="testimonials">
        <div class="quote-card">
            <p>Enfin une élection organisée sans urne perdue ni bulletin déchiré. Je vote depuis mon téléphone, le résultat est affiché immédiatement.</p>
            <div class="quote-author">
                <div class="avatar">A</div>
                <div>
                    <div class="who">Errahmouni Hajar</div>
                    <div class="where">2ème année, MGSI</div>
                </div>
            </div>
        </div>
        <div class="quote-card">
            <p>L'interface du tableau de bord administrateur est très claire. J'ai pu créer l'élection, ajouter les candidats et lancer le scrutin en moins de dix minutes.</p>
            <div class="quote-author">
                <div class="avatar">M</div>
                <div>
                    <div class="who">Mohamed Rachid</div>
                    <div class="where">Responsable BDE</div>
                </div>
            </div>
        </div>
        <div class="quote-card">
            <p>La transparence des résultats en temps réel a réconcilié notre promotion avec le processus électoral. On voit tout, on comprend tout.</p>
            <div class="quote-author">
                <div class="avatar">L</div>
                <div>
                    <div class="who">Sofia Dioune</div>
                    <div class="where">Déléguée 2025</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============== CONTACT ============== -->
<section id="contact">
    <span class="section-eyebrow">Contact</span>
    <h2 class="section-title">
        Une <em>question</em> ?<br>
        Écrivez-nous.
    </h2>

    <div class="contact-grid">
        <div class="contact-info">
            <p>
                Une remarque, une suggestion, un bug à signaler ? L'équipe technique
                répond dans les 48 heures ouvrables.
            </p>
            <div class="contact-detail">
                <span class="label">Email</span>
                <span class="value">vox@ensiasd.ma</span>
            </div>
            <div class="contact-detail">
                <span class="label">Adresse</span>
                <span class="value">ENSIASD, Taroudant</span>
            </div>
            <div class="contact-detail">
                <span class="label">Filière responsable</span>
                <span class="value">MGSI · Promotion 2026</span>
            </div>
        </div>

        <form class="contact-form" onsubmit="event.preventDefault(); alert('Merci pour votre message — la prise en charge se fera sous 48h.');">
            <input type="text" placeholder="Votre nom" required>
            <input type="email" placeholder="Votre email" required>
            <input type="text" placeholder="Sujet">
            <textarea placeholder="Votre message" required></textarea>
            <button type="submit" class="btn-primary-x"><span>Envoyer →</span></button>
        </form>
    </div>
</section>

<!-- ============== FOOTER ============== -->
<footer class="footer">
    <div class="footer-grid">
        <div>
            <span class="footer-brand">Vox<em>ENSIASD</em></span>
            <p>
                Plateforme de vote électronique pour les élections étudiantes
                de l'École Nationale Supérieure d'Intelligence Artificielle
                et Sciences des Données — Taroudant.
            </p>
        </div>
        <div>
            <h5>Plateforme</h5>
            <ul class="footer-links">
                <li><a href="#features">Fonctionnalités</a></li>
                <li><a href="#how">Fonctionnement</a></li>
                <li><a href="<?= BASE_URL ?>/pages/login.php">Connexion</a></li>
                <li><a href="<?= BASE_URL ?>/pages/register.php">Inscription</a></li>
            </ul>
        </div>
        <div>
            <h5>Légal</h5>
            <ul class="footer-links">
                <li><a href="#">Confidentialité</a></li>
                <li><a href="#">Conditions d'utilisation</a></li>
                <li><a href="#">Cookies</a></li>
            </ul>
        </div>
        <div>
            <h5>Auteurs</h5>
            <ul class="footer-links">
                <li>Oualid Mokrane</li>
                <li>Hajar Errahmouni</li>
                <li>MGSI · 2025–2026</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 VoxENSIASD — Tous droits réservés</span>
        <span>Conçu à Taroudant · ENSIASD</span>
    </div>
</footer>

<script src="<?= BASE_URL ?>/js/landing.js"></script>
</body>
</html>
