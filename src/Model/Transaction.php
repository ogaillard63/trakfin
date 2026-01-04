<?php
namespace App\Model;

use App\Database;
use PDO;

class Transaction
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(int $compteId, int $limit = 50, int $offset = 0): array
    {
        return $this->search($compteId, '', $limit, $offset);
    }

    public function search(int $compteId, string $query = '', int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT t.*, c.nom as categorie_nom, c.couleur as categorie_couleur, c.icone as categorie_icone
                FROM transactions t
                LEFT JOIN categories c ON t.categorie_id = c.id
                WHERE t.compte_id = :compte_id";
        
        $params = [':compte_id' => $compteId];

        if (!empty($query)) {
            $sql .= " AND (t.description LIKE :query_desc OR t.montant LIKE :query_amount)";
            $params[':query_desc'] = "%$query%";
            $params[':query_amount'] = "%$query%";
        }
        
        $sql .= " ORDER BY t.date_transaction DESC, t.id DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTotals(int $compteId, string $query = ''): array
    {
        $sql = "SELECT 
                    COUNT(*) as count,
                    SUM(CASE WHEN montant > 0 THEN montant ELSE 0 END) as total_recettes,
                    SUM(CASE WHEN montant < 0 THEN montant ELSE 0 END) as total_depenses
                FROM transactions t
                WHERE t.compte_id = :compte_id";
        
        $params = [':compte_id' => $compteId];

        if (!empty($query)) {
            $sql .= " AND (t.description LIKE :query_desc OR t.montant LIKE :query_amount)";
            $params[':query_desc'] = "%$query%";
            $params[':query_amount'] = "%$query%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return [
            'count' => $result['count'] ?? 0,
            'recettes' => $result['total_recettes'] ?? 0,
            'depenses' => $result['total_depenses'] ?? 0,
            'solde' => ($result['total_recettes'] ?? 0) + ($result['total_depenses'] ?? 0)
        ];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function create(array $data): int
    {
        // Générer un hash unique pour l'import
        // Si une signature brute est fournie (ex: toute la ligne CSV), on l'utilise
        if (!empty($data['import_signature'])) {
            $importHash = hash('sha256', $data['import_signature']);
        } else {
            // Fallback : date + description + montant
            $importHash = hash('sha256', $data['date_transaction'] . $data['description'] . number_format($data['montant'], 2, '.', ''));
        }
        
        // Auto-categorisation si categorie_id non fourni
        if (empty($data['categorie_id'])) {
            $data['categorie_id'] = $this->guessCategory($data['description']);
        }

        $sql = "INSERT INTO transactions (compte_id, date_transaction, description, montant, categorie_id, import_hash) 
                VALUES (:compte_id, :date_transaction, :description, :montant, :categorie_id, :import_hash)
                ON DUPLICATE KEY UPDATE import_hash = import_hash"; // Ignore si doublon
        
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'compte_id' => $data['compte_id'],
                'date_transaction' => $data['date_transaction'],
                'description' => $data['description'],
                'montant' => $data['montant'],
                'categorie_id' => $data['categorie_id'],
                'import_hash' => $importHash
            ]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // Si violation de contrainte unique (doublon), retourner 0
            if ($e->getCode() == 23000) {
                return 0;
            }
            throw $e;
        }
    }

    private function guessCategory(string $description): ?int
    {
        // Chercher des mots-clés dans la description pour deviner la catégorie
        // Cette logique repose sur la table category_params
        
        $sql = "SELECT categorie_id FROM category_params 
                WHERE :description LIKE CONCAT('%', keyword, '%') 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['description' => $description]);
        
        return $stmt->fetchColumn() ?: null;
    }

    public function addCategoryKeyword(string $keyword, int $categoryId): bool
    {
        $sql = "INSERT INTO category_params (keyword, categorie_id) VALUES (:keyword, :categorie_id)
                ON DUPLICATE KEY UPDATE categorie_id = :categorie_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'keyword' => $keyword,
            'categorie_id' => $categoryId
        ]);
    }
    
    public function getCategoryKeywords(int $categoryId): array
    {
        $sql = "SELECT keyword FROM category_params WHERE categorie_id = :categorie_id ORDER BY keyword ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['categorie_id' => $categoryId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
