-- Base de données Cover AR - Interface Administrateur
-- Compatible MariaDB / MySQL


-- Table des clients
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raison_sociale VARCHAR(255) NOT NULL,
    adresse TEXT NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    pays VARCHAR(100) NOT NULL DEFAULT 'France',
    email_facturation VARCHAR(255) NOT NULL,
    numero_tva VARCHAR(50) NULL,
    stripe_customer_id VARCHAR(255) UNIQUE,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telephone VARCHAR(20) NULL,
    mot_de_passe VARCHAR(32) NOT NULL, -- MD5 hash
    identifiant_appareil VARCHAR(255) NULL, -- UID ajouté lors de la première connexion
    type_utilisateur ENUM('MegaAdmin', 'Admin', 'User') NOT NULL DEFAULT 'User',
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

-- Table des catégories (niveau 1)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    mot_cle VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des sous-catégories (niveau 2)
CREATE TABLE sous_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    mot_cle VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Table des sous-sous-catégories (niveau 3)
CREATE TABLE sous_sous_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sous_categorie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    mot_cle VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sous_categorie_id) REFERENCES sous_categories(id) ON DELETE CASCADE
);

-- Table des modèles de matériel
CREATE TABLE modeles_materiel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT NULL,
    prix_mensuel DECIMAL(10,2) NOT NULL,
    depot_garantie DECIMAL(10,2) NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des formules d'abonnement
CREATE TABLE formules_abonnement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type_abonnement ENUM('application', 'application_materiel', 'materiel_seul') NOT NULL,
    nombre_utilisateurs_inclus INT NOT NULL DEFAULT 0,
    cout_utilisateur_supplementaire DECIMAL(10,2) NULL,
    duree ENUM('mensuelle', 'annuelle') NOT NULL,
    nombre_sous_categories INT NULL, -- NULL = illimité
    prix_base DECIMAL(10,2) NOT NULL,
    modele_materiel_id INT NULL, -- Pour les abonnements incluant du matériel
    stripe_product_id VARCHAR(255) NULL,
    stripe_price_id VARCHAR(255) NULL,
    stripe_price_supplementaire_id VARCHAR(255) NULL, -- Pour les utilisateurs supplémentaires
    lien_inscription TEXT NULL,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (modele_materiel_id) REFERENCES modeles_materiel(id) ON DELETE SET NULL
);

-- Table des abonnements clients
CREATE TABLE abonnements_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    formule_id INT NOT NULL,
    type_abonnement ENUM('principal', 'supplementaire') NOT NULL DEFAULT 'principal',
    stripe_subscription_id VARCHAR(255) UNIQUE,
    statut ENUM('actif', 'suspendu', 'annule', 'en_attente') DEFAULT 'en_attente',
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    nombre_utilisateurs_actuels INT DEFAULT 0,
    prix_total_mensuel DECIMAL(10,2) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (formule_id) REFERENCES formules_abonnement(id) ON DELETE RESTRICT
);

-- Table de liaison client - sous-catégories (accès autorisés)
CREATE TABLE client_sous_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    sous_categorie_id INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_client_sous_categorie (client_id, sous_categorie_id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (sous_categorie_id) REFERENCES sous_categories(id) ON DELETE CASCADE
);

-- Table du matériel loué
CREATE TABLE materiel_loue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    abonnement_id INT NOT NULL,
    modele_materiel_id INT NOT NULL,
    numero_serie VARCHAR(100) NULL,
    inclus_dans_abonnement BOOLEAN DEFAULT FALSE, -- TRUE si inclus, FALSE si facturé séparément
    statut ENUM('loue', 'retourne', 'maintenance') DEFAULT 'loue',
    date_location DATE NOT NULL,
    date_retour_prevue DATE NULL,
    date_retour_effective DATE NULL,
    depot_verse DECIMAL(10,2) DEFAULT 0.00,
    depot_rembourse BOOLEAN DEFAULT FALSE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (abonnement_id) REFERENCES abonnements_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (modele_materiel_id) REFERENCES modeles_materiel(id)
);

-- Table des factures Stripe (stockage des références)
CREATE TABLE factures_stripe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    abonnement_id INT NULL,
    stripe_invoice_id VARCHAR(255) NOT NULL UNIQUE,
    montant DECIMAL(10,2) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    lien_telechargement TEXT NULL,
    date_facture DATE NOT NULL,
    date_echeance DATE NULL,
    date_paiement DATETIME NULL,
    type_facture ENUM('abonnement', 'depot_garantie', 'autre') DEFAULT 'abonnement',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (abonnement_id) REFERENCES abonnements_clients(id) ON DELETE SET NULL
);

