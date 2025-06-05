<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;

class DashboardController
{
    public function index()
    {
        try {
            // Statistiques générales
            $stats = $this->getDashboardStats();
            
            // Activité récente
            $recentActivity = $this->getRecentActivity();
            
            // Clients récents
            $recentClients = $this->getRecentClients();
            
            // Abonnements à expirer
            $expiringSubscriptions = $this->getExpiringSubscriptions();

            $pageTitle = 'Tableau de bord';
            require_once 'app/Views/dashboard/index.php';
            
        } catch (\Exception $e) {
            error_log("Erreur dashboard : " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors du chargement du tableau de bord');
            
            // Initialiser des valeurs par défaut pour éviter les erreurs
            $stats = $this->getEmptyStats();
            $recentActivity = [];
            $recentClients = [];
            $expiringSubscriptions = [];
            
            $pageTitle = 'Tableau de bord';
            require_once 'app/Views/dashboard/index.php';
        }
    }

    private function getDashboardStats()
    {
        $stats = [];

        // Nombre total de clients
        $stats['total_clients'] = Database::fetch(
            "SELECT COUNT(*) as count FROM clients WHERE actif = 1"
        )['count'] ?? 0;

        // Nombre total d'utilisateurs (hors MegaAdmin)
        $stats['total_users'] = Database::fetch(
            "SELECT COUNT(*) as count FROM utilisateurs WHERE actif = 1 AND type_utilisateur != 'MegaAdmin'"
        )['count'] ?? 0;

        // Nombre d'abonnements actifs
        $stats['active_subscriptions'] = Database::fetch(
            "SELECT COUNT(*) as count FROM abonnements_clients WHERE statut = 'actif'"
        )['count'] ?? 0;

        // Revenus mensuels (estimation basée sur les abonnements actifs)
        $stats['monthly_revenue'] = Database::fetch(
            "SELECT COALESCE(SUM(prix_total_mensuel), 0) as revenue FROM abonnements_clients WHERE statut = 'actif'"
        )['revenue'] ?? 0;

        // Matériel loué
        $stats['rented_equipment'] = Database::fetch(
            "SELECT COUNT(*) as count FROM materiel_loue WHERE statut = 'loue'"
        )['count'] ?? 0;

        // Nouveaux clients ce mois
        $stats['new_clients_month'] = Database::fetch(
            "SELECT COUNT(*) as count FROM clients 
             WHERE DATE_FORMAT(date_creation, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
        )['count'] ?? 0;

        return $stats;
    }

    private function getRecentActivity()
    {
        // Activité récente basée sur les créations/modifications
        $activities = [];

        // Nouveaux clients
        $newClients = Database::fetchAll(
            "SELECT 'client' as type, raison_sociale as title, date_creation as date
             FROM clients 
             WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY date_creation DESC
             LIMIT 5"
        );

        foreach ($newClients as $client) {
            $activities[] = [
                'type' => 'client',
                'title' => 'Nouveau client : ' . $client['title'],
                'date' => $client['date'],
                'icon' => 'user-plus'
            ];
        }

        // Nouveaux abonnements
        $newSubscriptions = Database::fetchAll(
            "SELECT 'subscription' as type, 
                    CONCAT(c.raison_sociale, ' - ', fa.nom) as title, 
                    ac.date_creation as date
             FROM abonnements_clients ac
             JOIN clients c ON ac.client_id = c.id
             JOIN formules_abonnement fa ON ac.formule_id = fa.id
             WHERE ac.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY ac.date_creation DESC
             LIMIT 5"
        );

        foreach ($newSubscriptions as $sub) {
            $activities[] = [
                'type' => 'subscription',
                'title' => 'Nouvel abonnement : ' . $sub['title'],
                'date' => $sub['date'],
                'icon' => 'credit-card'
            ];
        }

        // Trier par date décroissante
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, 10);
    }

    private function getRecentClients()
    {
        return Database::fetchAll(
            "SELECT id, raison_sociale, email_facturation, ville, pays, date_creation,
                    (SELECT COUNT(*) FROM utilisateurs u WHERE u.client_id = c.id AND u.actif = 1 AND u.type_utilisateur != 'MegaAdmin') as nb_users,
                    (SELECT COUNT(*) FROM abonnements_clients ac WHERE ac.client_id = c.id AND ac.statut = 'actif') as nb_subscriptions
             FROM clients c
             WHERE actif = 1
             ORDER BY date_creation DESC
             LIMIT 5"
        );
    }

    private function getExpiringSubscriptions()
    {
        return Database::fetchAll(
            "SELECT ac.id, c.raison_sociale, fa.nom as formule_name, 
                    ac.date_fin, ac.prix_total_mensuel, ac.statut
             FROM abonnements_clients ac
             JOIN clients c ON ac.client_id = c.id
             JOIN formules_abonnement fa ON ac.formule_id = fa.id
             WHERE ac.statut = 'actif' 
             AND ac.date_fin IS NOT NULL 
             AND ac.date_fin <= DATE_ADD(NOW(), INTERVAL 30 DAY)
             ORDER BY ac.date_fin ASC
             LIMIT 10"
        );
    }

    private function getEmptyStats()
    {
        return [
            'total_clients' => 0,
            'total_users' => 0,
            'active_subscriptions' => 0,
            'monthly_revenue' => 0,
            'rented_equipment' => 0,
            'new_clients_month' => 0
        ];
    }
}
?>