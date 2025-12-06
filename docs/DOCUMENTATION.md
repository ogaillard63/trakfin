# TrakFin - Documentation Fonctionnelle Complète

## 📋 Vue d'ensemble

TrakFin est une application de suivi de contrats récurrents et de leurs échéances, conçue pour être **ultra-simple** et **rapide**. Elle permet de détecter automatiquement les augmentations de montants et de visualiser l'évolution dans le temps.

---

## 🎯 Objectifs de l'application

1. ✅ Enregistrer les contrats récurrents (assurance, internet, eau, électricité, impôts, etc.)
2. ✅ Générer et suivre leurs échéances (mensuelles ou annuelles)
3. ✅ Visualiser l'évolution des montants (hausses / baisses)
4. ✅ Dashboard clair avec échéances du mois et contrats à surveiller
5. ✅ Ergonomie minimaliste et rapide

---

## 📐 Structure des données

### Contrat
Chaque contrat contient :
- **Nom** (requis) : ex. "Assurance habitation"
- **Fournisseur** (optionnel) : ex. "AXA"
- **Catégorie** (optionnel) : Assurance, Énergie, Télécom, Habitation, Impôts, Abonnements
- **Fréquence** (requis) : Mensuel ou Annuel
- **Date de début** (requis)
- **Notes** (optionnel)

### Échéance
Chaque échéance contient :
- **Date d'échéance** (requis)
- **Montant** (requis)
- **Statut** (requis) : Prévu ou Payé
- **Commentaire** (optionnel)

### Catégorie
- **Nom** : ex. "Assurance"
- **Couleur** : code hexadécimal pour l'affichage
- **Icône** : nom de l'icône Lucide

---

## 🔄 Flux utilisateur principaux

### Flux A : Créer un contrat et générer les échéances

1. **Point de départ** : Dashboard ou page Contrats
2. **Action** : Clic sur le bouton "+" flottant ou "Nouveau contrat"
3. **Formulaire** :
   - Remplir le nom (obligatoire)
   - Remplir le fournisseur (optionnel)
   - Sélectionner une catégorie (optionnel)
   - Choisir la fréquence : Mensuel ou Annuel (obligatoire)
   - Définir la date de début (obligatoire)
   - Ajouter des notes (optionnel)
   - Cocher "Générer automatiquement les échéances" (activé par défaut)
4. **Validation** : Clic sur "Créer"
5. **Résultat** :
   - Le contrat est créé
   - Si génération automatique : 12 échéances mensuelles ou 1 échéance annuelle créées avec montant à 0
   - Redirection vers la page de détail du contrat
   - Message de confirmation affiché

### Flux B : Ajouter une échéance manuellement

1. **Point de départ** : Page de détail d'un contrat
2. **Action** : Clic sur "+ Ajouter" dans la section Échéances
3. **Formulaire** :
   - Le contrat est pré-sélectionné
   - Définir la date d'échéance (obligatoire)
   - Saisir le montant (obligatoire)
   - Choisir le statut : Prévu ou Payé (Prévu par défaut)
   - Ajouter un commentaire (optionnel)
4. **Validation** : Clic sur "Créer"
5. **Résultat** :
   - L'échéance est créée
   - Si le montant est différent de l'échéance précédente, la tendance est recalculée
   - Redirection vers la page du contrat
   - Message de confirmation affiché

### Flux C : Modifier une échéance

1. **Point de départ** : Page de détail d'un contrat
2. **Action** : Clic sur l'icône "éditer" d'une échéance
3. **Formulaire** :
   - Tous les champs sont pré-remplis
   - Le contrat ne peut pas être modifié
   - Modifier la date, le montant, le statut ou le commentaire
4. **Validation** : Clic sur "Enregistrer"
5. **Résultat** :
   - L'échéance est mise à jour
   - La tendance est recalculée automatiquement
   - Redirection vers la page du contrat
   - Message de confirmation affiché

### Flux D : Marquer une échéance comme payée

1. **Point de départ** : Dashboard ou page de détail d'un contrat
2. **Action** : Clic sur le badge "Prévu" d'une échéance
3. **Résultat** :
   - Le statut passe à "Payé"
   - Le badge devient vert
   - Pas de redirection (action AJAX-like)

### Flux E : Surveiller les augmentations

1. **Point de départ** : Dashboard
2. **Visualisation** : Section "Contrats ayant augmenté"
   - Liste des contrats dont le dernier montant > montant précédent
   - Affichage du montant précédent → montant actuel
   - Affichage du pourcentage d'augmentation
3. **Action** : Clic sur un contrat
4. **Résultat** :
   - Redirection vers la page de détail du contrat
   - Visualisation du graphique d'évolution
   - Les points rouges indiquent les hausses

### Flux F : Générer des échéances pour un contrat existant

