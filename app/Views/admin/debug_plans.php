<?php
// Script de debug pour vérifier la récupération des formules
require_once 'app/Helpers/Database.php';
require_once 'app/Helpers/Logger.php';
use App\Helpers\Database;
use App\Helpers\Logger;

Database::init();

echo "<h1>Debug Formules d'Abonnement</h1>";

try {
    // 1. Vérifier le contenu de la table
    echo "<h2>1. Contenu de la table formules_abonnement</h2>";
    $allPlans = Database::fetchAll("SELECT * FROM formules_abonnement ORDER BY date_creation DESC");
    
    if (empty($allPlans)) {
        echo "<p style='color: orange;'>⚠️ Aucune formule trouvée dans la base de données</p>";
        echo "<p><a href='/subscription-plans/create'>Créer une formule de test</a></p>";
    } else {
        echo "<p style='color: green;'>✅ " . count($allPlans) . " formule(s) trouvée(s)</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>ID</th><th>Nom</th><th>Type</th><th>Prix</th><th>Utilisateurs</th>";
        echo "<th>Matériel ID</th><th>Stripe Product</th><th>Actif</th><th>Date</th>";
        echo "</tr>";
        
        foreach ($allPlans as $plan) {
            echo "<tr>";
            echo "<td>{$plan['id']}</td>";
            echo "<td>" . htmlspecialchars($plan['nom']) . "</td>";
            echo "<td>{$plan['type_abonnement']}</td>";
            echo "<td>{$plan['prix_base']}€</td>";
            echo "<td>{$plan['nombre_utilisateurs_inclus']}</td>";
            echo "<td>" . ($plan['modele_materiel_id'] ?: '-') . "</td>";
            echo "<td>" . ($plan['stripe_product_id'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>" . ($plan['actif'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>{$plan['date_creation']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 2. Tester la requête du contrôleur
    echo "<h2>2. Test de la requête du contrôleur</h2>";
    $controllerQuery = "
        SELECT fa.*, mm.nom as materiel_nom, mm.description as materiel_description
        FROM formules_abonnement fa
        LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
        ORDER BY fa.type_abonnement, fa.prix_base ASC
    ";
    
    echo "<p><strong>Requête utilisée :</strong></p>";
    echo "<pre>" . htmlspecialchars($controllerQuery) . "</pre>";
    
    $plans = Database::query($controllerQuery)->fetchAll();
    
    if (empty($plans)) {
        echo "<p style='color: red;'>❌ La requête du contrôleur ne retourne aucun résultat</p>";
        
        // Test de la table modeles_materiel
        echo "<h3>Vérification de la table modeles_materiel</h3>";
        try {
            $materials = Database::fetchAll("SELECT * FROM modeles_materiel LIMIT 5");
            if (empty($materials)) {
                echo "<p style='color: orange;'>⚠️ Table modeles_materiel vide</p>";
                echo "<p>Cela ne devrait pas empêcher l'affichage des formules.</p>";
            } else {
                echo "<p style='color: green;'>✅ Table modeles_materiel contient " . count($materials) . " éléments</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur avec la table modeles_materiel: " . $e->getMessage() . "</p>";
            echo "<p>Essayons une requête sans JOIN...</p>";
            
            // Test sans JOIN
            $plansNoJoin = Database::fetchAll("SELECT * FROM formules_abonnement ORDER BY type_abonnement, prix_base ASC");
            if (!empty($plansNoJoin)) {
                echo "<p style='color: green;'>✅ Requête sans JOIN fonctionne: " . count($plansNoJoin) . " résultats</p>";
                echo "<p style='color: orange;'>Le problème vient du LEFT JOIN avec modeles_materiel</p>";
            }
        }
        
    } else {
        echo "<p style='color: green;'>✅ La requête du contrôleur retourne " . count($plans) . " résultat(s)</p>";
        
        // Afficher les premiers résultats
        echo "<h3>Premiers résultats :</h3>";
        foreach (array_slice($plans, 0, 3) as $plan) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
            echo "<strong>" . htmlspecialchars($plan['nom']) . "</strong><br>";
            echo "Type: {$plan['type_abonnement']}<br>";
            echo "Prix: {$plan['prix_base']}€<br>";
            echo "Matériel: " . ($plan['materiel_nom'] ?: 'Aucun') . "<br>";
            echo "Actif: " . ($plan['actif'] ? 'Oui' : 'Non') . "<br>";
            echo "</div>";
        }
    }

    // 3. Calcul des statistiques
    echo "<h2>3. Calcul des statistiques</h2>";
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

    echo "<pre>" . print_r($stats, true) . "</pre>";

    // 4. Test du contrôleur directement
    echo "<h2>4. Test du contrôleur SubscriptionPlanController</h2>";
    
    if (class_exists('\App\Controllers\SubscriptionPlanController')) {
        echo "<p style='color: green;'>✅ Classe SubscriptionPlanController trouvée</p>";
        
        // Test de la méthode index (capture output)
        ob_start();
        try {
            $controller = new \App\Controllers\SubscriptionPlanController();
            // Note: On ne peut pas vraiment tester index() car elle fait des require
            echo "<p style='color: green;'>✅ Contrôleur instancié avec succès</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur lors de l'instanciation: " . $e->getMessage() . "</p>";
        }
        ob_end_clean();
        
    } else {
        echo "<p style='color: red;'>❌ Classe SubscriptionPlanController non trouvée</p>";
    }

    // 5. Vérifier les logs
    echo "<h2>5. Logs récents</h2>";
    try {
        $logFile = __DIR__ . '/storage/logs/app.log';
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $recentLogs = array_slice(explode("\n", $logs), -10);
            echo "<p><strong>10 dernières lignes de log :</strong></p>";
            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: auto;'>";
            echo htmlspecialchars(implode("\n", $recentLogs));
            echo "</pre>";
        } else {
            echo "<p>Fichier de log non trouvé : {$logFile}</p>";
        }
    } catch (Exception $e) {
        echo "<p>Erreur lecture logs: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur générale :</p>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
    echo "<p><strong>Trace :</strong></p>";
    echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>