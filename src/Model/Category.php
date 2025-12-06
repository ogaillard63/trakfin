<?php
namespace App\Model;

use App\Database;
use PDO;

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM categories ORDER BY nom ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }
}
