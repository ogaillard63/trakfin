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
     * Connecte l'utilisateur avec un code à 4 chiffres
     */
    public static function loginWithCode(string $code): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (self::isBlocked()) {
            return false;
        }

        // Récupérer le code depuis .env
        $validCode = $_ENV['APP_PASSWORD'] ?? '1234';

        if ($code === $validCode) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = 'admin';
            unset($_SESSION['login_attempts']);
            unset($_SESSION['blocked_until']);
            return true;
        }

        // Incrémenter les tentatives
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        
        if ($_SESSION['login_attempts'] >= 3) {
            $_SESSION['blocked_until'] = time() + (15 * 60); // Bloqué pendant 15 minutes
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur est actuellement bloqué
     */
    public static function isBlocked(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['blocked_until']) && $_SESSION['blocked_until'] > time()) {
            return true;
        }

        // Si le temps de blocage est passé, on réinitialise
        if (isset($_SESSION['blocked_until'])) {
            unset($_SESSION['blocked_until']);
            unset($_SESSION['login_attempts']);
        }

        return false;
    }

    /**
     * Retourne le temps restant de blocage en secondes
     */
    public static function getBlockedTimeRemaining(): int
    {
        if (!isset($_SESSION['blocked_until'])) return 0;
        return max(0, $_SESSION['blocked_until'] - time());
    }

    /**
     * Connecte l'utilisateur (Ancienne méthode conservée pour compatibilité si besoin, mais dépréciée)
     */
    public static function login(string $username, string $password): bool
    {
        return self::loginWithCode($password);
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
