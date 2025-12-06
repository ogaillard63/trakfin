# 🔧 Guide de dépannage rapide - TrakFin

## Problème : Les liens ne fonctionnent pas

### Symptôme
Les URLs sont doublées ou contiennent des guillemets :
```
❌ http://trakfin.test/contrats/%22http://localhost/trakfin%22/
❌ http://trakfin.test/"http://localhost/trakfin"/contrats
```

### Solution
1. **Ouvrir le fichier `.env`**
2. **Supprimer TOUS les guillemets**

**Avant (incorrect)** :
```env
APP_NAME="TrakFin"
APP_URL="http://trakfin.test"
```

**Après (correct)** :
```env
APP_NAME=TrakFin
APP_URL=http://trakfin.test
```

3. **Vider le cache du navigateur** (Ctrl+F5)
4. **Recharger la page**

---

## Problème : Page blanche

### Solution
1. Activer l'affichage des erreurs :
   - Ajouter au début de `public/index.php` :
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. Vérifier les logs Apache/PHP

3. Vérifier que Composer est installé :
   ```bash
   composer install
   ```

---

## Problème : Erreur de base de données

### Solution
1. Vérifier que MySQL est démarré
2. Créer la base de données :
   - Ouvrir phpMyAdmin
   - Créer une base `trakfin`
   - Importer `database/schema.sql`

3. Vérifier les paramètres dans `.env` :
   ```env
   DB_HOST=localhost
   DB_NAME=trakfin
   DB_USER=root
   DB_PASS=
   ```

---

## Problème : 404 sur toutes les pages sauf l'accueil

### Solution
1. Vérifier que `mod_rewrite` est activé dans Apache
2. Vérifier que le fichier `.htaccess` existe dans `public/`
3. Redémarrer Apache

---

## Problème : L'URL de base est incorrecte

### Pour Laragon
```env
APP_URL=http://trakfin.test
```

### Pour XAMPP/WAMP
```env
APP_URL=http://localhost/trakfin
```

### Pour un autre serveur
Remplacer par votre URL complète **sans slash final** :
```env
APP_URL=http://monserveur.local/trakfin
```

---

## Checklist de vérification

- [ ] Le fichier `.env` existe
- [ ] Aucun guillemet dans `.env`
- [ ] `APP_URL` correspond à l'URL dans le navigateur
- [ ] MySQL est démarré
- [ ] La base de données `trakfin` existe
- [ ] `composer install` a été exécuté
- [ ] Le dossier `vendor/` existe
- [ ] Apache/Nginx est démarré
- [ ] `mod_rewrite` est activé (Apache)

---

## Commandes utiles

### Réinstaller les dépendances
```bash
cd c:\laragon\www\trakfin
composer install
```

### Recréer le fichier .env
```bash
copy .env.example .env
```

### Vérifier la version PHP
```bash
php -v
```
(Doit être >= 8.1)

### Tester la connexion à la base de données
```bash
mysql -u root -p -e "SHOW DATABASES;"
```

---

## Support

Si le problème persiste :
1. Consulter `CONFIG.md` pour la configuration
2. Consulter `INSTALL.md` pour l'installation
3. Consulter `DOCUMENTATION.md` pour les fonctionnalités
4. Vérifier les logs d'erreur PHP/Apache
