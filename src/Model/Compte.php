<?php
namespace App\Model;

use App\Database;
use PDO;

class Compte
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(int $banqueId = null): array
    {
        $sql = "SELECT c.*, b.nom as banque_nom, b.couleur as banque_couleur, b.logo_url,
                (c.solde_initial + IFNULL((SELECT SUM(t.montant) FROM transactions t WHERE t.compte_id = c.id), 0)) as solde_actuel
                FROM comptes c
                JOIN banques b ON c.banque_id = b.id";
        
        if ($banqueId) {
            $sql .= " WHERE c.banque_id = :banque_id";
        }
        
        $sql .= " ORDER BY c.nom ASC";

        if ($banqueId) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['banque_id' => $banqueId]);
        } else {
            $stmt = $this->db->query($sql);
        }

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT c.*, b.nom as banque_nom, b.couleur as banque_couleur, b.logo_url
                FROM comptes c
                JOIN banques b ON c.banque_id = b.id
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO comptes (banque_id, nom, numero, solde_initial) 
                VALUES (:banque_id, :nom, :numero, :solde_initial)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'banque_id' => $data['banque_id'],
            'nom' => $data['nom'],
            'numero' => $data['numero'] ?? null,
            'solde_initial' => $data['solde_initial'] ?? 0
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE comptes SET nom = :nom, numero = :numero, solde_initial = :solde_initial 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $data['nom'],
            'numero' => $data['numero'] ?? null,
            'solde_initial' => $data['solde_initial'] ?? 0
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM comptes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getSoldeActuel(int $id): float
    {
        // Récupérer le solde initial
        $stmt = $this->db->prepare("SELECT solde_initial FROM comptes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $soldeInitial = (float) $stmt->fetchColumn();

        // Récupérer la somme des transactions
        $stmt = $this->db->prepare("SELECT SUM(montant) FROM transactions WHERE compte_id = :id");
        $stmt->execute(['id' => $id]);
        $sommeTransactions = (float) $stmt->fetchColumn();

        return $soldeInitial + $sommeTransactions;
    }
}