1. **Point de départ** : Page de détail d'un contrat
2. **Action** : Clic sur "Générer échéances"
3. **Résultat** :
   - 12 échéances mensuelles ou 1 échéance annuelle créées
   - Les échéances existantes ne sont pas dupliquées
   - Message indiquant le nombre d'échéances générées
   - Rechargement de la page

---

## 🎨 Écrans et composants

### Écran 1 : Dashboard (/)

**Composants** :
- **Header** : Titre "Dashboard" + Sélecteur de mois (flèches gauche/droite)
- **Bloc "Échéances du mois"** :
  - Total du mois en haut à droite (gros chiffre)
  - Liste verticale compacte des échéances
  - Chaque ligne : icône catégorie, nom contrat, date, montant, badge statut
  - Clic sur badge "Prévu" pour marquer comme payé
- **Bloc "Contrats ayant augmenté"** :
  - Liste des contrats avec augmentation
  - Affichage : nom, montant avant → après, % d'augmentation
  - Flèche rouge ↑ pour indiquer la hausse
- **Bloc "Projection annuelle"** :
  - Gros chiffre : total des échéances restantes de l'année
  - Texte explicatif

**Actions** :
- Changer de mois (flèches)
- Marquer une échéance comme payée
- Accéder au détail d'un contrat (clic sur ligne)

### Écran 2 : Liste des contrats (/contrats)

**Composants** :
- **Header** : Titre "Mes Contrats" + Bouton "Nouveau contrat"
- **Grille de cartes** (2-3 colonnes) :
  - Icône de catégorie colorée
  - Nom du contrat
  - Fournisseur (petit texte)
  - Badge catégorie
  - Fréquence (Mensuel/Annuel)
  - Dernier montant (gros chiffre) ou "Aucune échéance"
  - Prochaine échéance (date)
  - Boutons : "Voir" + "+" (ajouter échéance)

**Actions** :
- Créer un contrat
- Voir le détail d'un contrat
- Ajouter une échéance directement

### Écran 3 : Détail d'un contrat (/contrats/{id})

**Composants** :
- **Header** : Bouton "Retour" + Boutons "Modifier" et "Générer échéances"
- **Bloc A : Informations du contrat** :
  - Icône de catégorie (grande)
  - Nom du contrat (titre)
  - Fournisseur
  - Grille d'informations : Catégorie, Fréquence, Date de début
  - Notes (si présentes)
- **Bloc B : Graphique d'évolution** (si au moins 2 échéances) :
  - Graphique en ligne (Chart.js)
  - Axe X : dates des échéances
  - Axe Y : montants
  - Points rouges pour les hausses
- **Bloc C : Liste des échéances** :
  - Tableau avec colonnes : Date, Montant, Statut, Commentaire, Actions
  - Tri automatique par date ascendante
  - Actions : Éditer, Supprimer
  - Bouton "+ Ajouter" en haut

**Actions** :
- Modifier le contrat
- Générer des échéances
- Ajouter une échéance
- Éditer une échéance
- Supprimer une échéance

### Écran 4 : Formulaire contrat (/contrats/create ou /contrats/{id}/edit)

**Composants** :
- **Header** : Bouton "Retour aux contrats"
- **Formulaire** :
  - Nom (input texte, requis)
  - Fournisseur (input texte, optionnel)
  - Catégorie (select, optionnel)
  - Fréquence (radio : Mensuel/Annuel, requis)
  - Date de début (date picker, requis)
  - Notes (textarea, optionnel)
  - Checkbox "Générer automatiquement les échéances" (création uniquement)
- **Actions** : Annuler, Supprimer (édition), Créer/Enregistrer

### Écran 5 : Formulaire échéance (/echeances/create ou /echeances/{id}/edit)

**Composants** :
- **Header** : Bouton "Retour"
- **Formulaire** :
  - Contrat (select, requis, désactivé en édition)
  - Date d'échéance (date picker, requis)
  - Montant (input numérique avec €, requis)
  - Statut (radio : Prévu/Payé, requis)
  - Commentaire (textarea, optionnel)
- **Actions** : Annuler, Créer/Enregistrer

---

## 🔧 Règles fonctionnelles

### Détection des augmentations

**Règle** : Une hausse est détectée si `montant_n > montant_(n-1)`

**Calcul** :
```
variation = montant_actuel - montant_precedent
pourcentage = (variation / montant_precedent) * 100
```

**Affichage** :
- Dashboard : Section "Contrats ayant augmenté"
- Graphique : Points rouges pour les hausses
- Tri : Par pourcentage décroissant

### Génération automatique des échéances

**Règles** :
- **Mensuel** : 12 échéances créées, espacées de 1 mois
- **Annuel** : 1 échéance créée, espacée de 1 an
- **Montant initial** : 0 € (à compléter manuellement)
- **Statut initial** : Prévu
- **Pas de duplication** : Si une échéance existe déjà à une date, elle n'est pas recréée

