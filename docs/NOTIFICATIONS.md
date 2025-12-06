# 🔔 Système de Notifications Toast

## Vue d'ensemble

L'application utilise un système de **toasts** modernes qui apparaissent en **haut à droite** de l'écran et disparaissent automatiquement après **3 secondes**.

## Caractéristiques

✨ **Position** : Haut à droite (fixe)  
⏱️ **Durée** : 3 secondes  
🎨 **Animation** : Slide-in depuis la droite  
❌ **Fermeture manuelle** : Bouton X disponible  
📚 **Empilable** : Plusieurs toasts peuvent s'afficher simultanément  

## Types de toasts

### 1. Success (Succès)
- **Couleur** : Vert
- **Icône** : ✓ (check-circle)
- **Usage** : Confirmation d'action réussie

### 2. Error (Erreur)
- **Couleur** : Rouge
- **Icône** : ✗ (x-circle)
- **Usage** : Erreur ou échec d'action

### 3. Info (Information)
- **Couleur** : Bleu
- **Icône** : ℹ (info)
- **Usage** : Information générale

## Utilisation

### Depuis PHP (Backend)

```php
// Dans un contrôleur (public/index.php)
use App\View;

// Toast de succès
View::flash('success', 'Contrat créé avec succès');

// Toast d'erreur
View::flash('error', 'Impossible de supprimer le contrat');

// Puis rediriger
Router::redirect('/contrats');
```

### Depuis JavaScript (Frontend)

```javascript
// Toast de succès
showToast('Opération réussie', 'success');

// Toast d'erreur
showToast('Une erreur est survenue', 'error');

// Toast d'information
showToast('Information importante', 'info');
```

## Exemples d'utilisation

### Création d'un contrat
```php
$id = $contratModel->create($data);
View::flash('success', 'Contrat créé avec succès');
Router::redirect('/contrats/' . $id);
```

### Modification d'un contrat
```php
$contratModel->update($id, $data);
View::flash('success', 'Contrat modifié avec succès');
Router::redirect('/contrats/' . $id);
```

### Suppression d'un contrat
```php
$contratModel->delete($id);
View::flash('success', 'Contrat supprimé');
Router::redirect('/contrats');
```

### Génération d'échéances
```php
$count = $echeanceModel->genererEcheances($id, 12);
View::flash('success', "$count échéances générées");
Router::redirect('/contrats/' . $id);
```

### Gestion d'erreur
```php
try {
    $contratModel->create($data);
    View::flash('success', 'Contrat créé');
} catch (Exception $e) {
    View::flash('error', 'Erreur : ' . $e->getMessage());
}
Router::redirect('/contrats');
```

## Personnalisation

### Modifier la durée d'affichage

Dans `templates/base.html.twig`, ligne ~100 :
```javascript
// Changer 3000 (3 secondes) par la valeur souhaitée en millisecondes
setTimeout(() => {
    toast.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => toast.remove(), 300);
}, 3000); // ← Modifier ici
```

### Modifier la position

Dans `templates/base.html.twig`, ligne ~59 :
```html
<!-- Haut à droite (actuel) -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Haut à gauche -->
<div id="toast-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<!-- Bas à droite -->
<div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

<!-- Bas à gauche -->
<div id="toast-container" class="fixed bottom-4 left-4 z-50 space-y-2"></div>

<!-- Centré en haut -->
<div id="toast-container" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 space-y-2"></div>
```

### Modifier les couleurs

Dans `templates/base.html.twig`, fonction `showToast()` :
```javascript
const colors = {
    success: 'bg-green-50 border-green-200 text-green-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800'
};
```

### Ajouter un nouveau type

```javascript
// Dans la fonction showToast()
const colors = {
    success: 'bg-green-50 border-green-200 text-green-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800',
    warning: 'bg-yellow-50 border-yellow-200 text-yellow-800' // Nouveau
};

const icons = {
    success: 'check-circle',
    error: 'x-circle',
    info: 'info',
    warning: 'alert-triangle' // Nouveau
};

// Utilisation
showToast('Attention !', 'warning');
```

## Animation

### Séquence d'animation

1. **Création** : Le toast est créé hors écran (translate-x-full)
2. **Entrée** : Slide-in depuis la droite (300ms)
3. **Affichage** : Visible pendant 3 secondes
4. **Sortie** : Slide-out vers la droite (300ms)
5. **Suppression** : Retrait du DOM

### Classes Tailwind utilisées

- `transform` : Active les transformations
- `transition-all` : Anime toutes les propriétés
- `duration-300` : Durée de 300ms
- `ease-out` : Courbe d'animation
- `translate-x-full` : Déplace de 100% vers la droite
- `opacity-0` : Transparent

## Avantages par rapport à l'ancien système

### Ancien système (flash messages)
❌ Messages statiques en haut de page  
❌ Prennent de la place dans le layout  
❌ Restent visibles jusqu'au rechargement  
❌ Pas d'animation  

### Nouveau système (toasts)
✅ Notifications non-intrusives  
✅ Ne perturbent pas le layout  
✅ Disparition automatique  
✅ Animations fluides  
✅ Empilables  
✅ Fermeture manuelle possible  

## Compatibilité

✅ Tous les navigateurs modernes  
✅ Chrome, Firefox, Safari, Edge  
✅ Mobile et Desktop  
✅ Pas de dépendance externe  

## Dépannage

### Les toasts n'apparaissent pas
**Vérifier** :
1. Que le conteneur `#toast-container` existe dans le DOM
2. Que la fonction `showToast()` est bien définie
3. Que Lucide Icons est chargé

### Les toasts ne disparaissent pas
**Vérifier** :
1. Que les `setTimeout` sont bien exécutés
2. Qu'il n'y a pas d'erreur JavaScript dans la console

### Les icônes ne s'affichent pas
**Solution** :
```javascript
// S'assurer que lucide.createIcons() est appelé après l'ajout du toast
container.appendChild(toast);
lucide.createIcons(); // ← Important
```

## Code source

**Fichier** : `templates/base.html.twig`  
**Lignes** : 59-115  
**Fonction principale** : `showToast(message, type)`  
