<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;
use App\Helpers\Config;

class SubscriptionPlanController
{
    public function index()
    {
        // Méthode alternative avec passage direct des données
        $this->showPlansIndex();
    }
    
    private function showPlansIndex()
    {
        try {
            // Récupérer toutes les formules avec les informations du matériel
            $plans = Database::query("
                SELECT fa.*, mm.nom as materiel_nom, mm.description as materiel_description
                FROM formules_abonnement fa
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                ORDER BY fa.type_abonnement, fa.prix_base ASC
            ")->fetchAll();

            // Statistiques
            $stats = [
                'total_plans' => count($plans),
                'active_plans' => 0,
                'app_plans' => 0,
                'mixed_plans' => 0,
                'material_plans' => 0
            ];

            foreach ($plans as $plan) {
                if ($plan['actif']) {
                    $stats['active_plans']++;
                }
                
                switch ($plan['type_abonnement']) {
                    case 'application':
                        $stats['app_plans']++;
                        break;
                    case 'application_materiel':
                        $stats['mixed_plans']++;
                        break;
                    case 'materiel_seul':
                        $stats['material_plans']++;
                        break;
                }
            }

            // Charger la vue avec les données
            $this->loadView('subscriptions/plans/index', [
                'plans' => $plans,
                'stats' => $stats,
                'pageTitle' => 'Formules d\'abonnement'
            ]);

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération des formules", [
                'error' => $e->getMessage()
            ]);
            
            Session::setFlash('error', 'Erreur lors du chargement des formules');
            
            // Charger la vue avec des données vides
            $this->loadView('subscriptions/plans/index', [
                'plans' => [],
                'stats' => [
                    'total_plans' => 0,
                    'active_plans' => 0,
                    'app_plans' => 0,
                    'mixed_plans' => 0,
                    'material_plans' => 0
                ],
                'pageTitle' => 'Formules d\'abonnement'
            ]);
        }
    }

    private function loadView($viewPath, $data = [])
    {
        // Extraire les variables pour qu'elles soient disponibles dans la vue
        extract($data);
        
        // Définir aussi comme globales pour compatibilité
        foreach ($data as $key => $value) {
            $GLOBALS[$key] = $value;
        }
        
        $viewFile = 'app/Views/' . str_replace('.', '/', $viewPath) . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            throw new \Exception("Vue non trouvée : {$viewFile}");
        }
    }

    public function create()
    {
        $this->showMaterialsCreate();
    }

    private function showMaterialsCreate() {
        try {
            // Récupérer toutes les formules avec les informations du matériel
            $materials = Database::query("
                SELECT id, nom, description, prix_mensuel, depot_garantie 
                FROM modeles_materiel 
                WHERE actif = 1 
                ORDER BY nom
            ")->fetchAll();


            // Charger la vue avec les données
            $this->loadView('subscriptions/plans/create', [
                'materials' => $materials,
                'pageTitle' => 'Créer une formule d\'abonnement'
            ]);

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du matériel", [
                'error' => $e->getMessage()
            ]);
            
            Session::setFlash('error', 'Erreur lors de la récupération du matériel');
            
            // Charger la vue avec des données vides
            $this->loadView('subscriptions/plans/create', [
                'materials' => [],
                'pageTitle' => 'Créer une formule d\'abonnement'
            ]);
        }
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /subscription-plans/create');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /subscription-plans/create');
            exit;
        }

        // Validation des données
        $data = $this->validatePlanData($_POST);
        if ($data === false) {
            header('Location: /subscription-plans/create');
            exit;
        }

        try {
            Database::beginTransaction();

            // Debug : Afficher les données à insérer
            Logger::debug('Données à insérer pour la formule', $data);

            // Insérer la formule
            $planId = Database::insert('formules_abonnement', $data);
            
            if (!$planId) {
                throw new \Exception('Erreur lors de l\'insertion de la formule en base de données');
            }

            Logger::info('Formule créée en base de données', [
                'plan_id' => $planId,
                'name' => $data['nom']
            ]);

            // Créer le produit Stripe après l'insertion en base
            $this->createStripeProduct($planId, $data);

            Database::commit();

            Session::setFlash('success', 'Formule d\'abonnement créée avec succès');
            header('Location: /subscription-plans');
            exit;

        } catch (\Exception $e) {
            Database::rollback();
            
            Logger::error("Erreur lors de la création de la formule", [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Message d'erreur plus détaillé en mode debug
            $errorMessage = 'Erreur lors de la création de la formule';
            if (\App\Helpers\Config::get('app.debug', false)) {
                $errorMessage .= ': ' . $e->getMessage();
            }
            
            Session::setFlash('error', $errorMessage);
            header('Location: /subscription-plans/create');
            exit;
        }
    }

    public function edit($id)
    {
        try {
            // Validation de l'ID
            if (!is_numeric($id) || $id <= 0) {
                Session::setFlash('error', 'ID de formule invalide');
                header('Location: /subscription-plans');
                exit;
            }

            // Récupérer la formule avec les informations du matériel
            $plan = Database::fetch("
                SELECT fa.*, mm.nom as materiel_nom, mm.description as materiel_description,
                       mm.prix_mensuel as materiel_prix, mm.depot_garantie as materiel_depot
                FROM formules_abonnement fa
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                WHERE fa.id = ?
            ", [$id]);

            if (!$plan) {
                Logger::warning("Formule non trouvée lors de l'édition", ['plan_id' => $id]);
                Session::setFlash('error', 'Formule non trouvée');
                header('Location: /subscription-plans');
                exit;
            }

            // Récupérer tous les modèles de matériel pour le formulaire
            $materials = Database::query("
                SELECT id, nom, description, prix_mensuel, depot_garantie 
                FROM modeles_materiel 
                WHERE actif = 1 
                ORDER BY nom
            ")->fetchAll();

            // Définir les variables globales pour la vue
            $GLOBALS['plan'] = $plan;
            $GLOBALS['materials'] = $materials;
            $GLOBALS['pageTitle'] = 'Modifier la formule d\'abonnement';

            Logger::info("Chargement de la page d'édition de formule", [
                'plan_id' => $id,
                'plan_name' => $plan['nom']
            ]);

            require_once 'app/Views/subscriptions/plans/edit.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération de la formule pour édition", [
                'plan_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Session::setFlash('error', 'Erreur lors du chargement de la formule');
            header('Location: /subscription-plans');
            exit;
        }
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /subscription-plans/' . $id . '/edit');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /subscription-plans/' . $id . '/edit');
            exit;
        }

        // Vérifier que la formule existe
        $existingPlan = Database::fetch("SELECT * FROM formules_abonnement WHERE id = ?", [$id]);
        if (!$existingPlan) {
            Session::setFlash('error', 'Formule non trouvée');
            header('Location: /subscription-plans');
            exit;
        }

        // Validation des données
        $data = $this->validatePlanData($_POST, $id);
        if ($data === false) {
            header('Location: /subscription-plans/' . $id . '/edit');
            exit;
        }

        try {
            Database::beginTransaction();

            // Mettre à jour la formule
            Database::update('formules_abonnement', $data, 'id = ?', [$id]);

            // Log de modification
            Logger::info('Formule d\'abonnement modifiée', [
                'plan_id' => $id,
                'name' => $data['nom'],
                'changes' => array_diff_assoc($data, $existingPlan)
            ]);

            // TODO: Mettre à jour le produit Stripe si nécessaire
            $this->updateStripeProduct($id, $data, $existingPlan);

            Database::commit();

            Session::setFlash('success', 'Formule d\'abonnement modifiée avec succès');
            header('Location: /subscription-plans');
            exit;

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la modification de la formule", [
                'plan_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            Session::setFlash('error', 'Erreur lors de la modification de la formule');
            header('Location: /subscription-plans/' . $id . '/edit');
            exit;
        }
    }

    public function toggleStatus($id)
    {
        try {
            $plan = Database::fetch("SELECT * FROM formules_abonnement WHERE id = ?", [$id]);
            if (!$plan) {
                Session::setFlash('error', 'Formule non trouvée');
                header('Location: /subscription-plans');
                exit;
            }

            $newStatus = $plan['actif'] ? 0 : 1;
            Database::update('formules_abonnement', ['actif' => $newStatus], 'id = ?', [$id]);

            Logger::info('Statut de formule modifié', [
                'plan_id' => $id,
                'old_status' => $plan['actif'],
                'new_status' => $newStatus
            ]);

            $statusText = $newStatus ? 'activée' : 'désactivée';
            Session::setFlash('success', "Formule {$statusText} avec succès");

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la modification du statut", [
                'plan_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la modification du statut');
        }

        header('Location: /subscription-plans');
        exit;
    }

    public function delete($id)
    {
        try {
            // Vérifier que la formule existe
            $plan = Database::fetch("SELECT * FROM formules_abonnement WHERE id = ?", [$id]);
            if (!$plan) {
                Session::setFlash('error', 'Formule non trouvée');
                header('Location: /subscription-plans');
                exit;
            }

            // Vérifier qu'aucun abonnement n'utilise cette formule
            $subscriptionsCount = Database::fetch("
                SELECT COUNT(*) as count 
                FROM abonnements_clients 
                WHERE formule_id = ? AND statut IN ('actif', 'en_attente')
            ", [$id]);

            if ($subscriptionsCount['count'] > 0) {
                Session::setFlash('error', 'Impossible de supprimer une formule utilisée par des abonnements actifs');
                header('Location: /subscription-plans');
                exit;
            }

            Database::beginTransaction();

            // Supprimer la formule
            Database::delete('formules_abonnement', 'id = ?', [$id]);

            Logger::info('Formule d\'abonnement supprimée', [
                'plan_id' => $id,
                'plan_name' => $plan['nom']
            ]);

            // TODO: Supprimer le produit Stripe
            $this->deleteStripeProduct($plan);

            Database::commit();

            Session::setFlash('success', 'Formule supprimée avec succès');

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la suppression de la formule", [
                'plan_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la suppression de la formule');
        }

        header('Location: /subscription-plans');
        exit;
    }

    private function validatePlanData($data, $planId = null)
    {
        $errors = [];

        // Validation du nom
        if (empty($data['nom'])) {
            $errors[] = 'Le nom de la formule est obligatoire';
        } elseif (strlen($data['nom']) > 100) {
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        // Vérifier l'unicité du nom
        $nameQuery = "SELECT id FROM formules_abonnement WHERE nom = ?";
        $nameParams = [$data['nom']];
        
        if ($planId) {
            $nameQuery .= " AND id != ?";
            $nameParams[] = $planId;
        }
        
        $existingPlan = Database::fetch($nameQuery, $nameParams);
        if ($existingPlan) {
            $errors[] = 'Ce nom de formule existe déjà';
        }

        // Validation du type d'abonnement
        if (!in_array($data['type_abonnement'], ['application', 'application_materiel', 'materiel_seul'])) {
            $errors[] = 'Type d\'abonnement invalide';
        }

        // Validation du nombre d'utilisateurs
        if (!is_numeric($data['nombre_utilisateurs_inclus']) || $data['nombre_utilisateurs_inclus'] < 0) {
            $errors[] = 'Le nombre d\'utilisateurs inclus doit être un nombre positif';
        }

        // Validation du coût utilisateur supplémentaire
        if (!empty($data['cout_utilisateur_supplementaire'])) {
            if (!is_numeric($data['cout_utilisateur_supplementaire']) || $data['cout_utilisateur_supplementaire'] < 0) {
                $errors[] = 'Le coût par utilisateur supplémentaire doit être un nombre positif';
            }
        }

        // Validation de la durée
        if (!in_array($data['duree'], ['mensuelle', 'annuelle'])) {
            $errors[] = 'Durée invalide';
        }

        // Validation du nombre de sous-catégories
        if (!empty($data['nombre_sous_categories'])) {
            if (!is_numeric($data['nombre_sous_categories']) || $data['nombre_sous_categories'] < 0) {
                $errors[] = 'Le nombre de sous-catégories doit être un nombre positif';
            }
        }

        if (!is_numeric($data['prix_base']) || $data['prix_base'] < 0) {
            $errors[] = 'Le prix de base doit être un nombre positif';
        }

        // NOUVELLE VALIDATION : Vérification cohérence prix/matériel
        if (in_array($data['type_abonnement'], ['application_materiel', 'materiel_seul'])) {
            if (!empty($data['modele_materiel_id'])) {
                $material = Database::fetch(
                    "SELECT prix_mensuel, nom FROM modeles_materiel WHERE id = ? AND actif = 1", 
                    [$data['modele_materiel_id']]
                );
                
                if ($material) {
                    $materialPrice = (float)$material['prix_mensuel'];
                    $basePrice = (float)$data['prix_base'];
                    
                    if ($data['type_abonnement'] === 'materiel_seul') {
                        $minExpectedPrice = $data['duree'] === 'annuelle' 
                            ? $materialPrice * 12 * 0.8  // Minimum 20% de remise max
                            : $materialPrice * 0.8;      // Minimum 20% de remise max
                            
                        if ($basePrice < $minExpectedPrice) {
                            $errors[] = sprintf(
                                'Le prix de base (%.2f€) semble trop bas pour le matériel "%s" (%.2f€/mois). Prix minimum suggéré : %.2f€',
                                $basePrice,
                                $material['nom'],
                                $materialPrice,
                                $minExpectedPrice
                            );
                        }
                    }
                    
                    // Log pour traçabilité
                    Logger::info('Validation prix formule avec matériel', [
                        'type' => $data['type_abonnement'],
                        'base_price' => $basePrice,
                        'material_price' => $materialPrice,
                        'material_name' => $material['nom'],
                        'duration' => $data['duree']
                    ]);
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            return false;
        }

        // Préparer les données nettoyées avec des valeurs par défaut pour éviter les erreurs SQL
        $cleanData = [
            'nom' => trim($data['nom']),
            'type_abonnement' => $data['type_abonnement'],
            'nombre_utilisateurs_inclus' => (int)$data['nombre_utilisateurs_inclus'],
            'cout_utilisateur_supplementaire' => !empty($data['cout_utilisateur_supplementaire']) ? (float)$data['cout_utilisateur_supplementaire'] : null,
            'duree' => $data['duree'],
            'nombre_sous_categories' => !empty($data['nombre_sous_categories']) ? (int)$data['nombre_sous_categories'] : null,
            'prix_base' => $this->calculateBasePriceWithMaterial($data), // Prix calculé/validé
            'modele_materiel_id' => !empty($data['modele_materiel_id']) ? (int)$data['modele_materiel_id'] : null,
            'stripe_product_id' => null,
            'stripe_price_id' => null,
            'stripe_price_supplementaire_id' => null,
            'lien_inscription' => null,
            'actif' => isset($data['actif']) ? 1 : 0,
            'date_creation' => date('Y-m-d H:i:s')
        ];

        // Debug : Logger les données nettoyées
        Logger::debug('Données validées et nettoyées pour la formule', $cleanData);

        return $cleanData;
    }

    private function createStripeProduct($planId, $data)
    {
        try {
            // Ajouter l'ID du plan aux données
            $data['id'] = $planId;
            
            // Créer le produit Stripe
            $product = \App\Services\StripeService::createProduct($data);
            
            // Créer le prix principal
            $price = \App\Services\StripeService::createPrice($product->id, $data);
            
            // Créer le prix pour les utilisateurs supplémentaires si nécessaire
            $extraUserPrice = null;
            if (!empty($data['cout_utilisateur_supplementaire']) && $data['cout_utilisateur_supplementaire'] > 0) {
                $extraUserPrice = \App\Services\StripeService::createExtraUserPrice($product->id, $data);
            }
            
            // Mettre à jour la formule avec les IDs Stripe
            $updateData = [
                'stripe_product_id' => $product->id,
                'stripe_price_id' => $price->id
            ];
            
            if ($extraUserPrice) {
                $updateData['stripe_price_supplementaire_id'] = $extraUserPrice->id;
            }
            
            Database::update('formules_abonnement', $updateData, 'id = ?', [$planId]);
            
            Logger::info('Formule synchronisée avec Stripe', [
                'plan_id' => $planId,
                'product_id' => $product->id,
                'price_id' => $price->id,
                'extra_price_id' => $extraUserPrice ? $extraUserPrice->id : null
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erreur lors de la création du produit Stripe', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ne pas faire échouer la création de la formule si Stripe échoue
            // Mais informer l'utilisateur
            Session::setFlash('warning', 'Formule créée mais synchronisation Stripe échouée: ' . $e->getMessage());
        }
    }

    private function updateStripeProduct($planId, $newData, $oldData)
    {
        try {
            if (empty($oldData['stripe_product_id'])) {
                // Si pas de produit Stripe existant, le créer
                $this->createStripeProduct($planId, $newData);
                return;
            }
            
            // Ajouter l'ID du plan aux données
            $newData['id'] = $planId;
            
            // Mettre à jour le produit Stripe
            \App\Services\StripeService::updateProduct($oldData['stripe_product_id'], $newData);
            
            // Si le prix a changé, créer un nouveau prix (Stripe ne permet pas de modifier les prix existants)
            if ($newData['prix_base'] != $oldData['prix_base'] || $newData['duree'] != $oldData['duree']) {
                $newPrice = \App\Services\StripeService::createPrice($oldData['stripe_product_id'], $newData);
                
                // Mettre à jour l'ID du prix
                Database::update('formules_abonnement', 
                    ['stripe_price_id' => $newPrice->id], 
                    'id = ?', 
                    [$planId]
                );
                
                Logger::info('Nouveau prix Stripe créé pour la formule', [
                    'plan_id' => $planId,
                    'old_price_id' => $oldData['stripe_price_id'],
                    'new_price_id' => $newPrice->id
                ]);
            }
            
            // Gérer le prix des utilisateurs supplémentaires
            $oldExtraCost = $oldData['cout_utilisateur_supplementaire'] ?? 0;
            $newExtraCost = $newData['cout_utilisateur_supplementaire'] ?? 0;
            
            if ($newExtraCost != $oldExtraCost) {
                if ($newExtraCost > 0) {
                    // Créer ou mettre à jour le prix supplémentaire
                    $extraUserPrice = \App\Services\StripeService::createExtraUserPrice($oldData['stripe_product_id'], $newData);
                    Database::update('formules_abonnement', 
                        ['stripe_price_supplementaire_id' => $extraUserPrice->id], 
                        'id = ?', 
                        [$planId]
                    );
                } else {
                    // Supprimer la référence au prix supplémentaire
                    Database::update('formules_abonnement', 
                        ['stripe_price_supplementaire_id' => null], 
                        'id = ?', 
                        [$planId]
                    );
                }
            }
            
            Logger::info('Formule mise à jour sur Stripe', [
                'plan_id' => $planId,
                'product_id' => $oldData['stripe_product_id']
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erreur lors de la mise à jour du produit Stripe', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Session::setFlash('warning', 'Formule modifiée mais synchronisation Stripe échouée: ' . $e->getMessage());
        }
    }

    private function deleteStripeProduct($plan)
    {
        try {
            if (!empty($plan['stripe_product_id'])) {
                // Archiver le produit Stripe (pas de suppression définitive)
                \App\Services\StripeService::archiveProduct($plan['stripe_product_id']);
                
                Logger::info('Produit Stripe archivé', [
                    'plan_id' => $plan['id'],
                    'product_id' => $plan['stripe_product_id']
                ]);
            }
            
        } catch (\Exception $e) {
            Logger::error('Erreur lors de l\'archivage du produit Stripe', [
                'plan_id' => $plan['id'],
                'product_id' => $plan['stripe_product_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            // Ne pas faire échouer la suppression de la formule si Stripe échoue
            Session::setFlash('warning', 'Formule supprimée mais archivage Stripe échoué: ' . $e->getMessage());
        }
    }

    private function calculateBasePriceWithMaterial($data)
    {
        $basePrice = (float)$data['prix_base'];
        $type = $data['type_abonnement'];
        $duration = $data['duree'];
        
        // Si le type inclut du matériel, vérifier que le prix inclut bien le matériel
        if (in_array($type, ['application_materiel', 'materiel_seul']) && !empty($data['modele_materiel_id'])) {
            $material = Database::fetch(
                "SELECT prix_mensuel FROM modeles_materiel WHERE id = ?", 
                [$data['modele_materiel_id']]
            );
            
            if ($material) {
                $materialPrice = (float)$material['prix_mensuel'];
                
                // Logique de calcul selon le type
                if ($type === 'materiel_seul') {
                    // Pour matériel seul, le prix de base doit être au moins égal au prix du matériel
                    if ($duration === 'annuelle') {
                        $expectedMinPrice = $materialPrice * 12 * 0.9; // 10% de remise annuelle
                    } else {
                        $expectedMinPrice = $materialPrice;
                    }
                    
                    if ($basePrice < $expectedMinPrice) {
                        Logger::warning('Prix de base inférieur au prix du matériel ajusté', [
                            'provided_price' => $basePrice,
                            'expected_min_price' => $expectedMinPrice,
                            'material_price' => $materialPrice,
                            'duration' => $duration
                        ]);
                    }
                } elseif ($type === 'application_materiel') {
                    // Pour application + matériel, vérifier que le prix inclut bien le matériel
                    // (On peut laisser une flexibilité ici car l'administrateur peut vouloir faire des offres)
                    Logger::info('Prix formule application + matériel', [
                        'total_price' => $basePrice,
                        'material_price' => $materialPrice,
                        'duration' => $duration
                    ]);
                }
            }
        }
        
        return $basePrice;
    }

    // Nouvelle méthode utilitaire pour obtenir les suggestions de prix
    public function getPricingSuggestion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }
        
        header('Content-Type: application/json');
        
        try {
            $materialId = (int)($_POST['material_id'] ?? 0);
            $type = $_POST['type'] ?? '';
            $duration = $_POST['duration'] ?? 'mensuelle';
            
            if (!$materialId || !in_array($type, ['application_materiel', 'materiel_seul'])) {
                echo json_encode(['error' => 'Paramètres invalides']);
                exit;
            }
            
            $material = Database::fetch(
                "SELECT prix_mensuel, nom FROM modeles_materiel WHERE id = ? AND actif = 1", 
                [$materialId]
            );
            
            if (!$material) {
                echo json_encode(['error' => 'Matériel non trouvé']);
                exit;
            }
            
            $materialPrice = (float)$material['prix_mensuel'];
            $suggestions = [];
            
            if ($type === 'materiel_seul') {
                if ($duration === 'annuelle') {
                    $suggestions = [
                        'recommended' => $materialPrice * 12 * 0.9, // 10% remise
                        'minimum' => $materialPrice * 12 * 0.8,     // 20% remise max
                        'premium' => $materialPrice * 12             // Prix plein
                    ];
                } else {
                    $suggestions = [
                        'recommended' => $materialPrice,
                        'minimum' => $materialPrice * 0.9,  // 10% remise max
                        'premium' => $materialPrice * 1.1   // 10% premium
                    ];
                }
            } elseif ($type === 'application_materiel') {
                // Prix suggéré = prix app de base + prix matériel
                $baseAppPrice = 30; // Prix de base application (à ajuster selon vos tarifs)
                
                if ($duration === 'annuelle') {
                    $suggestions = [
                        'recommended' => ($baseAppPrice + $materialPrice) * 12 * 0.9,
                        'minimum' => ($baseAppPrice + $materialPrice) * 12 * 0.8,
                        'premium' => ($baseAppPrice + $materialPrice) * 12
                    ];
                } else {
                    $suggestions = [
                        'recommended' => $baseAppPrice + $materialPrice,
                        'minimum' => ($baseAppPrice + $materialPrice) * 0.9,
                        'premium' => ($baseAppPrice + $materialPrice) * 1.1
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'material_name' => $material['nom'],
                'material_price' => $materialPrice,
                'suggestions' => $suggestions,
                'duration' => $duration
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erreur lors du calcul des suggestions de prix', [
                'error' => $e->getMessage(),
                'post_data' => $_POST
            ]);
            
            echo json_encode(['error' => 'Erreur serveur']);
        }
        
        exit;
    }
}
?>