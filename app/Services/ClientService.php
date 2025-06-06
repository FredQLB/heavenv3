<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Logger;
use App\Models\Client;

class ClientService
{
    private $clientModel;

    public function __construct()
    {
        $this->clientModel = new Client();
    }

    /**
     * Créer un client complet avec utilisateur admin et intégration Stripe
     */
    public function createCompleteClient($clientData, $options = [])
    {
        try {
            Database::beginTransaction();

            // 1. Créer le client
            $clientId = $this->clientModel->create($clientData);
            
            // 2. Créer le client sur Stripe
            $stripeCustomer = null;
            try {
                $stripeCustomer = \App\Services\StripeService::createCustomer(array_merge($clientData, ['id' => $clientId]));
                Database::update('clients', 
                    ['stripe_customer_id' => $stripeCustomer->id], 
                    'id = ?', 
                    [$clientId]
                );
                
                Logger::info('Client Stripe créé avec succès', [
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

            // 3. Créer un utilisateur administrateur si demandé
            if ($options['create_admin_user'] ?? false) {
                $adminUser = $this->createAdminUser($clientId, $clientData['email_facturation']);
                Logger::info('Utilisateur admin créé', [
                    'client_id' => $clientId,
                    'user_id' => $adminUser['id']
                ]);
            }

            // 4. Envoyer l'email de bienvenue si demandé
            if ($options['send_welcome_email'] ?? false) {
                $this->sendWelcomeEmail($clientId, $clientData);
            }

            Database::commit();

            return [
                'client_id' => $clientId,
                'stripe_customer' => $stripeCustomer,
                'admin_user' => $adminUser ?? null
            ];

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error('Erreur lors de la création complète du client', [
                'error' => $e->getMessage(),
                'client_data' => $clientData,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un utilisateur administrateur pour le client
     */
    private function createAdminUser($clientId, $email)
    {
        $password = $this->generateSecurePassword();
        
        $userData = [
            'client_id' => $clientId,
            'nom' => 'Administrateur',
            'prenom' => 'Principal',
            'email' => $email,
            'telephone' => null,
            'mot_de_passe' => md5($password),
            'identifiant_appareil' => null,
            'type_utilisateur' => 'Admin',
            'actif' => 1,
            'date_creation' => date('Y-m-d H:i:s')
        ];

        $userId = Database::insert('utilisateurs', $userData);

        // Envoyer les identifiants par email
        try {
            $this->sendCredentialsEmail($email, $password);
        } catch (\Exception $e) {
            Logger::warning('Échec envoi email identifiants', [
                'user_id' => $userId,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }

        return array_merge($userData, ['id' => $userId, 'password' => $password]);
    }

    /**
     * Générer un mot de passe sécurisé
     */
    private function generateSecurePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Calculer les statistiques d'un client
     */
    public function getClientStats($clientId)
    {
        $stats = [];

        // Utilisateurs
        $stats['users'] = Database::fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN type_utilisateur = 'Admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN identifiant_appareil IS NOT NULL THEN 1 ELSE 0 END) as connected
            FROM utilisateurs 
            WHERE client_id = ? AND type_utilisateur != 'MegaAdmin'
        ", [$clientId]);

        // Abonnements
        $stats['subscriptions'] = Database::fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN statut = 'actif' THEN prix_total_mensuel ELSE 0 END) as monthly_revenue,
                MAX(date_creation) as latest_subscription
            FROM abonnements_clients 
            WHERE client_id = ?
        ", [$clientId]);

        // Matériel
        $stats['materials'] = Database::fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'loue' THEN 1 ELSE 0 END) as active_rentals,
                SUM(CASE WHEN inclus_dans_abonnement = 1 THEN 1 ELSE 0 END) as included,
                SUM(depot_verse) as total_deposits
            FROM materiel_loue 
            WHERE client_id = ?
        ", [$clientId]);

        // Catégories
        $stats['categories'] = Database::fetch("
            SELECT COUNT(*) as total
            FROM client_sous_categories 
            WHERE client_id = ?
        ", [$clientId]);

        // Factures
        $stats['invoices'] = Database::fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN date_paiement IS NOT NULL THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN date_echeance < NOW() AND date_paiement IS NULL THEN 1 ELSE 0 END) as overdue,
                SUM(montant) as total_amount
            FROM factures_stripe 
            WHERE client_id = ?
        ", [$clientId]);

        return $stats;
    }

    /**
     * Vérifier les limites d'utilisateurs pour un client
     */
    public function checkUserLimits($clientId)
    {
        $result = Database::fetch("CALL VerifierLimiteUtilisateurs(?)", [$clientId]);
        
        return [
            'limit' => $result['limite_utilisateurs'] ?? 0,
            'current' => $result['utilisateurs_actuels'] ?? 0,
            'available' => $result['utilisateurs_disponibles'] ?? 0,
            'status' => $result['statut'] ?? 'Inconnu',
            'over_limit' => ($result['utilisateurs_actuels'] ?? 0) > ($result['limite_utilisateurs'] ?? 0)
        ];
    }

    /**
     * Gérer les catégories d'un client
     */
    public function updateClientCategories($clientId, $categoryIds)
    {
        try {
            Database::beginTransaction();

            // Supprimer les anciennes associations
            Database::delete('client_sous_categories', 'client_id = ?', [$clientId]);

            // Ajouter les nouvelles associations
            foreach ($categoryIds as $categoryId) {
                Database::insert('client_sous_categories', [
                    'client_id' => $clientId,
                    'sous_categorie_id' => $categoryId,
                    'date_ajout' => date('Y-m-d H:i:s')
                ]);
            }

            Database::commit();

            Logger::info('Catégories client mises à jour', [
                'client_id' => $clientId,
                'categories_count' => count($categoryIds)
            ]);

            return true;

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error('Erreur mise à jour catégories client', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Calculer le prochain paiement estimé pour un client
     */
    public function getNextPaymentEstimate($clientId)
    {
        $activeSubscriptions = Database::fetchAll("
            SELECT ac.*, fa.duree, fa.prix_base
            FROM abonnements_clients ac
            JOIN formules_abonnement fa ON ac.formule_id = fa.id
            WHERE ac.client_id = ? AND ac.statut = 'actif'
        ", [$clientId]);

        if (empty($activeSubscriptions)) {
            return null;
        }

        $totalAmount = 0;
        $nextPaymentDate = null;

        foreach ($activeSubscriptions as $subscription) {
            $totalAmount += $subscription['prix_total_mensuel'];
            
            // Calculer la prochaine date de facturation
            $lastPayment = $subscription['date_debut'];
            $interval = $subscription['duree'] === 'mensuelle' ? '+1 month' : '+1 year';
            $nextDate = date('Y-m-d', strtotime($lastPayment . ' ' . $interval));
            
            if (!$nextPaymentDate || $nextDate < $nextPaymentDate) {
                $nextPaymentDate = $nextDate;
            }
        }

        return [
            'amount' => $totalAmount,
            'date' => $nextPaymentDate,
            'subscriptions_count' => count($activeSubscriptions)
        ];
    }

    /**
     * Analyser l'activité d'un client
     */
    public function analyzeClientActivity($clientId, $period = '3months')
    {
        $periodCondition = match($period) {
            '1month' => "DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            '3months' => "DATE_SUB(NOW(), INTERVAL 3 MONTH)",
            '6months' => "DATE_SUB(NOW(), INTERVAL 6 MONTH)",
            '12months' => "DATE_SUB(NOW(), INTERVAL 12 MONTH)",
            default => "DATE_SUB(NOW(), INTERVAL 3 MONTH)"
        };

        // Connexions utilisateurs
        $userActivity = Database::fetchAll("
            SELECT u.nom, u.prenom, u.email,
                   COUNT(lc.id) as connections_count,
                   MAX(lc.date_connexion) as last_connection
            FROM utilisateurs u
            LEFT JOIN logs_connexion lc ON u.id = lc.utilisateur_id 
                AND lc.date_connexion >= {$periodCondition}
            WHERE u.client_id = ? AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin'
            GROUP BY u.id, u.nom, u.prenom, u.email
            ORDER BY connections_count DESC
        ", [$clientId]);

        // Évolution des abonnements
        $subscriptionActivity = Database::fetchAll("
            SELECT DATE_FORMAT(date_creation, '%Y-%m') as month,
                   COUNT(*) as new_subscriptions,
                   SUM(prix_total_mensuel) as revenue
            FROM abonnements_clients
            WHERE client_id = ? AND date_creation >= {$periodCondition}
            GROUP BY month
            ORDER BY month
        ", [$clientId]);

        // Utilisation du matériel
        $materialActivity = Database::fetchAll("
            SELECT mm.nom as material_name,
                   COUNT(ml.id) as rentals_count,
                   AVG(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as avg_duration
            FROM materiel_loue ml
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.client_id = ? AND ml.date_creation >= {$periodCondition}
            GROUP BY mm.id, mm.nom
            ORDER BY rentals_count DESC
        ", [$clientId]);

        return [
            'period' => $period,
            'user_activity' => $userActivity,
            'subscription_activity' => $subscriptionActivity,
            'material_activity' => $materialActivity,
            'summary' => [
                'active_users' => count(array_filter($userActivity, fn($u) => $u['connections_count'] > 0)),
                'total_connections' => array_sum(array_column($userActivity, 'connections_count')),
                'new_subscriptions' => array_sum(array_column($subscriptionActivity, 'new_subscriptions')),
                'period_revenue' => array_sum(array_column($subscriptionActivity, 'revenue'))
            ]
        ];
    }

    /**
     * Générer un rapport d'activité client
     */
    public function generateClientReport($clientId)
    {
        $client = $this->clientModel->findById($clientId);
        if (!$client) {
            throw new \Exception('Client non trouvé');
        }

        $stats = $this->getClientStats($clientId);
        $activity = $this->analyzeClientActivity($clientId);
        $nextPayment = $this->getNextPaymentEstimate($clientId);
        $userLimits = $this->checkUserLimits($clientId);

        // Alertes et recommandations
        $alerts = [];
        $recommendations = [];

        // Vérifier les limites d'utilisateurs
        if ($userLimits['over_limit']) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Dépassement de {$userLimits['current'] - $userLimits['limit']} utilisateur(s) autorisé(s)"
            ];
            $recommendations[] = 'Mettre à niveau l\'abonnement ou facturer les utilisateurs supplémentaires';
        }

        // Vérifier l'activité des utilisateurs
        $inactiveUsers = count(array_filter($activity['user_activity'], fn($u) => $u['connections_count'] === 0));
        if ($inactiveUsers > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$inactiveUsers} utilisateur(s) n'ont pas utilisé l'application récemment"
            ];
            $recommendations[] = 'Contacter les utilisateurs inactifs pour formation ou support';
        }

        // Vérifier les factures en retard
        $overdueInvoices = $stats['invoices']['overdue'] ?? 0;
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "{$overdueInvoices} facture(s) en retard de paiement"
            ];
            $recommendations[] = 'Relancer le client pour les factures impayées';
        }

        return [
            'client' => $client,
            'stats' => $stats,
            'activity' => $activity,
            'next_payment' => $nextPayment,
            'user_limits' => $userLimits,
            'alerts' => $alerts,
            'recommendations' => $recommendations,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Fusionner deux clients
     */
    public function mergeClients($sourceClientId, $targetClientId)
    {
        try {
            Database::beginTransaction();

            $sourceClient = $this->clientModel->findById($sourceClientId);
            $targetClient = $this->clientModel->findById($targetClientId);

            if (!$sourceClient || !$targetClient) {
                throw new \Exception('Client source ou cible non trouvé');
            }

            // Transférer les utilisateurs
            Database::query("
                UPDATE utilisateurs 
                SET client_id = ? 
                WHERE client_id = ? AND type_utilisateur != 'MegaAdmin'
            ", [$targetClientId, $sourceClientId]);

            // Transférer les abonnements
            Database::query("
                UPDATE abonnements_clients 
                SET client_id = ? 
                WHERE client_id = ?
            ", [$targetClientId, $sourceClientId]);

            // Transférer le matériel loué
            Database::query("
                UPDATE materiel_loue 
                SET client_id = ? 
                WHERE client_id = ?
            ", [$targetClientId, $sourceClientId]);

            // Transférer les catégories
            Database::query("
                UPDATE client_sous_categories 
                SET client_id = ? 
                WHERE client_id = ? 
                AND sous_categorie_id NOT IN (
                    SELECT sous_categorie_id 
                    FROM client_sous_categories 
                    WHERE client_id = ?
                )
            ", [$targetClientId, $sourceClientId, $targetClientId]);

            // Supprimer les doublons de catégories
            Database::query("
                DELETE FROM client_sous_categories 
                WHERE client_id = ?
            ", [$sourceClientId]);

            // Transférer les factures
            Database::query("
                UPDATE factures_stripe 
                SET client_id = ? 
                WHERE client_id = ?
            ", [$targetClientId, $sourceClientId]);

            // Marquer le client source comme inactif
            Database::update('clients', ['actif' => 0], 'id = ?', [$sourceClientId]);

            Database::commit();

            Logger::info('Clients fusionnés avec succès', [
                'source_client_id' => $sourceClientId,
                'target_client_id' => $targetClientId,
                'source_name' => $sourceClient['raison_sociale'],
                'target_name' => $targetClient['raison_sociale']
            ]);

            return true;

        } catch (\Exception $e) {
            Database::rollback();
            Logger::error('Erreur lors de la fusion des clients', [
                'source_client_id' => $sourceClientId,
                'target_client_id' => $targetClientId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Exporter les données d'un client
     */
    public function exportClientData($clientId, $format = 'json')
    {
        $client = $this->clientModel->findById($clientId);
        if (!$client) {
            throw new \Exception('Client non trouvé');
        }

        // Récupérer toutes les données du client
        $data = [
            'client' => $client,
            'users' => Database::fetchAll("
                SELECT * FROM utilisateurs 
                WHERE client_id = ? AND type_utilisateur != 'MegaAdmin'
                ORDER BY type_utilisateur, nom, prenom
            ", [$clientId]),
            'subscriptions' => Database::fetchAll("
                SELECT ac.*, fa.nom as formule_nom 
                FROM abonnements_clients ac
                JOIN formules_abonnement fa ON ac.formule_id = fa.id
                WHERE ac.client_id = ?
                ORDER BY ac.date_creation DESC
            ", [$clientId]),
            'materials' => Database::fetchAll("
                SELECT ml.*, mm.nom as materiel_nom 
                FROM materiel_loue ml
                JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
                WHERE ml.client_id = ?
                ORDER BY ml.date_creation DESC
            ", [$clientId]),
            'categories' => Database::fetchAll("
                SELECT csc.*, sc.nom as sous_categorie_nom, c.nom as categorie_nom
                FROM client_sous_categories csc
                JOIN sous_categories sc ON csc.sous_categorie_id = sc.id
                JOIN categories c ON sc.categorie_id = c.id
                WHERE csc.client_id = ?
                ORDER BY c.nom, sc.nom
            ", [$clientId]),
            'invoices' => Database::fetchAll("
                SELECT * FROM factures_stripe 
                WHERE client_id = ?
                ORDER BY date_facture DESC
            ", [$clientId])
        ];

        // Ajouter des métadonnées
        $data['export_metadata'] = [
            'export_date' => date('Y-m-d H:i:s'),
            'export_format' => $format,
            'client_id' => $clientId,
            'client_name' => $client['raison_sociale']
        ];

        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            case 'csv':
                return $this->exportToCsv($data);
            
            default:
                throw new \Exception('Format d\'export non supporté');
        }
    }

    /**
     * Convertir les données en CSV
     */
    private function exportToCsv($data)
    {
        $csv = '';
        
        // Export des informations client
        $csv .= "=== INFORMATIONS CLIENT ===\n";
        foreach ($data['client'] as $key => $value) {
            $csv .= sprintf('"%s","%s"' . "\n", $key, $value);
        }
        
        $csv .= "\n=== UTILISATEURS ===\n";
        if (!empty($data['users'])) {
            $headers = array_keys($data['users'][0]);
            $csv .= '"' . implode('","', $headers) . '"' . "\n";
            
            foreach ($data['users'] as $user) {
                $csv .= '"' . implode('","', array_map(fn($v) => str_replace('"', '""', $v), $user)) . '"' . "\n";
            }
        }
        
        $csv .= "\n=== ABONNEMENTS ===\n";
        if (!empty($data['subscriptions'])) {
            $headers = array_keys($data['subscriptions'][0]);
            $csv .= '"' . implode('","', $headers) . '"' . "\n";
            
            foreach ($data['subscriptions'] as $subscription) {
                $csv .= '"' . implode('","', array_map(fn($v) => str_replace('"', '""', $v), $subscription)) . '"' . "\n";
            }
        }
        
        return $csv;
    }

    /**
     * Envoyer l'email de bienvenue
     */
    private function sendWelcomeEmail($clientId, $clientData)
    {
        try {
            // TODO: Implémenter l'envoi d'email avec le service email
            Logger::info('Email de bienvenue envoyé', [
                'client_id' => $clientId,
                'email' => $clientData['email_facturation']
            ]);
        } catch (\Exception $e) {
            Logger::warning('Échec envoi email de bienvenue', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer les identifiants par email
     */
    private function sendCredentialsEmail($email, $password)
    {
        try {
            // TODO: Implémenter l'envoi d'email avec les identifiants
            Logger::info('Email identifiants envoyé', [
                'email' => $email
            ]);
        } catch (\Exception $e) {
            Logger::warning('Échec envoi email identifiants', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Nettoyer les données d'un client inactif
     */
    public function cleanupInactiveClient($clientId)
    {
        $client = $this->clientModel->findById($clientId);
        if (!$client || $client['actif']) {
            throw new \Exception('Client non trouvé ou toujours actif');
        }

        try {
            Database::beginTransaction();

            // Supprimer les associations catégories
            Database::delete('client_sous_categories', 'client_id = ?', [$clientId]);

            // Marquer les utilisateurs comme supprimés (garder pour l'historique)
            Database::query("
                UPDATE utilisateurs 
                SET actif = 0, email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP())
                WHERE client_id = ? AND type_utilisateur != 'MegaAdmin'
            ", [$clientId]);

            // Archiver les abonnements annulés
            Database::query("
                UPDATE abonnements_clients 
                SET statut = 'archive' 
                WHERE client_id = ? AND statut = 'annule'
            ", [$clientId]);

            Database::commit();

            Logger::info('Données client nettoyées', [
                'client_id' => $clientId,
                'client_name' => $client['raison_sociale']
            ]);

            return true;

        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Rechercher des clients avec filtres avancés
     */
    public function searchClients($filters = [], $pagination = [])
    {
        $query = "
            SELECT c.*, 
                   COUNT(DISTINCT u.id) as nb_users,
                   COUNT(DISTINCT ac.id) as nb_subscriptions,
                   SUM(CASE WHEN ac.statut = 'actif' THEN ac.prix_total_mensuel ELSE 0 END) as revenue_mensuel
            FROM clients c
            LEFT JOIN utilisateurs u ON c.id = u.client_id AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin'
            LEFT JOIN abonnements_clients ac ON c.id = ac.client_id
        ";

        $params = [];
        $whereConditions = [];

        // Filtres de recherche
        if (!empty($filters['search'])) {
            $whereConditions[] = "(c.raison_sociale LIKE ? OR c.email_facturation LIKE ? OR c.ville LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $whereConditions[] = "c.actif = ?";
            $params[] = (int)$filters['status'];
        }

        if (!empty($filters['country'])) {
            $whereConditions[] = "c.pays = ?";
            $params[] = $filters['country'];
        }

        if (!empty($filters['revenue_min'])) {
            $whereConditions[] = "ac.prix_total_mensuel >= ?";
            $params[] = (float)$filters['revenue_min'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "c.date_creation >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "c.date_creation <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // Assembler la requête
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $query .= " GROUP BY c.id";

        // Filtres post-GROUP BY
        $havingConditions = [];
        if (!empty($filters['min_users'])) {
            $havingConditions[] = "nb_users >= ?";
            $params[] = (int)$filters['min_users'];
        }

        if (!empty($filters['min_subscriptions'])) {
            $havingConditions[] = "nb_subscriptions >= ?";
            $params[] = (int)$filters['min_subscriptions'];
        }

        if (!empty($havingConditions)) {
            $query .= " HAVING " . implode(' AND ', $havingConditions);
        }

        // Tri
        $orderBy = $filters['sort'] ?? 'date_creation';
        $orderDirection = $filters['order'] ?? 'DESC';
        $query .= " ORDER BY {$orderBy} {$orderDirection}";

        // Pagination
        if (!empty($pagination['limit'])) {
            $query .= " LIMIT " . (int)$pagination['limit'];
            if (!empty($pagination['offset'])) {
                $query .= " OFFSET " . (int)$pagination['offset'];
            }
        }

        return Database::fetchAll($query, $params);
    }
}
?>