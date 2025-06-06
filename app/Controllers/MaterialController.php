<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;
use App\Models\MaterialModel;

class MaterialController
{
    private $materialModel;

    public function __construct()
    {
        $this->materialModel = new MaterialModel();
    }

    public function index()
    {
        try {
            // Paramètres de recherche et filtres
            $search = $_GET['search'] ?? '';
            $filters = [
                'status' => $_GET['status'] ?? '',
                'price_min' => $_GET['price_min'] ?? '',
                'price_max' => $_GET['price_max'] ?? ''
            ];

            // Récupérer les matériels
            if ($search || array_filter($filters)) {
                $materials = $this->materialModel->search($search, $filters);
            } else {
                $materials = $this->materialModel->findAll(false); // Inclure les inactifs
            }

            // Ajouter les statistiques d'usage pour chaque matériel
            foreach ($materials as &$material) {
                $material['usage_stats'] = $this->materialModel->getUsageStats($material['id']);
            }

            // Statistiques générales
            $stats = [
                'total_materials' => Database::fetch("SELECT COUNT(*) as count FROM modeles_materiel")['count'] ?? 0,
                'active_materials' => Database::fetch("SELECT COUNT(*) as count FROM modeles_materiel WHERE actif = 1")['count'] ?? 0,
                'rented_count' => Database::fetch("SELECT COUNT(*) as count FROM materiel_loue WHERE statut = 'loue'")['count'] ?? 0,
                'total_revenue' => Database::fetch("
                    SELECT COALESCE(SUM(fa.prix_base), 0) as revenue
                    FROM formules_abonnement fa
                    WHERE fa.modele_materiel_id IS NOT NULL AND fa.actif = 1
                ")['revenue'] ?? 0
            ];

            $pageTitle = 'Gestion du matériel';
            require_once 'app/Views/materials/index.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du matériel", [
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du matériel');
            
            // Données par défaut en cas d'erreur
            $materials = [];
            $stats = [
                'total_materials' => 0,
                'active_materials' => 0,
                'rented_count' => 0,
                'total_revenue' => 0
            ];
            
            $pageTitle = 'Gestion du matériel';
            require_once 'app/Views/materials/index.php';
        }
    }

    public function create()
    {
        $pageTitle = 'Ajouter un matériel';
        require_once 'app/Views/materials/create.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /materials/create');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /materials/create');
            exit;
        }

