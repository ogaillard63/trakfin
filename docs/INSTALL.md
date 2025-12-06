# Guide d'installation rapide - TrakFin

## Prérequis

- PHP 8.1 ou supérieur
- MySQL 5.7 ou supérieur
- Composer
- Serveur web (Apache/Nginx) ou Laragon/XAMPP/WAMP

## Installation en 5 étapes

### 1. Installer les dépendances

```bash
cd c:\laragon\www\trakfin
composer install
```

### 2. Configurer l'environnement

Le fichier `.env` est déjà créé avec les paramètres par défaut pour Laragon :

```env
APP_NAME="TrakFin"
APP_URL="http://localhost/trakfin"

DB_HOST=localhost
DB_NAME=trakfin
DB_USER=root
DB_PASS=
```

Si vous utilisez un autre environnement, modifiez ces valeurs.

### 3. Créer la base de données

**Option A : Via ligne de commande**
```bash
mysql -u root -p < database/schema.sql
```

**Option B : Via phpMyAdmin ou HeidiSQL**
1. Créer une base de données nommée `trakfin`
2. Importer le fichier `database/schema.sql`

### 4. Configurer le serveur web

**Pour Apache (Laragon/XAMPP/WAMP)** :
- Le fichier `.htaccess` est déjà configuré dans `public/`
- Assurez-vous que `mod_rewrite` est activé

**Pour Nginx** :
Ajouter cette configuration :
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. Accéder à l'application

Ouvrir dans votre navigateur :
```
http://localhost/trakfin
```

## Vérification de l'installation

### Test 1 : Page d'accueil
- Accéder à `http://localhost/trakfin`
- Vous devriez voir le Dashboard vide

### Test 2 : Créer un contrat
1. Cliquer sur le bouton "+" flottant
2. Remplir le formulaire :
   - Nom : "Test Assurance"
   - Fournisseur : "Test"
   - Catégorie : Assurance
   - Fréquence : Mensuel
   - Date : Aujourd'hui
3. Cocher "Générer automatiquement les échéances"
4. Cliquer sur "Créer"
5. Vous devriez voir 12 échéances générées

### Test 3 : Ajouter un montant
1. Dans la liste des échéances, cliquer sur "Éditer" pour la première
2. Modifier le montant : 100.00 €
3. Sauvegarder
4. Répéter pour la deuxième échéance avec 105.00 €
5. Le graphique d'évolution devrait apparaître

## Dépannage

### Erreur "404 Not Found"
- Vérifier que `mod_rewrite` est activé dans Apache
- Vérifier que le fichier `.htaccess` existe dans `public/`
- Vérifier l'URL de base dans `.env`

### Erreur de connexion à la base de données
- Vérifier les paramètres dans `.env`
- Vérifier que MySQL est démarré
- Vérifier que la base de données `trakfin` existe

### Page blanche
- Activer l'affichage des erreurs PHP :
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Vérifier les logs d'erreur PHP

### Composer install échoue
- Vérifier que PHP 8.1+ est installé : `php -v`
- Vérifier que Composer est installé : `composer -V`
- Essayer : `composer install --no-scripts`

## Structure des URLs

```
/                           → Dashboard
/contrats                   → Liste des contrats
/contrats/create            → Créer un contrat
/contrats/{id}              → Détail d'un contrat
/contrats/{id}/edit         → Modifier un contrat
/echeances/create           → Créer une échéance
/echeances/{id}/edit        → Modifier une échéance
```

## Données de démonstration

La base de données contient déjà 6 catégories :
- Assurance (rouge)
- Énergie (orange)
- Télécom (bleu)
- Habitation (violet)
- Impôts (rose)
- Abonnements (vert)

Vous pouvez commencer à créer vos contrats immédiatement !

## Support

Pour toute question ou problème :
1. Consulter la `DOCUMENTATION.md` pour les détails fonctionnels
2. Consulter le `README.md` pour la vue d'ensemble
3. Vérifier les logs d'erreur PHP

## Prochaines étapes

Une fois l'installation réussie :
1. Créer vos premiers contrats
2. Générer les échéances
3. Compléter les montants
4. Suivre l'évolution sur le dashboard

Bon suivi de vos contrats ! 🚀
