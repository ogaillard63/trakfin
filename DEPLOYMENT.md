# 🚀 Déploiement sur hébergeur

## Problème : `/public` dans l'URL

Si votre hébergeur affiche `/public` dans les URLs, le Router a été corrigé pour gérer cela automatiquement.

### Solution automatique

Le Router retire maintenant `/public` automatiquement de l'URI.

**URLs qui fonctionnent** :
- ✅ `http://votresite.com/`
- ✅ `http://votresite.com/public/`
- ✅ `http://votresite.com/contrats`
- ✅ `http://votresite.com/public/contrats`

## Configuration pour hébergeur

### 1. Fichier `.env`

Créez un fichier `.env` à la racine avec :

```env
APP_NAME=TrakFin
APP_URL=http://votresite.com

DB_HOST=localhost
DB_NAME=votre_base
DB_USER=votre_user
DB_PASS=votre_password

AUTH_USERNAME=admin
AUTH_PASSWORD=votre_mot_de_passe_securise
```

⚠️ **IMPORTANT** : Pas de guillemets !

### 2. Structure des fichiers

Uploadez tous les fichiers en gardant la structure :

```
/
├── config/
├── database/
├── public/          ← Point d'entrée
│   └── index.php
├── src/
├── templates/
├── vendor/
├── .env
└── composer.json
```

### 3. Configuration du serveur

#### Option A : Document Root sur `/public`

Si vous pouvez configurer le document root :
- Pointez vers le dossier `/public`
- URLs : `http://votresite.com/`

#### Option B : Document Root sur `/`

Si le document root est à la racine :
- Les URLs contiendront `/public`
- Le Router gère automatiquement
- URLs : `http://votresite.com/public/`

### 4. Fichier .htaccess

Assurez-vous que `.htaccess` est dans `/public/` :

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

## Installation sur l'hébergeur

### 1. Upload des fichiers

Via FTP/SFTP, uploadez tous les fichiers.

### 2. Installer les dépendances

Si vous avez accès SSH :
```bash
cd /path/to/trakfin
composer install --no-dev --optimize-autoloader
```

Sinon, uploadez le dossier `vendor/` depuis votre local après avoir exécuté `composer install`.

### 3. Créer la base de données

1. Créez une base MySQL via le panneau de contrôle
2. Importez `database/schema.sql`
3. Mettez à jour `.env` avec les identifiants

### 4. Permissions

Assurez-vous que les permissions sont correctes :
```bash
chmod 755 public
chmod 644 public/index.php
chmod 644 .env
```

## Vérification

1. Accédez à `http://votresite.com/public/login`
2. Connectez-vous avec vos identifiants
3. Testez la navigation

## Problèmes courants

### URLs avec /public/public/

**Cause** : Le `.htaccess` redirige vers `/public`

**Solution** : 
- Vérifiez que le document root pointe vers `/public`
- OU supprimez les règles de redirection vers `/public` dans `.htaccess`

### Erreur 500

**Causes possibles** :
- Permissions incorrectes
- `.env` manquant
- `vendor/` manquant
- PHP < 8.1

**Solution** :
1. Vérifier les logs d'erreur
2. Vérifier la version PHP : `php -v`
3. Réinstaller les dépendances

### Base de données non accessible

**Solution** :
1. Vérifier les identifiants dans `.env`
2. Vérifier que la base existe
3. Vérifier que l'utilisateur a les droits

## Hébergeurs testés

### InfinityFree / 000webhost

```env
APP_URL=http://votresite.free.nf
DB_HOST=sql123.free.nf
```

### OVH

```env
APP_URL=https://votresite.ovh
DB_HOST=votresite.mysql.db
```

### Hostinger

```env
APP_URL=https://votresite.com
DB_HOST=mysql.hostinger.com
```

## Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs d'erreur PHP
2. Activez le mode debug temporairement
3. Consultez `TROUBLESHOOTING.md`
