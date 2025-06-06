<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;
use App\Helpers\Pagination;

class ClientController
{
    public function index()
    {
        try {
            // Paramètres de recherche et filtres
            $search = $_GET['search'] ?? '';
            $filters = [
                'status' => $_GET['status'] ?? '',
                'country' => $_GET['country'] ?? '',
                'subscription_status' => $_GET['subscription_status'] ?? ''
            ];

            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 20;

            // Construire la requête de base
            $baseQuery = "
                SELECT c.*, 
                       COUNT(DISTINCT u.id) as nb_users,
                       COUNT(DISTINCT ac.id) as nb_subscriptions,
                       GROUP_CONCAT(DISTINCT fa.nom SEPARATOR ', ') as formules_souscrites,
                       MAX(ac.date_creation) as derniere_souscription
                FROM clients c
                LEFT JOIN utilisateurs u ON c.id = u.client_id AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin'
                LEFT JOIN abonnements_clients ac ON c.id = ac.client_id
                LEFT JOIN formules_abonnement fa ON ac.formule_id = fa.id
            ";

            $params = [];
            $whereConditions = [];

            // Filtres de recherche
            if (!empty($search)) {
                $whereConditions[] = "(c.raison_sociale LIKE ? OR c.email_facturation LIKE ? OR c.ville LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if ($filters['status'] !== '') {
                $whereConditions[] = "c.actif = ?";
                $params[] = (int)$filters['status'];
            }

            if (!empty($filters['country'])) {
                $whereConditions[] = "c.pays = ?";
                $params[] = $filters['country'];
            }

            // Assembler la requête
            $query = $baseQuery;
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }

            $query .= " GROUP BY c.id, c.raison_sociale, c.email_facturation, c.actif, c.date_creation";

            // Filtrer par statut d'abonnement après GROUP BY
            if (!empty($filters['subscription_status'])) {
                if ($filters['subscription_status'] === 'active') {
                    $query .= " HAVING COUNT(CASE WHEN ac.statut = 'actif' THEN 1 END) > 0";
                } elseif ($filters['subscription_status'] === 'inactive') {
                    $query .= " HAVING COUNT(CASE WHEN ac.statut = 'actif' THEN 1 END) = 0";
                }
            }

            $query .= " ORDER BY c.date_creation DESC";

            // Pagination manuelle simple
            $offset = ($page - 1) * $perPage;
            $paginatedQuery = $query . " LIMIT {$perPage} OFFSET {$offset}";
            
            // Récupérer les résultats
            $clients = Database::fetchAll($paginatedQuery, $params);

            // Compter le total pour la pagination
            $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as subquery";
            $totalResult = Database::fetch($countQuery, $params);
            $totalItems = $totalResult['total'] ?? 0;
            $totalPages = ceil($totalItems / $perPage);

            // Créer une classe pagination personnalisée
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
                'total_clients' => Database::fetch("SELECT COUNT(*) as count FROM clients")['count'] ?? 0,
                'active_clients' => Database::fetch("SELECT COUNT(*) as count FROM clients WHERE actif = 1")['count'] ?? 0,
                'with_subscriptions' => Database::fetch("
                    SELECT COUNT(DISTINCT c.id) as count 
                    FROM clients c 
                    JOIN abonnements_clients ac ON c.id = ac.client_id 
                    WHERE ac.statut = 'actif'
                ")['count'] ?? 0,
                'new_this_month' => Database::fetch("
                    SELECT COUNT(*) as count 
                    FROM clients 
                    WHERE DATE_FORMAT(date_creation, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                ")['count'] ?? 0
            ];

            // Pays pour le filtre
            $countries = Database::fetchAll("
                SELECT DISTINCT pays 
                FROM clients 
                WHERE pays IS NOT NULL AND pays != ''
                ORDER BY pays
            ");

            // Définir les variables globales
            $GLOBALS['clients'] = $clients;
            $GLOBALS['stats'] = $stats;
            $GLOBALS['countries'] = $countries;
            $GLOBALS['pagination'] = $pagination;
            $GLOBALS['search'] = $search;
            $GLOBALS['filters'] = $filters;
            $GLOBALS['pageTitle'] = 'Gestion des clients';

            require_once 'app/Views/clients/index.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération des clients", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Session::setFlash('error', 'Erreur lors du chargement des clients');
            
            // Données par défaut
            $GLOBALS['clients'] = [];
            $GLOBALS['stats'] = [
                'total_clients' => 0,
                'active_clients' => 0,
                'with_subscriptions' => 0,
                'new_this_month' => 0
            ];
            $GLOBALS['countries'] = [];
            $GLOBALS['pagination'] = null;
            $GLOBALS['search'] = '';
            $GLOBALS['filters'] = [];
            $GLOBALS['pageTitle'] = 'Gestion des clients';
            
            require_once 'app/Views/clients/index.php';
        }
    }       

    public function create()
    {
        $pageTitle = 'Nouveau client';
        require_once 'app/Views/clients/create.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clients/create');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /clients/create');
            exit;
        }

        // Validation des données
        $errors = $this->validateClientData($_POST);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            header('Location: /clients/create');
            exit;
        }

