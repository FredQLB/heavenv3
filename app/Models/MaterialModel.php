<?php

namespace App\Models;

use App\Helpers\Database;

class MaterialModel
{
    private $table = 'modeles_materiel';

    public function findAll($activeOnly = true)
    {
        $query = Database::table($this->table);
        
        if ($activeOnly) {
            $query->where('actif', 1);
        }
        
        return $query->orderBy('nom')->get();
    }

    public function findById($id)
    {
        return Database::table($this->table)
            ->where('id', $id)
            ->first();
    }

    public function create($data)
    {
        return Database::insert($this->table, array_merge($data, [
            'date_creation' => date('Y-m-d H:i:s')
        ]));
    }

    public function update($id, $data)
    {
        return Database::table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    public function delete($id)
    {
        return Database::table($this->table)
            ->where('id', $id)
            ->delete();
    }

    public function toggleStatus($id)
    {
        $material = $this->findById($id);
        if (!$material) {
            return false;
        }

        $newStatus = $material['actif'] ? 0 : 1;
        return $this->update($id, ['actif' => $newStatus]);
    }

    public function isUsedInPlans($id)
    {
        $count = Database::table('formules_abonnement')
            ->where('modele_materiel_id', $id)
            ->count();
        
        return $count > 0;
    }

    public function isUsedInRentals($id)
    {
        $count = Database::table('materiel_loue')
            ->where('modele_materiel_id', $id)
            ->whereIn('statut', ['loue', 'maintenance'])
            ->count();
        
        return $count > 0;
    }

    public function getUsageStats($id)
    {
        $stats = [
            'plans_count' => 0,
            'active_rentals' => 0,
            'total_rentals' => 0,
            'revenue_generated' => 0
        ];

        // Nombre de formules utilisant ce matériel
        $stats['plans_count'] = Database::table('formules_abonnement')
            ->where('modele_materiel_id', $id)
            ->where('actif', 1)
            ->count();

        // Locations actives
        $stats['active_rentals'] = Database::table('materiel_loue')
            ->where('modele_materiel_id', $id)
            ->whereIn('statut', ['loue', 'maintenance'])
            ->count();

        // Total des locations
        $stats['total_rentals'] = Database::table('materiel_loue')
            ->where('modele_materiel_id', $id)
            ->count();

        // Revenus générés (estimation basée sur les abonnements actifs)
        $revenue = Database::fetch("
            SELECT COALESCE(SUM(ac.prix_total_mensuel), 0) as revenue
            FROM abonnements_clients ac
            JOIN formules_abonnement fa ON ac.formule_id = fa.id
            WHERE fa.modele_materiel_id = ? AND ac.statut = 'actif'
        ", [$id]);

        $stats['revenue_generated'] = $revenue['revenue'] ?? 0;

        return $stats;
    }

    public function search($query, $filters = [])
    {
        $search = Database::table($this->table);

        // Recherche textuelle
        if (!empty($query)) {
            $search->where(function($q) use ($query) {
                $q->whereLike('nom', "%{$query}%")
                  ->orWhereLike('description', "%{$query}%");
            });
        }

        // Filtres
        if (isset($filters['status']) && $filters['status'] !== '') {
            $search->where('actif', $filters['status']);
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $search->where('prix_mensuel', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $search->where('prix_mensuel', '<=', $filters['price_max']);
        }

        return $search->orderBy('nom')->get();
    }

    public function validateData($data, $id = null)
    {
        $errors = [];

        // Validation du nom
        if (empty($data['nom'])) {
            $errors[] = 'Le nom du matériel est obligatoire';
        } elseif (strlen($data['nom']) > 100) {
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        // Vérifier l'unicité du nom
        $nameQuery = Database::table($this->table)->where('nom', $data['nom']);
        if ($id) {
            $nameQuery->where('id', '!=', $id);
        }
        
        if ($nameQuery->exists()) {
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

    public function getStats()
    {
        $stats = [];

        // Total des matériels
        $stats['total'] = Database::table($this->table)->count();

        // Matériels actifs
        $stats['active'] = Database::table($this->table)
            ->where('actif', 1)
            ->count();

        // Matériels actuellement loués
        $stats['rented'] = Database::fetch("
            SELECT COUNT(DISTINCT modele_materiel_id) as count
            FROM materiel_loue
            WHERE statut = 'loue'
        ")['count'] ?? 0;

        // Revenue total estimé des matériels
        $revenue = Database::fetch("
            SELECT COALESCE(SUM(fa.prix_base), 0) as total_revenue
            FROM formules_abonnement fa
            JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
            WHERE fa.actif = 1 AND mm.actif = 1
        ");

        $stats['total_revenue'] = $revenue['total_revenue'] ?? 0;

        // Matériel le plus loué
        $mostRented = Database::fetch("
            SELECT mm.nom, COUNT(ml.id) as rental_count
            FROM modeles_materiel mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id
            WHERE mm.actif = 1
            GROUP BY mm.id, mm.nom
            ORDER BY rental_count DESC
            LIMIT 1
        ");

        $stats['most_rented'] = $mostRented ? [
            'name' => $mostRented['nom'],
            'count' => $mostRented['rental_count']
        ] : null;

        return $stats;
    }

    public function getRecentActivity($limit = 10)
    {
        return Database::fetchAll("
            SELECT 
                ml.date_creation,
                ml.statut,
                mm.nom as materiel_nom,
                c.raison_sociale as client_nom,
                'rental' as type
            FROM materiel_loue ml
            JOIN modeles_materiel mm ON ml.modele_materiel_id = mm.id
            JOIN clients c ON ml.client_id = c.id
            ORDER BY ml.date_creation DESC
            LIMIT ?
        ", [$limit]);
    }

    public function getMaterialsForPlans()
    {
        return Database::table($this->table)
            ->where('actif', 1)
            ->select(['id', 'nom', 'prix_mensuel', 'depot_garantie'])
            ->orderBy('nom')
            ->get();
    }

    public function getMaterialsByCategory()
    {
        // Pour une future implémentation avec catégories de matériel
        // Pour l'instant, retourne tous les matériels groupés par prix
        return Database::fetchAll("
            SELECT 
                CASE 
                    WHEN prix_mensuel < 50 THEN 'Économique'
                    WHEN prix_mensuel < 100 THEN 'Standard'
                    ELSE 'Premium'
                END as category,
                COUNT(*) as count,
                AVG(prix_mensuel) as avg_price
            FROM {$this->table}
            WHERE actif = 1
            GROUP BY category
            ORDER BY avg_price
        ");
    }

    public function getMaintenanceSchedule()
    {
        // Matériels nécessitant une maintenance préventive
        return Database::fetchAll("
            SELECT 
                mm.id,
                mm.nom,
                COUNT(ml.id) as total_rentals,
                SUM(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as total_days_used,
                MAX(ml.date_retour_effective) as last_return
            FROM {$this->table} mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id
            WHERE mm.actif = 1
            GROUP BY mm.id, mm.nom
            HAVING total_days_used > 180 -- Plus de 6 mois d'utilisation
            ORDER BY total_days_used DESC
        ");
    }

    public function getUtilizationReport($period = '12months')
    {
        $periodCondition = match($period) {
            '1month' => "DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            '3months' => "DATE_SUB(NOW(), INTERVAL 3 MONTH)", 
            '6months' => "DATE_SUB(NOW(), INTERVAL 6 MONTH)",
            '12months' => "DATE_SUB(NOW(), INTERVAL 12 MONTH)",
            default => "DATE_SUB(NOW(), INTERVAL 12 MONTH)"
        };

        return Database::fetchAll("
            SELECT 
                mm.id,
                mm.nom,
                mm.prix_mensuel,
                COUNT(ml.id) as rental_count,
                SUM(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as total_days_rented,
                AVG(DATEDIFF(COALESCE(ml.date_retour_effective, NOW()), ml.date_location)) as avg_rental_duration,
                SUM(ml.depot_verse) as total_deposits_collected,
                SUM(CASE WHEN ml.statut = 'loue' THEN 1 ELSE 0 END) as currently_rented
            FROM {$this->table} mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id 
                AND ml.date_creation >= {$periodCondition}
            WHERE mm.actif = 1
            GROUP BY mm.id, mm.nom, mm.prix_mensuel
            ORDER BY rental_count DESC, mm.nom
        ");
    }

    public function getAvailableMaterials()
    {
        // Matériels disponibles (non loués actuellement)
        return Database::fetchAll("
            SELECT mm.*
            FROM {$this->table} mm
            LEFT JOIN materiel_loue ml ON mm.id = ml.modele_materiel_id 
                AND ml.statut IN ('loue', 'maintenance')
            WHERE mm.actif = 1 AND ml.id IS NULL
            ORDER BY mm.nom
        ");
    }

    public function canDelete($id)
    {
        $reasons = [];

        // Vérifier les formules d'abonnement
        $plansCount = Database::table('formules_abonnement')
            ->where('modele_materiel_id', $id)
            ->count();

        if ($plansCount > 0) {
            $reasons[] = "Utilisé dans {$plansCount} formule(s) d'abonnement";
        }

        // Vérifier les locations actives
        $activeRentalsCount = Database::table('materiel_loue')
            ->where('modele_materiel_id', $id)
            ->whereIn('statut', ['loue', 'maintenance'])
            ->count();

        if ($activeRentalsCount > 0) {
            $reasons[] = "A {$activeRentalsCount} location(s) active(s)";
        }

        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons
        ];
    }

    public function clone($id, $newName = null)
    {
        $original = $this->findById($id);
        if (!$original) {
            throw new \Exception('Matériel original non trouvé');
        }

        // Préparer les données du clone
        $cloneData = $original;
        unset($cloneData['id']);
        unset($cloneData['date_creation']);

        // Nouveau nom
        if ($newName) {
            $cloneData['nom'] = $newName;
        } else {
            $cloneData['nom'] = $original['nom'] . ' (Copie)';
        }

        // Vérifier l'unicité du nom
        $counter = 1;
        $baseName = $cloneData['nom'];
        while (Database::table($this->table)->where('nom', $cloneData['nom'])->exists()) {
            $cloneData['nom'] = $baseName . ' (' . $counter . ')';
            $counter++;
        }

        // Le clone est inactif par défaut
        $cloneData['actif'] = 0;

        return $this->create($cloneData);
    }

    /**
     * Méthodes pour compatibilité avec DatabaseQueryBuilder
     */
    private function where($column, $operator, $value = null)
    {
        // Cette méthode sera utilisée dans search() si besoin
        // Pour l'instant, utilisation directe de Database::table()
    }
}
?>