<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;
use App\Services\StripeService;
use App\Services\ClientService;

class SubscriptionController
{
    public function index()
    {
        $this->showSubsIndex();
    }

    private function showSubsIndex()
    {
        try {
            // Paramètres de recherche et filtres
            $search = $_GET['search'] ?? '';
            $filters = [
                'status' => $_GET['status'] ?? '',
                'type' => $_GET['type'] ?? '',
                'formule' => $_GET['formule'] ?? '',
                'client' => $_GET['client'] ?? ''
            ];

            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 20;

            // Requête de base pour les abonnements
            $baseQuery = "
                SELECT ac.*, 
                       c.raison_sociale, c.email_facturation, c.ville, c.pays,
                       fa.nom as formule_nom, fa.type_abonnement as formule_type, fa.duree,
                       mm.nom as materiel_nom,
                       CASE 
                           WHEN ac.date_fin IS NOT NULL AND ac.date_fin < NOW() THEN 'Expiré'
                           WHEN ac.statut = 'actif' THEN 'Actif'
                           WHEN ac.statut = 'suspendu' THEN 'Suspendu'
                           WHEN ac.statut = 'annule' THEN 'Annulé'
                           WHEN ac.statut = 'en_attente' THEN 'En attente'
                           ELSE ac.statut
                       END as statut_display,
                       DATEDIFF(ac.date_fin, NOW()) as jours_restants
                FROM abonnements_clients ac
                JOIN clients c ON ac.client_id = c.id
                JOIN formules_abonnement fa ON ac.formule_id = fa.id
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
            ";

            $params = [];
            $whereConditions = [];

            // Filtres de recherche
            if (!empty($search)) {
                $whereConditions[] = "(c.raison_sociale LIKE ? OR c.email_facturation LIKE ? OR fa.nom LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if (!empty($filters['status'])) {
                if ($filters['status'] === 'expire_soon') {
                    $whereConditions[] = "ac.statut = 'actif' AND ac.date_fin IS NOT NULL AND ac.date_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)";
                } elseif ($filters['status'] === 'expired') {
                    $whereConditions[] = "ac.date_fin IS NOT NULL AND ac.date_fin < NOW()";
                } else {
                    $whereConditions[] = "ac.statut = ?";
                    $params[] = $filters['status'];
                }
            }

            if (!empty($filters['type'])) {
                $whereConditions[] = "fa.type_abonnement = ?";
                $params[] = $filters['type'];
            }

            if (!empty($filters['formule'])) {
                $whereConditions[] = "ac.formule_id = ?";
                $params[] = $filters['formule'];
            }

            if (!empty($filters['client'])) {
                $whereConditions[] = "ac.client_id = ?";
                $params[] = $filters['client'];
            }

            // Assembler la requête
            $query = $baseQuery;
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }

            $query .= " ORDER BY ac.date_creation DESC";

            // Pagination
            $offset = ($page - 1) * $perPage;
            $paginatedQuery = $query . " LIMIT {$perPage} OFFSET {$offset}";
            
            $subscriptions = Database::fetchAll($paginatedQuery, $params);

            // Compter le total
            $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as subquery";
            $totalResult = Database::fetch($countQuery, $params);
            $totalItems = $totalResult['total'] ?? 0;
            $totalPages = ceil($totalItems / $perPage);

            // Pagination simple
            $pagination = new class($page, $perPage, $totalItems, $totalPages) {
                public $current_page;
                public $per_page;
                public $total_items;
                public $total_pages;
                public $has_previous;
                public $has_next;
                public $previous_page;
                public $next_page;
                public $first_item;
                public $last_item;

                public function __construct($page, $perPage, $totalItems, $totalPages) {
                    $this->current_page = $page;
                    $this->per_page = $perPage;
                    $this->total_items = $totalItems;
                    $this->total_pages = $totalPages;
                    $this->has_previous = $page > 1;
                    $this->has_next = $page < $totalPages;
                    $this->previous_page = $page > 1 ? $page - 1 : null;
                    $this->next_page = $page < $totalPages ? $page + 1 : null;
                    $this->first_item = $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0;
                    $this->last_item = min($page * $perPage, $totalItems);
                }

                public function getTotalPages() {
                    return $this->total_pages;
                }

                public function toArray() {
                    return [
                        'current_page' => $this->current_page,
                        'per_page' => $this->per_page,
                        'total_items' => $this->total_items,
                        'total_pages' => $this->total_pages,
                        'first_item' => $this->first_item,
                        'last_item' => $this->last_item,
                        'has_previous' => $this->has_previous,
                        'has_next' => $this->has_next,
                        'previous_page' => $this->previous_page,
                        'next_page' => $this->next_page,
                        'page_range' => range(max(1, $this->current_page - 2), min($this->total_pages, $this->current_page + 2))
                    ];
                }

                public function getPageUrl($page) {
                    $params = $_GET;
                    if ($page === 1) {
                        unset($params['page']);
                    } else {
                        $params['page'] = $page;
                    }
                    $queryString = http_build_query($params);
                    $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
                    return $baseUrl . ($queryString ? '?' . $queryString : '');
                }
            };

            // Statistiques
            $stats = [
                'total_subscriptions' => Database::fetch("SELECT COUNT(*) as count FROM abonnements_clients")['count'] ?? 0,
                'active_subscriptions' => Database::fetch("SELECT COUNT(*) as count FROM abonnements_clients WHERE statut = 'actif'")['count'] ?? 0,
                'pending_subscriptions' => Database::fetch("SELECT COUNT(*) as count FROM abonnements_clients WHERE statut = 'en_attente'")['count'] ?? 0,
                'expiring_soon' => Database::fetch("
                    SELECT COUNT(*) as count 
                    FROM abonnements_clients 
                    WHERE statut = 'actif' 
                    AND date_fin IS NOT NULL 
                    AND date_fin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
                ")['count'] ?? 0,
                'monthly_revenue' => Database::fetch("
                    SELECT COALESCE(SUM(prix_total_mensuel), 0) as revenue 
                    FROM abonnements_clients 
                    WHERE statut = 'actif'
                ")['revenue'] ?? 0
            ];

            // Données pour les filtres
            $formules = Database::fetchAll("
                SELECT id, nom, type_abonnement 
                FROM formules_abonnement 
                WHERE actif = 1 
                ORDER BY type_abonnement, nom
            ");

            $clients = Database::fetchAll("
                SELECT c.id, c.raison_sociale 
                FROM clients c 
                WHERE c.actif = 1 
                ORDER BY c.raison_sociale 
                LIMIT 100
            ");

            // Charger la vue avec les données
            $this->loadView('subscriptions/client-subscriptions/index', [
                'subscriptions' => $subscriptions,
                'stats' => $stats,
                'formules' => $formules,
                'clients' => $clients,
                'pagination' => $pagination,
                'search' => $search,
                'filters' => $filters,
                'pageTitle' => 'Abonnements clients'
            ]);

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération des abonnements", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Session::setFlash('error', $e->getMessage().' - ' . $baseQuery);
            
            // Variables par défaut
            $subscriptions = [];
            $stats = [
                'total_subscriptions' => 0,
                'active_subscriptions' => 0,
                'pending_subscriptions' => 0,
                'expiring_soon' => 0,
                'monthly_revenue' => 0
            ];
            $formules = [];
            $clients = [];
            $pagination = null;
            $search = '';
            $filters = [];
            $pageTitle = 'Abonnements clients';
            
            // Charger la vue avec les données
            $this->loadView('subscriptions/client-subscriptions/index', [
                'subscriptions' => $subscriptions,
                'stats' => $stats,
                'formules' => $formules,
                'clients' => $clients,
                'pagination' => $pagination,
                'search' => $search,
                'filters' => $filters,
                'pageTitle' => 'Abonnements clients'
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
        try {
            // Récupérer les clients actifs
            $clients = Database::fetchAll("
                SELECT id, raison_sociale, email_facturation, ville
                FROM clients 
                WHERE actif = 1 
                ORDER BY raison_sociale
            ");

            // Récupérer les formules actives
            $formules = Database::fetchAll("
                SELECT fa.*, mm.nom as materiel_nom, mm.prix_mensuel as materiel_prix
                FROM formules_abonnement fa
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                WHERE fa.actif = 1
                ORDER BY fa.type_abonnement, fa.nom
            ");

            $GLOBALS['clients'] = $clients;
            $GLOBALS['formules'] = $formules;
            $GLOBALS['pageTitle'] = 'Nouvel abonnement';

            require_once 'app/Views/subscriptions/client-subscriptions/create.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors du chargement du formulaire de création", [
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du formulaire');
            header('Location: /subscriptions');
            exit;
        }
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /subscriptions/create');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /subscriptions/create');
            exit;
        }

        // Validation des données
        $errors = $this->validateSubscriptionData($_POST);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            header('Location: /subscriptions/create');
            exit;
        }

        try {
            Database::beginTransaction();

            // Récupérer les informations nécessaires
            $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$_POST['client_id']]);
            $formule = Database::fetch("
                SELECT fa.*, mm.nom as materiel_nom, mm.depot_garantie
                FROM formules_abonnement fa
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                WHERE fa.id = ?
            ", [$_POST['formule_id']]);

            if (!$client || !$formule) {
                throw new \Exception('Client ou formule non trouvé');
            }

            // Calculer le prix total de l'abonnement
            $nombreUtilisateurs = (int)($_POST['nombre_utilisateurs'] ?? $formule['nombre_utilisateurs_inclus']);
            $utilisateursSupplementaires = max(0, $nombreUtilisateurs - $formule['nombre_utilisateurs_inclus']);
            
            $prixBase = $formule['prix_base'];
            $prixUtilisateursSupp = $utilisateursSupplementaires * ($formule['cout_utilisateur_supplementaire'] ?? 0);
            $prixAbonnement = $prixBase + $prixUtilisateursSupp;

            // Calculer le dépôt de garantie si matériel inclus
            $depotGarantie = 0;
            if (in_array($formule['type_abonnement'], ['application_materiel', 'materiel_seul']) && !empty($formule['depot_garantie'])) {
                $prixGarantie = $formule['depot_garantie'];
                $prixGarantieSupp = $utilisateursSupplementaires * ($formule['depot_garantie'] ?? 0);
                $depotGarantie = $formule['depot_garantie'] + $prixGarantieSupp;
            }

            // Prix total = abonnement + dépôt de garantie (pour la première facture)
            $prixTotalPremiereFois = $prixAbonnement + $depotGarantie;

            // Calculer les dates
            $dateDebut = $_POST['date_debut'] ?? date('Y-m-d');
            $dateFin = null;
            if ($formule['duree'] === 'mensuelle') {
                $dateFin = date('Y-m-d', strtotime($dateDebut . ' +1 month'));
            } elseif ($formule['duree'] === 'annuelle') {
                $dateFin = date('Y-m-d', strtotime($dateDebut . ' +1 year'));
            }

            // Créer l'abonnement en base
            $subscriptionData = [
                'client_id' => $client['id'],
                'formule_id' => $formule['id'],
                'type_abonnement' => $_POST['type_abonnement'] ?? 'principal',
                'statut' => 'en_attente',
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'nombre_utilisateurs_actuels' => $nombreUtilisateurs,
                'prix_total_mensuel' => $prixAbonnement,
                'date_creation' => date('Y-m-d H:i:s')
            ];

            $subscriptionId = Database::insert('abonnements_clients', $subscriptionData);

            // Vérifier si le client a un mode de paiement sur Stripe
            $needsPaymentSetup = $this->checkPaymentMethodRequired($client, $prixTotalPremiereFois);

            if ($needsPaymentSetup) {
                // Créer une session Checkout Stripe pour configurer le paiement
                $checkoutResult = $this->createStripeCheckoutSession(
                    $client, 
                    $formule, 
                    $subscriptionId, 
                    $prixAbonnement, 
                    $depotGarantie,
                    $nombreUtilisateurs
                );

                if ($checkoutResult['success']) {
                    // Sauvegarder l'URL de checkout pour redirection
                    Database::update('abonnements_clients', [
                        'stripe_checkout_session_id' => $checkoutResult['session_id'],
                        'statut' => 'en_attente'
                    ], 'id = ?', [$subscriptionId]);

                    Database::commit();

                    // Rediriger vers Stripe Checkout
                    header('Location: ' . $checkoutResult['url']);
                    exit;
                } else {
                    throw new \Exception('Erreur lors de la création de la session de paiement: ' . $checkoutResult['error']);
                }
            } else {
                // Client a déjà un mode de paiement, créer l'abonnement directement
                $stripeResult = $this->createStripeSubscription($client, $formule, $subscriptionId, $nombreUtilisateurs);
                
                if ($stripeResult['success']) {
                    Database::update('abonnements_clients', [
                        'stripe_subscription_id' => $stripeResult['subscription_id'],
                        'statut' => 'actif'
                    ], 'id = ?', [$subscriptionId]);

                    // Créer une facture séparée pour le dépôt de garantie si nécessaire
                    if ($depotGarantie > 0) {
                        $this->createDepositInvoice($client, $subscriptionId, $depotGarantie);
                    }
                } else {
                    Logger::warning('Abonnement créé sans Stripe', [
                        'subscription_id' => $subscriptionId,
                        'error' => $stripeResult['error']
                    ]);
                }

                // Gérer le matériel si nécessaire
                if (in_array($formule['type_abonnement'], ['application_materiel', 'materiel_seul']) && !empty($formule['modele_materiel_id'])) {
                    $this->createMaterialRental($subscriptionId, $client['id'], $formule, $depotGarantie);
                }

                Database::commit();

                Session::setFlash('success', 'Abonnement créé avec succès');
                header('Location: /subscriptions/' . $subscriptionId);
                exit;
            }

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la création de l'abonnement", [
                'error' => $e->getMessage(),
                'data' => $_POST,
                'trace' => $e->getTraceAsString()
            ]);
            Session::setFlash('error', 'Erreur lors de la création de l\'abonnement: ' . $e->getMessage());
            header('Location: /subscriptions/create');
            exit;
        }
    }

    public function show($id)
    {
        try {
            // Récupérer l'abonnement avec toutes les informations
            $subscription = Database::fetch("
                SELECT ac.*, 
                       c.raison_sociale, c.email_facturation, c.adresse, c.ville, c.pays,
                       c.stripe_customer_id,
                       fa.nom as formule_nom, fa.type_abonnement, fa.duree,
                       fa.nombre_utilisateurs_inclus, fa.cout_utilisateur_supplementaire,
                       mm.nom as materiel_nom, mm.prix_mensuel as materiel_prix,
                       CASE 
                           WHEN ac.date_fin IS NOT NULL AND ac.date_fin < NOW() THEN 'Expiré'
                           WHEN ac.statut = 'actif' THEN 'Actif'
                           WHEN ac.statut = 'suspendu' THEN 'Suspendu'
                           WHEN ac.statut = 'annule' THEN 'Annulé'
                           WHEN ac.statut = 'en_attente' THEN 'En attente'
                           ELSE ac.statut
                       END as statut_display,
                       DATEDIFF(ac.date_fin, NOW()) as jours_restants
                FROM abonnements_clients ac
                JOIN clients c ON ac.client_id = c.id
                JOIN formules_abonnement fa ON ac.formule_id = fa.id
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                WHERE ac.id = ?
            ", [$id]);

            if (!$subscription) {
                Session::setFlash('error', 'Abonnement non trouvé');
                header('Location: /subscriptions');
                exit;
            }

            // Récupérer le matériel loué pour cet abonnement
            $materials = Database::fetchAll("
                SELECT ml.*, mm.nom as materiel_nom, mm.description
                FROM materiel_loue ml
                JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
                WHERE ml.abonnement_id = ?
                ORDER BY ml.date_creation DESC
            ", [$id]);

            // Récupérer les factures liées
            $invoices = Database::fetchAll("
                SELECT fs.*,
                       CASE 
                           WHEN fs.date_paiement IS NOT NULL THEN 'Payée'
                           WHEN fs.date_echeance < NOW() THEN 'En retard'
                           ELSE 'En attente'
                       END as statut_paiement
                FROM factures_stripe fs
                WHERE fs.abonnement_id = ?
                ORDER BY fs.date_facture DESC
            ", [$id]);

            // Calculer les statistiques
            $stats = [
                'utilisateurs_inclus' => $subscription['nombre_utilisateurs_inclus'],
                'utilisateurs_actuels' => $subscription['nombre_utilisateurs_actuels'],
                'utilisateurs_supplementaires' => max(0, $subscription['nombre_utilisateurs_actuels'] - $subscription['nombre_utilisateurs_inclus']),
                'prix_base' => $subscription['prix_total_mensuel'] - (max(0, $subscription['nombre_utilisateurs_actuels'] - $subscription['nombre_utilisateurs_inclus']) * ($subscription['cout_utilisateur_supplementaire'] ?? 0)),
                'prix_utilisateurs_supp' => max(0, $subscription['nombre_utilisateurs_actuels'] - $subscription['nombre_utilisateurs_inclus']) * ($subscription['cout_utilisateur_supplementaire'] ?? 0),
                'total_factures' => array_sum(array_column($invoices, 'montant')),
                'factures_payees' => count(array_filter($invoices, fn($i) => $i['date_paiement'])),
                'materiels_loues' => count($materials)
            ];

            $GLOBALS['subscription'] = $subscription;
            $GLOBALS['materials'] = $materials;
            $GLOBALS['invoices'] = $invoices;
            $GLOBALS['stats'] = $stats;
            $GLOBALS['pageTitle'] = 'Abonnement - ' . $subscription['raison_sociale'];

            require_once 'app/Views/subscriptions/client-subscriptions/show.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération de l'abonnement", [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement de l\'abonnement');
            header('Location: /subscriptions');
            exit;
        }
    }

    public function edit($id)
    {
        try {
            $subscription = Database::fetch("
                SELECT ac.*, c.raison_sociale, fa.nom as formule_nom
                FROM abonnements_clients ac
                JOIN clients c ON ac.client_id = c.id
                JOIN formules_abonnement fa ON ac.formule_id = fa.id
                WHERE ac.id = ?
            ", [$id]);

            if (!$subscription) {
                Session::setFlash('error', 'Abonnement non trouvé');
                header('Location: /subscriptions');
                exit;
            }

            // Récupérer les formules disponibles
            $formules = Database::fetchAll("
                SELECT fa.*, mm.nom as materiel_nom
                FROM formules_abonnement fa
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                WHERE fa.actif = 1
                ORDER BY fa.type_abonnement, fa.nom
            ");

            $GLOBALS['subscription'] = $subscription;
            $GLOBALS['formules'] = $formules;
            $GLOBALS['pageTitle'] = 'Modifier l\'abonnement';

            require_once 'app/Views/subscriptions/client-subscriptions/edit.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors du chargement de l'abonnement pour édition", [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement de l\'abonnement');
            header('Location: /subscriptions');
            exit;
        }
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /subscriptions/' . $id . '/edit');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /subscriptions/' . $id . '/edit');
            exit;
        }

        try {
            $subscription = Database::fetch("SELECT * FROM abonnements_clients WHERE id = ?", [$id]);
            if (!$subscription) {
                Session::setFlash('error', 'Abonnement non trouvé');
                header('Location: /subscriptions');
                exit;
            }

            Database::beginTransaction();

            // Mettre à jour l'abonnement
            $data = [
                'nombre_utilisateurs_actuels' => (int)$_POST['nombre_utilisateurs_actuels'],
                'date_modification' => date('Y-m-d H:i:s')
            ];

            // Recalculer le prix si nécessaire
            if (isset($_POST['recalculate_price'])) {
                $formule = Database::fetch("SELECT * FROM formules_abonnement WHERE id = ?", [$subscription['formule_id']]);
                if ($formule) {
                    $utilisateursSupp = max(0, $data['nombre_utilisateurs_actuels'] - $formule['nombre_utilisateurs_inclus']);
                    $data['prix_total_mensuel'] = $formule['prix_base'] + ($utilisateursSupp * ($formule['cout_utilisateur_supplementaire'] ?? 0));
                }
            }

            Database::update('abonnements_clients', $data, 'id = ?', [$id]);

            // Mettre à jour sur Stripe si nécessaire
            if (!empty($subscription['stripe_subscription_id'])) {
                try {
                    // Logique de mise à jour Stripe selon les changements
                    Logger::info('Mise à jour Stripe requise', [
                        'subscription_id' => $id,
                        'stripe_subscription_id' => $subscription['stripe_subscription_id']
                    ]);
                } catch (\Exception $e) {
                    Logger::warning('Erreur mise à jour Stripe', [
                        'subscription_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Database::commit();

            Logger::info('Abonnement modifié', [
                'subscription_id' => $id,
                'changes' => $data
            ]);

            Session::setFlash('success', 'Abonnement modifié avec succès');
            header('Location: /subscriptions/' . $id);
            exit;

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la modification de l'abonnement", [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la modification de l\'abonnement');
            header('Location: /subscriptions/' . $id . '/edit');
            exit;
        }
    }

    public function cancel($id)
    {
        try {
            $subscription = Database::fetch("SELECT * FROM abonnements_clients WHERE id = ?", [$id]);
            if (!$subscription) {
                Session::setFlash('error', 'Abonnement non trouvé');
                header('Location: /subscriptions');
                exit;
            }

            if ($subscription['statut'] !== 'actif') {
                Session::setFlash('error', 'Seuls les abonnements actifs peuvent être annulés');
                header('Location: /subscriptions/' . $id);
                exit;
            }

            Database::beginTransaction();

            // Annuler l'abonnement
            Database::update('abonnements_clients', [
                'statut' => 'annule',
                'date_modification' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            // Annuler sur Stripe
            if (!empty($subscription['stripe_subscription_id'])) {
                try {
                    // Logique d'annulation Stripe
                    Logger::info('Annulation Stripe demandée', [
                        'subscription_id' => $id,
                        'stripe_subscription_id' => $subscription['stripe_subscription_id']
                    ]);
                } catch (\Exception $e) {
                    Logger::error('Erreur annulation Stripe', [
                        'subscription_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Retourner le matériel loué
            Database::update('materiel_loue', [
                'statut' => 'retourne',
                'date_retour_effective' => date('Y-m-d')
            ], 'abonnement_id = ? AND statut = ?', [$id, 'loue']);

            Database::commit();

            Logger::info('Abonnement annulé', [
                'subscription_id' => $id
            ]);

            Session::setFlash('success', 'Abonnement annulé avec succès');

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de l'annulation de l'abonnement", [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de l\'annulation de l\'abonnement');
        }

        header('Location: /subscriptions/' . $id);
    }

    /**
     * Vérifier si le client a besoin de configurer un mode de paiement
     */
    private function checkPaymentMethodRequired($client, $totalAmount)
    {
        try {
            // Si pas de customer Stripe, on a besoin de setup
            if (empty($client['stripe_customer_id'])) {
                return true;
            }

            // Vérifier si le client a des modes de paiement valides
            $hasValidPaymentMethod = StripeService::hasValidPaymentMethod($client['stripe_customer_id']);
            
            if (!$hasValidPaymentMethod) {
                Logger::info('Client sans mode de paiement valide', [
                    'client_id' => $client['id'],
                    'stripe_customer_id' => $client['stripe_customer_id']
                ]);
                return true;
            }

            // Si montant important (> 100€), double vérification
            if ($totalAmount > 100) {
                $canProcessPayment = StripeService::canProcessPayment($client['stripe_customer_id'], $totalAmount);
                return !$canProcessPayment;
            }

            return false;

        } catch (\Exception $e) {
            Logger::warning('Erreur vérification mode de paiement', [
                'client_id' => $client['id'],
                'error' => $e->getMessage()
            ]);
            // En cas d'erreur, on assume qu'un setup est nécessaire pour sécurité
            return true;
        }
    }

    /**
     * Créer une session Stripe Checkout pour l'abonnement et le dépôt
     */
    private function createStripeCheckoutSession($client, $formule, $subscriptionId, $prixAbonnement, $depotGarantie, $nombreUtilisateurs)
    {
        try {
            // URLs de retour
            $successUrl = "https://heaven.cover-ar.com/subscriptions/checkout-success?subscription_id={$subscriptionId}&session_id={CHECKOUT_SESSION_ID}";
            $cancelUrl = "https://heaven.cover-ar.com/subscriptions/checkout-cancel?subscription_id={$subscriptionId}";

            // Créer les line items
            $lineItems = [];

            // 1. Abonnement principal
            if (!empty($formule['stripe_price_id'])) {
                $lineItems[] = [
                    'price' => $formule['stripe_price_id'],
                    'quantity' => 1
                ];

                // Utilisateurs supplémentaires si applicable
                if (!empty($formule['stripe_price_supplementaire_id'])) {
                    $utilisateursSupp = max(0, $nombreUtilisateurs - $formule['nombre_utilisateurs_inclus']);
                    if ($utilisateursSupp > 0) {
                        $lineItems[] = [
                            'price' => $formule['stripe_price_supplementaire_id'],
                            'quantity' => $utilisateursSupp
                        ];
                    }
                }

                // Utilisateurs supplémentaires si applicable
                if (!empty($formule['stripe_caution_id'])) {
                    $utilisateursSupp = max(0, $nombreUtilisateurs - $formule['nombre_utilisateurs_inclus']);
                    if ($utilisateursSupp > 0) {
                        $lineItems[] = [
                            'price' => $formule['stripe_caution_id'],
                            'quantity' => $utilisateursSupp + 1
                        ];
                    }
                }
            }

            // Créer la session Checkout
            $sessionData = [
                'customer' => $client['stripe_customer_id'] ?? null,
                'customer_email' => empty($client['stripe_customer_id']) ? $client['email_facturation'] : null,
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'allow_promotion_codes' => true,
                'billing_address_collection' => 'required',
                'payment_method_collection' => 'always',
                'metadata' => [
                    'subscription_id' => $subscriptionId,
                    'client_id' => $client['id'],
                    'formule_id' => $formule['id'],
                    'deposit_amount' => $depotGarantie,
                    'users_count' => $nombreUtilisateurs
                ]
            ];

            // Si pas de customer Stripe, on en créera un automatiquement
            if (empty($client['stripe_customer_id'])) {
                $sessionData['customer_creation'] = 'always';
            }

            $session = StripeService::createCheckoutSession($sessionData);

            Logger::info('Session Checkout créée avec dépôt', [
                'subscription_id' => $subscriptionId,
                'session_id' => $session->id,
                'total_amount' => $prixAbonnement + $depotGarantie,
                'deposit_amount' => $depotGarantie
            ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'url' => $session->url
            ];

        } catch (\Exception $e) {
            Logger::error('Erreur création session Checkout', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Créer un abonnement Stripe directement (client a déjà un mode de paiement)
     */
    private function createStripeSubscription($client, $formule, $subscriptionId, $nombreUtilisateurs)
    {
        try {
            if (empty($client['stripe_customer_id']) || empty($formule['stripe_price_id'])) {
                return [
                    'success' => false,
                    'error' => 'Données Stripe manquantes'
                ];
            }

            // Créer l'abonnement principal
            $subscriptionItems = [
                [
                    'price' => $formule['stripe_price_id'],
                    'quantity' => 1
                ]
            ];

            // Ajouter les utilisateurs supplémentaires si nécessaire
            if (!empty($formule['stripe_price_supplementaire_id'])) {
                $utilisateursSupp = max(0, $nombreUtilisateurs - $formule['nombre_utilisateurs_inclus']);
                if ($utilisateursSupp > 0) {
                    $subscriptionItems[] = [
                        'price' => $formule['stripe_price_supplementaire_id'],
                        'quantity' => $utilisateursSupp
                    ];
                }
            }

            $stripeSubscription = StripeService::createSubscription(
                $client['stripe_customer_id'],
                $subscriptionItems,
                [
                    'subscription_id' => $subscriptionId,
                    'client_id' => $client['id'],
                    'formule_id' => $formule['id'],
                    'users_count' => $nombreUtilisateurs
                ]
            );

            Logger::info('Abonnement Stripe créé directement', [
                'subscription_id' => $subscriptionId,
                'stripe_subscription_id' => $stripeSubscription->id
            ]);

            return [
                'success' => true,
                'subscription_id' => $stripeSubscription->id
            ];

        } catch (\Exception $e) {
            Logger::error('Erreur création abonnement Stripe direct', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Créer une facture séparée pour le dépôt de garantie
     */
    private function createDepositInvoice($client, $subscriptionId, $depotAmount)
    {
        try {
            if (empty($client['stripe_customer_id']) || $depotAmount <= 0) {
                return false;
            }

            // Créer une invoice item pour le dépôt
            $invoiceItem = StripeService::createInvoiceItem(
                $client['stripe_customer_id'],
                $depotAmount,
                'Dépôt de garantie matériel',
                [
                    'subscription_id' => $subscriptionId,
                    'type' => 'deposit',
                    'auto_advance' => true
                ]
            );

            // Créer et finaliser la facture
            $invoice = StripeService::createAndPayInvoice($client['stripe_customer_id']);

            // Enregistrer en base
            Database::insert('factures_stripe', [
                'client_id' => $client['id'],
                'abonnement_id' => $subscriptionId,
                'stripe_invoice_id' => $invoice->id,
                'montant' => $depotAmount,
                'statut' => $invoice->status,
                'lien_telechargement' => $invoice->hosted_invoice_url,
                'date_facture' => date('Y-m-d'),
                'date_echeance' => $invoice->due_date ? date('Y-m-d', $invoice->due_date) : null,
                'type_facture' => 'depot_garantie',
                'date_creation' => date('Y-m-d H:i:s')
            ]);

            Logger::info('Facture dépôt de garantie créée', [
                'subscription_id' => $subscriptionId,
                'invoice_id' => $invoice->id,
                'amount' => $depotAmount
            ]);

            return true;

        } catch (\Exception $e) {
            Logger::error('Erreur création facture dépôt', [
                'subscription_id' => $subscriptionId,
                'amount' => $depotAmount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }


    /**
     * Gestion du retour de Stripe Checkout (succès)
     */
    public function checkoutSuccess()
    {
        $subscriptionId = (int)($_GET['subscription_id'] ?? 0);
        $sessionId = $_GET['session_id'] ?? '';

        if (!$subscriptionId || !$sessionId) {
            Session::setFlash('error', 'Paramètres de retour invalides');
            header('Location: /subscriptions');
            exit;
        }

        try {
            // Récupérer la session Stripe pour vérifier le paiement
            $session = StripeService::retrieveCheckoutSession($sessionId);
            
            if ($session->payment_status === 'paid') {
                // Mettre à jour l'abonnement
                Database::update('abonnements_clients', [
                    'statut' => 'actif',
                    'stripe_subscription_id' => $session->subscription,
                    'date_modification' => date('Y-m-d H:i:s')
                ], 'id = ?', [$subscriptionId]);

                // Mettre à jour le customer ID si nouveau client
                if ($session->customer) {
                    $subscription = Database::fetch("SELECT client_id FROM abonnements_clients WHERE id = ?", [$subscriptionId]);
                    if ($subscription) {
                        Database::update('clients', [
                            'stripe_customer_id' => $session->customer
                        ], 'id = ?', [$subscription['client_id']]);
                    }
                }

                Logger::info('Abonnement activé après paiement Checkout', [
                    'subscription_id' => $subscriptionId,
                    'session_id' => $sessionId
                ]);

                Session::setFlash('success', 'Abonnement créé et payé avec succès');
            } else {
                Session::setFlash('warning', 'Abonnement créé mais paiement en attente');
            }

        } catch (\Exception $e) {
            Logger::error('Erreur traitement retour Checkout', [
                'subscription_id' => $subscriptionId,
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('warning', 'Abonnement créé, vérification du paiement en cours');
        }

        header('Location: /subscriptions/' . $subscriptionId);
        exit;
    }

    /**
     * Gestion du retour de Stripe Checkout (annulation)
     */
    public function checkoutCancel()
    {
        $subscriptionId = (int)($_GET['subscription_id'] ?? 0);

        if (!$subscriptionId) {
            Session::setFlash('error', 'Paramètres de retour invalides');
            header('Location: /subscriptions');
            exit;
        }

        try {
            // Marquer l'abonnement comme annulé
            Database::update('abonnements_clients', [
                'statut' => 'annule',
                'date_modification' => date('Y-m-d H:i:s')
            ], 'id = ?', [$subscriptionId]);

            Logger::info('Abonnement annulé après échec paiement', [
                'subscription_id' => $subscriptionId
            ]);

            Session::setFlash('warning', 'Création d\'abonnement annulée - paiement non effectué');

        } catch (\Exception $e) {
            Logger::error('Erreur traitement annulation Checkout', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
        }

        header('Location: /subscriptions/create');
        exit;
    }

    public function suspend($id)
    {
        try {
            $subscription = Database::fetch("SELECT * FROM abonnements_clients WHERE id = ?", [$id]);
            if (!$subscription) {
                Session::setFlash('error', 'Abonnement non trouvé');
                header('Location: /subscriptions');
                exit;
            }

            if ($subscription['statut'] !== 'actif') {
                Session::setFlash('error', 'Seuls les abonnements actifs peuvent être suspendus');
                header('Location: /subscriptions/' . $id);
                exit;
            }

            Database::update('abonnements_clients', [
                'statut' => 'suspendu',
                'date_modification' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            Logger::info('Abonnement suspendu', ['subscription_id' => $id]);

            Session::setFlash('success', 'Abonnement suspendu avec succès');

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la suspension de l'abonnement", [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la suspension de l\'abonnement');
        }

        header('Location: /subscriptions/' . $id);
        exit;
    }

    public function resume($id)
    {
        try {
            $subscription = Database::fetch("SELECT * FROM abonnements_clients WHERE id = ?", [$id]);
            if (!$subscription) {
                Session::setFlash('error', 'Abonnement non trouvé');
                header('Location: /subscriptions');
                exit;
            }

            if ($subscription['statut'] !== 'suspendu') {
                Session::setFlash('error', 'Seuls les abonnements suspendus peuvent être réactivés');
                header('Location: /subscriptions/' . $id);
                exit;
            }

            Database::update('abonnements_clients', [
                'statut' => 'actif',
                'date_modification' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            Logger::info('Abonnement réactivé', ['subscription_id' => $id]);

            Session::setFlash('success', 'Abonnement réactivé avec succès');

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la réactivation de l'abonnement", [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la réactivation de l\'abonnement');
        }

        header('Location: /subscriptions/' . $id);
        exit;
    }

    private function validateSubscriptionData($data)
    {
        $errors = [];

        // Validation du client
        if (empty($data['client_id'])) {
            $errors[] = 'Le client est obligatoire';
        } else {
            $client = Database::fetch("SELECT id FROM clients WHERE id = ? AND actif = 1", [$data['client_id']]);
            if (!$client) {
                $errors[] = 'Client invalide ou inactif';
            }
        }

        // Validation de la formule
        if (empty($data['formule_id'])) {
            $errors[] = 'La formule d\'abonnement est obligatoire';
        } else {
            $formule = Database::fetch("SELECT id FROM formules_abonnement WHERE id = ? AND actif = 1", [$data['formule_id']]);
            if (!$formule) {
                $errors[] = 'Formule d\'abonnement invalide ou inactive';
            }
        }

        // Validation du nombre d'utilisateurs
        if (!empty($data['nombre_utilisateurs']) && (!is_numeric($data['nombre_utilisateurs']) || $data['nombre_utilisateurs'] < 1)) {
            $errors[] = 'Le nombre d\'utilisateurs doit être un nombre positif';
        }

        // Validation de la date de début
        if (!empty($data['date_debut'])) {
            $dateDebut = \DateTime::createFromFormat('Y-m-d', $data['date_debut']);
            if (!$dateDebut) {
                $errors[] = 'Format de date de début invalide';
            }
        }

        // Validation du type d'abonnement
        if (!empty($data['type_abonnement']) && !in_array($data['type_abonnement'], ['principal', 'supplementaire'])) {
            $errors[] = 'Type d\'abonnement invalide';
        }

        return $errors;
    }

    private function createMaterialRental($subscriptionId, $clientId, $formule)
    {
        try {
            $materialData = [
                'client_id' => $clientId,
                'abonnement_id' => $subscriptionId,
                'modele_materiel_id' => $formule['modele_materiel_id'],
                'inclus_dans_abonnement' => 1,
                'statut' => 'loue',
                'date_location' => date('Y-m-d'),
                'depot_verse' => 0, // Pas de dépôt pour matériel inclus
                'date_creation' => date('Y-m-d H:i:s')
            ];

            $rentalId = Database::insert('materiel_loue', $materialData);

            Logger::info('Location matériel créée', [
                'rental_id' => $rentalId,
                'subscription_id' => $subscriptionId,
                'material_id' => $formule['modele_materiel_id']
            ]);

            return $rentalId;

        } catch (\Exception $e) {
            Logger::error('Erreur création location matériel', [
                'subscription_id' => $subscriptionId,
                'material_id' => $formule['modele_materiel_id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * API pour récupérer les informations d'une formule
     */
    public function getFormuleInfo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }

        header('Content-Type: application/json');

        try {
            $formuleId = (int)($_GET['formule_id'] ?? 0);
            
            if (!$formuleId) {
                echo json_encode(['error' => 'ID de formule requis']);
                exit;
            }

            $formule = Database::fetch("
                SELECT fa.*, mm.nom as materiel_nom, mm.prix_mensuel as materiel_prix
                FROM formules_abonnement fa
                LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
                WHERE fa.id = ? AND fa.actif = 1
            ", [$formuleId]);

            if (!$formule) {
                echo json_encode(['error' => 'Formule non trouvée']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'formule' => $formule
            ]);

        } catch (\Exception $e) {
            Logger::error('Erreur API getFormuleInfo', [
                'error' => $e->getMessage(),
                'formule_id' => $_GET['formule_id'] ?? null
            ]);
            
            echo json_encode(['error' => 'Erreur serveur']);
        }

        exit;
    }

    /**
     * API pour calculer le prix d'un abonnement
     */
    public function calculatePrice()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            exit;
        }

        header('Content-Type: application/json');

        try {
            $formuleId = (int)($_POST['formule_id'] ?? 0);
            $nombreUtilisateurs = (int)($_POST['nombre_utilisateurs'] ?? 1);

            if (!$formuleId) {
                echo json_encode(['error' => 'ID de formule requis']);
                exit;
            }

            $formule = Database::fetch("
                SELECT * FROM formules_abonnement WHERE id = ? AND actif = 1
            ", [$formuleId]);

            if (!$formule) {
                echo json_encode(['error' => 'Formule non trouvée']);
                exit;
            }

            // Calculer le prix
            $utilisateursInclus = $formule['nombre_utilisateurs_inclus'];
            $utilisateursSupplementaires = max(0, $nombreUtilisateurs - $utilisateursInclus);
            $coutUtilisateurSupp = $formule['cout_utilisateur_supplementaire'] ?? 0;

            $prixBase = $formule['prix_base'];
            $prixUtilisateursSupp = $utilisateursSupplementaires * $coutUtilisateurSupp;
            $prixTotal = $prixBase + $prixUtilisateursSupp;

            echo json_encode([
                'success' => true,
                'pricing' => [
                    'utilisateurs_inclus' => $utilisateursInclus,
                    'utilisateurs_supplementaires' => $utilisateursSupplementaires,
                    'prix_base' => $prixBase,
                    'prix_utilisateurs_supplementaires' => $prixUtilisateursSupp,
                    'prix_total' => $prixTotal,
                    'duree' => $formule['duree']
                ]
            ]);

        } catch (\Exception $e) {
            Logger::error('Erreur API calculatePrice', [
                'error' => $e->getMessage(),
                'post_data' => $_POST
            ]);
            
            echo json_encode(['error' => 'Erreur serveur']);
        }

        exit;
    }
}
?>