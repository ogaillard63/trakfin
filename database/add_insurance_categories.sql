-- Ajout des catégories d'assurance spécifiques
USE trakfin;

-- Supprimer l'ancienne catégorie générique "Assurance" si elle existe
DELETE FROM categories WHERE nom = 'Assurance';

-- Ajouter les nouvelles catégories d'assurance
INSERT INTO categories (nom, couleur, icone) VALUES
('Assurance Habitation', '#3B82F6', 'home'),        -- Bleu
('Assurance Auto', '#EF4444', 'car'),               -- Rouge
('Assurance Scolaire', '#10B981', 'graduation-cap'); -- Vert

-- Afficher les catégories
SELECT * FROM categories ORDER BY nom;