        // Validation des données
        $errors = $this->materialModel->validateData($_POST);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            header('Location: /materials/create');
            exit;
        }

        try {
            // Préparer les données
            $data = [
                'nom' => trim($_POST['nom']),
                'description' => trim($_POST['description'] ?? ''),
                'prix_mensuel' => (float)$_POST['prix_mensuel'],
                'depot_garantie' => (float)$_POST['depot_garantie'],
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];

            // Créer le matériel
            $materialId = $this->materialModel->create($data);

            Logger::info('Nouveau matériel créé', [
                'material_id' => $materialId,
                'name' => $data['nom']
            ]);

            Session::setFlash('success', 'Matériel ajouté avec succès');
            header('Location: /materials');
            exit;

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la création du matériel", [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            Session::setFlash('error', 'Erreur lors de la création du matériel');
            header('Location: /materials/create');
            exit;
        }
    }

    public function show($id)
    {
        try {
            $material = $this->materialModel->findById($id);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Statistiques d'usage
            $usage_stats = $this->materialModel->getUsageStats($id);

            // Formules utilisant ce matériel
            $plans = Database::fetchAll("
                SELECT id, nom, type_abonnement, prix_base, actif
                FROM formules_abonnement
                WHERE modele_materiel_id = ?
                ORDER BY actif DESC, nom
            ", [$id]);

            // Locations actuelles
            $rentals = Database::fetchAll("
                SELECT ml.*, c.raison_sociale, ac.statut as abonnement_statut
                FROM materiel_loue ml
                JOIN clients c ON ml.client_id = c.id
                JOIN abonnements_clients ac ON ml.abonnement_id = ac.id
                WHERE ml.modele_materiel_id = ? AND ml.statut = 'loue'
                ORDER BY ml.date_location DESC
            ", [$id]);

            // Historique des locations (dernières 10)
            $rental_history = Database::fetchAll("
                SELECT ml.*, c.raison_sociale
                FROM materiel_loue ml
                JOIN clients c ON ml.client_id = c.id
                WHERE ml.modele_materiel_id = ?
                ORDER BY ml.date_creation DESC
                LIMIT 10
            ", [$id]);

            $pageTitle = 'Détails du matériel - ' . $material['nom'];
            require_once 'app/Views/materials/show.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du matériel", [
                'material_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du matériel');
            header('Location: /materials');
            exit;
        }
    }

    public function edit($id)
    {
        try {
            $material = $this->materialModel->findById($id);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            $pageTitle = 'Modifier le matériel - ' . $material['nom'];
            require_once 'app/Views/materials/edit.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du matériel", [
                'material_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du matériel');
            header('Location: /materials');
            exit;
        }
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /materials/' . $id . '/edit');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /materials/' . $id . '/edit');
            exit;
        }

        // Vérifier que le matériel existe
        $material = $this->materialModel->findById($id);
        if (!$material) {
            Session::setFlash('error', 'Matériel non trouvé');
            header('Location: /materials');
            exit;
        }

        // Validation des données
        $errors = $this->materialModel->validateData($_POST, $id);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            header('Location: /materials/' . $id . '/edit');
            exit;
        }

        try {
            // Préparer les données
            $data = [
                'nom' => trim($_POST['nom']),
                'description' => trim($_POST['description'] ?? ''),
                'prix_mensuel' => (float)$_POST['prix_mensuel'],
                'depot_garantie' => (float)$_POST['depot_garantie'],
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];

            // Mettre à jour le matériel
            $this->materialModel->update($id, $data);

            Logger::info('Matériel modifié', [
                'material_id' => $id,
                'name' => $data['nom'],
                'changes' => array_diff_assoc($data, $material)
            ]);

            Session::setFlash('success', 'Matériel modifié avec succès');
            header('Location: /materials');
            exit;

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la modification du matériel", [
                'material_id' => $id,
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            Session::setFlash('error', 'Erreur lors de la modification du matériel');
            header('Location: /materials/' . $id . '/edit');
            exit;
        }
    }

    public function toggleStatus($id)
    {
        try {
            $material = $this->materialModel->findById($id);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Vérifier si le matériel peut être désactivé
            if ($material['actif'] && $this->materialModel->isUsedInPlans($id)) {
                Session::setFlash('error', 'Impossible de désactiver un matériel utilisé dans des formules actives');
                header('Location: /materials');
                exit;
            }

            $this->materialModel->toggleStatus($id);

            $statusText = $material['actif'] ? 'désactivé' : 'activé';
            Session::setFlash('success', "Matériel {$statusText} avec succès");

            Logger::info('Statut de matériel modifié', [
                'material_id' => $id,
                'old_status' => $material['actif'],
                'new_status' => !$material['actif']
            ]);

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la modification du statut", [
                'material_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la modification du statut');
        }

        header('Location: /materials');
        exit;
    }

    public function delete($id)
    {
        try {
            $material = $this->materialModel->findById($id);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Vérifier que le matériel peut être supprimé
            if ($this->materialModel->isUsedInPlans($id)) {
                Session::setFlash('error', 'Impossible de supprimer un matériel utilisé dans des formules d\'abonnement');
                header('Location: /materials');
                exit;
            }

            if ($this->materialModel->isUsedInRentals($id)) {
                Session::setFlash('error', 'Impossible de supprimer un matériel actuellement loué');
                header('Location: /materials');
                exit;
            }

            // Supprimer le matériel
            $this->materialModel->delete($id);

            Logger::info('Matériel supprimé', [
                'material_id' => $id,
                'material_name' => $material['nom']
            ]);

            Session::setFlash('success', 'Matériel supprimé avec succès');

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la suppression du matériel", [
                'material_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la suppression du matériel');
        }

        header('Location: /materials');
        exit;
    }

    public function rentals()
    {
        try {
            // Récupérer toutes les locations
            $rentals = Database::fetchAll("
                SELECT ml.*, 
                       c.raison_sociale, c.email_facturation,
                       mm.nom as materiel_nom, mm.prix_mensuel,
                       ac.statut as abonnement_statut,
                       fa.nom as formule_nom
                FROM materiel_loue ml
                JOIN clients c ON ml.client_id = c.id
                JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
                LEFT JOIN abonnements_clients ac ON ml.abonnement_id = ac.id
                LEFT JOIN formules_abonnement fa ON ac.formule_id = fa.id
                ORDER BY ml.date_creation DESC
            ");

            // Statistiques des locations
            $rental_stats = [
                'total_rentals' => count($rentals),
                'active_rentals' => array_reduce($rentals, function($count, $rental) {
                    return $count + ($rental['statut'] === 'loue' ? 1 : 0);
                }, 0),
                'maintenance_count' => array_reduce($rentals, function($count, $rental) {
                    return $count + ($rental['statut'] === 'maintenance' ? 1 : 0);
                }, 0),
                'returned_count' => array_reduce($rentals, function($count, $rental) {
                    return $count + ($rental['statut'] === 'retourne' ? 1 : 0);
                }, 0)
            ];

            $pageTitle = 'Locations de matériel';
            require_once 'app/Views/materials/rentals.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération des locations", [
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement des locations');
            
            $rentals = [];
            $rental_stats = [
                'total_rentals' => 0,
                'active_rentals' => 0,
                'maintenance_count' => 0,
                'returned_count' => 0
            ];
            
            $pageTitle = 'Locations de matériel';
            require_once 'app/Views/materials/rentals.php';
        }
    }

    public function createRental()
    {
        try {
            // Récupérer les clients actifs
            $clients = Database::fetchAll("
                SELECT id, raison_sociale, email_facturation
                FROM clients
                WHERE actif = 1
                ORDER BY raison_sociale
            ");

            // Récupérer les matériels disponibles
            $materials = Database::fetchAll("
                SELECT id, nom, prix_mensuel, depot_garantie
                FROM modeles_materiel
                WHERE actif = 1
                ORDER BY nom
            ");

            $pageTitle = 'Nouvelle location de matériel';
            require_once 'app/Views/materials/create-rental.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors du chargement du formulaire de location", [
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du formulaire');
            header('Location: /materials/rentals');
            exit;
        }
    }

    public function storeRental()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /materials/rentals/create');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /materials/rentals/create');
            exit;
        }

        try {
            // Validation des données
            $errors = [];

            if (empty($_POST['client_id'])) {
                $errors[] = 'Le client est obligatoire';
            }

            if (empty($_POST['modele_materiel_id'])) {
                $errors[] = 'Le modèle de matériel est obligatoire';
            }

            if (empty($_POST['date_location'])) {
                $errors[] = 'La date de location est obligatoire';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    Session::setFlash('error', $error);
                }
                header('Location: /materials/rentals/create');
                exit;
            }

            // Préparer les données
            $data = [
                'client_id' => (int)$_POST['client_id'],
                'modele_materiel_id' => (int)$_POST['modele_materiel_id'],
                'numero_serie' => trim($_POST['numero_serie'] ?? ''),
                'inclus_dans_abonnement' => isset($_POST['inclus_dans_abonnement']) ? 1 : 0,
                'date_location' => $_POST['date_location'],
                'date_retour_prevue' => $_POST['date_retour_prevue'] ?? null,
                'depot_verse' => (float)($_POST['depot_verse'] ?? 0),
                'statut' => 'loue',
                'abonnement_id' => !empty($_POST['abonnement_id']) ? (int)$_POST['abonnement_id'] : null
            ];

            // Créer la location
            $rentalId = Database::insert('materiel_loue', $data);

            Logger::info('Nouvelle location créée', [
                'rental_id' => $rentalId,
                'client_id' => $data['client_id'],
                'material_id' => $data['modele_materiel_id']
            ]);

            Session::setFlash('success', 'Location créée avec succès');
            header('Location: /materials/rentals');
            exit;

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la création de la location", [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            Session::setFlash('error', 'Erreur lors de la création de la location');
            header('Location: /materials/rentals/create');
            exit;
        }
    }

    public function updateRental($rentalId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /materials/rentals');
            exit;
        }

        try {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'return':
                    $this->returnRental($rentalId);
                    break;
                    
                case 'maintenance':
                    $this->setMaintenanceRental($rentalId);
                    break;
                    
                case 'reactivate':
                    $this->reactivateRental($rentalId);
                    break;
                    
                default:
                    Session::setFlash('error', 'Action non valide');
            }

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la mise à jour de la location", [
                'rental_id' => $rentalId,
                'action' => $_POST['action'] ?? '',
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la mise à jour de la location');
        }

        header('Location: /materials/rentals');
        exit;
    }

    private function returnRental($rentalId)
    {
        $rental = Database::fetch("SELECT * FROM materiel_loue WHERE id = ?", [$rentalId]);
        if (!$rental) {
            throw new \Exception('Location non trouvée');
        }

        $updateData = [
            'statut' => 'retourne',
            'date_retour_effective' => date('Y-m-d'),
            'depot_rembourse' => 1
        ];

        Database::update('materiel_loue', $updateData, 'id = ?', [$rentalId]);

        Logger::info('Matériel retourné', [
            'rental_id' => $rentalId,
            'client_id' => $rental['client_id']
        ]);

        Session::setFlash('success', 'Matériel marqué comme retourné');
    }

    private function setMaintenanceRental($rentalId)
    {
        Database::update('materiel_loue', ['statut' => 'maintenance'], 'id = ?', [$rentalId]);
        Session::setFlash('success', 'Matériel mis en maintenance');
    }

    private function reactivateRental($rentalId)
    {
        Database::update('materiel_loue', ['statut' => 'loue'], 'id = ?', [$rentalId]);
        Session::setFlash('success', 'Location réactivée');
    }
}
?>