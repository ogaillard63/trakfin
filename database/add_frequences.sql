-- Ajout des fréquences : Bimensuel, Trimestriel, Semestriel
USE trakfin;

-- Modifier la colonne frequence pour ajouter toutes les fréquences
ALTER TABLE contrats 
MODIFY COLUMN frequence ENUM('mensuel', 'bimensuel', 'trimestriel', 'semestriel', 'annuel') DEFAULT 'mensuel';
