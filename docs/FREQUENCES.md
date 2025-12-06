# 📅 Ajout des nouvelles fréquences

## Fréquences disponibles

| Fréquence | Intervalle | Exemple |
|-----------|------------|---------|
| **Mensuel** | 1 mois | Loyer, abonnements |
| **Bimensuel** | 2 mois | Certaines factures |
| **Trimestriel** | 3 mois | Impôts, charges |
| **Semestriel** | 6 mois | Assurances |
| **Annuel** | 1 an | Taxes, cotisations |

## Installation

### Étape 1 : Exécuter le script SQL

**Via phpMyAdmin** :
1. Ouvrir phpMyAdmin
2. Sélectionner la base `trakfin`
3. Onglet "SQL"
4. Copier-coller le contenu de `database/add_frequences.sql`
5. Exécuter

**Via ligne de commande** :
```bash
mysql -u root -p trakfin < database/add_frequences.sql
```

### Étape 2 : Vérifier

Les fichiers suivants ont été mis à jour automatiquement :
- ✅ `src/Model/Echeance.php` : Calcul des intervalles
- ✅ `templates/contrats/index.html.twig` : Affichage liste
- ✅ `templates/contrats/show.html.twig` : Affichage détail

## Utilisation

### Créer un contrat avec une nouvelle fréquence

1. **Nouveau contrat** → Sélectionner la fréquence
2. **Générer échéances** → Automatique selon la fréquence

### Exemples de génération

**Contrat Trimestriel** (01/01/2024) :
- 01/01/2024
- 01/04/2024
- 01/07/2024
- 01/10/2024

**Contrat Semestriel** (01/01/2024) :
- 01/01/2024
- 01/07/2024

**Contrat Bimensuel** (01/01/2024) :
- 01/01/2024
- 01/03/2024
- 01/05/2024
- 01/07/2024
- 01/09/2024
- 01/11/2024

## Intervalles PHP

| Fréquence | Code PHP |
|-----------|----------|
| Mensuel | `P1M` (1 mois) |
| Bimensuel | `P2M` (2 mois) |
| Trimestriel | `P3M` (3 mois) |
| Semestriel | `P6M` (6 mois) |
| Annuel | `P1Y` (1 an) |

Toutes les fréquences sont maintenant disponibles ! 🎉
