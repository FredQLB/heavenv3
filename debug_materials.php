<?php
// Script de debug pour vérifier l'affichage des matériels
require_once 'app/Helpers/Database.php';
require_once 'app/Helpers/Logger.php';
use App\Helpers\Database;
use App\Helpers\Logger;

Database::init();

echo "<h1>Debug Matériels - Diagnostic complet</h1>";

try {
    // 1. Vérifier que la table existe
    echo "<h2>1. Vérification de la table modeles_materiel</h2>";
    $tables = Database::query("SHOW TABLES LIKE 'modeles_materiel'")->fetchAll();
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Table 'modeles_materiel' n'existe pas !</p>";
        echo "<p>Vous devez créer la table avec le script SQL fourni.</p>";
        exit;
    } else {
        echo "<p style='color: green;'>✅ Table 'modeles_materiel' existe</p>";
    }

    // 2. Afficher la structure de la table
    echo "<h2>2. Structure de la table</h2>";
    $structure = Database::query("DESCRIBE modeles_materiel")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr>";
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Compter le nombre total de matériels
    echo "<h2>3. Contenu de la table</h2>";
    $totalCount = Database::fetch("SELECT COUNT(*) as count FROM modeles_materiel")['count'] ?? 0;
    echo "<p><strong>Total matériels en base :</strong> {$totalCount}</p>";

    if ($totalCount === 0) {
        echo "<p style='color: orange;'>⚠️ Aucun matériel dans la base de données</p>";
        echo "<p><a href='/materials/create'>Créer un matériel de test</a></p>";
        
        // Insérer un matériel de test
        echo "<h3>Création d'un matériel de test</h3>";
        $testData = [
            'nom' => 'Casque VR Test - ' . date('Y-m-d H:i:s'),
            'description' => 'Matériel de test créé automatiquement',
            'prix_mensuel' => 99.99,
            'depot_garantie' => 200.00,
            'actif' => 1,
            'date_creation' => date('Y-m-d H:i:s')
        ];
        
        try {
            $insertId = Database::insert('modeles_materiel', $testData);
            echo "<p style='color: green;'>✅ Matériel de test créé avec ID : {$insertId}</p>";
            $totalCount = 1;
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur lors de la création : " . $e->getMessage() . "</p>";
        }
    }

    // 4. Afficher tous les matériels
    if ($totalCount > 0) {
        echo "<h3>Liste des matériels</h3>";
        $allMaterials = Database::fetchAll("SELECT * FROM modeles_materiel ORDER BY date_creation DESC");
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        echo "<th>ID</th><th>Nom</th><th>Prix mensuel</th><th>Dépôt</th><th>Actif</th><th>Date création</th>";
        echo "</tr>";
        
        foreach ($allMaterials as $material) {
            echo "<tr>";
            echo "<td>{$material['id']}</td>";
            echo "<td>" . htmlspecialchars($material['nom']) . "</td>";
            echo "<td>{$material['prix_mensuel']}€</td>";
            echo "<td>{$material['depot_garantie']}€</td>";
            echo "<td>" . ($material['actif'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>{$material['date_creation']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 5. Tester la requête du contrôleur
    echo "<h2>4. Test de la requête du MaterialController</h2>";
    
    // Simuler le code du contrôleur
    echo "<h3>Test MaterialController->index()</h3>";
    
    // Test 1 : Requête simple
    echo "<p><strong>Test 1 : Requête simple</strong></p>";
    $materials = Database::fetchAll("SELECT * FROM modeles_materiel ORDER BY nom");
    echo "<p>Résultat : " . count($materials) . " matériel(s) trouvé(s)</p>";
    
    // Test 2 : Requête avec filtres (comme dans le contrôleur)
    echo "<p><strong>Test 2 : Avec statistiques d'usage</strong></p>";
    $materialsWithStats = Database::fetchAll("
        SELECT mm.*
        FROM modeles_materiel mm
        ORDER BY mm.nom
    ");
    echo "<p>Résultat avec stats : " . count($materialsWithStats) . " matériel(s)</p>";

    // 6. Vérifier si MaterialModel existe
    echo "<h2>5. Vérification de MaterialModel</h2>";
    if (class_exists('\App\Models\MaterialModel')) {
        echo "<p style='color: green;'>✅ Classe MaterialModel trouvée</p>";
        
        try {
            $materialModel = new \App\Models\MaterialModel();
            $materials = $materialModel->findAll(false); // Inclure les inactifs
            echo "<p style='color: green;'>✅ MaterialModel->findAll() fonctionne : " . count($materials) . " matériel(s)</p>";
            
            if (!empty($materials)) {
                echo "<p><strong>Premier matériel retourné :</strong></p>";
                echo "<pre>" . print_r($materials[0], true) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur MaterialModel : " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Classe MaterialModel non trouvée</p>";
        echo "<p>Vérifiez que le fichier app/Models/MaterialModel.php existe</p>";
    }

    // 7. Tester le contrôleur directement
    echo "<h2>6. Test du MaterialController</h2>";
    if (class_exists('\App\Controllers\MaterialController')) {
        echo "<p style='color: green;'>✅ Classe MaterialController trouvée</p>";
        
        // Capture de sortie pour tester sans affichage
        ob_start();
        try {
            $controller = new \App\Controllers\MaterialController();
            echo "<p style='color: green;'>✅ MaterialController instancié avec succès</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur MaterialController : " . $e->getMessage() . "</p>";
        }
        ob_end_clean();
    } else {
        echo "<p style='color: red;'>❌ Classe MaterialController non trouvée</p>";
    }

    // 8. Vérifier les routes
    echo "<h2>7. Vérification des routes</h2>";
    $indexFile = file_get_contents('index.php');
    if (strpos($indexFile, 'MaterialController') !== false) {
        echo "<p style='color: green;'>✅ Routes MaterialController trouvées dans index.php</p>";
    } else {
        echo "<p style='color: red;'>❌ Routes MaterialController non trouvées dans index.php</p>";
        echo "<p>Ajoutez les routes du matériel dans index.php :</p>";
        echo "<pre>
\$router->get('/materials', 'MaterialController@index', ['auth']);
\$router->get('/materials/create', 'MaterialController@create', ['auth']);
\$router->post('/materials', 'MaterialController@store', ['auth']);
// ... autres routes
        </pre>";
    }

    // 9. Vérifier les permissions/authentification
    echo "<h2>8. Vérification de l'authentification</h2>";
    session_start();
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        echo "<p style='color: green;'>✅ Utilisateur connecté : " . ($_SESSION['user_name'] ?? 'Inconnu') . "</p>";
        echo "<p>Type : " . $_SESSION['user_type'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Aucun utilisateur connecté</p>";
        echo "<p>Vous devez être connecté pour accéder à /materials</p>";
    }

    // 10. URL actuelle et problèmes potentiels
    echo "<h2>9. Diagnostic final</h2>";
    echo "<p><strong>URL pour accéder aux matériels :</strong> <a href='/materials'>/materials</a></p>";
    
    if ($totalCount > 0) {
        echo "<p style='color: green;'>✅ Des matériels existent en base</p>";
        if (class_exists('\App\Models\MaterialModel')) {
            echo "<p style='color: green;'>✅ MaterialModel disponible</p>";
            if (class_exists('\App\Controllers\MaterialController')) {
                echo "<p style='color: green;'>✅ MaterialController disponible</p>";
                echo "<p style='color: blue;'>🔍 Le problème est probablement :</p>";
                echo "<ul>";
                echo "<li>Routes non ajoutées dans index.php</li>";
                echo "<li>Problème d'authentification</li>";
                echo "<li>Erreur dans la vue materials/index.php</li>";
                echo "<li>Variables non passées à la vue</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ MaterialController manquant</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ MaterialModel manquant</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Aucun matériel en base</p>";
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
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>