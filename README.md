================================================================
  VoxENSIASD — Plateforme de Vote Électronique
  Mini-Projet Développement Web — Filière MGSI
  Étudiants : Oualid Mokrane & Hajar Errahmouni
  ENSIASD Taroudant
================================================================


1. PRÉSENTATION
---------------
VoxENSIASD est une plateforme web sécurisée d'organisation de
scrutins étudiants (élection de délégués, membres d'associations…).
Elle garantit l'unicité du vote, la confidentialité des bulletins
et l'affichage transparent des résultats en temps réel.


2. PRÉ-REQUIS
-------------
- XAMPP (ou WAMP / MAMP / LAMP)        — serveur local
- Apache 2.4+                          — serveur web
- PHP 7.4 minimum (recommandé : 8.0+)  — interpréteur
- MySQL 5.7+ ou MariaDB 10.4+          — base de données
- Navigateur récent (Chrome, Firefox, Edge, Safari)


3. INSTALLATION (XAMPP — pas à pas)
------------------------------------
  Étape 1 — Copier le projet
    Copier le dossier VotePlateforme_MGSI/ entier dans :
        C:\xampp\htdocs\
    (sous Linux : /opt/lampp/htdocs/  ou  /var/www/html/)

  Étape 2 — Démarrer Apache + MySQL
    Ouvrir le panneau XAMPP.
    Cliquer sur "Start" pour Apache et MySQL.

  Étape 3 — Importer la base de données
    Ouvrir : http://localhost/phpmyadmin
    Onglet "Importer" → choisir le fichier projet.sql
    (situé à la racine du projet) → "Exécuter".
    Une base "vote_plateforme" est créée avec ses tables et
    quelques données de test.

  Étape 4 — Accéder à l'application
        http://localhost/VotePlateforme_MGSI/


4. COMPTES PAR DÉFAUT
---------------------
  ┌────────────────────────────────────────────────────────────┐
  │ ADMINISTRATEUR (compte exigé par le cahier des charges)    │
  │   Login    : ENSIASD                                       │
  │   Password : ENSIASD2026                                   │
  │   Rôle     : admin                                         │
  └────────────────────────────────────────────────────────────┘

  ┌────────────────────────────────────────────────────────────┐
  │ ÉTUDIANTS (mot de passe commun pour les tests)             │
  │   Password : student123                                    │
  │                                                            │
  │   - oualid.mokrane@ensiasd.ma     (Oualid Mokrane)         │
  │   - hajar.errahmouni@ensiasd.ma   (Hajar Errahmouni)       │
  └────────────────────────────────────────────────────────────┘

L'inscription d'autres étudiants est possible via la page
"S'inscrire" ; le mot de passe choisi est alors hashé via bcrypt.


5. STRUCTURE DU PROJET
-----------------------
  VotePlateforme_MGSI/
  │
  ├── index.php                  Landing page publique
  ├── config.php                 Connexion PDO + helpers globaux
  ├── projet.sql                 Schéma + données de test
  │
  ├── css/
  │   ├── style.css              Styles landing + auth
  │   └── dashboard.css          Styles tableau de bord
  │
  ├── js/
  │   ├── landing.js             Animations + validation front
  │   └── dashboard.js           Chart.js + AJAX polling
  │
  ├── images/                    (logos, illustrations)
  │
  ├── includes/
  │   ├── header.php             <head>, polices, CSS
  │   ├── navbar.php             Sidebar avec liens par rôle
  │   └── footer.php             Scripts (Bootstrap, Chart.js)
  │
  ├── pages/
  │   ├── login.php              Connexion (email OU "ENSIASD")
  │   ├── register.php           Inscription étudiant
  │   ├── logout.php             Déconnexion
  │   ├── dashboard.php          Tableau de bord (admin/étudiant)
  │   ├── elections.php          Liste des scrutins (étudiant)
  │   ├── vote.php               Bulletin de vote
  │   ├── results.php            Résultats (étudiant)
  │   ├── profile.php            Profil + changement mot de passe
  │   ├── admin_elections.php    CRUD des élections
  │   ├── admin_candidates.php   CRUD des candidats
  │   ├── admin_results.php      Résultats temps réel (admin)
  │   ├── admin_archive.php      Archives des scrutins
  │   └──api_results.php         Endpoint JSON (AJAX polling)
  │    
  │
  └── doc/
      ├── Fiche.pdf                                   Fiche de validation remplie
      ├── README.txt                              Ce fichier
      ├── plateforme_rapport.pdf          Rapport 
      ├── plateforme_rapport.docx
      ├── captures/              Captures d'écran (PNG)
      └── diagrammes/            MCD + MLD (Looping)


6. SCHÉMA DE LA BASE DE DONNÉES
--------------------------------
  Table users
    id        INT, PK, AUTO_INCREMENT
    name      VARCHAR(100)
    email     VARCHAR(150) UNIQUE
    password  VARCHAR(255)        — hash bcrypt
    role      ENUM('admin','student')
    created_at DATETIME

  Table elections
    id          INT, PK
    title       VARCHAR(150)
    description TEXT
    status      ENUM('open','closed','archived')
    created_at  DATETIME
    closed_at   DATETIME NULL

  Table candidates
    id          INT, PK
    name        VARCHAR(100)
    program     TEXT
    election_id INT, FK → elections(id) ON DELETE CASCADE

  Table votes
    id           INT, PK
    user_id      INT, FK → users(id)
    candidate_id INT, FK → candidates(id)
    election_id  INT, FK → elections(id)
    voted_at     DATETIME
    UNIQUE(user_id, election_id)   


7. SÉCURITÉ
-----------
- Mots de passe hashés via password_hash() / PASSWORD_BCRYPT
- 100 % des requêtes SQL utilisent PDO + paramètres préparés
- Tokens CSRF sur tous les formulaires sensibles
- session_regenerate_id() à la connexion
- Contrainte UNIQUE(user_id, election_id) en base : double
  protection (PHP + SGBD) contre le double vote
- Échappement HTML systématique via la fonction e()
- Validation des entrées côté serveur ET côté client


8. FONCTIONNALITÉS
------------------
  Étudiant :
    • Voir les scrutins ouverts
    • Voter une seule fois par scrutin
    • Consulter les résultats après avoir voté
    • Modifier son mot de passe

  Administrateur :
    • Créer / clôturer / archiver / supprimer un scrutin
    • Gérer les candidats (ajout, modification, suppression)
    • Suivre les résultats en temps réel (AJAX 5 s + Chart.js)
    • Consulter les archives des scrutins passés
    • Statistiques globales (utilisateurs, votes, participation)


9. TECHNOLOGIES
---------------
  Frontend : HTML5, CSS3, Bootstrap 5.3, JavaScript ES6, Chart.js 4
  Backend  : PHP 7.4+ (PDO MySQL)
  Base     : MySQL / MariaDB (UTF-8 mb4)


10. NAVIGATEURS TESTÉS
-----------------------
  - Google Chrome 120+
  - Mozilla Firefox 121+
  - Microsoft Edge 120+
  - Responsive : desktop / tablette / mobile (≥ 360 px)


================================================================