-- Index pour optimiser les performances
CREATE INDEX idx_clients_email ON clients(email_facturation);
CREATE INDEX idx_clients_stripe ON clients(stripe_customer_id);
CREATE INDEX idx_utilisateurs_email ON utilisateurs(email);
CREATE INDEX idx_utilisateurs_client ON utilisateurs(client_id);
CREATE INDEX idx_utilisateurs_type ON utilisateurs(type_utilisateur);
CREATE INDEX idx_abonnements_client ON abonnements_clients(client_id);
CREATE INDEX idx_abonnements_statut ON abonnements_clients(statut);
CREATE INDEX idx_materiel_client ON materiel_loue(client_id);
CREATE INDEX idx_factures_client ON factures_stripe(client_id);

-- Insertion de l'utilisateur MegaAdmin de test
INSERT INTO utilisateurs (
    nom, 
    prenom, 
    email, 
    mot_de_passe, 
    type_utilisateur, 
    actif
) VALUES (
    'Administrateur',
    'Système',
    'admin@cover-ar.com',
    MD5('admin123'), -- Mot de passe : admin123 (crypté en MD5)
    'MegaAdmin',
    TRUE
);

-- Vues pour faciliter les requêtes courantes

-- Vue des clients avec leurs informations d'abonnement
CREATE VIEW vue_clients_abonnements AS
SELECT 
    c.id,
    c.raison_sociale,
    c.email_facturation,
    c.actif as client_actif,
    COUNT(DISTINCT u.id) as nombre_utilisateurs,
    COUNT(DISTINCT ac.id) as nombre_abonnements,
    GROUP_CONCAT(DISTINCT fa.nom SEPARATOR ', ') as formules_souscrites
FROM clients c
LEFT JOIN utilisateurs u ON c.id = u.client_id AND u.actif = TRUE AND u.type_utilisateur != 'MegaAdmin'
LEFT JOIN abonnements_clients ac ON c.id = ac.client_id AND ac.statut = 'actif'
LEFT JOIN formules_abonnement fa ON ac.formule_id = fa.id
GROUP BY c.id, c.raison_sociale, c.email_facturation, c.actif;

-- Vue des utilisateurs avec leurs informations client
CREATE VIEW vue_utilisateurs_complet AS
SELECT 
    u.id,
    u.nom,
    u.prenom,
    u.email,
    u.telephone,
    u.type_utilisateur,
    u.actif,
    u.identifiant_appareil,
    c.raison_sociale as nom_client,
    c.id as client_id
FROM utilisateurs u
LEFT JOIN clients c ON u.client_id = c.id;

-- Procédure stockée pour vérifier les limites d'utilisateurs
DELIMITER //
CREATE PROCEDURE VerifierLimiteUtilisateurs(IN p_client_id INT)
BEGIN
    DECLARE v_limite INT DEFAULT 0;
    DECLARE v_actuel INT DEFAULT 0;
    DECLARE v_message VARCHAR(255);
    
    -- Récupérer la limite d'utilisateurs pour le client
    SELECT COALESCE(SUM(fa.nombre_utilisateurs_inclus), 0)
    INTO v_limite
    FROM abonnements_clients ac
    JOIN formules_abonnement fa ON ac.formule_id = fa.id
    WHERE ac.client_id = p_client_id AND ac.statut = 'actif';
    
    -- Récupérer le nombre actuel d'utilisateurs actifs (hors MegaAdmin)
    SELECT COUNT(*)
    INTO v_actuel
    FROM utilisateurs
    WHERE client_id = p_client_id 
    AND actif = TRUE 
    AND type_utilisateur != 'MegaAdmin';
    
    -- Retourner les informations
    SELECT 
        v_limite as limite_utilisateurs,
        v_actuel as utilisateurs_actuels,
        (v_limite - v_actuel) as utilisateurs_disponibles,
        CASE 
            WHEN v_actuel > v_limite THEN CONCAT('Dépassement de ', (v_actuel - v_limite), ' utilisateur(s)')
            WHEN v_actuel = v_limite THEN 'Limite atteinte'
            ELSE 'Dans les limites'
        END as statut;
END //
DELIMITER ;