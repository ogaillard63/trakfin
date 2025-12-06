-- Base de données TrakFin
CREATE DATABASE IF NOT EXISTS trakfin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trakfin;

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    couleur VARCHAR(7) DEFAULT '#6366F1',
    icone VARCHAR(50) DEFAULT 'tag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des contrats
CREATE TABLE IF NOT EXISTS contrats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    fournisseur VARCHAR(200),
    categorie_id INT,
    frequence ENUM('mensuel', 'annuel') DEFAULT 'mensuel',
    date_debut DATE NOT NULL,
    notes TEXT,
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des échéances
CREATE TABLE IF NOT EXISTS echeances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrat_id INT NOT NULL,
    date_echeance DATE NOT NULL,
    montant DECIMAL(10,2) NOT NULL DEFAULT 0,
    statut ENUM('prevu', 'paye') DEFAULT 'prevu',
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contrat_id) REFERENCES contrats(id) ON DELETE CASCADE,
    INDEX idx_date (date_echeance),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de démonstration - Catégories
INSERT INTO categories (nom, couleur, icone) VALUES
('Assurance', '#EF4444', 'shield'),
('Énergie', '#F59E0B', 'zap'),
('Télécom', '#3B82F6', 'wifi'),
('Habitation', '#8B5CF6', 'home'),
('Impôts', '#EC4899', 'file-text'),
('Abonnements', '#10B981', 'tv');
