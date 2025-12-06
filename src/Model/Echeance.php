<?php
namespace App\Model;

use App\Database;
use PDO;

class Echeance
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['contrat_id'])) {
            $where[] = 'e.contrat_id = :contrat_id';
            $params['contrat_id'] = $filters['contrat_id'];
        }

        if (!empty($filters['mois'])) {
            $where[] = "DATE_FORMAT(e.date_echeance, '%Y-%m') = :mois";
            $params['mois'] = $filters['mois'];
        }

        if (!empty($filters['statut'])) {
            $where[] = 'e.statut = :statut';
            $params['statut'] = $filters['statut'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT e.*, 
                       c.nom as contrat_nom,
                       c.fournisseur,
                       cat.nom as categorie_nom,
                       cat.couleur as categorie_couleur,
                       cat.icone as categorie_icone
                FROM echeances e
                JOIN contrats c ON e.contrat_id = c.id
                LEFT JOIN categories cat ON c.categorie_id = cat.id
                {$whereClause}
                ORDER BY e.date_echeance DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getEcheancesDuMois(int $year, int $month): array
    {
        $mois = sprintf('%04d-%02d', $year, $month);
        return $this->getAll(['mois' => $mois]);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT e.*, c.nom as contrat_nom
                FROM echeances e
                JOIN contrats c ON e.contrat_id = c.id
                WHERE e.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO echeances (contrat_id, date_echeance, montant, statut, commentaire)
                VALUES (:contrat_id, :date_echeance, :montant, :statut, :commentaire)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'contrat_id' => $data['contrat_id'],
            'date_echeance' => $data['date_echeance'],
            'montant' => $data['montant'],
            'statut' => $data['statut'] ?? 'prevu',
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE echeances SET
                date_echeance = :date_echeance, montant = :montant,
                statut = :statut, commentaire = :commentaire,
                updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'date_echeance' => $data['date_echeance'],
            'montant' => $data['montant'],
            'statut' => $data['statut'] ?? 'prevu',
            'commentaire' => $data['commentaire'] ?? null,
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM echeances WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function marquerPaye(int $id): bool
    {
        $sql = "UPDATE echeances SET statut = 'paye' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getTotalMois(int $year, int $month): float
    {
        $mois = sprintf('%04d-%02d', $year, $month);
        
        $sql = "SELECT SUM(montant) as total
                FROM echeances
                WHERE DATE_FORMAT(date_echeance, '%Y-%m') = :mois";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mois' => $mois]);
        $result = $stmt->fetch();

        return (float) ($result['total'] ?? 0);
    }

    public function getProjectionAnnuelle(int $year): float
    {
        $sql = "SELECT SUM(montant) as total
                FROM echeances
                WHERE YEAR(date_echeance) = :year
                AND date_echeance >= CURDATE()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['year' => $year]);
        $result = $stmt->fetch();

        return (float) ($result['total'] ?? 0);
    }

    public function genererEcheances(int $contratId, int $nombre = 12): int
    {
        $contratModel = new Contrat();
        $contrat = $contratModel->getById($contratId);
        
        if (!$contrat) return 0;

        $dateDebut = new \DateTime($contrat['date_debut']);
        $interval = $contrat['frequence'] === 'mensuel' ? 'P1M' : 'P1Y';
        $count = 0;

        for ($i = 0; $i < $nombre; $i++) {
            $dateEcheance = clone $dateDebut;
            
            // Ajouter l'intervalle multiplié par i
            if ($contrat['frequence'] === 'mensuel') {
                $dateEcheance->add(new \DateInterval('P' . $i . 'M'));
            } else {
                $dateEcheance->add(new \DateInterval('P' . $i . 'Y'));
            }

            // Vérifier si l'échéance existe déjà
            $sql = "SELECT id FROM echeances 
                    WHERE contrat_id = :contrat_id 
                    AND date_echeance = :date";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'contrat_id' => $contratId,
                'date' => $dateEcheance->format('Y-m-d')
            ]);

            if (!$stmt->fetch()) {
                $this->create([
                    'contrat_id' => $contratId,
                    'date_echeance' => $dateEcheance->format('Y-m-d'),
                    'montant' => 0,
                    'statut' => 'prevu',
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function getEvolution(int $contratId): array
    {
        $sql = "SELECT date_echeance, montant
                FROM echeances
                WHERE contrat_id = :contrat_id
                AND date_echeance <= CURDATE()
                ORDER BY date_echeance ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['contrat_id' => $contratId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Génère automatiquement les échéances manquantes pour tous les contrats actifs
     * jusqu'à une date donnée
     */
    public function genererEcheancesManquantes(int $year, int $month): int
    {
        $contratModel = new Contrat();
        $contratsActifs = $contratModel->getAll();
        $totalGenere = 0;

        // Date cible (dernier jour du mois)
        $dateCible = new \DateTime("$year-$month-01");
        $dateCible->modify('last day of this month');

        foreach ($contratsActifs as $contrat) {
            if (!$contrat['actif']) {
                continue;
            }

            // Récupérer la dernière échéance du contrat
            $sql = "SELECT * FROM echeances 
                    WHERE contrat_id = :contrat_id 
                    ORDER BY date_echeance DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['contrat_id' => $contrat['id']]);
            $derniereEcheance = $stmt->fetch();

            // Déterminer la date de départ
            if ($derniereEcheance) {
                $dateDepart = new \DateTime($derniereEcheance['date_echeance']);
                $montantBase = $derniereEcheance['montant'];
            } else {
                // Pas d'échéance, partir de la date de début du contrat
                $dateDepart = new \DateTime($contrat['date_debut']);
                $montantBase = 0;
            }

            // Générer les échéances manquantes
            switch ($contrat['frequence']) {
                case 'mensuel':
                    $interval = 'P1M';
                    break;
                case 'bimensuel':
                    $interval = 'P2M';
                    break;
                case 'trimestriel':
                    $interval = 'P3M';
                    break;
                case 'semestriel':
                    $interval = 'P6M';
                    break;
                default: // annuel
                    $interval = 'P1Y';
                    break;
            }
            $dateActuelle = clone $dateDepart;

            while ($dateActuelle <= $dateCible) {
                $dateActuelle->add(new \DateInterval($interval));

                if ($dateActuelle > $dateCible) {
                    break;
                }

                // Vérifier si l'échéance existe déjà
                $sql = "SELECT id FROM echeances 
                        WHERE contrat_id = :contrat_id 
                        AND date_echeance = :date";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'contrat_id' => $contrat['id'],
                    'date' => $dateActuelle->format('Y-m-d')
                ]);

                if (!$stmt->fetch()) {
                    // Créer l'échéance
                    $this->create([
                        'contrat_id' => $contrat['id'],
                        'date_echeance' => $dateActuelle->format('Y-m-d'),
                        'montant' => $montantBase,
                        'statut' => 'prevu',
                    ]);
                    $totalGenere++;
                }
            }
        }

        return $totalGenere;
    }

    /**
     * Récupère les statistiques d'un contrat
     * - Première échéance (montant initial)
     * - Dernière échéance (montant actuel)
     * - Augmentation en euros et en pourcentage
     * - Total des échéances payées
     */
    public function getStatistiquesContrat(int $contratId): array
    {
        // Récupérer la première échéance
        $sqlPremiere = "SELECT montant, date_echeance 
                        FROM echeances 
                        WHERE contrat_id = :contrat_id 
                        ORDER BY date_echeance ASC 
                        LIMIT 1";
        $stmt = $this->db->prepare($sqlPremiere);
        $stmt->execute(['contrat_id' => $contratId]);
        $premiereEcheance = $stmt->fetch();

        // Récupérer la dernière échéance
        $sqlDerniere = "SELECT montant, date_echeance 
                        FROM echeances 
                        WHERE contrat_id = :contrat_id 
                        ORDER BY date_echeance DESC 
                        LIMIT 1";
        $stmt = $this->db->prepare($sqlDerniere);
        $stmt->execute(['contrat_id' => $contratId]);
        $derniereEcheance = $stmt->fetch();

        // Calculer le total des échéances payées
        $sqlTotalPaye = "SELECT SUM(montant) as total 
                         FROM echeances 
                         WHERE contrat_id = :contrat_id 
                         AND statut = 'paye'";
        $stmt = $this->db->prepare($sqlTotalPaye);
        $stmt->execute(['contrat_id' => $contratId]);
        $resultTotal = $stmt->fetch();
        $totalPaye = (float) ($resultTotal['total'] ?? 0);

        // Calculer l'augmentation
        $montantInitial = $premiereEcheance ? (float) $premiereEcheance['montant'] : 0;
        $montantActuel = $derniereEcheance ? (float) $derniereEcheance['montant'] : 0;
        $augmentationEuros = $montantActuel - $montantInitial;
        $augmentationPourcentage = $montantInitial > 0 
            ? ($augmentationEuros / $montantInitial * 100) 
            : 0;

        return [
            'montant_initial' => $montantInitial,
            'montant_actuel' => $montantActuel,
            'augmentation_euros' => $augmentationEuros,
            'augmentation_pourcentage' => $augmentationPourcentage,
            'total_paye' => $totalPaye,
            'premiere_date' => $premiereEcheance ? $premiereEcheance['date_echeance'] : null,
            'derniere_date' => $derniereEcheance ? $derniereEcheance['date_echeance'] : null,
        ];
    }
}
