<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Logger;
use App\Models\MaterialModel;

class MaterialService
{
    private $materialModel;

    public function __construct()
    {
        $this->materialModel = new MaterialModel();
    }

    /**
     * Calculer le coût total d'une location
     */
    public function calculateRentalCost($materialId, $startDate, $endDate = null, $includeDeposit = true)
    {
        $material = $this->materialModel->findById($materialId);
        if (!$material) {
            throw new \Exception('Matériel non trouvé');
        }

        $start = new \DateTime($startDate);
        $end = $endDate ? new \DateTime($endDate) : new \DateTime();
        
        $duration = $start->diff($end);
        $days = $duration->days;
        $months = max(1, ceil($days / 30)); // Minimum 1 mois

        $cost = [
            'monthly_price' => $material['prix_mensuel'],
            'deposit' => $material['depot_garantie'],
            'duration_days' => $days,
            'duration_months' => $months,
            'rental_cost' => $material['prix_mensuel'] * $months,
            'total_cost' => ($material['prix_mensuel'] * $months) + ($includeDeposit ? $material['depot_garantie'] : 0)
        ];

        return $cost;
    }

    /**
     * Vérifier la disponibilité d'un matériel
     */
    public function checkAvailability($materialId, $excludeRentalId = null)
    {
        $query = "
            SELECT COUNT(*) as active_rentals
            FROM materiel_loue
            WHERE modele_materiel_id = ? 
            AND statut IN ('loue', 'maintenance')
        ";
        
        $params = [$materialId];
        
        if ($excludeRentalId) {
            $query .= " AND id != ?";
            $params[] = $excludeRentalId;
        }

        $result = Database::fetch($query, $params);
        $activeRentals = $result['active_rentals'] ?? 0;

        // Pour simplifier, on considère qu'il n'y a qu'un exemplaire par modèle
        // En réalité, il faudrait gérer un stock
        return $activeRentals === 0;
    }

