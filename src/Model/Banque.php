<?php
namespace App\Model;

use App\Database;
use PDO;

class Banque
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM banques ORDER BY nom ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM banques WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO banques (nom, logo_url, couleur) VALUES (:nom, :logo_url, :couleur)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nom' => $data['nom'],
            'logo_url' => $data['logo_url'] ?? null,
            'couleur' => $data['couleur'] ?? '#6366F1'
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE banques SET nom = :nom, logo_url = :logo_url, couleur = :couleur WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $data['nom'],
            'logo_url' => $data['logo_url'] ?? null,
            'couleur' => $data['couleur'] ?? '#6366F1'
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM banques WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
