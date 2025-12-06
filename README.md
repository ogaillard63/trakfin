# TrakFin - Application de suivi de contrats

Application ultra-simple pour suivre vos contrats récurrents et leurs échéances.

## Stack technique

- **Backend**: PHP 8.1+
- **Frontend**: Twig + Tailwind CSS
- **Base de données**: MySQL
- **Icônes**: Lucide Icons
- **Graphiques**: Chart.js

## Installation

1. **Cloner le projet** dans votre dossier web (ex: `c:\laragon\www\trakfin`)

2. **Installer les dépendances**:
```bash
composer install
```

3. **Configurer la base de données**:
   - Copier `.env.example` vers `.env`
   - Modifier les paramètres de connexion dans `.env`

4. **Créer la base de données**:
```bash
mysql -u root -p < database/schema.sql
```

5. **Accéder à l'application**:
   - Ouvrir `http://localhost/trakfin` dans votre navigateur

## Fonctionnalités

### Dashboard
- Vue mensuelle des échéances
- Total du mois
- Contrats ayant augmenté
- Projection annuelle

### Contrats
- Créer/modifier/supprimer des contrats
- Catégorisation (assurance, énergie, télécom, etc.)
- Fréquence mensuelle ou annuelle
- Génération automatique des échéances

### Échéances
- Ajout manuel ou génération automatique
- Suivi du statut (prévu/payé)
- Détection des augmentations
- Graphique d'évolution des montants

## Structure du projet

```
trakfin/
├── config/
│   └── config.php          # Configuration
├── database/
│   └── schema.sql          # Schéma de base de données
├── public/
│   ├── .htaccess          # Règles de réécriture
│   └── index.php          # Point d'entrée
├── src/
│   ├── Model/
│   │   ├── Contrat.php
│   │   ├── Echeance.php
│   │   └── Category.php
│   ├── Database.php
│   ├── Router.php
│   └── View.php
├── templates/
│   ├── base.html.twig
│   ├── dashboard.html.twig
│   ├── contrats/
│   │   ├── index.html.twig
│   │   ├── form.html.twig
│   │   └── show.html.twig
│   └── echeances/
│       └── form.html.twig
├── .env                    # Configuration locale
├── .env.example           # Exemple de configuration
└── composer.json
```

## Utilisation

### Créer un contrat
1. Cliquer sur le bouton "+" flottant ou "Nouveau contrat"
2. Remplir les informations (nom, fournisseur, catégorie, fréquence)
3. Cocher "Générer automatiquement les échéances" pour créer 12 mois d'échéances
4. Sauvegarder

### Ajouter une échéance
1. Depuis la page d'un contrat, cliquer sur "+ Ajouter"
2. Renseigner la date et le montant
3. Définir le statut (prévu/payé)
4. Sauvegarder

### Suivre les augmentations
- Le dashboard affiche automatiquement les contrats dont le dernier montant est supérieur au précédent
- Le pourcentage d'augmentation est calculé et affiché
- Le graphique d'évolution met en évidence les hausses (points rouges)

## Licence

Projet personnel - Tous droits réservés
