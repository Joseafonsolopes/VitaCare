# VitaCare - Plateforme de santé et bien-être

## Description
VitaCare est une plateforme web dynamique de réservation et de gestion de services de santé et de bien-être. Elle permet à des utilisateurs de réserver des activités proposées par des intervenants, et de suivre leur progression via des programmes.

## Technologies utilisées
- **Frontend** : HTML, CSS, JavaScript, React (via CDN Babel)
- **Backend** : PHP 8.3
- **Base de données** : MySQL 8.4
- **Serveur local** : WAMP 3.4

## Prérequis
- WAMP Server (ou équivalent : XAMPP, MAMP)
- Navigateur web moderne

## Installation

### Étape 1 — Copier les fichiers
Copier le dossier `vitacare_v2` dans le répertoire web de WAMP :
```
C:\wamp64\www\vitacare_v2\
```

### Étape 2 — Importer la base de données
1. Lancer WAMP et ouvrir phpMyAdmin : `http://localhost/phpmyadmin5.2.3`
2. Connexion : utilisateur `root`, mot de passe vide
3. Cliquer sur **Importer**
4. Sélectionner le fichier `php/vitacare.sql`
5. Cliquer sur **Exécuter**

### Étape 3 — Installer les tables supplémentaires
Ouvrir dans le navigateur :
```
http://localhost/vitacare_v2/install_programmes.php
```

### Étape 4 — Corriger l'encodage des emojis
```
http://localhost/vitacare_v2/fix_emojis.php
```

### Étape 5 — Lancer le site
```
http://localhost/vitacare_v2/index.php
```

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@vitacare.fr | vitacare123 |
| Intervenant | marie.dupont@vitacare.fr | vitacare123 |
| Utilisateur | natheo.milliet@vitacare.fr | vitacare123 |

## Structure des fichiers
```
vitacare_v2/
├── index.php                   ← Page d'accueil
├── services.php                ← Catalogue des services
├── reservation.php             ← Réservation d'un service
├── profil.php                  ← Profil utilisateur
├── connexion.php               ← Connexion
├── inscription.php             ← Inscription
├── deconnexion.php             ← Déconnexion
├── notifications.php           ← Notifications
├── programmes.php              ← Programmes disponibles
├── dashboard_admin.php         ← Espace administrateur
├── dashboard_intervenant.php   ← Espace intervenant
├── php/
│   ├── connexion.php           ← Connexion BDD
│   ├── get_services.php        ← API services
│   ├── get_creneaux.php        ← API créneaux
│   ├── reserver.php            ← API réservation
│   └── vitacare.sql            ← Script SQL
└── css/
    ├── global.css              ← Styles globaux + navbar
    ├── accueil.css
    ├── services.css
    ├── reservation.css
    ├── profil.css
    ├── auth.css
    └── dashboard.css
```

## Fonctionnalités principales
- Inscription et connexion avec 3 rôles (admin, intervenant, utilisateur)
- Catalogue des services avec filtres et recherche
- Réservation avec calendrier dynamique et créneaux réels
- Annulation de réservation
- Espace intervenant : création de services, créneaux et programmes
- Espace admin : gestion des utilisateurs, création de comptes
- Profil utilisateur avec historique, stats et progression des programmes
- Système de notifications
