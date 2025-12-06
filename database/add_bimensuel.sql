-- Ajout de la fréquence Bimensuel
USE trakfin;

-- Modifier la colonne frequence pour ajouter 'bimensuel'
ALTER TABLE contrats 
MODIFY COLUMN frequence ENUM('mensuel', 'bimensuel', 'annuel') DEFAULT 'mensuel';
