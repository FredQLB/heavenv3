<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;

class MaterialController
{
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

            // Construire la requête de base
            $query = "SELECT * FROM modeles_materiel";
            $params = [];
            $whereConditions = [];

            // Ajouter les filtres
            if (!empty($search)) {
                $whereConditions[] = "(nom LIKE ? OR description LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if ($filters['status'] !== '') {
                $whereConditions[] = "actif = ?";
                $params[] = (int)$filters['status'];
            }

            if (!empty($filters['price_min'])) {
                $whereConditions[] = "prix_mensuel >= ?";
                $params[] = (float)$filters['price_min'];
            }

            if (!empty($filters['price_max'])) {
                $whereConditions[] = "prix_mensuel <= ?";
                $params[] = (float)$filters['price_max'];
            }

            // Assembler la requête
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }
            $query .= " ORDER BY nom";

            // Exécuter la requête
            $materials = Database::fetchAll($query, $params);

            // Ajouter les statistiques d'usage pour chaque matériel
            foreach ($materials as &$material) {
                $material['usage_stats'] = $this->getUsageStats($material['id']);
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

            // Définir les variables globales pour la vue
            $GLOBALS['materials'] = $materials;
            $GLOBALS['stats'] = $stats;
            $GLOBALS['pageTitle'] = 'Gestion du matériel';

            // Debug : Logger les données
            Logger::info('MaterialController index - données chargées', [
                'materials_count' => count($materials),
                'stats' => $stats,
                'search' => $search,
                'filters' => $filters
            ]);

            // Charger la vue
            require_once 'app/Views/materials/index.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du matériel", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Session::setFlash('error', 'Erreur lors du chargement du matériel');
            
            // Données par défaut en cas d'erreur
            $GLOBALS['materials'] = [];
            $GLOBALS['stats'] = [
                'total_materials' => 0,
                'active_materials' => 0,
                'rented_count' => 0,
                'total_revenue' => 0
            ];
            $GLOBALS['pageTitle'] = 'Gestion du matériel';
            
            require_once 'app/Views/materials/index.php';
        }
    }

    private function getUsageStats($materialId)
    {
        $stats = [
            'plans_count' => 0,
            'active_rentals' => 0,
            'total_rentals' => 0,
            'revenue_generated' => 0
        ];

        try {
            // Nombre de formules utilisant ce matériel
            $plans = Database::fetch("
                SELECT COUNT(*) as count 
                FROM formules_abonnement 
                WHERE modele_materiel_id = ? AND actif = 1
            ", [$materialId]);
            $stats['plans_count'] = $plans['count'] ?? 0;

            // Locations actives
            $activeRentals = Database::fetch("
                SELECT COUNT(*) as count 
                FROM materiel_loue 
                WHERE modele_materiel_id = ? AND statut IN ('loue', 'maintenance')
            ", [$materialId]);
            $stats['active_rentals'] = $activeRentals['count'] ?? 0;

            // Total des locations
            $totalRentals = Database::fetch("
                SELECT COUNT(*) as count 
                FROM materiel_loue 
                WHERE modele_materiel_id = ?
            ", [$materialId]);
            $stats['total_rentals'] = $totalRentals['count'] ?? 0;

            // Revenus générés
            $revenue = Database::fetch("
                SELECT COALESCE(SUM(ac.prix_total_mensuel), 0) as revenue
                FROM abonnements_clients ac
                JOIN formules_abonnement fa ON ac.formule_id = fa.id
                WHERE fa.modele_materiel_id = ? AND ac.statut = 'actif'
            ", [$materialId]);
            $stats['revenue_generated'] = $revenue['revenue'] ?? 0;

        } catch (\Exception $e) {
            Logger::error("Erreur lors du calcul des stats d'usage", [
                'material_id' => $materialId,
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
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
        $errors = $this->validateMaterialData($_POST);
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
                'actif' => isset($_POST['actif']) ? 1 : 0,
                'date_creation' => date('Y-m-d H:i:s')
            ];

            // Créer le matériel
            $materialId = Database::insert('modeles_materiel', $data);

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
            $material = Database::fetch("SELECT * FROM modeles_materiel WHERE id = ?", [$id]);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Statistiques d'usage
            $usage_stats = $this->getUsageStats($id);

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
                LEFT JOIN abonnements_clients ac ON ml.abonnement_id = ac.id
                WHERE ml.modele_materiel_id = ? AND ml.statut = 'loue'
                ORDER BY ml.date_location DESC
            ", [$id]);

            // Historique des locations
            $rental_history = Database::fetchAll("
                SELECT ml.*, c.raison_sociale
                FROM materiel_loue ml
                JOIN clients c ON ml.client_id = c.id
                WHERE ml.modele_materiel_id = ?
                ORDER BY ml.date_creation DESC
                LIMIT 10
            ", [$id]);

            // Définir les variables globales
            $GLOBALS['material'] = $material;
            $GLOBALS['usage_stats'] = $usage_stats;
            $GLOBALS['plans'] = $plans;
            $GLOBALS['rentals'] = $rentals;
            $GLOBALS['rental_history'] = $rental_history;
            $GLOBALS['pageTitle'] = 'Détails du matériel - ' . $material['nom'];

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
            $material = Database::fetch("SELECT * FROM modeles_materiel WHERE id = ?", [$id]);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Définir les variables globales
            $GLOBALS['material'] = $material;
            $GLOBALS['pageTitle'] = 'Modifier le matériel - ' . $material['nom'];

            require_once 'app/Views/materials/edit.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du matériel pour édition", [
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
        $material = Database::fetch("SELECT * FROM modeles_materiel WHERE id = ?", [$id]);
        if (!$material) {
            Session::setFlash('error', 'Matériel non trouvé');
            header('Location: /materials');
            exit;
        }

        // Validation des données
        $errors = $this->validateMaterialData($_POST, $id);
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
            Database::update('modeles_materiel', $data, 'id = ?', [$id]);

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
            $material = Database::fetch("SELECT * FROM modeles_materiel WHERE id = ?", [$id]);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Vérifier si le matériel peut être désactivé
            if ($material['actif'] && $this->isUsedInPlans($id)) {
                Session::setFlash('error', 'Impossible de désactiver un matériel utilisé dans des formules actives');
                header('Location: /materials');
                exit;
            }

            $newStatus = $material['actif'] ? 0 : 1;
            Database::update('modeles_materiel', ['actif' => $newStatus], 'id = ?', [$id]);

            $statusText = $newStatus ? 'activé' : 'désactivé';
            Session::setFlash('success', "Matériel {$statusText} avec succès");

            Logger::info('Statut de matériel modifié', [
                'material_id' => $id,
                'old_status' => $material['actif'],
                'new_status' => $newStatus
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
            $material = Database::fetch("SELECT * FROM modeles_materiel WHERE id = ?", [$id]);
            if (!$material) {
                Session::setFlash('error', 'Matériel non trouvé');
                header('Location: /materials');
                exit;
            }

            // Vérifier que le matériel peut être supprimé
            if ($this->isUsedInPlans($id)) {
                Session::setFlash('error', 'Impossible de supprimer un matériel utilisé dans des formules d\'abonnement');
                header('Location: /materials');
                exit;
            }

            if ($this->isUsedInRentals($id)) {
                Session::setFlash('error', 'Impossible de supprimer un matériel actuellement loué');
                header('Location: /materials');
                exit;
            }

            // Supprimer le matériel
            Database::delete('modeles_materiel', 'id = ?', [$id]);

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

    private function validateMaterialData($data, $id = null)
    {
        $errors = [];

        // Validation du nom
        if (empty($data['nom'])) {
            $errors[] = 'Le nom du matériel est obligatoire';
        } elseif (strlen($data['nom']) > 100) {
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        // Vérifier l'unicité du nom
        $nameQuery = "SELECT id FROM modeles_materiel WHERE nom = ?";
        $nameParams = [$data['nom']];
        
        if ($id) {
            $nameQuery .= " AND id != ?";
            $nameParams[] = $id;
        }
        
        $existingMaterial = Database::fetch($nameQuery, $nameParams);
        if ($existingMaterial) {
            $errors[] = 'Ce nom de matériel existe déjà';
        }

        // Validation du prix mensuel
        if (!is_numeric($data['prix_mensuel']) || $data['prix_mensuel'] < 0) {
            $errors[] = 'Le prix mensuel doit être un nombre positif';
        }

        // Validation du dépôt de garantie
        if (!is_numeric($data['depot_garantie']) || $data['depot_garantie'] < 0) {
            $errors[] = 'Le dépôt de garantie doit être un nombre positif';
        }

        return $errors;
    }

    private function isUsedInPlans($materialId)
    {
        $count = Database::fetch("
            SELECT COUNT(*) as count 
            FROM formules_abonnement 
            WHERE modele_materiel_id = ?
        ", [$materialId]);
        
        return ($count['count'] ?? 0) > 0;
    }

    private function isUsedInRentals($materialId)
    {
        $count = Database::fetch("
            SELECT COUNT(*) as count 
            FROM materiel_loue 
            WHERE modele_materiel_id = ? AND statut IN ('loue', 'maintenance')
        ", [$materialId]);
        
        return ($count['count'] ?? 0) > 0;
    }

    // Méthodes pour les locations de matériel
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
                'active_rentals' => 0,
                'maintenance_count' => 0,
                'returned_count' => 0
            ];

            foreach ($rentals as $rental) {
                switch ($rental['statut']) {
                    case 'loue':
                        $rental_stats['active_rentals']++;
                        break;
                    case 'maintenance':
                        $rental_stats['maintenance_count']++;
                        break;
                    case 'retourne':
                        $rental_stats['returned_count']++;
                        break;
                }
            }

            // Définir les variables globales
            $GLOBALS['rentals'] = $rentals;
            $GLOBALS['rental_stats'] = $rental_stats;
            $GLOBALS['pageTitle'] = 'Locations de matériel';

            require_once 'app/Views/materials/rentals.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération des locations", [
                'error' => $e->getMessage()
            ]);
            
            Session::setFlash('error', 'Erreur lors du chargement des locations');
            
            $GLOBALS['rentals'] = [];
            $GLOBALS['rental_stats'] = [
                'total_rentals' => 0,
                'active_rentals' => 0,
                'maintenance_count' => 0,
                'returned_count' => 0
            ];
            $GLOBALS['pageTitle'] = 'Locations de matériel';
            
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

            $GLOBALS['clients'] = $clients;
            $GLOBALS['materials'] = $materials;
            $GLOBALS['pageTitle'] = 'Nouvelle location de matériel';

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
                'abonnement_id' => !empty($_POST['abonnement_id']) ? (int)$_POST['abonnement_id'] : null,
                'date_creation' => date('Y-m-d H:i:s')
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