# ⚠️ IMPORTANT - Configuration de l'URL

## Problème des guillemets

**NE PAS mettre de guillemets** dans le fichier `.env` !

### ❌ INCORRECT
```env
APP_NAME="TrakFin"
APP_URL="http://trakfin.test"
```

### ✅ CORRECT
```env
APP_NAME=TrakFin
APP_URL=http://trakfin.test
```

## Configuration pour Laragon

Si vous utilisez Laragon, l'URL par défaut sera :
```
http://trakfin.test
```

Votre fichier `.env` doit contenir :
```env
APP_NAME=TrakFin
APP_URL=http://trakfin.test

DB_HOST=localhost
DB_NAME=trakfin
DB_USER=root
DB_PASS=
```

## Configuration pour XAMPP/WAMP

Si vous utilisez XAMPP ou WAMP :
```env
APP_NAME=TrakFin
APP_URL=http://localhost/trakfin

DB_HOST=localhost
DB_NAME=trakfin
DB_USER=root
DB_PASS=
```

## Vérification

Après avoir modifié le fichier `.env` :

1. **Redémarrer le serveur web** (si nécessaire)
2. **Vider le cache du navigateur** (Ctrl+F5)
3. **Tester l'URL** : `http://trakfin.test`

Les liens devraient maintenant fonctionner correctement :
- ✅ `http://trakfin.test/`
- ✅ `http://trakfin.test/contrats`
- ✅ `http://trakfin.test/contrats/create`

## En cas de problème

Si les liens ne fonctionnent toujours pas :

1. Vérifier que le fichier `.env` ne contient **AUCUN guillemet**
2. Vérifier que `APP_URL` correspond exactement à l'URL dans votre navigateur
3. Redémarrer Apache/Nginx
4. Vider le cache du navigateur

## Note technique

Le fichier `config/config.php` lit les variables d'environnement avec `trim()`, mais les guillemets dans les valeurs causent des problèmes car ils sont inclus dans la valeur elle-même.

**Toujours écrire les valeurs sans guillemets dans `.env` !**
