<?php
/**
 * Script pour vider le cache OPcache
 * À uploader sur l'hébergeur et exécuter via le navigateur
 * Puis supprimer ce fichier après utilisation
 */

echo "<h1>Clear Cache</h1>";

// Vider OPcache si disponible
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p>✅ OPcache vidé</p>";
} else {
    echo "<p>⚠️ OPcache non disponible</p>";
}

// Vérifier la date de modification du template
$templateFile = __DIR__ . '/templates/contrats/show.html.twig';
if (file_exists($templateFile)) {
    $modTime = filemtime($templateFile);
    echo "<p>📄 Fichier show.html.twig modifié le : " . date('Y-m-d H:i:s', $modTime) . "</p>";
    
    // Afficher les 20 premières lignes pour vérifier
    $content = file_get_contents($templateFile);
    $lines = explode("\n", $content);
    echo "<h2>Premières lignes du fichier :</h2>";
    echo "<pre>";
    for ($i = 0; $i < min(20, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
    
    // Chercher les classes order-
    if (strpos($content, 'order-1') !== false && strpos($content, 'order-2') !== false) {
        echo "<p>✅ Les classes 'order-1' et 'order-2' sont présentes dans le fichier</p>";
    } else {
        echo "<p>❌ Les classes 'order-1' et 'order-2' ne sont PAS trouvées dans le fichier</p>";
    }
} else {
    echo "<p>❌ Fichier non trouvé : $templateFile</p>";
}

echo "<hr>";
echo "<p><strong>Instructions :</strong></p>";
echo "<ol>";
echo "<li>Si le fichier est à jour, videz le cache de votre navigateur (Ctrl+Shift+R)</li>";
echo "<li>Si le fichier n'est pas à jour, re-uploadez le fichier templates/contrats/show.html.twig</li>";
echo "<li>Supprimez ce fichier clear_cache.php après utilisation</li>";
echo "</ol>";
