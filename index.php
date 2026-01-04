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
    
    $isBlocked = Auth::isBlocked();
    $blockedTime = Auth::getBlockedTimeRemaining();
    
    View::display('login.html.twig', [
        'error' => $_SESSION['login_error'] ?? null,
        'is_blocked' => $isBlocked,
        'blocked_time' => $blockedTime,
        'attempts' => $_SESSION['login_attempts'] ?? 0
    ]);
    unset($_SESSION['login_error']);
});

$router->post('/login', function () {
    $code = $_POST['password'] ?? '';
    
    if (Auth::isBlocked()) {
        $_SESSION['login_error'] = 'Trop de tentatives. Veuillez patienter.';
        Router::redirect('/login');
        return;
    }

    if (Auth::loginWithCode($code)) {
        Router::redirect('/');
    } else {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        if ($attempts >= 3) {
            $_SESSION['login_error'] = 'Compte bloqué après 3 tentatives erronées.';
        } else {
            $_SESSION['login_error'] = 'Code incorrect. Tentative ' . $attempts . '/3';
        }
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

// ===== BANQUES & COMPTES =====
$router->get('/banques', function () {
    Auth::requireAuth();
    $banqueModel = new App\Model\Banque();
    $compteModel = new App\Model\Compte();
    
    // Organiser les comptes par banque
    $banques = $banqueModel->getAll();
    $banquesAvecComptes = [];
    
    foreach ($banques as $banque) {
        $banque['comptes'] = $compteModel->getAll($banque['id']);
        $banquesAvecComptes[] = $banque;
    }
    
    View::display('banques/index.html.twig', [
        'current_page' => 'banques',
        'banques' => $banquesAvecComptes,
        'flash' => View::getFlash(),
    ]);
});

// Créer une banque
$router->get('/banques/create', function () {
    Auth::requireAuth();
    View::display('banques/form.html.twig', [
        'current_page' => 'banques',
        'banque' => null
    ]);
});

$router->post('/banques/create', function () {
    $banqueModel = new App\Model\Banque();
    $banqueModel->create([
        'nom' => $_POST['nom'],
        'couleur' => $_POST['couleur'],
        'logo_url' => $_POST['logo_url'] ?? null
    ]);
    View::flash('success', 'Banque ajoutée');
    Router::redirect('/banques');
});

$router->get('/banques/{id}/edit', function ($id) {
    Auth::requireAuth();
    $banqueModel = new App\Model\Banque();
    $banque = $banqueModel->getById((int)$id);
    
    if (!$banque) Router::redirect('/banques');
    
    View::display('banques/form.html.twig', [
        'current_page' => 'banques',
        'banque' => $banque
    ]);
});

$router->post('/banques/{id}/edit', function ($id) {
    $banqueModel = new App\Model\Banque();
    $banqueModel->update((int)$id, [
        'nom' => $_POST['nom'],
        'couleur' => $_POST['couleur'],
        'logo_url' => $_POST['logo_url'] ?? null
    ]);
    View::flash('success', 'Banque modifiée');
    Router::redirect('/banques');
});

// Créer un compte
$router->get('/comptes/create', function () {
    Auth::requireAuth();
    $banqueModel = new App\Model\Banque();
    $banqueId = $_GET['banque_id'] ?? null;
    
    View::display('comptes/form.html.twig', [
        'current_page' => 'banques',
        'compte' => null,
        'banques' => $banqueModel->getAll(),
        'banque_id' => $banqueId
    ]);
});

$router->post('/comptes/create', function () {
    $compteModel = new App\Model\Compte();
    $compteModel->create([
        'banque_id' => (int)$_POST['banque_id'],
        'nom' => $_POST['nom'],
        'numero' => $_POST['numero'],
        'solde_initial' => (float)$_POST['solde_initial']
    ]);
    View::flash('success', 'Compte ajouté');
    Router::redirect('/banques');
});

// Voir un compte (Transactions)
$router->get('/comptes/{id}/edit', function ($id) {
    Auth::requireAuth();
    $banqueModel = new App\Model\Banque();
    $compteModel = new App\Model\Compte();
    $compte = $compteModel->getById((int)$id);
    
    if (!$compte) {
        Router::redirect('/banques');
    }
    
    $banques = $banqueModel->getAll();
    View::display('comptes/form.html.twig', [
        'banques' => $banques,
        'compte' => $compte
    ]);
});

$router->post('/comptes/{id}/edit', function ($id) {
    Auth::requireAuth();
    $data = [
        'banque_id' => $_POST['banque_id'],
        'nom' => $_POST['nom'],
        'numero' => $_POST['numero'],
        'solde_initial' => (float)$_POST['solde_initial']
    ];

    $compteModel = new App\Model\Compte();
    $compteModel->update((int)$id, $data);
    
    View::flash('success', 'Compte mis à jour avec succès');
    Router::redirect('/banques');
});

// Supprimer une transaction
$router->post('/transactions/{id}/delete', function ($id) {
    Auth::requireAuth();
    $transactionModel = new App\Model\Transaction();
    $transactionModel->delete((int)$id);
    
    // Si c'est une requête AJAX (depuis AlpineJS), renvoyer JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        return;
    }
    
    // Sinon redirection standard (fallback)
    View::flash('success', 'Transaction supprimée');
    // On ne sait pas facilement vers quel compte rediriger ici sans faire une requête de plus, 
    // mais ce cas ne devrait pas arriver avec l'interface actuelle.
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        Router::redirect('/banques');
    }
});

$router->get('/comptes/{id}', function ($id) {
    Auth::requireAuth();
    $compteModel = new App\Model\Compte();
    $transactionModel = new App\Model\Transaction();
    $compte = $compteModel->getById((int)$id);
    
    if (!$compte) Router::redirect('/banques');
    
    $compte['solde_actuel'] = $compteModel->getSoldeActuel((int)$id);
    
    View::display('banques/show.html.twig', [
        'current_page' => 'banques',
        'compte' => $compte,
        'transactions' => $transactionModel->getAll((int)$id),
        'flash' => View::getFlash(),
    ]);
});

// Import transactions
$router->get('/comptes/{id}/import', function ($id) {
    Auth::requireAuth();
    $compteModel = new App\Model\Compte();
    $compte = $compteModel->getById((int)$id);
    
    if (!$compte) Router::redirect('/banques');
    
    View::display('transactions/import.html.twig', [
        'current_page' => 'banques',
        'compte' => $compte
    ]);
});

$router->post('/comptes/{id}/import', function ($id) {
    ini_set('display_errors', 1);
    ini_set('max_execution_time', 300); // 5 minutes max
    error_reporting(E_ALL);

    try {
        // Format attendu: dateOp;dateVal;label;category;categoryParent;supplierFound;amount;comment;...
        // Ou format simple: Date;Description;Montant
        
        $content = $_POST['content'] ?? '';
        // Utiliser preg_split pour gérer tous les types de retours à la ligne (\n, \r\n, \r)
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $transactionModel = new App\Model\Transaction();
        
        $stats = [
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'lines_processed' => 0
        ];
        $duplicateList = [];
    
        $forceImport = isset($_POST['force_import']) && $_POST['force_import'] == '1';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Ignorer l'en-tête du CSV d'export
        if (stripos($line, 'dateOp') === 0 || stripos($line, 'Date;') === 0) continue;
        
        $stats['lines_processed']++;
        $parts = str_getcsv($line, ';');
        $data = [];
        
        try {
            if (count($parts) >= 7) {
                // Format Export: dateOp(0);dateVal(1);label(2);category(3);categoryParent(4);supplierFound(5);amount(6);comment(7);accountNum(8);accountLabel(9);accountbalance(10)
                $date = trim($parts[0]);
                $desc = trim($parts[2]);
                $amountStr = trim($parts[6]);
                
                // Nettoyage montant (espaces insécables, virgules)
                $montant = (float)str_replace([' ', "\xC2\xA0", "\xA0", ','], ['', '', '', '.'], $amountStr);
                
                // Format date
                if (strpos($date, '/') !== false) {
                    $d = \DateTime::createFromFormat('d/m/Y', $date);
                    if ($d) $date = $d->format('Y-m-d');
                }

                // Construction de la signature unique basée sur TOUS les champs pertinents
                // On inclut dateVal et accountbalance qui permettent souvent de différencier des doublons
                $signatureParts = [
                   $date, // dateOp
                   trim($parts[1] ?? ''), // dateVal
                   $desc, // label
                   $montant, // amount
                   trim($parts[7] ?? ''), // comment
                   trim($parts[10] ?? '') // accountbalance
                ];
                $signature = implode('|', $signatureParts);
                
                $data = [
                    'compte_id' => (int)$id,
                    'date_transaction' => $date,
                    'description' => $desc,
                    'montant' => $montant,
                    'categorie_id' => null,
                    'import_signature' => $signature
                ];
                
            } elseif (count($parts) >= 3) {
                // Format Simple
                $date = trim($parts[0]);
                $desc = trim($parts[1]);
                $amountStr = trim($parts[2]);
                $montant = (float)str_replace([' ', "\xC2\xA0", "\xA0", ','], ['', '', '', '.'], $amountStr);
                
                if (strpos($date, '/') !== false) {
                    $d = \DateTime::createFromFormat('d/m/Y', $date);
                    if ($d) $date = $d->format('Y-m-d');
                }
                
                // Signature simple
                $signature = $date . '|' . $desc . '|' . $montant;

                $data = [
                    'compte_id' => (int)$id,
                    'date_transaction' => $date,
                    'description' => $desc,
                    'montant' => $montant,
                    'categorie_id' => null,
                    'import_signature' => $signature
                ];
            }
        
            
            if (!empty($data)) {
                // Si force_import est actif, on rend la signature unique artificiellement
                if ($forceImport) {
                    $data['import_signature'] .= '|forced_' . uniqid();
                }

                $res = $transactionModel->create($data);
                if ($res > 0) {
                    $stats['imported']++;
                } else {
                    $stats['duplicates']++;
                    // Stocker les détails du doublon
                    $duplicateList[] = [
                        'date' => $data['date_transaction'],
                        'description' => $data['description'],
                        'montant' => $data['montant']
                    ];
                }
            } else {
                $stats['errors']++; // Format non reconnu
            }
        } catch (\Exception $e) {
            $stats['errors']++;
            error_log("Import error line: $line - " . $e->getMessage());
        }
    }
    
    // Afficher la page de résultat
    View::display('transactions/result.html.twig', [
        'stats' => $stats,
        'duplicates' => $duplicateList,
        'compte_id' => $id
    ]);
    
    } catch (\Throwable $e) {
        // En cas de crash PHP (erreur fatale)
        echo "<div style='background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 2rem; margin: 2rem; border-radius: 0.5rem;'>";
        echo "<h2 style='font-weight: bold; font-size: 1.25rem; margin-bottom: 1rem;'>Erreur critique lors de l'import</h2>";
        echo "<p>Une erreur inattendue est survenue :</p>";
        echo "<pre style='background: white; padding: 1rem; border-radius: 0.25rem; margin-top: 0.5rem;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<p style='margin-top: 1rem;'>Ligne : " . $e->getLine() . " dans " . $e->getFile() . "</p>";
        echo "</div>";
        die();
    }
});

// API Transactions (recherche et lazy loading)
$router->get('/api/comptes/{id}/transactions', function ($id) {
    Auth::requireAuth();
    $transactionModel = new App\Model\Transaction();
    
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $transactions = $transactionModel->search((int)$id, $query, $limit, $offset);
    $totals = $transactionModel->getTotals((int)$id, $query);
    
    header('Content-Type: application/json');
    echo json_encode([
        'transactions' => $transactions,
        'totals' => $totals
    ]);
});

// Dispatch
$router->dispatch();
