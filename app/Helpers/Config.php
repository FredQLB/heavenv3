<?php

namespace App\Helpers;

class Config
{
    private static $configs = [];

    /**
     * Charger un fichier de configuration
     */
    public static function load($name)
    {
        if (!isset(self::$configs[$name])) {
            $configPath = __DIR__ . "/../Config/{$name}.php";
            
            if (file_exists($configPath)) {
                self::$configs[$name] = require $configPath;
            } else {
                throw new \Exception("Fichier de configuration '{$name}' non trouvé");
            }
        }
        
        return self::$configs[$name];
    }

    /**
     * Obtenir une valeur de configuration
     */
    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $configName = array_shift($keys);
        
        $config = self::load($configName);
        
        // Parcourir les clés imbriquées
        foreach ($keys as $k) {
            if (is_array($config) && array_key_exists($k, $config)) {
                $config = $config[$k];
            } else {
                return $default;
            }
        }
        
        return $config;
    }

    /**
     * Définir une valeur de configuration (temporaire, en mémoire)
     */
    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $configName = array_shift($keys);
        
        if (!isset(self::$configs[$configName])) {
            self::load($configName);
        }
        
        $config = &self::$configs[$configName];
        
        // Créer la structure imbriquée si nécessaire
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }

    /**
     * Vérifier si une configuration existe
     */
    public static function has($key)
    {
        try {
            $value = self::get($key);
            return $value !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir toute la configuration d'un fichier
     */
    public static function all($configName)
    {
        return self::load($configName);
    }

    /**
     * Obtenir l'environnement actuel
     */
    public static function env()
    {
        return self::get('app.env', 'local');
    }

    /**
     * Vérifier si on est en mode debug
     */
    public static function isDebug()
    {
        return self::get('app.debug', false);
    }

    /**
     * Vérifier si on est en production
     */
    public static function isProduction()
    {
        return self::env() === 'production';
    }

    /**
     * Obtenir l'URL de base de l'application
     */
    public static function url($path = '')
    {
        $baseUrl = rtrim(self::get('app.url', 'http://localhost'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Obtenir la configuration de la base de données
     */
    public static function database($connection = null)
    {
        if ($connection === null) {
            $connection = self::get('database.default', 'mysql');
        }
        
        return self::get("database.connections.{$connection}");
    }

    /**
     * Obtenir la configuration Stripe selon l'environnement
     */
    public static function stripe()
    {
        $environment = self::get('stripe.environment', 'sandbox');
        $config = self::get("stripe.{$environment}");
        
        // Ajouter la configuration générale
        $config['currency'] = self::get('stripe.currency', 'eur');
        $config['locale'] = self::get('stripe.locale', 'fr');
        
        return $config;
    }

    /**
     * Obtenir la configuration email
     */
    public static function mail($mailer = null)
    {
        if ($mailer === null) {
            $mailer = self::get('mail.default', 'smtp');
        }
        
        return self::get("mail.mailers.{$mailer}");
    }

    /**
     * Recharger toutes les configurations
     */
    public static function reload()
    {
        self::$configs = [];
    }

    /**
     * Obtenir les configurations cachées (pour éviter de les logger)
     */
    public static function getSensitiveKeys()
    {
        return [
            'database.connections.*.password',
            'mail.mailers.*.password',
            'stripe.*.secret_key',
            'stripe.*.webhook_secret',
            'app.key',
        ];
    }

    /**
     * Masquer les données sensibles pour les logs
     */
    public static function maskSensitiveData($config, $path = '')
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $currentPath = $path ? "{$path}.{$key}" : $key;
                
                if (self::isSensitiveKey($currentPath)) {
                    $config[$key] = '***';
                } elseif (is_array($value)) {
                    $config[$key] = self::maskSensitiveData($value, $currentPath);
                }
            }
        }
        
        return $config;
    }

    /**
     * Vérifier si une clé est sensible
     */
    private static function isSensitiveKey($key)
    {
        $sensitivePatterns = [
            '/password/i',
            '/secret/i',
            '/key/i',
            '/token/i',
            '/credential/i',
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        
        return false;
    }
}
?>