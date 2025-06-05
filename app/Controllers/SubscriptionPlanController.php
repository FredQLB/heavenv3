<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;

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
}
?>