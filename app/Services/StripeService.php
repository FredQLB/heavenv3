<?php

namespace App\Services;

use App\Helpers\Config;
use App\Helpers\Logger;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Invoice;
use Exception;

class StripeService
{
    private static $initialized = false;

    public static function init()
    {
        if (!self::$initialized) {
            $stripeConfig = Config::stripe();
            
            if (empty($stripeConfig['secret_key'])) {
                throw new Exception('Clé secrète Stripe non configurée');
            }
            
            Stripe::setApiKey($stripeConfig['secret_key']);
            self::$initialized = true;
            
            Logger::info('Service Stripe initialisé', [
                'environment' => Config::get('stripe.environment')
            ]);
        }
    }

    /**
     * Créer un produit Stripe pour une formule d'abonnement
     */
    public static function createProduct($planData)
    {
        try {
            self::init();
            
            $productData = [
                'name' => $planData['nom'],
                'description' => self::generateProductDescription($planData),
                'metadata' => [
                    'plan_id' => $planData['id'] ?? 'new',
                    'type' => $planData['type_abonnement'],
                    'users_included' => $planData['nombre_utilisateurs_inclus'],
                    'categories_limit' => $planData['nombre_sous_categories'] ?? 'unlimited',
                    'duration' => $planData['duree'],
                    'created_by' => 'cover_ar_admin'
                ]
            ];

            $product = Product::create($productData);
            
            Logger::info('Produit Stripe créé', [
                'product_id' => $product->id,
                'plan_name' => $planData['nom']
            ]);

            return $product;
            
        } catch (Exception $e) {
            Logger::error('Erreur création produit Stripe', [
                'error' => $e->getMessage(),
                'plan_data' => $planData
            ]);
            throw $e;
        }
    }

