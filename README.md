# 🗳️ VoxENSIASD — Plateforme de Vote Électronique

> Mini-Projet Développement Web — Filière MGSI  
> Réalisé par **Oualid Mokrane** & **Hajar Errahmouni**  

---

# 📌 Présentation du Projet

**VoxENSIASD** est une plateforme web sécurisée dédiée à l’organisation et à la gestion des scrutins étudiants (élections de délégués, représentants d’associations, clubs, etc.).

L’application garantit :

- ✅ L’unicité du vote
- ✅ La confidentialité des bulletins
- ✅ La transparence des résultats
- ✅ Un suivi en temps réel des statistiques électorales

Le système propose deux espaces distincts :

- 👨‍🎓 Espace Étudiant
- 👨‍💼 Espace Administrateur

avec des fonctionnalités adaptées à chaque rôle.

---

# 🌐 Hébergement & Démo

## 🔗 Site Web


https://vote-plateforme.ct.ws/


---

### Configuration recommandée

| Technologie | Version recommandée |
|---|---|
| PHP | 8.0+ |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Apache | 2.4+ |

---

# ⚙️ Pré-requis

Avant l’installation, assurez-vous d’avoir :

- XAMPP / WAMP / MAMP / LAMP
- Apache 2.4+
- PHP 7.4 minimum (8.0+ recommandé)
- MySQL 5.7+ ou MariaDB 10.4+
- Un navigateur moderne :
  - Google Chrome
  - Mozilla Firefox
  - Microsoft Edge
  - Safari

---

# 🚀 Installation du Projet (XAMPP)

## Étape 1 — Copier le projet

Copier le dossier :

```txt
VotePlateforme_MGSI/
```

dans :

```txt
C:\xampp\htdocs\
```

Sous Linux :

```txt
/opt/lampp/htdocs/
```

ou

```txt
/var/www/html/
```

---

## Étape 2 — Démarrer Apache & MySQL

Depuis le panneau XAMPP :

- Cliquer sur **Start** pour Apache
- Cliquer sur **Start** pour MySQL

---

## Étape 3 — Importer la Base de Données

Ouvrir :

```txt
http://localhost/phpmyadmin
```

Puis :

1. Aller dans l’onglet **Importer**
2. Sélectionner le fichier :

```txt
projet.sql
```

3. Cliquer sur **Exécuter**

La base de données `vote_plateforme` sera automatiquement créée avec :

- les tables
- les relations
- des données de test

---

## Étape 4 — Lancer l’application

Accéder au projet via :

```txt
http://localhost/VotePlateforme_MGSI/
```

---

# 👤 Comptes de Test

## 👨‍💼 Administrateur

| Champ | Valeur |
|---|---|
| Login | ENSIASD |
| Mot de passe | ENSIASD2026 |
| Rôle | admin |

---

## 👨‍🎓 Étudiants

### Mot de passe commun

```txt
student123
```

### Comptes disponibles

- oualid@ensiasd.ma
- hajar@ensiasd.ma

Les nouveaux utilisateurs peuvent également créer un compte via la page :

```txt
S'inscrire
```

Les mots de passe sont sécurisés avec **bcrypt**.

---

# 📂 Structure du Projet

```txt
VotePlateforme_MGSI/
│
├── index.php
├── config.php
├── projet.sql
│
├── css/
│   ├── style.css
│   └── dashboard.css
│
├── js/
│   ├── landing.js
│   └── dashboard.js
│
├── images/
│
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
│
└── pages/
    ├── login.php
    ├── register.php
    ├── logout.php
    ├── dashboard.php
    ├── elections.php
    ├── vote.php
    ├── results.php
    ├── profile.php
    ├── admin_elections.php
    ├── admin_candidates.php
    ├── admin_results.php
    ├── admin_archive.php
    └── api_results.php


```

---

# 🗄️ Schéma de la Base de Données

## Table `users`

| Champ | Type |
|---|---|
| id | INT PK AUTO_INCREMENT |
| name | VARCHAR(100) |
| email | VARCHAR(150) UNIQUE |
| password | VARCHAR(255) |
| role | ENUM('admin','student') |
| created_at | DATETIME |

---

## Table `elections`

| Champ | Type |
|---|---|
| id | INT PK |
| title | VARCHAR(150) |
| description | TEXT |
| status | ENUM('open','closed','archived') |
| created_at | DATETIME |
| closed_at | DATETIME NULL |

---

## Table `candidates`

| Champ | Type |
|---|---|
| id | INT PK |
| name | VARCHAR(100) |
| program | TEXT |
| election_id | FK → elections(id) |

---

## Table `votes`

| Champ | Type |
|---|---|
| id | INT PK |
| user_id | FK → users(id) |
| candidate_id | FK → candidates(id) |
| election_id | FK → elections(id) |
| voted_at | DATETIME |

### Contrainte importante

```sql
UNIQUE(user_id, election_id)
```

Cette contrainte empêche un étudiant de voter plusieurs fois dans le même scrutin.

---

# 🔐 Sécurité

Le projet implémente plusieurs mécanismes de sécurité :

- Hashage des mots de passe avec :
  ```php
  password_hash()
  ```
- Utilisation de :
  ```php
  PASSWORD_BCRYPT
  ```
- Requêtes SQL sécurisées avec PDO + paramètres préparés
- Protection CSRF sur les formulaires sensibles
- Régénération des sessions avec :
  ```php
  session_regenerate_id()
  ```
- Protection contre le double vote
- Échappement HTML via la fonction `e()`
- Validation des données côté client et côté serveur

---

# ✨ Fonctionnalités

## 👨‍🎓 Espace Étudiant

- Consulter les élections ouvertes
- Voter une seule fois par scrutin
- Voir les résultats après participation
- Modifier son mot de passe
- Consulter son tableau de bord

---

## 👨‍💼 Espace Administrateur

- Créer des scrutins
- Modifier les élections
- Clôturer ou archiver un scrutin
- Supprimer des élections
- Gérer les candidats
- Voir les résultats en temps réel
- Consulter les statistiques globales
- Accéder aux archives

---

# 📊 Résultats en Temps Réel

Le système intègre :

- AJAX Polling toutes les 5 secondes
- Graphiques dynamiques avec Chart.js
- Actualisation automatique des statistiques

---

# 🛠️ Technologies Utilisées

## Frontend

- HTML5
- CSS3
- Bootstrap 5.3
- JavaScript ES6
- Chart.js 4

## Backend

- PHP 7.4+
- PDO MySQL

## Base de données

- MySQL
- MariaDB
- UTF8MB4

---

# 📱 Compatibilité & Responsive Design

Le projet a été testé sur :

- Google Chrome 120+
- Mozilla Firefox 121+
- Microsoft Edge 120+

Le site est entièrement responsive :

- 💻 Desktop
- 📱 Mobile
- 📲 Tablette

---

# 👨‍💻 Auteurs

- **Oualid Mokrane**
- **Hajar Errahmouni**

Filière : **Management et Gouvernance des Systèmes d’Information (MGSI)**  
ENSIASD Taroudant

---