        try {
            Database::beginTransaction();

            // Préparer les données client
            $clientData = [
                'raison_sociale' => trim($_POST['raison_sociale']),
                'adresse' => trim($_POST['adresse']),
                'code_postal' => trim($_POST['code_postal']),
                'ville' => trim($_POST['ville']),
                'pays' => trim($_POST['pays']),
                'email_facturation' => trim($_POST['email_facturation']),
                'numero_tva' => !empty($_POST['numero_tva']) ? trim($_POST['numero_tva']) : null,
                'actif' => 1,
                'date_creation' => date('Y-m-d H:i:s')
            ];

            // Créer le client
            $clientId = Database::insert('clients', $clientData);

            // Créer le client sur Stripe
            try {
                $stripeCustomer = \App\Services\StripeService::createCustomer($clientData);
                Database::update('clients', 
                    ['stripe_customer_id' => $stripeCustomer->id], 
                    'id = ?', 
                    [$clientId]
                );
                
                Logger::info('Client Stripe créé', [
                    'client_id' => $clientId,
                    'stripe_customer_id' => $stripeCustomer->id
                ]);
            } catch (\Exception $e) {
                Logger::warning('Échec création client Stripe', [
                    'client_id' => $clientId,
                    'error' => $e->getMessage()
                ]);
                // Ne pas faire échouer la création du client
            }

            Database::commit();

            Logger::info('Nouveau client créé', [
                'client_id' => $clientId,
                'raison_sociale' => $clientData['raison_sociale']
            ]);

            Session::setFlash('success', 'Client créé avec succès');
            header('Location: /clients/' . $clientId);
            exit;

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la création du client", [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            Session::setFlash('error', 'Erreur lors de la création du client');
            header('Location: /clients/create');
            exit;
        }
    }

    public function show($id)
    {
        try {
            // Récupérer le client
            $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$id]);
            if (!$client) {
                Session::setFlash('error', 'Client non trouvé');
                header('Location: /clients');
                exit;
            }

            // Utilisateurs du client
            $users = Database::fetchAll("
                SELECT u.*, 
                       CASE WHEN u.identifiant_appareil IS NOT NULL THEN 'Connecté' ELSE 'Non connecté' END as statut_connexion
                FROM utilisateurs u
                WHERE u.client_id = ? AND u.type_utilisateur != 'MegaAdmin'
                ORDER BY u.type_utilisateur, u.nom, u.prenom
            ", [$id]);

            // Abonnements du client
            $subscriptions = Database::fetchAll("
                SELECT ac.*, fa.nom as formule_nom, fa.type_abonnement,
                       DATE_ADD(ac.date_debut, INTERVAL 1 MONTH) as prochaine_facture
                FROM abonnements_clients ac
                JOIN formules_abonnement fa ON ac.formule_id = fa.id
                WHERE ac.client_id = ?
                ORDER BY ac.date_creation DESC
            ", [$id]);

            // Matériel loué
            $materials = Database::fetchAll("
                SELECT ml.*, mm.nom as materiel_nom, mm.prix_mensuel,
                       CASE WHEN ml.inclus_dans_abonnement = 1 THEN 'Inclus' ELSE 'Séparé' END as type_location
                FROM materiel_loue ml
                JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
                WHERE ml.client_id = ?
                ORDER BY ml.date_creation DESC
            ", [$id]);

            // Catégories autorisées
            $categories = Database::fetchAll("
                SELECT sc.nom as sous_categorie, c.nom as categorie
                FROM client_sous_categories csc
                JOIN sous_categories sc ON csc.sous_categorie_id = sc.id
                JOIN categories c ON sc.categorie_id = c.id
                WHERE csc.client_id = ?
                ORDER BY c.nom, sc.nom
            ", [$id]);

            // Factures
            $invoices = Database::fetchAll("
                SELECT fs.*, 
                       CASE WHEN fs.date_paiement IS NOT NULL THEN 'Payée' 
                            WHEN fs.date_echeance < NOW() THEN 'En retard'
                            ELSE 'En attente' END as statut_paiement
                FROM factures_stripe fs
                WHERE fs.client_id = ?
                ORDER BY fs.date_facture DESC
                LIMIT 10
            ", [$id]);

            // Statistiques du client
            $client_stats = [
                'total_users' => count($users),
                'active_users' => count(array_filter($users, fn($u) => $u['actif'])),
                'total_subscriptions' => count($subscriptions),
                'active_subscriptions' => count(array_filter($subscriptions, fn($s) => $s['statut'] === 'actif')),
                'total_materials' => count($materials),
                'active_materials' => count(array_filter($materials, fn($m) => $m['statut'] === 'loue')),
                'total_categories' => count($categories),
                'revenue_mensuel' => array_sum(array_column(
                    array_filter($subscriptions, fn($s) => $s['statut'] === 'actif'),
                    'prix_total_mensuel'
                ))
            ];

            // Définir les variables globales
            $GLOBALS['client'] = $client;
            $GLOBALS['users'] = $users;
            $GLOBALS['subscriptions'] = $subscriptions;
            $GLOBALS['materials'] = $materials;
            $GLOBALS['categories'] = $categories;
            $GLOBALS['invoices'] = $invoices;
            $GLOBALS['client_stats'] = $client_stats;
            $GLOBALS['pageTitle'] = 'Client - ' . $client['raison_sociale'];

            require_once 'app/Views/clients/show.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du client", [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du client');
            header('Location: /clients');
            exit;
        }
    }

    public function edit($id)
    {
        try {
            $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$id]);
            if (!$client) {
                Session::setFlash('error', 'Client non trouvé');
                header('Location: /clients');
                exit;
            }

            $GLOBALS['client'] = $client;
            $GLOBALS['pageTitle'] = 'Modifier le client - ' . $client['raison_sociale'];

            require_once 'app/Views/clients/edit.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la récupération du client pour édition", [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors du chargement du client');
            header('Location: /clients');
            exit;
        }
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clients/' . $id . '/edit');
            exit;
        }

        // Vérification CSRF
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /clients/' . $id . '/edit');
            exit;
        }

        // Vérifier que le client existe
        $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$id]);
        if (!$client) {
            Session::setFlash('error', 'Client non trouvé');
            header('Location: /clients');
            exit;
        }

        // Validation des données
        $errors = $this->validateClientData($_POST, $id);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::setFlash('error', $error);
            }
            header('Location: /clients/' . $id . '/edit');
            exit;
        }

        try {
            Database::beginTransaction();

            // Préparer les données
            $data = [
                'raison_sociale' => trim($_POST['raison_sociale']),
                'adresse' => trim($_POST['adresse']),
                'code_postal' => trim($_POST['code_postal']),
                'ville' => trim($_POST['ville']),
                'pays' => trim($_POST['pays']),
                'email_facturation' => trim($_POST['email_facturation']),
                'numero_tva' => !empty($_POST['numero_tva']) ? trim($_POST['numero_tva']) : null,
                'date_modification' => date('Y-m-d H:i:s')
            ];

            // Mettre à jour le client
            Database::update('clients', $data, 'id = ?', [$id]);

            // Mettre à jour sur Stripe si nécessaire
            if (!empty($client['stripe_customer_id'])) {
                try {
                    \App\Services\StripeService::updateCustomer($client['stripe_customer_id'], $data);
                } catch (\Exception $e) {
                    Logger::warning('Échec mise à jour client Stripe', [
                        'client_id' => $id,
                        'stripe_customer_id' => $client['stripe_customer_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Database::commit();

            Logger::info('Client modifié', [
                'client_id' => $id,
                'raison_sociale' => $data['raison_sociale'],
                'changes' => array_diff_assoc($data, $client)
            ]);

            Session::setFlash('success', 'Client modifié avec succès');
            header('Location: /clients/' . $id);
            exit;

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la modification du client", [
                'client_id' => $id,
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            Session::setFlash('error', 'Erreur lors de la modification du client');
            header('Location: /clients/' . $id . '/edit');
            exit;
        }
    }

    public function toggleStatus($id)
    {
        try {
            $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$id]);
            if (!$client) {
                Session::setFlash('error', 'Client non trouvé');
                header('Location: /clients');
                exit;
            }

            // Vérifier s'il y a des abonnements actifs
            if ($client['actif']) {
                $activeSubscriptions = Database::fetch("
                    SELECT COUNT(*) as count 
                    FROM abonnements_clients 
                    WHERE client_id = ? AND statut = 'actif'
                ", [$id]);

                if ($activeSubscriptions['count'] > 0) {
                    Session::setFlash('error', 'Impossible de désactiver un client avec des abonnements actifs');
                    header('Location: /clients');
                    exit;
                }
            }

            $newStatus = $client['actif'] ? 0 : 1;
            Database::update('clients', ['actif' => $newStatus], 'id = ?', [$id]);

            // Désactiver/activer aussi les utilisateurs du client
            Database::query("
                UPDATE utilisateurs 
                SET actif = ? 
                WHERE client_id = ? AND type_utilisateur != 'MegaAdmin'
            ", [$newStatus, $id]);

            $statusText = $newStatus ? 'activé' : 'désactivé';
            Session::setFlash('success', "Client {$statusText} avec succès");

            Logger::info('Statut client modifié', [
                'client_id' => $id,
                'old_status' => $client['actif'],
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la modification du statut", [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la modification du statut');
        }

        header('Location: /clients');
        exit;
    }

    public function delete($id)
    {
        try {
            $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$id]);
            if (!$client) {
                Session::setFlash('error', 'Client non trouvé');
                header('Location: /clients');
                exit;
            }

            // Vérifier qu'aucun abonnement n'existe
            $subscriptionsCount = Database::fetch("
                SELECT COUNT(*) as count 
                FROM abonnements_clients 
                WHERE client_id = ?
            ", [$id]);

            if ($subscriptionsCount['count'] > 0) {
                Session::setFlash('error', 'Impossible de supprimer un client avec un historique d\'abonnements (règles comptables)');
                header('Location: /clients');
                exit;
            }

            Database::beginTransaction();

            // Supprimer les utilisateurs associés
            Database::delete('utilisateurs', 'client_id = ?', [$id]);

            // Supprimer les associations catégories
            Database::delete('client_sous_categories', 'client_id = ?', [$id]);

            // Supprimer le client (cascade pour le matériel loué)
            Database::delete('clients', 'id = ?', [$id]);

            Database::commit();

            Logger::info('Client supprimé', [
                'client_id' => $id,
                'raison_sociale' => $client['raison_sociale']
            ]);

            Session::setFlash('success', 'Client supprimé avec succès');

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error("Erreur lors de la suppression du client", [
                'client_id' => $id,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Erreur lors de la suppression du client');
        }

        header('Location: /clients');
        exit;
    }

    private function validateClientData($data, $id = null)
    {
        $errors = [];

        // Validation de la raison sociale
        if (empty($data['raison_sociale'])) {
            $errors[] = 'La raison sociale est obligatoire';
        } elseif (strlen($data['raison_sociale']) > 255) {
            $errors[] = 'La raison sociale ne peut pas dépasser 255 caractères';
        }

        // Validation de l'email
        if (empty($data['email_facturation'])) {
            $errors[] = 'L\'email de facturation est obligatoire';
        } elseif (!filter_var($data['email_facturation'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email de facturation n\'est pas valide';
        } else {
            // Vérifier l'unicité de l'email
            $emailQuery = "SELECT id FROM clients WHERE email_facturation = ?";
            $emailParams = [$data['email_facturation']];
            
            if ($id) {
                $emailQuery .= " AND id != ?";
                $emailParams[] = $id;
            }
            
            $existingClient = Database::fetch($emailQuery, $emailParams);
            if ($existingClient) {
                $errors[] = 'Cet email de facturation est déjà utilisé';
            }
        }

        // Validation de l'adresse
        if (empty($data['adresse'])) {
            $errors[] = 'L\'adresse est obligatoire';
        }

        // Validation du code postal
        if (empty($data['code_postal'])) {
            $errors[] = 'Le code postal est obligatoire';
        } elseif (strlen($data['code_postal']) > 10) {
            $errors[] = 'Le code postal ne peut pas dépasser 10 caractères';
        }

        // Validation de la ville
        if (empty($data['ville'])) {
            $errors[] = 'La ville est obligatoire';
        } elseif (strlen($data['ville']) > 100) {
            $errors[] = 'La ville ne peut pas dépasser 100 caractères';
        }

        // Validation du pays
        if (empty($data['pays'])) {
            $errors[] = 'Le pays est obligatoire';
        } elseif (strlen($data['pays']) > 100) {
            $errors[] = 'Le pays ne peut pas dépasser 100 caractères';
        }

        // Validation du numéro TVA (optionnel)
        if (!empty($data['numero_tva'])) {
            if (strlen($data['numero_tva']) > 50) {
                $errors[] = 'Le numéro TVA ne peut pas dépasser 50 caractères';
            }
            
            // Validation basique du format TVA européenne
            if ($data['pays'] !== 'France' && !preg_match('/^[A-Z]{2}[A-Z0-9]+$/', $data['numero_tva'])) {
                $errors[] = 'Le format du numéro TVA intracommunautaire n\'est pas valide';
            }
        }

        return $errors;
    }
}
?>