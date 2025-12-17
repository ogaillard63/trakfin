<?php
/**
 * TrakFin - Point d'entrée principal
 */

require_once __DIR__ . '/config/config.php';

use App\Router;
use App\View;
use App\Auth;
use App\Model\Contrat;
use App\Model\Echeance;
use App\Model\Category;
use App\ApiController;
use App\BackupManager;

$router = new Router(APP_URL);

// ===== API REST =====
$api = new ApiController();

$router->get('/api', [$api, 'index']);
$router->get('/api/sync', [$api, 'sync']);

// Contrats
$router->get('/api/contrats', [$api, 'listContrats']);
$router->post('/api/contrats', [$api, 'createContrat']);
$router->get('/api/contrats/{id}', [$api, 'getContrat']);
$router->put('/api/contrats/{id}', [$api, 'updateContrat']);
$router->delete('/api/contrats/{id}', [$api, 'deleteContrat']);

// Échéances
$router->get('/api/echeances', [$api, 'listEcheances']);
$router->post('/api/echeances', [$api, 'createEcheance']);
$router->put('/api/echeances/{id}', [$api, 'updateEcheance']);
$router->delete('/api/echeances/{id}', [$api, 'deleteEcheance']);

// Catégories
$router->get('/api/categories', [$api, 'listCategories']);

// ===== AUTHENTIFICATION =====
$router->get('/login', function () {
    // Si déjà connecté, rediriger vers le dashboard
    if (Auth::check()) {
        Router::redirect('/');
    }
    
    View::display('login.html.twig', [
        'error' => $_SESSION['login_error'] ?? null,
    ]);
    unset($_SESSION['login_error']);
});

$router->post('/login', function () {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (Auth::login($username, $password)) {
        Router::redirect('/');
    } else {
        $_SESSION['login_error'] = 'Identifiants incorrects';
        Router::redirect('/login');
    }
});

$router->get('/logout', function () {
    Auth::logout();
    Router::redirect('/login');
});

// ===== PAGES =====
$router->get('/about', function () {
    Auth::requireAuth();
    View::display('about.html.twig', [
        'current_page' => 'about'
    ]);
});

// ===== DASHBOARD =====
$router->get('/', function () {
    Auth::requireAuth(); // Protection
    
    $echeanceModel = new Echeance();
    $contratModel = new Contrat();
    
    $year = (int)($_GET['year'] ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('m'));
    
    // Générer automatiquement les échéances manquantes jusqu'au mois affiché
    // Générer automatiquement les échéances manquantes jusqu'au mois affiché
    // $echeanceModel->genererEcheancesManquantes($year, $month); // DÉSACTIVÉ : Gestion virtuelle
    
    View::display('dashboard.html.twig', [
        'current_page' => 'dashboard',
        'year' => $year,
        'month' => $month,
        'echeances_mois' => $echeanceModel->getEcheancesDuMois($year, $month),
        'total_mois' => $echeanceModel->getTotalMois($year, $month),
        'contrats_augmentation' => $contratModel->getContratsAvecAugmentation(),
        'projection_annuelle' => $echeanceModel->getProjectionAnnuelle($year),
        'flash' => View::getFlash(),
    ]);
});

// ===== CONTRATS =====
$router->get('/contrats', function () {
    Auth::requireAuth();
    $contratModel = new Contrat();
    
    View::display('contrats/index.html.twig', [
        'current_page' => 'contrats',
        'contrats' => $contratModel->getAll(),
        'flash' => View::getFlash(),
    ]);
});

$router->get('/contrats/create', function () {
    Auth::requireAuth();
    $categoryModel = new Category();
    
    View::display('contrats/form.html.twig', [
        'current_page' => 'contrats',
        'contrat' => null,
        'categories' => $categoryModel->getAll(),
    ]);
});

$router->post('/contrats/create', function () {
    $contratModel = new Contrat();
    
    $id = $contratModel->create([
        'nom' => $_POST['nom'],
        'fournisseur' => $_POST['fournisseur'] ?? null,
        'categorie_id' => $_POST['categorie_id'] ?? null,
        'frequence' => $_POST['frequence'],
        'date_debut' => $_POST['date_debut'],
        'notes' => $_POST['notes'] ?? null,
    ]);
    
    View::flash('success', 'Contrat créé avec succès');
    Router::redirect('/contrats/' . $id);
});

$router->get('/contrats/{id}', function ($id) {
    Auth::requireAuth();
    $contratModel = new Contrat();
    $echeanceModel = new Echeance();
    
    $contrat = $contratModel->getById((int) $id);
    if (!$contrat) {
        Router::redirect('/contrats');
    }
    
    View::display('contrats/show.html.twig', [
        'current_page' => 'contrats',
        'contrat' => $contrat,
        'echeances' => $echeanceModel->getAll(['contrat_id' => (int) $id]),
        'evolution' => $echeanceModel->getEvolution((int) $id),
        'statistiques' => $echeanceModel->getStatistiquesContrat((int) $id),
        'flash' => View::getFlash(),
    ]);

});