    /**
     * Obtenir les statistiques de performance d'un matériel
     */
    public function getPerformanceStats($materialId, $period = '12months')
    {
        $periodCondition = match($period) {
            '1month' => "date_creation >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            '3months' => "date_creation >= DATE_SUB(NOW(), INTERVAL 3 MONTH)",
            '6months' => "date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)",
            '12months' => "date_creation >= DATE_SUB(NOW(), INTERVAL 12 MONTH)",
            'all' => "1=1",
            default => "date_creation >= DATE_SUB(NOW(), INTERVAL 12 MONTH)"
        };

        // Nombre de locations
        $rentalsCount = Database::fetch("
            SELECT COUNT(*) as count
            FROM materiel_loue
            WHERE modele_materiel_id = ? AND {$periodCondition}
        ", [$materialId])['count'] ?? 0;

        // Durée moyenne des locations
        $avgDuration = Database::fetch("
            SELECT AVG(DATEDIFF(
                COALESCE(date_retour_effective, NOW()), 
                date_location
            )) as avg_days
            FROM materiel_loue
            WHERE modele_materiel_id = ? AND {$periodCondition}
        ", [$materialId])['avg_days'] ?? 0;

        // Taux de retour en retard
        $lateReturns = Database::fetch("
            SELECT 
                COUNT(*) as total_returned,
                SUM(CASE WHEN date_retour_effective > date_retour_prevue THEN 1 ELSE 0 END) as late_returns
            FROM materiel_loue
            WHERE modele_materiel_id = ? 
            AND date_retour_effective IS NOT NULL 
            AND date_retour_prevue IS NOT NULL
            AND {$periodCondition}
        ", [$materialId]);

        $lateReturnRate = 0;
        if ($lateReturns['total_returned'] > 0) {
            $lateReturnRate = ($lateReturns['late_returns'] / $lateReturns['total_returned']) * 100;
        }

        // Revenus générés
        $revenue = Database::fetch("
            SELECT 
                COALESCE(SUM(ml.depot_verse), 0) as total_deposits,
                COUNT(*) * mm.prix_mensuel as estimated_revenue
            FROM materiel_loue ml
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.modele_materiel_id = ? AND {$periodCondition}
            GROUP BY mm.prix_mensuel
        ", [$materialId]);

        return [
            'period' => $period,
            'rentals_count' => $rentalsCount,
            'avg_duration_days' => round($avgDuration, 1),
            'late_return_rate' => round($lateReturnRate, 1),
            'total_deposits' => $revenue['total_deposits'] ?? 0,
            'estimated_revenue' => $revenue['estimated_revenue'] ?? 0
        ];
    }

    /**
     * Générer un rapport de location
     */
    public function generateRentalReport($filters = [])
    {
        $whereConditions = ["1=1"];
        $params = [];

        // Filtres par période
        if (!empty($filters['start_date'])) {
            $whereConditions[] = "ml.date_location >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereConditions[] = "ml.date_location <= ?";
            $params[] = $filters['end_date'];
        }

        // Filtres par matériel
        if (!empty($filters['material_id'])) {
            $whereConditions[] = "ml.modele_materiel_id = ?";
            $params[] = $filters['material_id'];
        }

        // Filtres par statut
        if (!empty($filters['status'])) {
            $whereConditions[] = "ml.statut = ?";
            $params[] = $filters['status'];
        }

        // Filtres par client
        if (!empty($filters['client_id'])) {
            $whereConditions[] = "ml.client_id = ?";
            $params[] = $filters['client_id'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Requête principale
        $rentals = Database::fetchAll("
            SELECT 
                ml.*,
                c.raison_sociale,
                c.email_facturation,
                mm.nom as materiel_nom,
                mm.prix_mensuel,
                DATEDIFF(
                    COALESCE(ml.date_retour_effective, NOW()), 
                    ml.date_location
                ) as duration_days,
                CASE 
                    WHEN ml.date_retour_effective IS NOT NULL AND ml.date_retour_prevue IS NOT NULL 
                         AND ml.date_retour_effective > ml.date_retour_prevue 
                    THEN DATEDIFF(ml.date_retour_effective, ml.date_retour_prevue)
                    ELSE 0
                END as late_days
            FROM materiel_loue ml
            JOIN clients c ON ml.client_id = c.id
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE {$whereClause}
            ORDER BY ml.date_location DESC
        ", $params);

        // Calcul des statistiques du rapport
        $stats = [
            'total_rentals' => count($rentals),
            'total_revenue' => 0,
            'total_deposits' => 0,
            'avg_duration' => 0,
            'late_returns' => 0,
            'materials_breakdown' => [],
            'clients_breakdown' => []
        ];

        $totalDuration = 0;

        foreach ($rentals as $rental) {
            // Revenus
            $estimatedCost = ceil($rental['duration_days'] / 30) * $rental['prix_mensuel'];
            $stats['total_revenue'] += $estimatedCost;
            $stats['total_deposits'] += $rental['depot_verse'];

            // Durée
            $totalDuration += $rental['duration_days'];

            // Retards
            if ($rental['late_days'] > 0) {
                $stats['late_returns']++;
            }

            // Répartition par matériel
            $materialName = $rental['materiel_nom'];
            if (!isset($stats['materials_breakdown'][$materialName])) {
                $stats['materials_breakdown'][$materialName] = [
                    'count' => 0,
                    'revenue' => 0
                ];
            }
            $stats['materials_breakdown'][$materialName]['count']++;
            $stats['materials_breakdown'][$materialName]['revenue'] += $estimatedCost;

            // Répartition par client
            $clientName = $rental['raison_sociale'];
            if (!isset($stats['clients_breakdown'][$clientName])) {
                $stats['clients_breakdown'][$clientName] = [
                    'count' => 0,
                    'revenue' => 0
                ];
            }
            $stats['clients_breakdown'][$clientName]['count']++;
            $stats['clients_breakdown'][$clientName]['revenue'] += $estimatedCost;
        }

        if (count($rentals) > 0) {
            $stats['avg_duration'] = round($totalDuration / count($rentals), 1);
        }

        return [
            'rentals' => $rentals,
            'stats' => $stats,
            'filters_applied' => $filters
        ];
    }

    /**
     * Notifier les retours en retard
     */
    public function notifyOverdueReturns()
    {
        $overdueRentals = Database::fetchAll("
            SELECT 
                ml.*,
                c.raison_sociale,
                c.email_facturation,
                mm.nom as materiel_nom,
                DATEDIFF(NOW(), ml.date_retour_prevue) as days_overdue
            FROM materiel_loue ml
            JOIN clients c ON ml.client_id = c.id
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.statut = 'loue'
            AND ml.date_retour_prevue IS NOT NULL
            AND ml.date_retour_prevue < NOW()
            ORDER BY ml.date_retour_prevue ASC
        ");

        foreach ($overdueRentals as $rental) {
            Logger::info('Location en retard détectée', [
                'rental_id' => $rental['id'],
                'client' => $rental['raison_sociale'],
                'material' => $rental['materiel_nom'],
                'days_overdue' => $rental['days_overdue']
            ]);

            // Ici, vous pourriez envoyer des notifications email
            // EmailService::sendOverdueNotification($rental);
        }

        return $overdueRentals;
    }

    /**
     * Planifier la maintenance préventive
     */
    public function schedulePreventiveMaintenance()
    {
        // Matériels ayant beaucoup servi (plus de 6 mois cumulés de location)
        $materialsForMaintenance = Database::fetchAll("
            SELECT 
                mm.id,
                mm.nom,
                COUNT(ml.id) as total_rentals,
                SUM(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as total_days_rented,
                MAX(ml.date_retour_effective) as last_return
            FROM modeles_materiel mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id
            WHERE mm.actif = 1
            GROUP BY mm.id, mm.nom
            HAVING total_days_rented > 180
            AND (last_return IS NULL OR last_return < DATE_SUB(NOW(), INTERVAL 30 DAY))
        ");

        foreach ($materialsForMaintenance as $material) {
            Logger::info('Matériel nécessitant une maintenance préventive', [
                'material_id' => $material['id'],
                'material_name' => $material['nom'],
                'total_rentals' => $material['total_rentals'],
                'total_days_rented' => $material['total_days_rented']
            ]);
        }

        return $materialsForMaintenance;
    }

    /**
     * Optimiser les prix en fonction de l'utilisation
     */
    public function suggestPriceOptimization()
    {
        $materials = Database::fetchAll("
            SELECT 
                mm.*,
                COUNT(ml.id) as rental_count,
                AVG(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as avg_rental_duration,
                SUM(ml.depot_verse) as total_deposits_collected
            FROM modeles_materiel mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id 
                AND ml.date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            WHERE mm.actif = 1
            GROUP BY mm.id
        ");

        $suggestions = [];

        foreach ($materials as $material) {
            $suggestion = [
                'material_id' => $material['id'],
                'material_name' => $material['nom'],
                'current_price' => $material['prix_mensuel'],
                'rental_count' => $material['rental_count'],
                'avg_duration' => round($material['avg_rental_duration'], 1),
                'recommendation' => 'maintenir',
                'suggested_price' => $material['prix_mensuel'],
                'reason' => ''
            ];

            // Logique de suggestion
            if ($material['rental_count'] > 10) {
                // Très demandé - augmenter le prix
                $suggestion['recommendation'] = 'augmenter';
                $suggestion['suggested_price'] = $material['prix_mensuel'] * 1.1;
                $suggestion['reason'] = 'Forte demande (plus de 10 locations en 6 mois)';
            } elseif ($material['rental_count'] < 2) {
                // Peu demandé - diminuer le prix
                $suggestion['recommendation'] = 'diminuer';
                $suggestion['suggested_price'] = $material['prix_mensuel'] * 0.9;
                $suggestion['reason'] = 'Faible demande (moins de 2 locations en 6 mois)';
            } elseif ($material['avg_rental_duration'] > 60) {
                // Locations longues - augmenter légèrement
                $suggestion['recommendation'] = 'augmenter_leger';
                $suggestion['suggested_price'] = $material['prix_mensuel'] * 1.05;
                $suggestion['reason'] = 'Locations longues durée (moyenne > 60 jours)';
            }

            $suggestions[] = $suggestion;
        }

        return $suggestions;
    }

    /**
     * Analyser la rentabilité des matériels
     */
    public function analyzeProfitability($period = '12months')
    {
        $periodCondition = match($period) {
            '1month' => "DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            '3months' => "DATE_SUB(NOW(), INTERVAL 3 MONTH)",
            '6months' => "DATE_SUB(NOW(), INTERVAL 6 MONTH)",
            '12months' => "DATE_SUB(NOW(), INTERVAL 12 MONTH)",
            default => "DATE_SUB(NOW(), INTERVAL 12 MONTH)"
        };

        $profitability = Database::fetchAll("
            SELECT 
                mm.id,
                mm.nom,
                mm.prix_mensuel,
                mm.depot_garantie,
                COUNT(ml.id) as total_rentals,
                SUM(ml.depot_verse) as total_deposits_collected,
                SUM(CASE WHEN ml.depot_rembourse = 1 THEN ml.depot_verse ELSE 0 END) as deposits_refunded,
                SUM(
                    CEIL(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location) / 30) 
                    * mm.prix_mensuel
                ) as estimated_rental_revenue,
                AVG(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as avg_rental_days,
                SUM(CASE WHEN ml.statut = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count
            FROM modeles_materiel mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id 
                AND ml.date_creation >= {$periodCondition}
            WHERE mm.actif = 1
            GROUP BY mm.id, mm.nom, mm.prix_mensuel, mm.depot_garantie
            ORDER BY estimated_rental_revenue DESC
        ");

        foreach ($profitability as &$item) {
            // Calculs de rentabilité
            $item['net_deposits'] = $item['total_deposits_collected'] - $item['deposits_refunded'];
            $item['total_revenue'] = $item['estimated_rental_revenue'] + $item['net_deposits'];
            $item['utilization_rate'] = 0;
            
            if ($period === '12months') {
                $maxPossibleDays = 365;
            } elseif ($period === '6months') {
                $maxPossibleDays = 182;
            } elseif ($period === '3months') {
                $maxPossibleDays = 91;
            } else {
                $maxPossibleDays = 30;
            }
            
            $totalRentalDays = $item['avg_rental_days'] * $item['total_rentals'];
            $item['utilization_rate'] = min(100, ($totalRentalDays / $maxPossibleDays) * 100);
            
            // Classification de performance
            if ($item['total_revenue'] > 1000 && $item['utilization_rate'] > 50) {
                $item['performance'] = 'excellent';
            } elseif ($item['total_revenue'] > 500 && $item['utilization_rate'] > 25) {
                $item['performance'] = 'bon';
            } elseif ($item['total_revenue'] > 100) {
                $item['performance'] = 'moyen';
            } else {
                $item['performance'] = 'faible';
            }
        }

        return $profitability;
    }

    /**
     * Générer des alertes automatiques
     */
    public function generateAlerts()
    {
        $alerts = [];

        // 1. Matériels non loués depuis longtemps
        $unusedMaterials = Database::fetchAll("
            SELECT mm.id, mm.nom, COALESCE(MAX(ml.date_creation), mm.date_creation) as last_activity
            FROM modeles_materiel mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id
            WHERE mm.actif = 1
            GROUP BY mm.id, mm.nom, mm.date_creation
            HAVING last_activity < DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");

        foreach ($unusedMaterials as $material) {
            $alerts[] = [
                'type' => 'unused_material',
                'severity' => 'warning',
                'title' => 'Matériel peu utilisé',
                'message' => "Le matériel '{$material['nom']}' n'a pas été loué depuis " . 
                           date('d/m/Y', strtotime($material['last_activity'])),
                'material_id' => $material['id'],
                'action_needed' => 'Considérer réviser le prix ou la stratégie marketing'
            ];
        }

        // 2. Locations en retard
        $overdueRentals = Database::fetchAll("
            SELECT ml.id, c.raison_sociale, mm.nom as materiel_nom, 
                   DATEDIFF(NOW(), ml.date_retour_prevue) as days_overdue
            FROM materiel_loue ml
            JOIN clients c ON ml.client_id = c.id
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.statut = 'loue' 
            AND ml.date_retour_prevue < NOW()
        ");

        foreach ($overdueRentals as $rental) {
            $severity = $rental['days_overdue'] > 7 ? 'critical' : 'warning';
            $alerts[] = [
                'type' => 'overdue_rental',
                'severity' => $severity,
                'title' => 'Location en retard',
                'message' => "Location de '{$rental['materiel_nom']}' par {$rental['raison_sociale']} " .
                           "en retard de {$rental['days_overdue']} jour(s)",
                'rental_id' => $rental['id'],
                'action_needed' => 'Contacter le client pour organiser le retour'
            ];
        }

        // 3. Matériels en maintenance depuis longtemps
        $longMaintenance = Database::fetchAll("
            SELECT ml.id, mm.nom as materiel_nom, ml.date_creation as maintenance_start
            FROM materiel_loue ml
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.statut = 'maintenance'
            AND ml.date_creation < DATE_SUB(NOW(), INTERVAL 2 WEEK)
        ");

        foreach ($longMaintenance as $maintenance) {
            $alerts[] = [
                'type' => 'long_maintenance',
                'severity' => 'info',
                'title' => 'Maintenance prolongée',
                'message' => "Le matériel '{$maintenance['materiel_nom']}' est en maintenance depuis " .
                           date('d/m/Y', strtotime($maintenance['maintenance_start'])),
                'rental_id' => $maintenance['id'],
                'action_needed' => 'Vérifier le statut de la maintenance'
            ];
        }

        // 4. Dépôts non remboursés
        $unrefundedDeposits = Database::fetchAll("
            SELECT ml.id, c.raison_sociale, mm.nom as materiel_nom, ml.depot_verse
            FROM materiel_loue ml
            JOIN clients c ON ml.client_id = c.id
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            WHERE ml.statut = 'retourne'
            AND ml.depot_rembourse = 0
            AND ml.depot_verse > 0
            AND ml.date_retour_effective < DATE_SUB(NOW(), INTERVAL 1 WEEK)
        ");

        foreach ($unrefundedDeposits as $deposit) {
            $alerts[] = [
                'type' => 'unrefunded_deposit',
                'severity' => 'warning',
                'title' => 'Dépôt non remboursé',
                'message' => "Dépôt de {$deposit['depot_verse']}€ non remboursé pour {$deposit['raison_sociale']} " .
                           "({$deposit['materiel_nom']})",
                'rental_id' => $deposit['id'],
                'action_needed' => 'Traiter le remboursement du dépôt'
            ];
        }

        return $alerts;
    }

    /**
     * Exporter les données de matériel
     */
    public function exportMaterialData($format = 'csv', $filters = [])
    {
        $whereConditions = ["mm.actif = 1"];
        $params = [];

        if (!empty($filters['status'])) {
            $whereConditions[0] = "mm.actif = ?";
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        $materials = Database::fetchAll("
            SELECT 
                mm.*,
                COUNT(ml.id) as total_rentals,
                SUM(CASE WHEN ml.statut = 'loue' THEN 1 ELSE 0 END) as active_rentals,
                COALESCE(SUM(ml.depot_verse), 0) as total_deposits,
                COALESCE(SUM(
                    CEIL(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location) / 30) 
                    * mm.prix_mensuel
                ), 0) as estimated_revenue
            FROM modeles_materiel mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id
            WHERE {$whereClause}
            GROUP BY mm.id
            ORDER BY mm.nom
        ", $params);

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($materials);
            case 'json':
                return $this->exportToJson($materials);
            case 'excel':
                return $this->exportToExcel($materials);
            default:
                throw new \Exception('Format d\'export non supporté');
        }
    }

    private function exportToCsv($data)
    {
        $csv = "ID,Nom,Description,Prix mensuel,Dépôt garantie,Actif,Total locations,Locations actives,Dépôts collectés,Revenus estimés,Date création\n";
        
        foreach ($data as $row) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",%.2f,%.2f,%s,%d,%d,%.2f,%.2f,\"%s\"\n",
                $row['id'],
                str_replace('"', '""', $row['nom']),
                str_replace('"', '""', $row['description'] ?? ''),
                $row['prix_mensuel'],
                $row['depot_garantie'],
                $row['actif'] ? 'Oui' : 'Non',
                $row['total_rentals'],
                $row['active_rentals'],
                $row['total_deposits'],
                $row['estimated_revenue'],
                $row['date_creation']
            );
        }
        
        return $csv;
    }

    private function exportToJson($data)
    {
        return json_encode([
            'export_date' => date('Y-m-d H:i:s'),
            'total_count' => count($data),
            'materials' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function exportToExcel($data)
    {
        // Implémentation basique - dans un vrai projet, utiliser PhpSpreadsheet
        throw new \Exception('Export Excel non implémenté dans cette version');
    }
}
?>