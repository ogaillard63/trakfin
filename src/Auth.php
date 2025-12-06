<?php
namespace App;

class Auth
{
    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    /**
     * Connecte l'utilisateur
     */
    public static function login(string $username, string $password): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Récupérer les identifiants depuis .env
        $validUsername = $_ENV['AUTH_USERNAME'] ?? 'admin';
        $validPassword = $_ENV['AUTH_PASSWORD'] ?? 'admin';

        if ($username === $validUsername && $password === $validPassword) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            return true;
        }

        return false;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();
    }

    /**
     * Redirige vers la page de login si non authentifié
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            if (self::isApiRequest()) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Non authentifié']);
                exit;
            }
            
            header('Location: /login');
            exit;
        }
    }

    private static function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
    }

    /**
     * Récupère le nom d'utilisateur connecté
     */
    public static function user(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['username'] ?? null;
    }
}