$router->get('/contrats/{id}/edit', function ($id) {
    Auth::requireAuth();
    $contratModel = new Contrat();
    $categoryModel = new Category();
    
    $contrat = $contratModel->getById((int) $id);
    if (!$contrat) {
        Router::redirect('/contrats');
    }
    
    View::display('contrats/form.html.twig', [
        'current_page' => 'contrats',
        'contrat' => $contrat,
        'categories' => $categoryModel->getAll(),
    ]);
});

$router->post('/contrats/{id}/edit', function ($id) {
    $contratModel = new Contrat();
    
    $contratModel->update((int) $id, [
        'nom' => $_POST['nom'],
        'fournisseur' => $_POST['fournisseur'] ?? null,
        'categorie_id' => $_POST['categorie_id'] ?? null,
        'frequence' => $_POST['frequence'],
        'date_debut' => $_POST['date_debut'],
        'notes' => $_POST['notes'] ?? null,
    ]);
    
    View::flash('success', 'Contrat modifié avec succès');
    Router::redirect('/contrats/' . $id);
});

$router->post('/contrats/{id}/delete', function ($id) {
    $contratModel = new Contrat();
    $contratModel->delete((int) $id);
    
    View::flash('success', 'Contrat supprimé');
    Router::redirect('/contrats');
});

$router->post('/contrats/{id}/generer', function ($id) {
    $echeanceModel = new Echeance();
    $contratModel = new Contrat();
    
    $contrat = $contratModel->getById((int) $id);
    $nombre = $contrat['frequence'] === 'mensuel' ? 12 : 1;
    $count = $echeanceModel->genererEcheances((int) $id, $nombre);
    
    View::flash('success', "$count échéances générées");
    Router::redirect('/contrats/' . $id);
});

// ===== ÉCHÉANCES =====
$router->get('/echeances/create', function () {
    $contratModel = new Contrat();
    $echeanceModel = new Echeance();
    
    $contratId = $_GET['contrat_id'] ?? null;
    $dateSuggeree = null;
    $montantSuggere = null;
    
    // Si un contrat est spécifié, calculer la prochaine date
    if ($contratId) {
        $contrat = $contratModel->getById((int)$contratId);
        $dernieresEcheances = $echeanceModel->getAll(['contrat_id' => (int)$contratId]);
        
        if ($contrat && !empty($dernieresEcheances)) {
            // Prendre la dernière échéance (la plus récente car ORDER BY DESC)
            $derniereEcheance = $dernieresEcheances[0];
            $derniereDate = new \DateTime($derniereEcheance['date_echeance']);
            $montantSuggere = $derniereEcheance['montant'];
            
            // Calculer la prochaine date selon la fréquence
            if ($contrat['frequence'] === 'mensuel') {
                $derniereDate->add(new \DateInterval('P1M'));
            } else {
                $derniereDate->add(new \DateInterval('P1Y'));
            }
            
            $dateSuggeree = $derniereDate->format('Y-m-d');
        } elseif ($contrat) {
            // Si aucune échéance, utiliser la date de début du contrat
            $dateSuggeree = $contrat['date_debut'];
        }
    }
    
    View::display('echeances/form.html.twig', [
        'current_page' => 'echeances',
        'echeance' => null,
        'contrats' => $contratModel->getAll(),
        'contrat_id' => $contratId,
        'date_suggeree' => $dateSuggeree,
        'montant_suggere' => $montantSuggere,
    ]);
});

$router->post('/echeances/create', function () {
    $echeanceModel = new Echeance();
    
    $montant = str_replace([' ', ','], ['', '.'], $_POST['montant']);
    
    $echeanceModel->create([
        'contrat_id' => (int) $_POST['contrat_id'],
        'date_echeance' => $_POST['date_echeance'],
        'montant' => (float) $montant,
        'statut' => $_POST['statut'] ?? 'prevu',
        'commentaire' => $_POST['commentaire'] ?? null,
    ]);
    
    View::flash('success', 'Échéance créée');
    
    if (!empty($_POST['contrat_id'])) {
        Router::redirect('/contrats/' . $_POST['contrat_id']);
    } else {
        Router::redirect('/');
    }
});

