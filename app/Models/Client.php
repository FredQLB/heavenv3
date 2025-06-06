<?php

namespace App\Models;

use App\Helpers\Database;

class Client
{
    private $table = 'clients';

    public function findAll($activeOnly = false)
    {
        $query = Database::table($this->table);
        
        if ($activeOnly) {
            $query->where('actif', 1);
        }
        
        return $query->orderBy('raison_sociale')->get();
    }

    public function findById($id)
    {
        return Database::table($this->table)
            ->where('id', $id)
            ->first();
    }

    public function findByEmail($email)
    {
        return Database::table($this->table)
            ->where('email_facturation', $email)
            ->first();
    }

    public function findByStripeCustomerId($stripeCustomerId)
    {
        return Database::table($this->table)
            ->where('stripe_customer_id', $stripeCustomerId)
            ->first();
    }

    public function create($data)
    {
        return Database::insert($this->table, array_merge($data, [
            'date_creation' => date('Y-m-d H:i:s'),
            'date_modification' => date('Y-m-d H:i:s')
        ]));
    }

    public function update($id, $data)
    {
        return Database::table($this->table)
            ->where('id', $id)
            ->update(array_merge($data, [
                'date_modification' => date('Y-m-d H:i:s')
            ]));
    }

    public function delete($id)
    {
        return Database::table($this->table)
            ->where('id', $id)
            ->delete();
    }

    public function toggleStatus($id)
    {
        $client = $this->findById($id);
        if (!$client) {
            return false;
        }

        $newStatus = $client['actif'] ? 0 : 1;
        return $this->update($id, ['actif' => $newStatus]);
    }

    public function hasActiveSubscriptions($id)
    {
        $count = Database::table('abonnements_clients')
            ->where('client_id', $id)
            ->where('statut', 'actif')
            ->count();

        return $count > 0;
    }

    public function getUsersCount($id)
    {
        return Database::table('utilisateurs')
            ->where('client_id', $id)
            ->where('type_utilisateur', '!=', 'MegaAdmin')
            ->count();
    }

    public function getActiveUsersCount($id)
    {
        return Database::table('utilisateurs')
            ->where('client_id', $id)
            ->where('actif', 1)
            ->where('type_utilisateur', '!=', 'MegaAdmin')
            ->count();
    }

    public function getSubscriptionsCount($id)
    {
        return [
            'total' => Database::table('abonnements_clients')
                ->where('client_id', $id)
                ->count(),
            'active' => Database::table('abonnements_clients')
                ->where('client_id', $id)
                ->where('statut', 'actif')
                ->count(),
            'pending' => Database::table('abonnements_clients')
                ->where('client_id', $id)
                ->where('statut', 'en_attente')
                ->count(),
            'cancelled' => Database::table('abonnements_clients')
                ->where('client_id', $id)
                ->where('statut', 'annule')
                ->count()
        ];
    }

    public function getMaterialsCount($id)
    {
        return [
            'total' => Database::table('materiel_loue')
                ->where('client_id', $id)
                ->count(),
            'active' => Database::table('materiel_loue')
                ->where('client_id', $id)
                ->where('statut', 'loue')
                ->count(),
            'returned' => Database::table('materiel_loue')
                ->where('client_id', $id)
                ->where('statut', 'retourne')
                ->count(),
            'maintenance' => Database::table('materiel_loue')
                ->where('client_id', $id)
                ->where('statut', 'maintenance')
                ->count()
        ];
    }

