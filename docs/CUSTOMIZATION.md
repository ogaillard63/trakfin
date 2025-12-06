# 🎨 Guide de personnalisation - TrakFin

## Graphiques

### Hauteur du graphique d'évolution

Le graphique d'évolution des montants a une hauteur fixe de **300px**.

**Pour modifier la hauteur** :
1. Ouvrir `templates/contrats/show.html.twig`
2. Chercher la ligne :
   ```html
   <div style="height: 300px;">
   ```
3. Modifier la valeur (ex: `400px`, `250px`, etc.)

### Couleurs du graphique

Les couleurs sont définies dans le script Chart.js :

- **Ligne principale** : `#6366F1` (Indigo)
- **Zone de remplissage** : `rgba(99, 102, 241, 0.1)` (Indigo transparent)
- **Points normaux** : `#6366F1` (Indigo)
- **Points en hausse** : `#EF4444` (Rouge)

**Pour modifier** :
1. Ouvrir `templates/contrats/show.html.twig`
2. Chercher la section `new Chart(ctx, {`
3. Modifier les valeurs de `borderColor`, `backgroundColor`, `pointBackgroundColor`

## Couleurs des catégories

Les catégories ont des couleurs prédéfinies dans la base de données :

| Catégorie | Couleur | Code |
|-----------|---------|------|
| Assurance | Rouge | `#EF4444` |
| Énergie | Orange | `#F59E0B` |
| Télécom | Bleu | `#3B82F6` |
| Habitation | Violet | `#8B5CF6` |
| Impôts | Rose | `#EC4899` |
| Abonnements | Vert | `#10B981` |

**Pour modifier** :
1. Accéder à phpMyAdmin
2. Ouvrir la table `categories`
3. Modifier la colonne `couleur` (format hexadécimal)

## Icônes

Les icônes utilisent **Lucide Icons**.

**Icônes disponibles** : https://lucide.dev/icons/

**Pour changer l'icône d'une catégorie** :
1. Accéder à phpMyAdmin
2. Ouvrir la table `categories`
3. Modifier la colonne `icone` avec le nom de l'icône Lucide (ex: `shield`, `zap`, `wifi`)

## Largeur maximale

La largeur maximale du contenu est de **900px** (classe Tailwind `max-w-5xl`).

**Pour modifier** :
1. Ouvrir `templates/base.html.twig`
2. Chercher la ligne :
   ```html
   <div class="max-w-5xl mx-auto p-8">
   ```
3. Remplacer `max-w-5xl` par :
   - `max-w-4xl` : 896px (plus étroit)
   - `max-w-6xl` : 1152px (plus large)
   - `max-w-7xl` : 1280px (très large)

## Sidebar

### Largeur de la sidebar

La sidebar a une largeur de **256px** (classe `w-64`).

**Pour modifier** :
1. Ouvrir `templates/base.html.twig`
2. Chercher :
   ```html
   <aside class="w-64 bg-white border-r border-gray-200">
   ```
3. Remplacer `w-64` par :
   - `w-56` : 224px (plus étroit)
   - `w-72` : 288px (plus large)
   - `w-80` : 320px (très large)

### Couleur de fond de la sidebar

**Pour modifier** :
1. Ouvrir `templates/base.html.twig`
2. Chercher :
   ```html
   <aside class="w-64 bg-white border-r border-gray-200">
   ```
3. Remplacer `bg-white` par :
   - `bg-gray-50` : Gris très clair
   - `bg-gray-100` : Gris clair
   - `bg-indigo-50` : Indigo très clair

## Bouton flottant

Le bouton "+" flottant est positionné en **bas à droite**.

**Pour modifier la position** :
1. Ouvrir `templates/base.html.twig`
2. Chercher :
   ```html
   <a href="{{ url('/contrats/create') }}" 
      class="fixed bottom-8 right-8 ...">
   ```
3. Modifier :
   - `bottom-8` : Distance du bas (32px)
   - `right-8` : Distance de la droite (32px)
   - Valeurs possibles : `4`, `8`, `12`, `16`, `20`, `24`

**Pour changer la couleur** :
- Remplacer `bg-indigo-600` et `hover:bg-indigo-700` par d'autres couleurs Tailwind

## Cartes de contrats

### Nombre de colonnes

Par défaut : **3 colonnes** sur grand écran.

**Pour modifier** :
1. Ouvrir `templates/contrats/index.html.twig`
2. Chercher :
   ```html
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
   ```
3. Modifier `lg:grid-cols-3` :
   - `lg:grid-cols-2` : 2 colonnes
   - `lg:grid-cols-4` : 4 colonnes

### Espacement entre les cartes

Par défaut : **16px** (`gap-4`).

**Pour modifier** :
- Remplacer `gap-4` par `gap-6` (24px), `gap-8` (32px), etc.

## Polices

Par défaut, Tailwind utilise la pile de polices système.

**Pour utiliser une police personnalisée** :
1. Ajouter dans `templates/base.html.twig` (dans `<head>`) :
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
   ```
2. Ajouter dans `<style>` :
   ```css
   body { font-family: 'Inter', sans-serif; }
   ```

## Thème sombre

Pour implémenter un thème sombre, il faudrait :
1. Ajouter `dark:` classes dans tous les templates
2. Utiliser `localStorage` pour sauvegarder la préférence
3. Ajouter un bouton de basculement

**Exemple de modification** :
```html
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
```

## Animations

Tailwind CSS supporte les transitions et animations.

**Exemple d'ajout d'une transition sur les cartes** :
```html
<div class="... transition-all duration-200 hover:scale-105">
```

## Ressources

- **Tailwind CSS** : https://tailwindcss.com/docs
- **Lucide Icons** : https://lucide.dev/icons/
- **Chart.js** : https://www.chartjs.org/docs/
- **Palette de couleurs** : https://tailwindcss.com/docs/customizing-colors