$router->get('/echeances/{id}/edit', function ($id) {
    $echeanceModel = new Echeance();
    $contratModel = new Contrat();
    
    $echeance = null;
    $isVirtual = strpos($id, 'virtual_') === 0;

    if ($isVirtual) {
        // ID format: virtual_{contrat_id}_{YYYYMMDD}
        $parts = explode('_', $id);
        $contratId = (int)$parts[1];
        $dateStr = $parts[2];
        $dateFormatted = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
        
        $contrat = $contratModel->getById($contratId);
        if ($contrat) {
            $echeance = [
                'id' => $id,
                'contrat_id' => $contratId,
                'date_echeance' => $dateFormatted,
                'montant' => $contrat['dernier_montant'] ?? 0,
                'statut' => 'prevu',
                'commentaire' => 'Échéance provisionnelle'
            ];
        }
    } else {
        $echeance = $echeanceModel->getById((int) $id);
    }

    if (!$echeance) {
        Router::redirect('/');
    }
    
    View::display('echeances/form.html.twig', [
        'current_page' => 'echeances',
        'echeance' => $echeance,
        'contrats' => $contratModel->getAll(),
    ]);
});

$router->post('/echeances/{id}/edit', function ($id) {
    $echeanceModel = new Echeance();
    
    $montant = str_replace([' ', ','], ['', '.'], $_POST['montant']);
    
    $montant = str_replace([' ', ','], ['', '.'], $_POST['montant']);
    $isVirtual = strpos($id, 'virtual_') === 0;

    if ($isVirtual) {
        $echeanceModel->create([
            'contrat_id' => (int) $_POST['contrat_id'],
            'date_echeance' => $_POST['date_echeance'],
            'montant' => (float) $montant,
            'statut' => $_POST['statut'] ?? 'prevu',
            'commentaire' => $_POST['commentaire'] ?? null,
        ]);
        View::flash('success', 'Échéance créée (validée)');
    } else {
        $echeanceModel->update((int) $id, [
            'date_echeance' => $_POST['date_echeance'],
            'montant' => (float) $montant,
            'statut' => $_POST['statut'] ?? 'prevu',
            'commentaire' => $_POST['commentaire'] ?? null,
        ]);
        View::flash('success', 'Échéance modifiée');
    }
    
    if (!empty($_POST['contrat_id'])) {
        Router::redirect('/contrats/' . $_POST['contrat_id']);
    } else {
        Router::redirect('/');
    }
});

$router->post('/echeances/{id}/payer', function ($id) {
    $echeanceModel = new Echeance();
    
    if (strpos($id, 'virtual_') === 0) {
        // C'est une échéance virtuelle, on la crée comme payée
        $parts = explode('_', $id);
        $contratId = (int)$parts[1];
        $dateStr = $parts[2];
        $dateFormatted = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
        
        // Récupérer montant suggéré
        $contratModel = new Contrat();
        $contrat = $contratModel->getById($contratId);
        $montant = $contrat['dernier_montant'] ?? 0;
        
        $echeanceModel->create([
            'contrat_id' => $contratId,
            'date_echeance' => $dateFormatted,
            'montant' => $montant,
            'statut' => 'paye'
        ]);
    } else {
        $echeanceModel->marquerPaye((int) $id);
    }
    
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    header('Location: ' . $referer);
    exit;
});

$router->post('/echeances/{id}/delete', function ($id) {
    if (strpos($id, 'virtual_') === 0) {
        // Impossible de supprimer une échéance virtuelle sans modifier le contrat
        View::flash('error', 'Impossible de supprimer une échéance prévisionnelle automatique.');
        Router::redirect('/');
        return;
    }
    
    $echeanceModel = new Echeance();
    $echeance = $echeanceModel->getById((int) $id);
    $echeanceModel->delete((int) $id);
    
    View::flash('success', 'Échéance supprimée');
    
    if ($echeance && !empty($echeance['contrat_id'])) {
        Router::redirect('/contrats/' . $echeance['contrat_id']);
    } else {
        Router::redirect('/');
    }
});

// ===== MAINTENANCE =====
$router->get('/maintenance', function () {
    Auth::requireAuth();
    $backupManager = new BackupManager();
    
    View::display('maintenance/index.html.twig', [
        'current_page' => 'maintenance',
        'backups' => $backupManager->getList(),
        'flash' => View::getFlash(),
    ]);
});

$router->post('/maintenance/backup', function () {
    Auth::requireAuth();
    $backupManager = new BackupManager();
    
    if ($filename = $backupManager->createBackup()) {
        View::flash('success', "Sauvegarde créée : $filename");
    } else {
        $error = $backupManager->getLastError();
        View::flash('error', "Erreur lors de la création de la sauvegarde : " . $error);
    }
    
    Router::redirect('/maintenance');
});

$router->post('/maintenance/restore', function () {
    Auth::requireAuth();
    $backupManager = new BackupManager();
    $filename = $_POST['filename'] ?? '';
    
    if ($backupManager->restoreBackup($filename)) {
        View::flash('success', "Base de données restaurée depuis $filename");
    } else {
        View::flash('error', "Erreur lors de la restauration");
    }
    
    Router::redirect('/maintenance');
});

// Dispatch
$router->dispatch();