    public function getMonthlyRevenue($id)
    {
        $result = Database::fetch("
            SELECT COALESCE(SUM(prix_total_mensuel), 0) as revenue
            FROM abonnements_clients
            WHERE client_id = ? AND statut = 'actif'
        ", [$id]);

        return $result['revenue'] ?? 0;
    }

    public function getCategoriesCount($id)
    {
        return Database::table('client_sous_categories')
            ->where('client_id', $id)
            ->count();
    }

    public function getInvoicesStats($id)
    {
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN date_paiement IS NOT NULL THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN date_echeance < NOW() AND date_paiement IS NULL THEN 1 ELSE 0 END) as overdue,
                SUM(montant) as total_amount,
                SUM(CASE WHEN date_paiement IS NOT NULL THEN montant ELSE 0 END) as paid_amount
            FROM factures_stripe
            WHERE client_id = ?
        ", [$id]);

        return [
            'total' => $stats['total'] ?? 0,
            'paid' => $stats['paid'] ?? 0,
            'overdue' => $stats['overdue'] ?? 0,
            'pending' => ($stats['total'] ?? 0) - ($stats['paid'] ?? 0) - ($stats['overdue'] ?? 0),
            'total_amount' => $stats['total_amount'] ?? 0,
            'paid_amount' => $stats['paid_amount'] ?? 0,
            'outstanding_amount' => ($stats['total_amount'] ?? 0) - ($stats['paid_amount'] ?? 0)
        ];
    }

