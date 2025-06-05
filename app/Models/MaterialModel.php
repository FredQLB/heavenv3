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
}
?>