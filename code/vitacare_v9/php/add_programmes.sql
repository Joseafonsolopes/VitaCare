-- Table programmes
CREATE TABLE IF NOT EXISTS programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_service INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    nb_seances INT NOT NULL DEFAULT 4,
    duree_jours INT NOT NULL DEFAULT 30,
    date_debut DATE NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_service) REFERENCES services(id)
);

-- Table inscriptions_programmes
CREATE TABLE IF NOT EXISTS inscriptions_programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_programme INT NOT NULL,
    date_inscription DATE NOT NULL,
    nb_seances_faites INT DEFAULT 0,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_programme) REFERENCES programmes(id)
);

-- Ajouter colonne telephone2 et preferences si pas encore la
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS telephone2 VARCHAR(20);
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS preferences VARCHAR(255);