    public function getRecentActivity($id, $limit = 10)
    {
        $activities = [];

        // Nouvelles connexions utilisateurs
        $connections = Database::fetchAll("
            SELECT 'user_login' as type, u.nom, u.prenom, lc.date_connexion as date
            FROM logs_connexion lc
            JOIN utilisateurs u ON lc.utilisateur_id = u.id
            WHERE u.client_id = ? AND lc.date_connexion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY lc.date_connexion DESC
            LIMIT 5
        ", [$id]);

        foreach ($connections as $conn) {
            $activities[] = [
                'type' => 'user_login',
                'title' => 'Connexion de ' . $conn['prenom'] . ' ' . $conn['nom'],
                'date' => $conn['date'],
                'icon' => 'user'
            ];
        }

        // Nouveaux abonnements
        $subscriptions = Database::fetchAll("
            SELECT 'subscription' as type, fa.nom, ac.date_creation as date, ac.statut
            FROM abonnements_clients ac
            JOIN formules_abonnement fa ON ac.formule_id = fa.id
            WHERE ac.client_id = ? AND ac.date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY ac.date_creation DESC
            LIMIT 3
        ", [$id]);

        foreach ($subscriptions as $sub) {
            $activities[] = [
                'type' => 'subscription',
                'title' => 'Abonnement ' . $sub['nom'] . ' (' . $sub['statut'] . ')',
                'date' => $sub['date'],
                'icon' => 'credit-card'
            ];
        }

        // Nouvelles locations de matériel
        $materials = Database::fetchAll("
            SELECT 'material' as type, mm.nom, ml.date_creation as date, ml.statut
            FROM materiel_loue ml
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.client_id = ? AND ml.date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY ml.date_creation DESC
            LIMIT 3
        ", [$id]);

        foreach ($materials as $mat) {
            $activities[] = [
                'type' => 'material',
                'title' => 'Location ' . $mat['nom'] . ' (' . $mat['statut'] . ')',
                'date' => $mat['date'],
                'icon' => 'package'
            ];
        }

        // Trier par date décroissante
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, $limit);
    }

    public function getStats()
    {
        $stats = [];

        // Total des clients
        $stats['total'] = Database::table($this->table)->count();

        // Clients actifs
        $stats['active'] = Database::table($this->table)
            ->where('actif', 1)
            ->count();

        // Clients avec abonnements actifs
        $stats['with_active_subscriptions'] = Database::fetch("
            SELECT COUNT(DISTINCT c.id) as count
            FROM {$this->table} c
            JOIN abonnements_clients ac ON c.id = ac.client_id
            WHERE c.actif = 1 AND ac.statut = 'actif'
        ")['count'] ?? 0;

        // Nouveaux clients ce mois
        $stats['new_this_month'] = Database::table($this->table)
            ->where('date_creation', '>=', date('Y-m-01'))
            ->count();

        // Revenue total mensuel
        $stats['total_monthly_revenue'] = Database::fetch("
            SELECT COALESCE(SUM(ac.prix_total_mensuel), 0) as revenue
            FROM abonnements_clients ac
            JOIN {$this->table} c ON ac.client_id = c.id
            WHERE c.actif = 1 AND ac.statut = 'actif'
        ")['revenue'] ?? 0;

        // Pays les plus représentés
        $stats['top_countries'] = Database::fetchAll("
            SELECT pays, COUNT(*) as count
            FROM {$this->table}
            WHERE actif = 1 AND pays IS NOT NULL
            GROUP BY pays
            ORDER BY count DESC
            LIMIT 5
        ");

        // Client le plus rentable
        $stats['most_profitable'] = Database::fetch("
            SELECT c.id, c.raison_sociale, SUM(ac.prix_total_mensuel) as revenue
            FROM {$this->table} c
            JOIN abonnements_clients ac ON c.id = ac.client_id
            WHERE c.actif = 1 AND ac.statut = 'actif'
            GROUP BY c.id, c.raison_sociale
            ORDER BY revenue DESC
            LIMIT 1
        ");

        return $stats;
    }

    public function search($query, $filters = [])
    {
        $search = Database::table($this->table);

        // Recherche textuelle
        if (!empty($query)) {
            $search->where(function($q) use ($query) {
                $q->whereLike('raison_sociale', "%{$query}%")
                  ->orWhereLike('email_facturation', "%{$query}%")
                  ->orWhereLike('ville', "%{$query}%")
                  ->orWhereLike('pays', "%{$query}%");
            });
        }

        // Filtres
        if (isset($filters['status']) && $filters['status'] !== '') {
            $search->where('actif', $filters['status']);
        }

        if (!empty($filters['country'])) {
            $search->where('pays', $filters['country']);
        }

        if (!empty($filters['date_from'])) {
            $search->where('date_creation', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $search->where('date_creation', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $search->orderBy('raison_sociale')->get();
    }

    public function validateData($data, $id = null)
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
            $emailQuery = Database::table($this->table)->where('email_facturation', $data['email_facturation']);
            if ($id) {
                $emailQuery->where('id', '!=', $id);
            }
            
            if ($emailQuery->exists()) {
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

    public function canDelete($id)
    {
        $reasons = [];

        // Vérifier les abonnements
        $subscriptionsCount = Database::table('abonnements_clients')
            ->where('client_id', $id)
            ->count();

        if ($subscriptionsCount > 0) {
            $reasons[] = "A {$subscriptionsCount} abonnement(s) dans l'historique";
        }

        // Vérifier les utilisateurs actifs
        $activeUsersCount = Database::table('utilisateurs')
            ->where('client_id', $id)
            ->where('actif', 1)
            ->where('type_utilisateur', '!=', 'MegaAdmin')
            ->count();

        if ($activeUsersCount > 0) {
            $reasons[] = "A {$activeUsersCount} utilisateur(s) actif(s)";
        }

        // Vérifier le matériel loué
        $activeMaterialsCount = Database::table('materiel_loue')
            ->where('client_id', $id)
            ->whereIn('statut', ['loue', 'maintenance'])
            ->count();

        if ($activeMaterialsCount > 0) {
            $reasons[] = "A {$activeMaterialsCount} matériel(s) en location";
        }

        // Vérifier les factures impayées
        $unpaidInvoicesCount = Database::table('factures_stripe')
            ->where('client_id', $id)
            ->whereNull('date_paiement')
            ->count();

        if ($unpaidInvoicesCount > 0) {
            $reasons[] = "A {$unpaidInvoicesCount} facture(s) impayée(s)";
        }

        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons
        ];
    }

    public function getClientsByCountry()
    {
        return Database::fetchAll("
            SELECT 
                pays,
                COUNT(*) as total_clients,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as active_clients,
                SUM(CASE WHEN actif = 1 AND (
                    SELECT COUNT(*) FROM abonnements_clients ac 
                    WHERE ac.client_id = c.id AND ac.statut = 'actif'
                ) > 0 THEN 1 ELSE 0 END) as clients_with_subscriptions
            FROM {$this->table} c
            WHERE pays IS NOT NULL AND pays != ''
            GROUP BY pays
            ORDER BY total_clients DESC
        ");
    }

    public function getClientsByCreationMonth($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        return Database::fetchAll("
            SELECT 
                MONTH(date_creation) as month,
                COUNT(*) as clients_created,
                SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as still_active
            FROM {$this->table}
            WHERE YEAR(date_creation) = ?
            GROUP BY MONTH(date_creation)
            ORDER BY month
        ", [$year]);
    }

    public function getRevenueByClient($limit = 10)
    {
        return Database::fetchAll("
            SELECT 
                c.id,
                c.raison_sociale,
                c.ville,
                c.pays,
                SUM(ac.prix_total_mensuel) as monthly_revenue,
                COUNT(ac.id) as subscriptions_count,
                COUNT(DISTINCT u.id) as users_count
            FROM {$this->table} c
            JOIN abonnements_clients ac ON c.id = ac.client_id
            LEFT JOIN utilisateurs u ON c.id = u.client_id AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin'
            WHERE c.actif = 1 AND ac.statut = 'actif'
            GROUP BY c.id, c.raison_sociale, c.ville, c.pays
            ORDER BY monthly_revenue DESC
            LIMIT ?
        ", [$limit]);
    }

    public function getClientsWithExpiringSubscriptions($days = 30)
    {
        return Database::fetchAll("
            SELECT DISTINCT
                c.id,
                c.raison_sociale,
                c.email_facturation,
                c.ville,
                c.pays,
                COUNT(ac.id) as expiring_subscriptions,
                MIN(ac.date_fin) as earliest_expiration
            FROM {$this->table} c
            JOIN abonnements_clients ac ON c.id = ac.client_id
            WHERE c.actif = 1 
            AND ac.statut = 'actif'
            AND ac.date_fin IS NOT NULL
            AND ac.date_fin <= DATE_ADD(NOW(), INTERVAL ? DAY)
            GROUP BY c.id, c.raison_sociale, c.email_facturation, c.ville, c.pays
            ORDER BY earliest_expiration ASC
        ", [$days]);
    }

    public function getInactiveClients($days = 90)
    {
        return Database::fetchAll("
            SELECT 
                c.id,
                c.raison_sociale,
                c.email_facturation,
                c.date_creation,
                MAX(lc.date_connexion) as last_user_login,
                COUNT(DISTINCT u.id) as total_users,
                COUNT(DISTINCT ac.id) as active_subscriptions
            FROM {$this->table} c
            LEFT JOIN utilisateurs u ON c.id = u.client_id AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin'
            LEFT JOIN logs_connexion lc ON u.id = lc.utilisateur_id
            LEFT JOIN abonnements_clients ac ON c.id = ac.client_id AND ac.statut = 'actif'
            WHERE c.actif = 1
            GROUP BY c.id, c.raison_sociale, c.email_facturation, c.date_creation
            HAVING (last_user_login IS NULL OR last_user_login < DATE_SUB(NOW(), INTERVAL ? DAY))
            AND (active_subscriptions > 0)
            ORDER BY last_user_login ASC
        ", [$days]);
    }

    public function getDuplicateEmails()
    {
        return Database::fetchAll("
            SELECT 
                email_facturation,
                COUNT(*) as count,
                GROUP_CONCAT(id) as client_ids,
                GROUP_CONCAT(raison_sociale SEPARATOR ' | ') as company_names
            FROM {$this->table}
            GROUP BY email_facturation
            HAVING count > 1
            ORDER BY count DESC
        ");
    }

    public function getClientsRequiringAttention()
    {
        $clients = [];

        // Clients avec abonnements expirés
        $expiredSubscriptions = Database::fetchAll("
            SELECT DISTINCT c.id, c.raison_sociale, 'Abonnements expirés' as issue,
                   COUNT(ac.id) as count
            FROM {$this->table} c
            JOIN abonnements_clients ac ON c.id = ac.client_id
            WHERE c.actif = 1 AND ac.date_fin < NOW() AND ac.statut = 'actif'
            GROUP BY c.id, c.raison_sociale
        ");

        // Clients avec factures en retard
        $overdueInvoices = Database::fetchAll("
            SELECT DISTINCT c.id, c.raison_sociale, 'Factures en retard' as issue,
                   COUNT(fs.id) as count
            FROM {$this->table} c
            JOIN factures_stripe fs ON c.id = fs.client_id
            WHERE c.actif = 1 AND fs.date_echeance < NOW() AND fs.date_paiement IS NULL
            GROUP BY c.id, c.raison_sociale
        ");

        // Clients dépassant leurs limites d'utilisateurs
        $overLimitUsers = Database::fetchAll("
            SELECT DISTINCT c.id, c.raison_sociale, 'Limite utilisateurs dépassée' as issue,
                   (COUNT(DISTINCT u.id) - COALESCE(SUM(fa.nombre_utilisateurs_inclus), 0)) as count
            FROM {$this->table} c
            LEFT JOIN utilisateurs u ON c.id = u.client_id AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin'
            LEFT JOIN abonnements_clients ac ON c.id = ac.client_id AND ac.statut = 'actif'
            LEFT JOIN formules_abonnement fa ON ac.formule_id = fa.id
            WHERE c.actif = 1
            GROUP BY c.id, c.raison_sociale
            HAVING count > 0
        ");

        return array_merge($expiredSubscriptions, $overdueInvoices, $overLimitUsers);
    }

    public function exportClients($format = 'csv', $filters = [])
    {
        $clients = $this->search('', $filters);

        // Enrichir avec des données supplémentaires
        foreach ($clients as &$client) {
            $client['nb_users'] = $this->getUsersCount($client['id']);
            $client['nb_subscriptions'] = $this->getSubscriptionsCount($client['id'])['active'];
            $client['monthly_revenue'] = $this->getMonthlyRevenue($client['id']);
            $client['nb_categories'] = $this->getCategoriesCount($client['id']);
        }

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($clients);
            case 'json':
                return $this->exportToJson($clients);
            default:
                throw new \Exception('Format d\'export non supporté');
        }
    }

    private function exportToCsv($clients)
    {
        $csv = "ID,Raison sociale,Email,Adresse,Code postal,Ville,Pays,TVA,Utilisateurs,Abonnements,Revenue mensuel,Catégories,Statut,Date création\n";
        
        foreach ($clients as $client) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%d,%d,%.2f,%d,\"%s\",\"%s\"\n",
                $client['id'],
                str_replace('"', '""', $client['raison_sociale']),
                str_replace('"', '""', $client['email_facturation']),
                str_replace('"', '""', $client['adresse']),
                $client['code_postal'],
                str_replace('"', '""', $client['ville']),
                str_replace('"', '""', $client['pays']),
                str_replace('"', '""', $client['numero_tva'] ?? ''),
                $client['nb_users'] ?? 0,
                $client['nb_subscriptions'] ?? 0,
                $client['monthly_revenue'] ?? 0,
                $client['nb_categories'] ?? 0,
                $client['actif'] ? 'Actif' : 'Inactif',
                $client['date_creation']
            );
        }
        
        return $csv;
    }

    private function exportToJson($clients)
    {
        return json_encode([
            'export_date' => date('Y-m-d H:i:s'),
            'total_count' => count($clients),
            'clients' => $clients
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function getClientSummary($id)
    {
        $client = $this->findById($id);
        if (!$client) {
            return null;
        }

        return [
            'client' => $client,
            'stats' => [
                'users' => $this->getUsersCount($id),
                'active_users' => $this->getActiveUsersCount($id),
                'subscriptions' => $this->getSubscriptionsCount($id),
                'materials' => $this->getMaterialsCount($id),
                'monthly_revenue' => $this->getMonthlyRevenue($id),
                'categories' => $this->getCategoriesCount($id),
                'invoices' => $this->getInvoicesStats($id)
            ],
            'recent_activity' => $this->getRecentActivity($id, 5),
            'deletion_check' => $this->canDelete($id)
        ];
    }
}
?>