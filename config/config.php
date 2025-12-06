<?php
/**
 * Configuration de l'application TrakFin
 */

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Supprimer les guillemets doubles ou simples
        $value = trim($value, '"\'');
        
        $_ENV[$name] = $value;
    }
}

// Configuration de l'application
define('APP_NAME', $_ENV['APP_NAME'] ?? 'TrakFin');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/trakfin');

// Configuration de la base de données
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'trakfin');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Timezone
date_default_timezone_set('Europe/Paris');

// Autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Constantes utiles
define('MOIS_FR', [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
]);
