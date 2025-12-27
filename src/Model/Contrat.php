<?php
namespace App\Model;

use App\Database;
use PDO;

class Contrat
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE c.actif = 1' : '';
        
        $sql = "SELECT c.*, 
                       cat.nom as categorie_nom, 
                       cat.couleur as categorie_couleur,
                       cat.icone as categorie_icone,
                       (SELECT e.montant FROM echeances e 
                        WHERE e.contrat_id = c.id 
                        ORDER BY e.date_echeance DESC LIMIT 1) as dernier_montant,
                       (SELECT e.date_echeance FROM echeances e 
                        WHERE e.contrat_id = c.id AND e.statut = 'prevu'
                        ORDER BY e.date_echeance ASC LIMIT 1) as prochaine_echeance,
                       (SELECT e.date_echeance FROM echeances e 
                        WHERE e.contrat_id = c.id AND e.statut = 'paye'
                        ORDER BY e.date_echeance DESC LIMIT 1) as derniere_echeance_payee,
                       (SELECT COUNT(*) FROM echeances e 
                        WHERE e.contrat_id = c.id) as nb_echeances
                FROM contrats c
                LEFT JOIN categories cat ON c.categorie_id = cat.id
                {$where}
                ORDER BY cat.nom ASC, c.nom ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT c.*, 
                       cat.nom as categorie_nom,
                       cat.couleur as categorie_couleur,
                       cat.icone as categorie_icone,
                       (SELECT e.montant FROM echeances e 
                        WHERE e.contrat_id = c.id 
                        ORDER BY e.date_echeance DESC LIMIT 1) as dernier_montant
                FROM contrats c
                LEFT JOIN categories cat ON c.categorie_id = cat.id
                WHERE c.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO contrats (nom, fournisseur, categorie_id, frequence, date_debut, notes)
                VALUES (:nom, :fournisseur, :categorie_id, :frequence, :date_debut, :notes)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nom' => $data['nom'],
            'fournisseur' => !empty($data['fournisseur']) ? $data['fournisseur'] : null,
            'categorie_id' => !empty($data['categorie_id']) ? (int)$data['categorie_id'] : null,
            'frequence' => $data['frequence'] ?? 'mensuel',
            'date_debut' => $data['date_debut'],
            'notes' => !empty($data['notes']) ? $data['notes'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE contrats SET
                nom = :nom, fournisseur = :fournisseur, categorie_id = :categorie_id,
                frequence = :frequence, date_debut = :date_debut, notes = :notes,
                updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $data['nom'],
            'fournisseur' => !empty($data['fournisseur']) ? $data['fournisseur'] : null,
            'categorie_id' => !empty($data['categorie_id']) ? (int)$data['categorie_id'] : null,
            'frequence' => $data['frequence'] ?? 'mensuel',
            'date_debut' => $data['date_debut'],
            'notes' => !empty($data['notes']) ? $data['notes'] : null,
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "UPDATE contrats SET actif = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getContratsAvecAugmentation(): array
    {
        $sql = "SELECT c.*, 
                       cat.nom as categorie_nom,
                       cat.couleur as categorie_couleur,
                       e1.montant as dernier_montant,
                       e2.montant as avant_dernier_montant,
                       ((e1.montant - e2.montant) / e2.montant * 100) as pourcentage_augmentation
                FROM contrats c
                LEFT JOIN categories cat ON c.categorie_id = cat.id
                LEFT JOIN echeances e1 ON e1.id = (
                    SELECT id FROM echeances 
                    WHERE contrat_id = c.id 
                    ORDER BY date_echeance DESC LIMIT 1
                )
                LEFT JOIN echeances e2 ON e2.id = (
                    SELECT id FROM echeances 
                    WHERE contrat_id = c.id 
                    ORDER BY date_echeance DESC LIMIT 1 OFFSET 1
                )
                WHERE c.actif = 1 
                AND e1.montant > e2.montant
                ORDER BY pourcentage_augmentation DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
