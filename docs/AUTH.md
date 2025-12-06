# 🔐 Authentification TrakFin

## Configuration

Ajoutez ces lignes dans votre fichier `.env` :

```env
# Authentification
AUTH_USERNAME=admin
AUTH_PASSWORD=admin
```

## Utilisation

### Connexion
- URL : `http://trakfin.test/login`
- Identifiants par défaut :
  - **Username** : `admin`
  - **Password** : `admin`

### Déconnexion
- Bouton en bas de la sidebar
- URL directe : `http://trakfin.test/logout`

## Sécurité

⚠️ **IMPORTANT** : Changez les identifiants par défaut !

1. Ouvrir `.env`
2. Modifier :
```env
AUTH_USERNAME=votre_nom
AUTH_PASSWORD=votre_mot_de_passe_securise
```

## Fonctionnement

- **Toutes les pages sont protégées** sauf `/login`
- **Session PHP** : Reste connecté jusqu'à déconnexion
- **Redirection automatique** : `/login` si non authentifié

## Fichiers créés

- ✅ `src/Auth.php` : Classe d'authentification
- ✅ `templates/login.html.twig` : Page de connexion
- ✅ `public/index.php` : Routes protégées

L'authentification est maintenant active ! 🎉
