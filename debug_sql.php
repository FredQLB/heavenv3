<?php
// Script de debug pour vérifier la structure de la table formules_abonnement
require_once 'app/Helpers/Database.php';
use App\Helpers\Database;

Database::init();

echo "<h1>Debug SQL - Table formules_abonnement</h1>";

try {
    // 1. Vérifier que la table existe
    echo "<h2>1. Vérification existence de la table</h2>";
    $tables = Database::query("SHOW TABLES LIKE 'formules_abonnement'")->fetchAll();
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Table 'formules_abonnement' n'existe pas !</p>";
        echo "<p>Vous devez importer le fichier db.sql pour créer la structure.</p>";
        exit;
    } else {
        echo "<p style='color: green;'>✅ Table 'formules_abonnement' existe</p>";
    }

    // 2. Afficher la structure de la table
    echo "<h2>2. Structure de la table</h2>";
    $structure = Database::query("DESCRIBE formules_abonnement")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Tester une insertion simple
    echo "<h2>3. Test d'insertion</h2>";
    
    $testData = [
        'nom' => 'Test Formule Debug ' . date('Y-m-d H:i:s'),
        'type_abonnement' => 'application',
        'nombre_utilisateurs_inclus' => 1,
        'cout_utilisateur_supplementaire' => null,
        'duree' => 'mensuelle',
        'nombre_sous_categories' => null,
        'prix_base' => 29.99,
        'modele_materiel_id' => null,
        'stripe_product_id' => null,
        'stripe_price_id' => null,
        'stripe_price_supplementaire_id' => null,
        'lien_inscription' => null,
        'actif' => 1,
        'date_creation' => date('Y-m-d H:i:s')
    ];

    echo "<p><strong>Données à insérer :</strong></p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";

    try {
        $insertId = Database::insert('formules_abonnement', $testData);
        echo "<p style='color: green;'>✅ Insertion réussie ! ID généré : {$insertId}</p>";
        
        // Vérifier l'insertion
        $inserted = Database::fetch("SELECT * FROM formules_abonnement WHERE id = ?", [$insertId]);
        echo "<p><strong>Données insérées :</strong></p>";
        echo "<pre>" . print_r($inserted, true) . "</pre>";
        
        // Nettoyer (supprimer le test)
        Database::delete('formules_abonnement', 'id = ?', [$insertId]);
        echo "<p style='color: blue;'>🧹 Test nettoyé (formule supprimée)</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur lors de l'insertion :</p>";
        echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
        echo "<p><strong>Trace complète :</strong></p>";
        echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
    }

    // 4. Vérifier les contraintes de clés étrangères
    echo "<h2>4. Vérification des contraintes</h2>";
    
    // Vérifier si la table modeles_materiel existe
    $materialTables = Database::query("SHOW TABLES LIKE 'modeles_materiel'")->fetchAll();
    if (empty($materialTables)) {
        echo "<p style='color: orange;'>⚠️ Table 'modeles_materiel' n'existe pas. Cela peut causer des problèmes avec les clés étrangères.</p>";
    } else {
        echo "<p style='color: green;'>✅ Table 'modeles_materiel' existe</p>";
    }

    // 5. Afficher les formules existantes
    echo "<h2>5. Formules existantes</h2>";
    $existingPlans = Database::fetchAll("SELECT id, nom, type_abonnement, prix_base, actif, date_creation FROM formules_abonnement ORDER BY date_creation DESC LIMIT 5");
    if (empty($existingPlans)) {
        echo "<p>Aucune formule existante</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Type</th><th>Prix</th><th>Actif</th><th>Date création</th></tr>";
        foreach ($existingPlans as $plan) {
            echo "<tr>";
            echo "<td>{$plan['id']}</td>";
            echo "<td>{$plan['nom']}</td>";
            echo "<td>{$plan['type_abonnement']}</td>";
            echo "<td>{$plan['prix_base']}€</td>";
            echo "<td>" . ($plan['actif'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>{$plan['date_creation']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur générale :</p>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
    echo "<p><strong>Trace :</strong></p>";
    echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Informations de connexion :</strong></p>";
echo "<p>Base de données : " . Database::getConfig('connections.mysql.database') . "</p>";
echo "<p>Host : " . Database::getConfig('connections.mysql.host') . "</p>";
echo "<p>Utilisateur : " . Database::getConfig('connections.mysql.username') . "</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>