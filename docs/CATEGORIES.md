# 🏷️ Ajout des catégories d'assurance

## Catégories à créer

| Catégorie | Couleur | Icône | Code couleur |
|-----------|---------|-------|--------------|
| **Assurance Habitation** | 🔵 Bleu | 🏠 home | #3B82F6 |
| **Assurance Auto** | 🔴 Rouge | 🚗 car | #EF4444 |
| **Assurance Scolaire** | 🟢 Vert | 🎓 graduation-cap | #10B981 |

## Méthode 1 : Via phpMyAdmin (Laragon)

1. **Ouvrir phpMyAdmin** :
   - Cliquer sur le bouton "Database" dans Laragon
   - Ou aller sur `http://localhost/phpmyadmin`

2. **Sélectionner la base** :
   - Cliquer sur `trakfin` dans la liste de gauche

3. **Exécuter le script SQL** :
   - Cliquer sur l'onglet "SQL"
   - Copier-coller le contenu de `database/add_insurance_categories.sql`
   - Cliquer sur "Exécuter"

4. **Vérifier** :
   - Aller dans la table `categories`
   - Vous devriez voir les 3 nouvelles catégories

## Méthode 2 : Via ligne de commande

```bash
# Dans le terminal Laragon
mysql -u root -p trakfin < database/add_insurance_categories.sql
```

## Méthode 3 : Insertion manuelle

Si vous préférez ajouter manuellement via phpMyAdmin :

### Assurance Habitation
- **nom** : `Assurance Habitation`
- **couleur** : `#3B82F6`
- **icone** : `home`

### Assurance Auto
- **nom** : `Assurance Auto`
- **couleur** : `#EF4444`
- **icone** : `car`

### Assurance Scolaire
- **nom** : `Assurance Scolaire`
- **couleur** : `#10B981`
- **icone** : `graduation-cap`

## Résultat attendu

Après l'ajout, vous aurez ces catégories dans votre application :

```
┌────────────────────────────────────────┐
│ 🏠 Assurance Habitation (Bleu)        │
│ 🚗 Assurance Auto (Rouge)             │
│ 🎓 Assurance Scolaire (Vert)          │
│ ⚡ Énergie (Orange)                    │
│ 📡 Télécom (Bleu)                      │
│ 🏡 Habitation (Violet)                 │
│ 📄 Impôts (Rose)                       │
│ 📺 Abonnements (Vert)                  │
└────────────────────────────────────────┘
```

## Utilisation dans l'application

1. **Créer un nouveau contrat**
2. **Sélectionner une catégorie** : Assurance Habitation, Auto ou Scolaire
3. **La carte affichera** :
   - L'icône correspondante (🏠, 🚗 ou 🎓)
   - La couleur de fond appropriée
   - Le badge avec la couleur de la catégorie

## Icônes Lucide disponibles

Autres icônes d'assurance possibles :
- `shield` : Bouclier (protection)
- `shield-check` : Bouclier avec coche
- `shield-alert` : Bouclier avec alerte
- `heart-pulse` : Santé
- `briefcase` : Professionnel
- `users` : Famille

Pour changer une icône, modifiez le champ `icone` dans la table `categories`.

## Couleurs recommandées

- **Bleu** : `#3B82F6` (confiance, sécurité)
- **Rouge** : `#EF4444` (urgence, auto)
- **Vert** : `#10B981` (santé, scolaire)
- **Orange** : `#F59E0B` (énergie)
- **Violet** : `#8B5CF6` (habitation)
- **Rose** : `#EC4899` (famille)

## Dépannage

### Erreur "Duplicate entry"
Si vous obtenez cette erreur, les catégories existent déjà.
- Vérifiez dans la table `categories`
- Supprimez les doublons si nécessaire

### Icône ne s'affiche pas
- Vérifiez que le nom de l'icône est correct
- Consultez : https://lucide.dev/icons/
- Utilisez le nom exact (ex: `graduation-cap`, pas `graduation`)

### Couleur incorrecte
- Format : `#RRGGBB` (6 caractères hexadécimaux)
- Toujours commencer par `#`
- Exemple : `#3B82F6` (pas `3B82F6`)
