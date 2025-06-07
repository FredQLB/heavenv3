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
     * Créer un prix pour les utilisateurs supplémentaires
     */
    public static function createDepositPrice($productId, $planData)
    {
        if (empty($planData['DepositAmount']) || $planData['DepositAmount'] <= 0) {
            return null;
        }

        try {
            self::init();
        
            $priceData = [
                'product' => $productId,
                'unit_amount' => (int)($planData['DepositAmount'] * 100),
                'currency' => Config::get('stripe.currency', 'eur'),
                'metadata' => [
                    'plan_id' => $planData['id'] ?? 'new',
                    'type' => 'deposit_amount',
                    'base_price' => $planData['DepositAmount']
                ]
            ];

            $price = Price::create($priceData);
            
            Logger::info('Prix caution Stripe créé', [
                'price_id' => $price->id,
                'amount' => $planData['DepositAmount']
            ]);

            return $price;
            
        } catch (Exception $e) {
            Logger::error('Erreur création prix caution', [
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
    }*/

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

    /**
     * Vérifier si un customer a des modes de paiement valides
     */
    public static function hasValidPaymentMethod($customerId)
    {
        try {
            self::init();
            
            $paymentMethods = \Stripe\PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
            ]);
            
            // Vérifier qu'il y a au moins une carte valide
            foreach ($paymentMethods->data as $pm) {
                if ($pm->card && $pm->card->funding !== 'prepaid') {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Logger::error('Erreur vérification modes de paiement', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérifier si on peut traiter un paiement pour un montant donné
     */
    public static function canProcessPayment($customerId, $amount)
    {
        try {
            self::init();
            
            // Récupérer le mode de paiement par défaut
            $customer = \Stripe\Customer::retrieve($customerId);
            
            if (!$customer->invoice_settings->default_payment_method) {
                return false;
            }
            
            // Pour les gros montants, on peut ajouter des vérifications supplémentaires
            if ($amount > 500) {
                // Vérifier l'historique de paiement du client
                $invoices = \Stripe\Invoice::all([
                    'customer' => $customerId,
                    'status' => 'paid',
                    'limit' => 5
                ]);
                
                // Si le client n'a pas d'historique de paiement pour de gros montants, être prudent
                return count($invoices->data) > 0;
            }
            
            return true;
            
        } catch (\Exception $e) {
            Logger::error('Erreur vérification capacité de paiement', [
                'customer_id' => $customerId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Créer une session Checkout Stripe
     */
    public static function createCheckoutSession($sessionData)
    {
        try {
            self::init();
            
            $session = \Stripe\Checkout\Session::create($sessionData);
            
            Logger::info('Session Checkout créée', [
                'session_id' => $session->id,
                'mode' => $sessionData['mode'],
                'customer' => $sessionData['customer'] ?? 'new'
            ]);
            
            return $session;
            
        } catch (\Exception $e) {
            Logger::error('Erreur création session Checkout', [
                'error' => $e->getMessage(),
                'session_data' => array_diff_key($sessionData, ['line_items' => null])
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer une session Checkout
     */
    public static function retrieveCheckoutSession($sessionId)
    {
        try {
            self::init();
            
            $session = \Stripe\Checkout\Session::retrieve([
                'id' => $sessionId,
                'expand' => ['subscription', 'payment_intent']
            ]);
            
            return $session;
            
        } catch (\Exception $e) {
            Logger::error('Erreur récupération session Checkout', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un prix one-time pour les dépôts de garantie
     */
    public static function createOneTimePrice($name, $amount, $metadata = [])
    {
        try {
            self::init();
            
            // Créer d'abord un produit pour le dépôt
            $product = \Stripe\Product::create([
                'name' => $name,
                'type' => 'service',
                'metadata' => array_merge($metadata, [
                    'type' => 'deposit',
                    'created_by' => 'cover_ar_admin'
                ])
            ]);
            
            // Créer le prix one-time
            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => (int)($amount * 100), // Convertir en centimes
                'currency' => Config::get('stripe.currency', 'eur'),
                'metadata' => $metadata
            ]);
            
            Logger::info('Prix one-time créé pour dépôt', [
                'price_id' => $price->id,
                'product_id' => $product->id,
                'amount' => $amount
            ]);
            
            return $price;
            
        } catch (\Exception $e) {
            Logger::error('Erreur création prix one-time', [
                'name' => $name,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un abonnement avec items multiples
     */
    public static function createSubscriptionWithItems($customerId, $items, $metadata = [])
    {
        try {
            self::init();
            
            $subscriptionData = [
                'customer' => $customerId,
                'items' => $items,
                'collection_method' => 'charge_automatically',
                'metadata' => array_merge($metadata, [
                    'created_by' => 'cover_ar_admin',
                    'created_at' => date('Y-m-d H:i:s')
                ])
            ];
            
            $subscription = \Stripe\Subscription::create($subscriptionData);
            
            Logger::info('Abonnement multi-items créé', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'items_count' => count($items)
            ]);
            
            return $subscription;
            
        } catch (\Exception $e) {
            Logger::error('Erreur création abonnement multi-items', [
                'customer_id' => $customerId,
                'items_count' => count($items),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un invoice item
     */
    public static function createInvoiceItem($customerId, $amount, $description, $metadata = [])
    {
        try {
            self::init();
            
            $invoiceItem = \Stripe\InvoiceItem::create([
                'customer' => $customerId,
                'amount' => (int)($amount * 100),
                'currency' => Config::get('stripe.currency', 'eur'),
                'description' => $description,
                'metadata' => $metadata
            ]);
            
            Logger::info('Invoice item créé', [
                'invoice_item_id' => $invoiceItem->id,
                'customer_id' => $customerId,
                'amount' => $amount
            ]);
            
            return $invoiceItem;
            
        } catch (\Exception $e) {
            Logger::error('Erreur création invoice item', [
                'customer_id' => $customerId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer et payer une facture immédiatement
     */
    public static function createAndPayInvoice($customerId, $autoAdvance = true)
    {
        try {
            self::init();
            
            // Créer la facture
            $invoice = \Stripe\Invoice::create([
                'customer' => $customerId,
                'auto_advance' => $autoAdvance,
                'collection_method' => 'charge_automatically'
            ]);
            
            // Finaliser la facture
            $invoice->finalizeInvoice();
            
            // Tenter le paiement
            if ($autoAdvance) {
                $invoice->pay();
            }
            
            Logger::info('Facture créée et payée', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customerId,
                'status' => $invoice->status,
                'amount' => $invoice->amount_paid / 100
            ]);
            
            return $invoice;
            
        } catch (\Exception $e) {
            Logger::error('Erreur création/paiement facture', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour un customer avec les informations de paiement
     */
    public static function updateCustomer($customerId, $data)
    {
        try {
            self::init();
            
            $updateData = [];
            
            if (isset($data['raison_sociale'])) {
                $updateData['name'] = $data['raison_sociale'];
            }
            
            if (isset($data['email_facturation'])) {
                $updateData['email'] = $data['email_facturation'];
            }
            
            if (isset($data['adresse'], $data['code_postal'], $data['ville'], $data['pays'])) {
                $updateData['address'] = [
                    'line1' => $data['adresse'],
                    'postal_code' => $data['code_postal'],
                    'city' => $data['ville'],
                    'country' => $data['pays'] === 'France' ? 'FR' : 'FR'
                ];
            }
            
            if (isset($data['numero_tva']) && !empty($data['numero_tva'])) {
                $updateData['tax_ids'] = [
                    [
                        'type' => 'eu_vat',
                        'value' => $data['numero_tva']
                    ]
                ];
            }
            
            $customer = \Stripe\Customer::update($customerId, $updateData);
            
            Logger::info('Customer Stripe mis à jour', [
                'customer_id' => $customerId,
                'updated_fields' => array_keys($updateData)
            ]);
            
            return $customer;
            
        } catch (\Exception $e) {
            Logger::error('Erreur mise à jour customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un setup intent pour configurer un mode de paiement
     */
    public static function createSetupIntent($customerId, $metadata = [])
    {
        try {
            self::init();
            
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
                'metadata' => $metadata
            ]);
            
            Logger::info('Setup Intent créé', [
                'setup_intent_id' => $setupIntent->id,
                'customer_id' => $customerId
            ]);
            
            return $setupIntent;
            
        } catch (\Exception $e) {
            Logger::error('Erreur création Setup Intent', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer les modes de paiement d'un customer
     */
    public static function getCustomerPaymentMethods($customerId, $type = 'card')
    {
        try {
            self::init();
            
            $paymentMethods = \Stripe\PaymentMethod::all([
                'customer' => $customerId,
                'type' => $type,
            ]);
            
            return $paymentMethods->data;
            
        } catch (\Exception $e) {
            Logger::error('Erreur récupération modes de paiement', [
                'customer_id' => $customerId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Calculer le montant total d'un checkout (abonnement + dépôt)
     */
    public static function calculateCheckoutTotal($priceIds, $quantities = [], $oneTimePrices = [])
    {
        try {
            self::init();
            
            $total = 0;
            
            // Récupérer les prix des abonnements
            foreach ($priceIds as $index => $priceId) {
                $price = \Stripe\Price::retrieve($priceId);
                $quantity = $quantities[$index] ?? 1;
                
                if ($price->recurring) {
                    // Pour les abonnements, prendre le montant de la première période
                    $total += ($price->unit_amount / 100) * $quantity;
                } else {
                    // Pour les prix one-time
                    $total += ($price->unit_amount / 100) * $quantity;
                }
            }
            
            // Ajouter les prix one-time supplémentaires
            foreach ($oneTimePrices as $amount) {
                $total += $amount;
            }
            
            return $total;
            
        } catch (\Exception $e) {
            Logger::error('Erreur calcul total checkout', [
                'price_ids' => $priceIds,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
?>