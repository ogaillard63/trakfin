<?php
namespace App;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class View
{
    private static ?Environment $twig = null;

    public static function init(): void
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../templates');
            self::$twig = new Environment($loader, [
                'cache' => false,
                'debug' => true,
            ]);

            // Filtres personnalisés
            self::$twig->addFilter(new TwigFilter('money', function ($value) {
                return number_format((float)$value, 2, ',', ' ') . ' €';
            }));

            self::$twig->addFilter(new TwigFilter('date_fr', function ($value) {
                if (empty($value)) return '';
                $date = is_string($value) ? new \DateTime($value) : $value;
                return $date->format('d/m/Y');
            }));

            self::$twig->addFilter(new TwigFilter('mois_fr', function ($value) {
                return MOIS_FR[(int)$value] ?? $value;
            }));

            self::$twig->addFilter(new TwigFilter('variation', function ($current, $previous) {
                if ($previous == 0) return 'N/A';
                $diff = $current - $previous;
                $percent = ($diff / $previous) * 100;
                $sign = $diff > 0 ? '+' : '';
                return $sign . number_format($percent, 1) . '%';
            }));

            // Fonction url()
            // Fonction url()
            self::$twig->addFunction(new \Twig\TwigFunction('url', function ($path) {
                // Utiliser le chemin relatif à la racine pour éviter les problèmes de protocole (Mixed Content)
                $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
                $basePath = rtrim($basePath, '/');
                return $basePath . $path;
            }));

            // Variables globales
            self::$twig->addGlobal('app_name', APP_NAME);
        }
    }

    public static function display(string $template, array $data = []): void
    {
        self::init();
        echo self::$twig->render($template, $data);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