    /**
     * Créer un prix Stripe pour une formule
     */
    public static function createPrice($productId, $planData)
    {
        try {
            self::init();
            
            $priceData = [
                'product' => $productId,
                'unit_amount' => (int)($planData['prix_base'] * 100), // Convertir en centimes
                'currency' => Config::get('stripe.currency', 'eur'),
                'recurring' => [
                    'interval' => $planData['duree'] === 'mensuelle' ? 'month' : 'year',
                    'interval_count' => 1
                ],
                'metadata' => [
                    'plan_id' => $planData['id'] ?? 'new',
                    'base_price' => $planData['prix_base'],
                    'duration' => $planData['duree']
                ]
            ];

            $price = Price::create($priceData);
            
            Logger::info('Prix Stripe créé', [
                'price_id' => $price->id,
                'amount' => $planData['prix_base'],
                'currency' => $priceData['currency']
            ]);

            return $price;
            
        } catch (Exception $e) {
            Logger::error('Erreur création prix Stripe', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'plan_data' => $planData
            ]);
            throw $e;
        }
    }

    /**
     * Créer un prix pour les utilisateurs supplémentaires
     */
    public static function createExtraUserPrice($productId, $planData)
    {
        if (empty($planData['cout_utilisateur_supplementaire']) || $planData['cout_utilisateur_supplementaire'] <= 0) {
            return null;
        }

        try {
            self::init();
            
            $priceData = [
                'product' => $productId,
                'unit_amount' => (int)($planData['cout_utilisateur_supplementaire'] * 100),
                'currency' => Config::get('stripe.currency', 'eur'),
                'recurring' => [
                    'interval' => $planData['duree'] === 'mensuelle' ? 'month' : 'year',
                    'interval_count' => 1
                ],
                'metadata' => [
                    'plan_id' => $planData['id'] ?? 'new',
                    'type' => 'extra_user',
                    'base_price' => $planData['cout_utilisateur_supplementaire'],
                    'duration' => $planData['duree']
                ]
            ];

            $price = Price::create($priceData);
            
            Logger::info('Prix utilisateur supplémentaire Stripe créé', [
                'price_id' => $price->id,
                'amount' => $planData['cout_utilisateur_supplementaire']
            ]);

            return $price;
            
        } catch (Exception $e) {
            Logger::error('Erreur création prix utilisateur supplémentaire', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour un produit Stripe
     */
    public static function updateProduct($productId, $planData)
    {
        try {
            self::init();
            
            $updateData = [
                'name' => $planData['nom'],
                'description' => self::generateProductDescription($planData),
                'metadata' => [
                    'plan_id' => $planData['id'],
                    'type' => $planData['type_abonnement'],
                    'users_included' => $planData['nombre_utilisateurs_inclus'],
                    'categories_limit' => $planData['nombre_sous_categories'] ?? 'unlimited',
                    'duration' => $planData['duree'],
                    'updated_by' => 'cover_ar_admin',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            $product = Product::update($productId, $updateData);
            
            Logger::info('Produit Stripe mis à jour', [
                'product_id' => $productId,
                'plan_name' => $planData['nom']
            ]);

            return $product;
            
        } catch (Exception $e) {
            Logger::error('Erreur mise à jour produit Stripe', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            throw $e;
        }
    }

    /**
     * Archiver un produit Stripe (pas de suppression définitive)
     */
    public static function archiveProduct($productId)
    {
        try {
            self::init();
            
            $product = Product::update($productId, ['active' => false]);
            
            Logger::info('Produit Stripe archivé', [
                'product_id' => $productId
            ]);

            return $product;
            
        } catch (Exception $e) {
            Logger::error('Erreur archivage produit Stripe', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            throw $e;
        }
    }

    /**
     * Créer un client Stripe
     */
    public static function createCustomer($clientData)
    {
        try {
            self::init();
            
            $customerData = [
                'name' => $clientData['raison_sociale'],
                'email' => $clientData['email_facturation'],
                'address' => [
                    'line1' => $clientData['adresse'],
                    'postal_code' => $clientData['code_postal'],
                    'city' => $clientData['ville'],
                    'country' => $clientData['pays'] === 'France' ? 'FR' : 'FR' // À adapter selon les pays
                ],
                'metadata' => [
                    'client_id' => $clientData['id'] ?? 'new',
                    'numero_tva' => $clientData['numero_tva'] ?? '',
                    'created_by' => 'cover_ar_admin'
                ]
            ];

            if (!empty($clientData['numero_tva'])) {
                $customerData['tax_ids'] = [
                    [
                        'type' => 'eu_vat',
                        'value' => $clientData['numero_tva']
                    ]
                ];
            }

            $customer = Customer::create($customerData);
            
            Logger::info('Client Stripe créé', [
                'customer_id' => $customer->id,
                'client_name' => $clientData['raison_sociale']
            ]);

            return $customer;
            
        } catch (Exception $e) {
            Logger::error('Erreur création client Stripe', [
                'error' => $e->getMessage(),
                'client_data' => $clientData
            ]);
            throw $e;
        }
    }

    /**
     * Créer un abonnement Stripe
     */
    public static function createSubscription($customerId, $priceId, $quantity = 1, $metadata = [])
    {
        try {
            self::init();
            
            $subscriptionData = [
                'customer' => $customerId,
                'items' => [
                    [
                        'price' => $priceId,
                        'quantity' => $quantity
                    ]
                ],
                'collection_method' => 'charge_automatically',
                'metadata' => array_merge($metadata, [
                    'created_by' => 'cover_ar_admin',
                    'created_at' => date('Y-m-d H:i:s')
                ])
            ];

            $subscription = Subscription::create($subscriptionData);
            
            Logger::info('Abonnement Stripe créé', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'price_id' => $priceId
            ]);

            return $subscription;
            
        } catch (Exception $e) {
            Logger::error('Erreur création abonnement Stripe', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'price_id' => $priceId
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer les factures d'un client
     */
    public static function getCustomerInvoices($customerId, $limit = 10)
    {
        try {
            self::init();
            
            $invoices = Invoice::all([
                'customer' => $customerId,
                'limit' => $limit,
                'expand' => ['data.subscription', 'data.payment_intent']
            ]);
            
            return $invoices->data;
            
        } catch (Exception $e) {
            Logger::error('Erreur récupération factures Stripe', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            throw $e;
        }
    }

    /**
     * Générer un lien de checkout pour un abonnement
     */
    public static function createCheckoutSession($priceId, $successUrl, $cancelUrl, $metadata = [])
    {
        try {
            self::init();
            
            $sessionData = [
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $priceId,
                        'quantity' => 1
                    ]
                ],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'allow_promotion_codes' => true,
                'billing_address_collection' => 'required',
                'metadata' => $metadata
            ];

            $session = \Stripe\Checkout\Session::create($sessionData);
            
            Logger::info('Session Checkout créée', [
                'session_id' => $session->id,
                'price_id' => $priceId
            ]);

            return $session;
            
        } catch (Exception $e) {
            Logger::error('Erreur création session Checkout', [
                'error' => $e->getMessage(),
                'price_id' => $priceId
            ]);
            throw $e;
        }
    }

    /**
     * Synchroniser les données depuis Stripe
     */
    public static function syncStripeData()
    {
        try {
            self::init();
            
            $stats = [
                'products_synced' => 0,
                'customers_synced' => 0,
                'subscriptions_synced' => 0,
                'invoices_synced' => 0
            ];

            // Synchroniser les produits
            $products = Product::all(['limit' => 100]);
            foreach ($products->data as $product) {
                // Logique de synchronisation des produits
                $stats['products_synced']++;
            }

            // Synchroniser les clients
            $customers = Customer::all(['limit' => 100]);
            foreach ($customers->data as $customer) {
                // Logique de synchronisation des clients
                $stats['customers_synced']++;
            }

            Logger::info('Synchronisation Stripe terminée', $stats);
            
            return $stats;
            
        } catch (Exception $e) {
            Logger::error('Erreur synchronisation Stripe', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Générer une description pour un produit Stripe
     */
    private static function generateProductDescription($planData)
    {
        $description = "Formule d'abonnement Cover AR - " . $planData['nom'];
        
        $features = [];
        
        // Type d'abonnement
        switch ($planData['type_abonnement']) {
            case 'application':
                $features[] = "Accès à l'application Cover AR";
                break;
            case 'application_materiel':
                $features[] = "Accès à l'application Cover AR";
                $features[] = "Location de matériel incluse";
                break;
            case 'materiel_seul':
                $features[] = "Location de matériel";
                break;
        }
        
        // Utilisateurs
        if ($planData['nombre_utilisateurs_inclus'] > 0) {
            $features[] = $planData['nombre_utilisateurs_inclus'] . " utilisateur(s) inclus";
        }
        
        // Catégories
        if (!empty($planData['nombre_sous_categories'])) {
            $features[] = $planData['nombre_sous_categories'] . " catégories de textures";
        } else {
            $features[] = "Accès illimité aux catégories";
        }
        
        // Durée
        $features[] = "Facturation " . $planData['duree'];
        
        if (!empty($features)) {
            $description .= "\n\nInclus :\n• " . implode("\n• ", $features);
        }
        
        return $description;
    }

    /**
     * Vérifier la configuration Stripe
     */
    public static function checkConfiguration()
    {
        try {
            $stripeConfig = Config::stripe();
            
            $checks = [
                'secret_key_configured' => !empty($stripeConfig['secret_key']),
                'publishable_key_configured' => !empty($stripeConfig['publishable_key']),
                'webhook_secret_configured' => !empty($stripeConfig['webhook_secret']),
                'currency_configured' => !empty($stripeConfig['currency']),
                'environment' => Config::get('stripe.environment')
            ];
            
            if ($checks['secret_key_configured']) {
                self::init();
                
                // Test de connexion à Stripe
                try {
                    Product::all(['limit' => 1]);
                    $checks['api_connection'] = true;
                } catch (Exception $e) {
                    $checks['api_connection'] = false;
                    $checks['api_error'] = $e->getMessage();
                }
            } else {
                $checks['api_connection'] = false;
            }
            
            return $checks;
            
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'api_connection' => false
            ];
        }
    }
}
?>