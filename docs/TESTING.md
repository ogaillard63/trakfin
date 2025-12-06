# 🧪 Guide de test - Génération d'échéances

## Test 1 : Génération automatique à la création

### Étapes :
1. Aller sur **Contrats** → **Nouveau contrat**
2. Remplir :
   - Nom : "Test Mensuel"
   - Fréquence : **Mensuel**
   - Date de début : **01/12/2024**
3. **Cocher** "Générer automatiquement les échéances"
4. Cliquer sur **Créer**

### Résultat attendu :
✅ 12 échéances créées avec les dates :
- 01/12/2024 (mois 0)
- 01/01/2025 (mois 1)
- 01/02/2025 (mois 2)
- ... jusqu'à 01/11/2025 (mois 11)

✅ Toutes avec montant = 0,00 €
✅ Toutes avec statut = Prévu

---

## Test 2 : Génération manuelle depuis le détail

### Étapes :
1. Créer un contrat **sans** générer les échéances
2. Aller sur le détail du contrat
3. Cliquer sur **"Générer échéances"**

### Résultat attendu :
✅ Message : "12 échéances générées" (ou 1 si annuel)
✅ Les échéances apparaissent dans le tableau

---

## Test 3 : Génération annuelle

### Étapes :
1. Créer un contrat avec :
   - Nom : "Test Annuel"
   - Fréquence : **Annuel**
   - Date de début : **15/01/2024**
2. Cocher "Générer automatiquement les échéances"
3. Créer

### Résultat attendu :
✅ 1 échéance créée
✅ Date : 15/01/2024

---

## Test 4 : Pas de duplication

### Étapes :
1. Créer un contrat avec génération automatique (12 échéances)
2. Cliquer à nouveau sur **"Générer échéances"**

### Résultat attendu :
✅ Message : "0 échéances générées"
✅ Aucune échéance dupliquée

---

## Test 5 : Génération partielle

### Étapes :
1. Créer un contrat avec génération automatique
2. Supprimer quelques échéances au milieu
3. Cliquer sur **"Générer échéances"**

### Résultat attendu :
✅ Seules les échéances manquantes sont recréées
✅ Les échéances existantes ne sont pas touchées

---

## Vérification des dates

### Contrat mensuel avec date de début : 15/03/2024

Les 12 échéances doivent être :
```
0.  15/03/2024
1.  15/04/2024
2.  15/05/2024
3.  15/06/2024
4.  15/07/2024
5.  15/08/2024
6.  15/09/2024
7.  15/10/2024
8.  15/11/2024
9.  15/12/2024
10. 15/01/2025
11. 15/02/2025
```

### Contrat annuel avec date de début : 01/01/2024

L'échéance doit être :
```
0. 01/01/2024
```

---

## Dépannage

### Problème : Aucune échéance générée
**Cause possible** : Toutes les échéances existent déjà
**Solution** : Vérifier dans la base de données ou supprimer les échéances existantes

### Problème : Dates incorrectes
**Cause possible** : Bug dans le calcul
**Solution** : Vérifier que la correction a bien été appliquée dans `src/Model/Echeance.php`

### Problème : Erreur SQL
**Cause possible** : Champ manquant
**Solution** : Vérifier que la table `echeances` existe avec tous les champs

---

## SQL pour vérifier les échéances

```sql
-- Voir toutes les échéances d'un contrat
SELECT * FROM echeances 
WHERE contrat_id = 1 
ORDER BY date_echeance;

-- Compter les échéances par contrat
SELECT contrat_id, COUNT(*) as nb_echeances 
FROM echeances 
GROUP BY contrat_id;

-- Supprimer toutes les échéances d'un contrat (pour retester)
DELETE FROM echeances WHERE contrat_id = 1;
```

---

## Correction appliquée

**Fichier** : `src/Model/Echeance.php`
**Ligne** : 164-188
**Problème** : Double addition de l'intervalle
**Solution** : Calcul correct avec `P{i}M` ou `P{i}Y`

**Avant** :
```php
$dateEcheance->add(new \DateInterval($interval));
$dateEcheance = $dateEcheance->add(new \DateInterval('P' . $i . 'M'));
// Résultat : dates doublées
```

**Après** :
```php
if ($contrat['frequence'] === 'mensuel') {
    $dateEcheance->add(new \DateInterval('P' . $i . 'M'));
} else {
    $dateEcheance->add(new \DateInterval('P' . $i . 'Y'));
}
// Résultat : dates correctes
```
