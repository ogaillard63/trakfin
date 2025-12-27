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
        
        // 1. Récupérer les échéances RÉELLES (payées ou modifiées manuellement)
        // On exclut les 'prevu' qui sont purement automatiques (si on change de logique)
        // Mais pour la transition, on va considérer que tout ce qui est en base est "réel" 
        // ou a été validé.
        $reelles = $this->getAll(['mois' => $mois]);
        
        // Indexer par contrat_id pour vérification rapide
        $reellesParContrat = [];
        foreach ($reelles as $ech) {
            $reellesParContrat[$ech['contrat_id']] = $ech;
        }

        // 2. Générer les virtuelles pour les contrats actifs
        $contratModel = new Contrat();
        $contrats = $contratModel->getAll(true); // Actifs seulement
        
        $virtuelles = [];
        $dateDebutMois = new \DateTime("$year-$month-01");
        $dateFinMois = (clone $dateDebutMois)->modify('last day of this month');

        foreach ($contrats as $contrat) {
            // Si une échéance réelle existe déjà pour ce mois, on ne génère rien
            if (isset($reellesParContrat[$contrat['id']])) {
                continue;
            }

            // Vérifier si une échéance "tombe" ce mois-ci
            $dateEcheance = $this->calculeDateEcheanceMois($contrat, $dateDebutMois, $dateFinMois);
            
            if ($dateEcheance) {
                // Trouver le dernier montant connu
                $dernierMontant = $contrat['dernier_montant'] ?? 0;
                
                $virtuelles[] = [
                    'id' => 'virtual_' . $contrat['id'] . '_' . $dateEcheance->format('Ymd'),
                    'contrat_id' => $contrat['id'],
                    'date_echeance' => $dateEcheance->format('Y-m-d'),
                    'montant' => $dernierMontant,
                    'statut' => 'prevu', // Statut simulé
                    'commentaire' => 'Provisionnel',
                    'contrat_nom' => $contrat['nom'],
                    'fournisseur' => $contrat['fournisseur'],
                    'categorie_nom' => $contrat['categorie_nom'],
                    'categorie_couleur' => $contrat['categorie_couleur'],
                    'categorie_icone' => $contrat['categorie_icone'],
                    'is_virtual' => true
                ];
            }
        }

        // 3. Fusionner et trier
        $toutes = array_merge($reelles, $virtuelles);
        usort($toutes, function($a, $b) {
            return $b['date_echeance'] <=> $a['date_echeance'];
        });

        return $toutes;
    }

    private function calculeDateEcheanceMois(array $contrat, \DateTime $debutMois, \DateTime $finMois): ?\DateTime
    {
        if (!empty($contrat['derniere_echeance_payee'])) {
            $debutContrat = new \DateTime($contrat['derniere_echeance_payee']);
        } else {
            $debutContrat = new \DateTime($contrat['date_debut']);
        }
        
        // Si le contrat commence après le mois en question, pas d'échéance
        if ($debutContrat > $finMois) {
            return null;
        }

        // Calcul simple pour mensuel
        if ($contrat['frequence'] === 'mensuel') {
            // L'échéance est au même jour que le début du contrat
            $jour = $debutContrat->format('d');
            // Attention aux mois courts (ex: 31 janvier -> avril)
            // On prend le jour, s'il dépasse le nombre de jours du mois, on prend le dernier jour
            $maxJours = $finMois->format('d');
            $jour = min($jour, $maxJours);
            return new \DateTime($debutMois->format('Y-m-') . $jour);
        }

        // Pour les autres fréquences, il faut itérer ou calculer modulo
        $interval = match($contrat['frequence']) {
            'bimensuel' => 'P2M',
            'trimestriel' => 'P3M',
            'semestriel' => 'P6M',
            'annuel' => 'P1Y',
            default => 'P1M'
        };

        // On part de la date de début et on avance jusqu'à trouver une date dans le mois cible
        // Optimisation possible : calculer le nombre d'intervalles approximatif
        $date = clone $debutContrat;
        while ($date <= $finMois) {
            if ($date >= $debutMois && $date <= $finMois) {
                return clone $date;
            }
            $date->add(new \DateInterval($interval));
        }

        return null;
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
        $echeances = $this->getEcheancesDuMois($year, $month);
        $total = 0;
        foreach ($echeances as $e) {
            $total += (float) $e['montant'];
        }
        return $total;
    }

    public function getProjectionAnnuelle(int $year): float
    {
        $total = 0;
        // On calcule la projection pour l'année entière ou juste le reste ?
        // La logique précédente était "reste à payer" (date >= CURDATE)
        // Gardons cette logique mais en utilisant la méthode virtuelle
        
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        
        // Si on regarde une année passée, le reste à payer est 0
        if ($year < $currentYear) return 0;
        
        // Si on regarde l'année en cours, on commence au mois courant
        // Si on regarde une année future, on commence en janvier
        $startMonth = ($year == $currentYear) ? $currentMonth : 1;
        
        for ($m = $startMonth; $m <= 12; $m++) {
            $echeances = $this->getEcheancesDuMois($year, $m);
            foreach ($echeances as $ech) {
                // Si c'est le mois courant, on ne compte que ce qui est futur (optionnel, selon besoin)
                // Ma logique précédente regardait la date exacte.
                // On va simplifier : somme de tout ce qui est prévu/virtuel ou payé
                // ATTENTION : La demande originale était pour une "projection".
                // Souvent "projection" = ce qui reste à sortir.
                // Mais sur le dashboard, on veut souvent "Total Annuel Estimé".
                // Le code précédent `date_echeance >= CURDATE()` impliquait "Reste à payer".
                
                $dateEcheance = new \DateTime($ech['date_echeance']);
                if ($dateEcheance >= new \DateTime('today')) {
                    $total += (float)$ech['montant'];
                }
            }
        }
        
        return $total;
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
    /**
     * @deprecated Cette méthode n'est plus utilisée, remplacée par la génération virtuelle à la volée
     */
    public function genererEcheancesManquantes(int $year, int $month): int
    {
        return 0;
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
