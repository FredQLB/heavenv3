<?php

namespace App\Models;

use App\Helpers\Database;

class SubscriptionPlan
{
    private $table = 'formules_abonnement';

    public function findAll($activeOnly = false)
    {
        $query = Database::query("
            SELECT fa.*, mm.nom as materiel_nom, mm.description as materiel_description,
                   mm.prix_mensuel as materiel_prix, mm.depot_garantie as materiel_depot
            FROM {$this->table} fa
            LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
        ");

        if ($activeOnly) {
            $query .= " WHERE fa.actif = 1";
        }

        $query .= " ORDER BY fa.type_abonnement, fa.prix_base ASC";

        return Database::query($query)->fetchAll();
    }

    public function findById($id)
    {
        return Database::fetch("
            SELECT fa.*, mm.nom as materiel_nom, mm.description as materiel_description,
                   mm.prix_mensuel as materiel_prix, mm.depot_garantie as materiel_depot
            FROM {$this->table} fa
            LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
            WHERE fa.id = ?
        ", [$id]);
    }

    public function findByType($type, $activeOnly = true)
    {
        $query = Database::table($this->table)
            ->where('type_abonnement', $type);

        if ($activeOnly) {
            $query->where('actif', 1);
        }

        return $query->orderBy('prix_base')->get();
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
        $plan = $this->findById($id);
        if (!plan) {
            return false;
        }

        $newStatus = $plan['actif'] ? 0 : 1;
        return $this->update($id, ['actif' => $newStatus]);
    }

    public function hasActiveSubscriptions($id)
    {
        $count = Database::table('abonnements_clients')
            ->where('formule_id', $id)
            ->whereIn('statut', ['actif', 'en_attente'])
            ->count();

        return $count > 0;
    }

    public function getSubscriptionsCount($id)
    {
        return [
            'active' => Database::table('abonnements_clients')
                ->where('formule_id', $id)
                ->where('statut', 'actif')
                ->count(),
            'pending' => Database::table('abonnements_clients')
                ->where('formule_id', $id)
                ->where('statut', 'en_attente')
                ->count(),
            'total' => Database::table('abonnements_clients')
                ->where('formule_id', $id)
                ->count()
        ];
    }

    public function getRevenue($id, $period = 'monthly')
    {
        $revenue = Database::fetch("
            SELECT 
                COALESCE(SUM(ac.prix_total_mensuel), 0) as monthly_revenue,
                COUNT(ac.id) as active_subscriptions
            FROM abonnements_clients ac
            WHERE ac.formule_id = ? AND ac.statut = 'actif'
        ", [$id]);

        $monthlyRevenue = $revenue['monthly_revenue'] ?? 0;

        return [
            'monthly' => $monthlyRevenue,
            'yearly' => $monthlyRevenue * 12,
            'active_subscriptions' => $revenue['active_subscriptions'] ?? 0
        ];
    }

    public function getStats()
    {
        $stats = [];

        // Total des formules
        $stats['total'] = Database::table($this->table)->count();

        // Formules actives
        $stats['active'] = Database::table($this->table)
            ->where('actif', 1)
            ->count();

        // Par type
        $stats['by_type'] = [];
        $types = ['application', 'application_materiel', 'materiel_seul'];
        
        foreach ($types as $type) {
            $stats['by_type'][$type] = Database::table($this->table)
                ->where('type_abonnement', $type)
                ->where('actif', 1)
                ->count();
        }

        // Revenue total estimé
        $revenue = Database::fetch("
            SELECT COALESCE(SUM(ac.prix_total_mensuel), 0) as total_revenue
            FROM abonnements_clients ac
            JOIN {$this->table} fa ON ac.formule_id = fa.id
            WHERE ac.statut = 'actif' AND fa.actif = 1
        ");

        $stats['total_revenue'] = $revenue['total_revenue'] ?? 0;

        return $stats;
    }

    public function search($query, $filters = [])
    {
        $sql = "
            SELECT fa.*, mm.nom as materiel_nom
            FROM {$this->table} fa
            LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
            WHERE 1=1
        ";
        $params = [];

        // Recherche textuelle
        if (!empty($query)) {
            $sql .= " AND (fa.nom LIKE ? OR fa.type_abonnement LIKE ?)";
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }

        // Filtres
        if (isset($filters['type']) && $filters['type'] !== '') {
            $sql .= " AND fa.type_abonnement = ?";
            $params[] = $filters['type'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= " AND fa.actif = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['duration']) && $filters['duration'] !== '') {
            $sql .= " AND fa.duree = ?";
            $params[] = $filters['duration'];
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $sql .= " AND fa.prix_base >= ?";
            $params[] = $filters['price_min'];
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $sql .= " AND fa.prix_base <= ?";
            $params[] = $filters['price_max'];
        }

        $sql .= " ORDER BY fa.type_abonnement, fa.prix_base";

        return Database::query($sql, $params)->fetchAll();
    }

    public function validateData($data, $id = null)
    {
        $errors = [];

        // Validation du nom
        if (empty($data['nom'])) {
            $errors[] = 'Le nom de la formule est obligatoire';
        } elseif (strlen($data['nom']) > 100) {
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        // Vérifier l'unicité du nom
        $nameQuery = Database::table($this->table)->where('nom', $data['nom']);
        if ($id) {
            $nameQuery->where('id', '!=', $id);
        }
        
        if ($nameQuery->exists()) {
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

        // Validation du prix de base
        if (!is_numeric($data['prix_base']) || $data['prix_base'] < 0) {
            $errors[] = 'Le prix de base doit être un nombre positif';
        }

        // Validation du matériel pour les types qui l'incluent
        if (in_array($data['type_abonnement'], ['application_materiel', 'materiel_seul'])) {
            if (empty($data['modele_materiel_id'])) {
                $errors[] = 'Le modèle de matériel est obligatoire pour ce type d\'abonnement';
            } else {
                $material = Database::table('modeles_materiel')
                    ->where('id', $data['modele_materiel_id'])
                    ->where('actif', 1)
                    ->first();
                    
                if (!$material) {
                    $errors[] = 'Modèle de matériel invalide';
                }
            }
        }

        return $errors;
    }

    public function canDelete($id)
    {
        // Vérifier s'il y a des abonnements actifs
        $activeSubscriptions = Database::table('abonnements_clients')
            ->where('formule_id', $id)
            ->whereIn('statut', ['actif', 'en_attente'])
            ->count();

        if ($activeSubscriptions > 0) {
            return [
                'can_delete' => false,
                'reason' => 'Cette formule est utilisée par des abonnements actifs'
            ];
        }

        return ['can_delete' => true];
    }

    public function generateSignupLink($id)
    {
        $plan = $this->findById($id);
        if (!$plan || !$plan['actif']) {
            return null;
        }

        // Générer un lien d'inscription unique
        $token = bin2hex(random_bytes(16));
        $link = "https://account.cover-ar.com/signup/{$token}";

        // Sauvegarder le lien
        $this->update($id, ['lien_inscription' => $link]);

        return $link;
    }
}
?>