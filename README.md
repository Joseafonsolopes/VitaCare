# 🏥 VitaCare — Les services de santé et bien-être de notre époque

> Plateforme web dynamique de réservation et de gestion de services de santé et de bien-être, pensée pour les seniors.

---

## 👥 Équipe

| Nom | Rôle |
|-----|------|
| José Afonso Lopes | chef de projet |
| Nathéo Milliet-Treboux | design |
| Eliott Munoz | code |

---

## 📌 Description du projet

VitaCare est une application web dynamique développée dans le cadre du module **Projet Web dynamique**

La plateforme s'adresse aux **seniors** et leur permet de :
- Consulter un catalogue de services de santé et de bien-être
- Réserver des rendez-vous avec des professionnels de santé
- S'inscrire à des activités et programmes de bien-être
- Suivre leurs consultations et leur historique
- Recevoir des notifications liées à leurs réservations

---

## 🛠️ Stack technique

| Couche | Technologies |
|--------|-------------|
| Frontend | React, HTML, CSS |
| Backend | PHP |
| Base de données | MySQL |
| Versioning | Git / GitHub |

---

## 📁 Structure du projet

```
VitaCare/
├── code/              # Code source (frontend React + backend PHP)
├── docs/              # Documentation (MCD, wireframes, spécifications)
│   └── MCD.png        # Modèle Conceptuel de Données
├── maquette/          # Maquettes et prototypes UI
├── .gitignore
└── README.md
```

---

## ⚙️ Installation

### Prérequis
- Node.js (v18+)
- PHP (v8.1+)
- MySQL (v8+)
- Composer (optionnel)

### 1. Cloner le dépôt

```bash
git clone https://github.com/USERNAME/VitaCare.git
cd VitaCare
```

### 2. Base de données

```bash
mysql -u root -p < database/vitacare.sql
```

Configurer les identifiants dans `backend/config/db.php`.

### 3. Backend (PHP)

```bash
cd backend
php -S localhost:8000
```

### 4. Frontend (React)

```bash
cd frontend
npm install
npm run dev
```

L'application est accessible sur `http://localhost:5173`.

---

## 🚀 Fonctionnalités principales

- [x] Authentification et gestion des rôles (patient, praticien, admin)
- [x] Catalogue des services avec recherche et filtres
- [x] Réservation de rendez-vous avec gestion des disponibilités
- [x] Activités et programmes de bien-être
- [x] Suivi des consultations et historique
- [x] Notifications
- [ ] Tableau de bord administrateur
- [ ] Panier de réservations (paiement simulé)

---

## 📄 Livrables

| Livrable | Deadline |
|----------|----------|
| Premier livrable (conception) | Jeudi 28 mai 2026 à 23h55 |
| Projet final | Dimanche 31 mai 2026 à 23h55 |

---

## 🤖 Outils d'IA utilisés

Ce projet a été développé avec l'assistance d'outils d'IA (Claude, GitHub Copilot, etc.) conformément aux consignes du module. L'utilisation de ces outils est documentée dans le journal d'assistance par IA inclus dans la présentation PowerPoint.

---

## 📚 Références

- Sujet du projet : `Sujet_6_-_VitaCare.pdf`
- Documentation React : https://react.dev
- Documentation PHP : https://www.php.net

---

*ECE Paris — ING2 — Projet Web dynamique 2026*
