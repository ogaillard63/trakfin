<?php
namespace App;

use App\Model\Contrat;
use App\Model\Echeance;
use App\Model\Category;

class ApiController
{
    private function jsonResponse($data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    private function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?? [];
    }

    public function index()
    {
        $this->jsonResponse([
            'name' => APP_NAME,
            'version' => '1.0.0',
            'endpoints' => [
                'GET /api/contrats' => 'Liste des contrats',
                'GET /api/echeances' => 'Liste des échéances',
                'POST /api/sync' => 'Synchronisation des données',
            ]
        ]);
    }

    // ===== CONTRATS =====

    public function listContrats()
    {
        Auth::requireAuth();
        $model = new Contrat();
        $contrats = $model->getAll(false); // false = inclure les inactifs
        $this->jsonResponse($contrats);
    }

    public function getContrat($id)
    {
        Auth::requireAuth();
        $model = new Contrat();
        $contrat = $model->getById((int)$id);
        
        if (!$contrat) {
            $this->jsonResponse(['error' => 'Contrat non trouvé'], 404);
        }
        
        $this->jsonResponse($contrat);
    }

    public function createContrat()
    {
        Auth::requireAuth();
        $data = $this->getJsonInput();
        
        // Validation basique
        if (empty($data['nom']) || empty($data['date_debut']) || empty($data['frequence'])) {
            $this->jsonResponse(['error' => 'Champs obligatoires manquants (nom, date_debut, frequence)'], 400);
        }

        $model = new Contrat();
        $id = $model->create([
            'nom' => $data['nom'],
            'fournisseur' => $data['fournisseur'] ?? null,
            'categorie_id' => $data['categorie_id'] ?? null,
            'frequence' => $data['frequence'],
            'date_debut' => $data['date_debut'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->jsonResponse(['id' => $id, 'message' => 'Contrat créé'], 201);
    }

    public function updateContrat($id)
    {
        Auth::requireAuth();
        $data = $this->getJsonInput();
        $model = new Contrat();
        
        if (!$model->getById((int)$id)) {
            $this->jsonResponse(['error' => 'Contrat non trouvé'], 404);
        }

        $model->update((int)$id, [
            'nom' => $data['nom'],
            'fournisseur' => $data['fournisseur'] ?? null,
            'categorie_id' => $data['categorie_id'] ?? null,
            'frequence' => $data['frequence'],
            'date_debut' => $data['date_debut'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->jsonResponse(['message' => 'Contrat mis à jour']);
    }

    public function deleteContrat($id)
    {
        Auth::requireAuth();
        $model = new Contrat();
        $model->delete((int)$id);
        $this->jsonResponse(['message' => 'Contrat supprimé (désactivé)']);
    }

    // ===== ECHEANCES =====

    public function listEcheances()
    {
        Auth::requireAuth();
        $model = new Echeance();
        
        // Filtres optionnels
        $filters = [];
        if (isset($_GET['contrat_id'])) $filters['contrat_id'] = (int)$_GET['contrat_id'];
        if (isset($_GET['mois'])) $filters['mois'] = $_GET['mois'];
        
        $echeances = $model->getAll($filters);
        $this->jsonResponse($echeances);
    }

    public function createEcheance()
    {
        Auth::requireAuth();
        $data = $this->getJsonInput();
        
        if (empty($data['contrat_id']) || empty($data['date_echeance']) || !isset($data['montant'])) {
            $this->jsonResponse(['error' => 'Champs obligatoires manquants'], 400);
        }

        $model = new Echeance();
        $id = $model->create([
            'contrat_id' => (int)$data['contrat_id'],
            'date_echeance' => $data['date_echeance'],
            'montant' => (float)$data['montant'],
            'statut' => $data['statut'] ?? 'prevu',
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        $this->jsonResponse(['id' => $id, 'message' => 'Échéance créée'], 201);
    }

    public function updateEcheance($id)
    {
        Auth::requireAuth();
        $data = $this->getJsonInput();
        $model = new Echeance();

        $model->update((int)$id, [
            'date_echeance' => $data['date_echeance'],
            'montant' => (float)$data['montant'],
            'statut' => $data['statut'] ?? 'prevu',
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        $this->jsonResponse(['message' => 'Échéance mise à jour']);
    }

    public function deleteEcheance($id)
    {
        Auth::requireAuth();
        $model = new Echeance();
        $model->delete((int)$id);
        $this->jsonResponse(['message' => 'Échéance supprimée']);
    }

    // ===== CATEGORIES =====

    public function listCategories()
    {
        Auth::requireAuth();
        $model = new Category();
        $this->jsonResponse($model->getAll());
    }

    // ===== SYNC (Simple implementation) =====
    
    /**
     * Endpoint pour récupérer toutes les données nécessaires à la PWA en une fois
     */
    public function sync()
    {
        Auth::requireAuth();
        
        $contratModel = new Contrat();
        $echeanceModel = new Echeance();
        $categoryModel = new Category();

        $data = [
            'contrats' => $contratModel->getAll(false),
            'categories' => $categoryModel->getAll(),
            // Pour les échéances, on peut limiter pour éviter de tout charger (ex: 2 dernières années)
            'echeances' => $echeanceModel->getAll(), 
            'sync_timestamp' => time()
        ];

        $this->jsonResponse($data);
    }
}
