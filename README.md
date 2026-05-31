# 🏥 VitaCare — Plateforme de santé et bien-être pour seniors

> Application web dynamique de réservation et de gestion de services de santé, pensée pour les personnes de 40 ans et plus souhaitant préserver leur autonomie.

---

## 👥 Équipe

| Nom                    | Rôle                                          |
| ---------------------- | --------------------------------------------- |
| José Afonso Lopes      | Backend — endpoints PHP, sessions, sécurité   |
| Nathéo Milliet-Treboux | Frontend — accueil, catalogue, réservation, connexion |
| Eliott Munoz           | Intégration — dashboards patient, intervenant, admin |

---

## 📌 Description

VitaCare est une application web développée dans le cadre du module **Projet Web Dynamique** à ECE Paris (ING2, 2026).

La plateforme permet aux utilisateurs de :

- Consulter un catalogue de services de santé et de bien-être
- Réserver des rendez-vous avec des professionnels de santé
- S'inscrire à des activités et programmes de bien-être
- Suivre leurs consultations et leur historique
- Recevoir des notifications liées à leurs réservations

Trois rôles sont distincts : **Patient** (consulte, réserve, suit son historique), **Intervenant** (gère ses services et créneaux), **Administrateur** (accès complet, modération).

---

## 🛠️ Stack technique

| Couche          | Technologies                          |
| --------------- | ------------------------------------- |
| Frontend        | React 18 (via Babel in-browser), HTML, CSS |
| Backend         | PHP 8.1, sessions PHP natives         |
| Base de données | MySQL 8                               |
| Serveur local   | WAMP / phpMyAdmin                     |
| Versioning      | Git / GitHub                          |

> **Note :** React est chargé directement via Babel Standalone — aucune dépendance Node.js, pas de build Vite/npm nécessaire.

---

## 📁 Structure du projet

```
VitaCare/
├── code/                          # Code source (versions successives)
│   ├── VITACARE.v1/
│   ├── vitacare_v2/ … vitacare_v9/  # Itérations du projet
│   ├── Rapport Du code            # Rapport technique
│   └── Utilisation !              # Guide d'utilisation
├── docs/                          # Documentation
│   ├── MCD.png                    # Modèle Conceptuel de Données
│   ├── architecture.png           # Schéma d'architecture
│   ├── VitaCare_Specifications_Fonctionnelles-final.pdf
│   └── Presentation - VitaCare Plateforme de santé (2).pdf
├── maquette/                      # Maquettes UI
│   ├── maquette.pdf / .docx
│   ├── Storyboard.pdf / .docx
│   └── lien figma.txt             # Lien vers le prototype Figma
├── .gitignore
└── README.md
```

---

## ⚙️ Installation

### Prérequis

- WAMP / XAMPP / Laragon (PHP 8.1+ + MySQL 8+)
- phpMyAdmin (ou client MySQL)

### 1. Télécharger le projet

Télécharger le dossier `code/vitacare_v9/` et le placer dans le répertoire `www/` (WAMP) ou `htdocs/` (XAMPP).

### 2. Base de données

Ouvrir **phpMyAdmin** (`http://localhost/phpmyadmin`), créer une nouvelle base de données, puis importer le fichier suivant :

```
code/vitacare_v9/php/vitacare.sql
```

Ensuite, ouvrir `code/vitacare_v9/php/db.php` et renseigner les identifiants de connexion (nom de la base, utilisateur, mot de passe).

### 3. Lancer l'application

Démarrer WAMP/XAMPP puis accéder à :

```
http://localhost/VitaCare/code/vitacare_v9/
```

---

## 🚀 Fonctionnalités

**Priorité haute**
- [x] Authentification et gestion des rôles (patient, intervenant, admin)
- [x] Catalogue des services avec recherche et filtres
- [x] Réservation de créneaux avec gestion des disponibilités
- [x] Dashboard utilisateur (patient, intervenant, admin)

**Priorité moyenne**
- [x] Notifications
- [ ] Panier de réservations (paiement simulé, non fonctionnel)
- [ ] Tableau de bord administrateur complet

---

## ⚠️ Limites connues

- Tourne uniquement en local (WAMP/XAMPP)
- Paiement simulé, non fonctionnel
- Pas de tests automatisés
- Conflits de créneaux en réservation simultanée non entièrement résolus

---

## 📄 Livrables

| Livrable                      | Deadline                     |
| ----------------------------- | ---------------------------- |
| Premier livrable (conception) | Jeudi 28 mai 2026 à 23h55    |
| Projet final                  | Dimanche 31 mai 2026 à 23h55 |

---

## 🤖 IA utilisée

Claude, ChatGPT et GitHub Copilot ont assisté le développement (génération de code PHP/React, debug, documentation). L'utilisation est détaillée dans le journal d'IA inclus dans la présentation PowerPoint.

---

## 📚 Références

- Sujet du projet : `Sujet_6_-_VitaCare.pdf`
- PHP 8.1 : https://www.php.net
- React 18 : https://react.dev
- MySQL 8 : https://dev.mysql.com/doc
- Babel Standalone : https://babeljs.io/docs/babel-standalone
- MDN fetch() API : https://developer.mozilla.org/fr/docs/Web/API/Fetch_API

---

*ECE Paris — ING2 — Projet Web Dynamique 2026*
