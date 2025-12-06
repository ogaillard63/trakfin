# Fichiers à uploader sur trakfin.free.nf

Pour que les statistiques financières apparaissent sur votre site en ligne, vous devez uploader les fichiers suivants :

## 📁 Fichiers modifiés :

1. **src/Model/Echeance.php**
   - Contient la nouvelle méthode `getStatistiquesContrat()`

2. **index.php** (à la racine)
   - Passe maintenant la variable `statistiques` à la vue

3. **templates/contrats/show.html.twig**
   - Affiche le nouveau cadre de statistiques financières

## 📤 Étapes pour uploader :

1. Connectez-vous à votre hébergement FTP (trakfin.free.nf)
2. Uploadez ces 3 fichiers en écrasant les anciens
3. Rafraîchissez la page http://trakfin.free.nf/contrats/13

## ✅ Vérification locale :

Pour tester en local d'abord :
- Visitez http://localhost/trakfin/contrats/13
- Vous devriez voir le cadre "Statistiques financières" dans la sidebar (colonne de gauche)

## 🎨 Ce que vous devriez voir :

Un cadre avec fond bleu dégradé contenant :
- **Augmentation depuis souscription** : différence entre le premier et le dernier montant
- **Total des échéances versées** : somme de toutes les échéances payées
