-- ============================================
-- VITACARE - Base de données
-- À importer dans phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS vitacare;
USE vitacare;

-- ============================================
-- TABLE : utilisateurs
-- Les 3 rôles : senior, intervenant, admin
-- ============================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('senior', 'intervenant', 'admin') NOT NULL DEFAULT 'senior',
    adresse VARCHAR(255),
    telephone VARCHAR(20),
    date_inscription DATE NOT NULL,
    actif TINYINT(1) DEFAULT 1
);

-- ============================================
-- TABLE : services
-- Les activités créées par les intervenants
-- ============================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_intervenant INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    emoji VARCHAR(10) DEFAULT '🧘',
    couleur VARCHAR(20) DEFAULT '#e8f0eb',
    categorie ENUM('Bien-être', 'Nutrition', 'Santé mentale', 'Activités', 'Thérapies', 'Consultations') NOT NULL,
    duree INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    date_creation DATE NOT NULL,
    FOREIGN KEY (id_intervenant) REFERENCES utilisateurs(id)
);

-- ============================================
-- TABLE : creneaux
-- Les disponibilités pour chaque service
-- ============================================
CREATE TABLE creneaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_service INT NOT NULL,
    date_creneau DATE NOT NULL,
    heure VARCHAR(10) NOT NULL,
    places_total INT NOT NULL DEFAULT 10,
    places_restantes INT NOT NULL DEFAULT 10,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_service) REFERENCES services(id)
);

-- ============================================
-- TABLE : reservations
-- Les réservations faites par les seniors
-- ============================================
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_creneau INT NOT NULL,
    statut ENUM('confirmee', 'annulee', 'en_attente') DEFAULT 'confirmee',
    date_reservation DATE NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_creneau) REFERENCES creneaux(id)
);

-- ============================================
-- TABLE : notifications
-- Les notifications envoyées aux utilisateurs
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    date_notification DATETIME NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);

-- ============================================
-- DONNÉES DE TEST
-- ============================================

-- Mot de passe pour tous les comptes de test : "vitacare123"
-- hashé avec password_hash() de PHP
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, adresse, telephone, date_inscription) VALUES
('Admin', 'VitaCare', 'admin@vitacare.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, '2025-01-01'),
('Dupont', 'Marie', 'marie.dupont@vitacare.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'intervenant', '5 rue de la Paix, 75001 Paris', '0612345678', '2025-01-15'),
('Garnier', 'Élise', 'elise.garnier@vitacare.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'intervenant', '12 avenue des Fleurs, 75008 Paris', '0623456789', '2025-02-01'),
('Milliet', 'Nathéo', 'natheo.milliet@vitacare.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'senior', '10 rue Sextius Michel, 75015 Paris', '0634567890', '2025-03-10');

-- Services créés par Marie Dupont (id=2)
INSERT INTO services (id_intervenant, nom, description, emoji, couleur, categorie, duree, prix, date_creation) VALUES
(2, 'Yoga Douceur', 'Séance de yoga adaptée pour débutants, axée sur la relaxation et la souplesse.', '🧘', '#e8f0eb', 'Bien-être', 60, 45.00, '2025-03-01'),
(2, 'Méditation guidée', 'Séance de méditation pour réduire le stress et améliorer le bien-être mental.', '🌿', '#e8f5eb', 'Bien-être', 45, 25.00, '2025-03-05');

-- Services créés par Élise Garnier (id=3)
INSERT INTO services (id_intervenant, nom, description, emoji, couleur, categorie, duree, prix, date_creation) VALUES
(3, 'Consultation nutrition', 'Bilan nutritionnel complet et conseils personnalisés pour une alimentation saine.', '🥗', '#f5f0e8', 'Nutrition', 45, 35.00, '2025-03-10'),
(3, 'Sophrologie', 'Techniques de relaxation et de gestion du stress par la sophrologie.', '🧠', '#fce8f0', 'Santé mentale', 50, 59.00, '2025-03-12');

-- Créneaux pour Yoga Douceur (id_service=1)
INSERT INTO creneaux (id_service, date_creneau, heure, places_total, places_restantes) VALUES
(1, '2026-06-02', '9h00', 10, 8),
(1, '2026-06-02', '11h00', 10, 10),
(1, '2026-06-09', '9h00', 10, 5),
(1, '2026-06-09', '14h00', 10, 10);

-- Créneaux pour Consultation nutrition (id_service=3)
INSERT INTO creneaux (id_service, date_creneau, heure, places_total, places_restantes) VALUES
(3, '2026-06-03', '10h00', 5, 3),
(3, '2026-06-03', '14h00', 5, 5),
(3, '2026-06-10', '10h00', 5, 1);

-- Une réservation de test pour Nathéo (id=4)
INSERT INTO reservations (id_utilisateur, id_creneau, statut, date_reservation) VALUES
(4, 1, 'confirmee', '2026-05-20'),
(4, 5, 'confirmee', '2026-05-21');

-- Notifications de test
INSERT INTO notifications (id_utilisateur, message, lu, date_notification) VALUES
(4, 'Votre réservation pour Yoga Douceur le 2 juin à 9h00 est confirmée.', 0, '2026-05-20 10:30:00'),
(4, 'Votre réservation pour Consultation nutrition le 3 juin à 10h00 est confirmée.', 1, '2026-05-21 14:00:00');