**Algorithme** :
1. Récupérer la dernière échéance du contrat
2. Si aucune échéance : partir de la date de début du contrat
3. Sinon : partir de la date de la dernière échéance + intervalle
4. Créer N échéances (12 pour mensuel, 1 pour annuel)
5. Vérifier qu'aucune échéance n'existe déjà à chaque date

### Calcul de la projection annuelle

**Règle** : Somme de toutes les échéances dont la date >= aujourd'hui et année = année en cours

**SQL** :
```sql
SELECT SUM(montant) 
FROM echeances 
WHERE YEAR(date_echeance) = :year 
AND date_echeance >= CURDATE()
```

### Tri des échéances

**Règle** : Toujours par date ascendante (plus anciennes en premier)

### Statuts des échéances

**Valeurs** :
- `prevu` : Échéance à venir ou non payée
- `paye` : Échéance réglée

**Affichage** :
- Badge gris "Prévu" (cliquable pour passer à Payé)
- Badge vert "Payé" (non cliquable)

---

## 🎨 Design et ergonomie

### Principes

1. **Minimalisme** : Pas de surcharge visuelle
2. **Rapidité** : Lecture des données essentielles en 5 secondes
3. **Compacité** : Cartes et tableaux compacts
4. **Couleurs** :
   - Vert : OK, payé
   - Rouge : Hausse, attention
   - Bleu/Indigo : Information, action principale
   - Gris : Neutre, prévu

### Navigation

**Menu latéral** (sidebar) :
- Dashboard
- Contrats

**Bouton flottant** :
- Position : Bas droite
- Action : Créer un contrat
- Icône : Plus (+)
- Couleur : Indigo

### Largeur maximale

- Desktop : 900px (5xl Tailwind)
- Centré horizontalement
- Responsive sur mobile

### Composants visuels

1. **Cartes** : Bordure grise, ombre légère, coins arrondis
2. **Badges** : Petits, colorés, coins arrondis complets
3. **Tableaux** : Lignes alternées au survol, bordures fines
4. **Graphiques** : Chart.js, couleurs cohérentes avec le design
5. **Icônes** : Lucide Icons, taille 16-20px
6. **Boutons** :
   - Primaire : Indigo, texte blanc
   - Secondaire : Gris clair, texte gris foncé
   - Danger : Rouge clair, texte rouge

---

## 🚀 Points techniques

### Stack
- **PHP 8.1+** : Backend
- **Twig 3** : Templates
- **Tailwind CSS** : Styling (via CDN)
- **MySQL** : Base de données
- **Lucide Icons** : Icônes
- **Chart.js** : Graphiques

### Architecture
- **MVC simplifié** : Models, Views (Twig), Controller (index.php)
- **Router custom** : Gestion des routes GET/POST
- **PDO** : Accès base de données
- **Sessions** : Flash messages

### Sécurité
- **Prepared statements** : Protection SQL injection
- **Validation** : Champs requis côté serveur
- **CSRF** : À implémenter si nécessaire

---

## 📊 Exemples de données

### Contrat exemple
```
Nom: Assurance habitation
Fournisseur: AXA
Catégorie: Assurance
Fréquence: Annuel
Date de début: 2024-01-15
Notes: Contrat n° 123456789
```

### Échéances exemple
```
Date: 2024-01-15, Montant: 450.00 €, Statut: Payé
Date: 2025-01-15, Montant: 465.00 €, Statut: Prévu, Commentaire: +3.3% d'augmentation
```

### Augmentation détectée
```
Montant précédent: 450.00 €
Montant actuel: 465.00 €
Variation: +15.00 €
Pourcentage: +3.3%
```

---

## ✅ Checklist de livraison

- [x] Base de données créée avec schéma
- [x] Modèles PHP (Contrat, Echeance, Category)
- [x] Router et système de routes
- [x] Templates Twig avec Tailwind CSS
- [x] Dashboard fonctionnel
- [x] CRUD Contrats complet
- [x] CRUD Échéances complet
- [x] Génération automatique d'échéances
- [x] Détection des augmentations
- [x] Graphique d'évolution
- [x] Projection annuelle
- [x] Flash messages
- [x] Design responsive
- [x] Documentation README
- [x] Documentation fonctionnelle

---

## 🎯 Prochaines améliorations possibles

1. Export CSV/PDF des échéances
2. Notifications par email
3. Gestion multi-utilisateurs
4. Catégories personnalisables
5. Filtres avancés
6. Statistiques détaillées
7. Import de données
8. API REST

---

**Version** : 1.0  
**Date** : Décembre 2024  
**Auteur** : TrakFin Team
