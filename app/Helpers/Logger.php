<?php

namespace App\Helpers;

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    private static $levels = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    private static $config = null;

    /**
     * Initialiser le logger avec la configuration
     */
    public static function init()
    {
        if (self::$config === null) {
            self::$config = Config::get('app.log');
        }
    }

    /**
     * Log d'urgence - système inutilisable
     */
    public static function emergency($message, array $context = [])
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log d'alerte - action immédiate requise
     */
    public static function alert($message, array $context = [])
    {
        self::log(self::ALERT, $message, $context);
    }

    /**
     * Log critique - conditions critiques
     */
    public static function critical($message, array $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log d'erreur - erreurs d'exécution
     */
    public static function error($message, array $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log d'avertissement - avertissements
     */
    public static function warning($message, array $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log de notice - événements normaux mais significatifs
     */
    public static function notice($message, array $context = [])
    {
        self::log(self::NOTICE, $message, $context);
    }

    /**
     * Log d'information - messages informatifs
     */
    public static function info($message, array $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log de debug - informations de débogage
     */
    public static function debug($message, array $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log principal
     */
    public static function log($level, $message, array $context = [])
    {
        self::init();

        // Vérifier si le niveau de log est autorisé
        if (!self::shouldLog($level)) {
            return;
        }

        $logEntry = self::formatLogEntry($level, $message, $context);
        self::writeToFile($logEntry, 'app.log');
    }

    /**
     * Log spécialisé pour Stripe
     */
    public static function stripe($level, $message, array $context = [])
    {
        self::init();

        if (!self::shouldLog($level)) {
            return;
        }

        $logEntry = self::formatLogEntry($level, $message, $context);
        self::writeToFile($logEntry, 'stripe.log');
    }

    /**
     * Log spécialisé pour les erreurs
     */
    public static function logError($message, $file = null, $line = null, array $context = [])
    {
        $context['file'] = $file;
        $context['line'] = $line;
        $context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        $logEntry = self::formatLogEntry(self::ERROR, $message, $context);
        self::writeToFile($logEntry, 'error.log');
    }

    /**
     * Log spécialisé pour les requêtes SQL
     */
    public static function logQuery($sql, array $params = [], $executionTime = null)
    {
        if (!Config::get('app.debug', false)) {
            return;
        }

        $context = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
        ];

        self::log(self::DEBUG, 'SQL Query executed', $context);
    }

    /**
     * Log spécialisé pour les authentifications
     */
    public static function logAuth($action, $userId = null, $email = null, array $context = [])
    {
        $context['action'] = $action;
        $context['user_id'] = $userId;
        $context['email'] = $email;
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $message = "Authentication: {$action}";
        if ($email) {
            $message .= " for {$email}";
        }

        self::log(self::INFO, $message, $context);
    }

    /**
     * Vérifier si le niveau de log doit être enregistré
     */
    private static function shouldLog($level)
    {
        $configLevel = self::$config['level'] ?? 'info';
        $configLevelValue = self::$levels[$configLevel] ?? 6;
        $levelValue = self::$levels[$level] ?? 6;

        return $levelValue <= $configLevelValue;
    }

    /**
     * Formater l'entrée de log
     */
    private static function formatLogEntry($level, $message, array $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Informations sur la requête
        $requestInfo = [];
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $requestInfo['method'] = $_SERVER['REQUEST_METHOD'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestInfo['uri'] = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $requestInfo['ip'] = $_SERVER['REMOTE_ADDR'];
        }

        // Session info si disponible
        $sessionInfo = [];
        if (Session::isLoggedIn()) {
            $sessionInfo['user_id'] = Session::getUserId();
            $sessionInfo['user_type'] = Session::getUserType();
        }

        // Interpolation des variables dans le message
        $message = self::interpolate($message, $context);

        $logData = [
            'timestamp' => $timestamp,
            'level' => $levelUpper,
            'message' => $message,
            'request' => $requestInfo,
            'session' => $sessionInfo,
            'context' => $context,
        ];

        // Masquer les données sensibles
        $logData = Config::maskSensitiveData($logData);

        return json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * Interpoler les variables dans le message
     */
    private static function interpolate($message, array $context = [])
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Écrire dans le fichier de log
     */
    private static function writeToFile($logEntry, $filename)
    {
        $logPath = self::$config['path'] ?? __DIR__ . '/../../storage/logs/';
        $logFile = $logPath . $filename;

        // Créer le dossier s'il n'existe pas
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        // Rotation des logs si nécessaire
        self::rotateLogIfNeeded($logFile);

        // Écrire le log
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotation des logs si le fichier devient trop volumineux
     */
    private static function rotateLogIfNeeded($logFile)
    {
        if (!file_exists($logFile)) {
            return;
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        $maxFiles = self::$config['max_files'] ?? 30;

        if (filesize($logFile) > $maxSize) {
            // Déplacer les anciens fichiers
            for ($i = $maxFiles - 1; $i >= 1; $i--) {
                $oldFile = $logFile . '.' . $i;
                $newFile = $logFile . '.' . ($i + 1);
                
                if (file_exists($oldFile)) {
                    if ($i === $maxFiles - 1) {
                        unlink($oldFile); // Supprimer le plus ancien
                    } else {
                        rename($oldFile, $newFile);
                    }
                }
            }

            // Renommer le fichier actuel
            rename($logFile, $logFile . '.1');
        }
    }

    /**
     * Nettoyer les anciens logs
     */
    public static function cleanup($days = 30)
    {
        $logPath = self::$config['path'] ?? __DIR__ . '/../../storage/logs/';
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        if (is_dir($logPath)) {
            $files = glob($logPath . '*.log*');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Obtenir les logs récents
     */
    public static function getRecentLogs($filename = 'app.log', $lines = 100)
    {
        $logPath = self::$config['path'] ?? __DIR__ . '/../../storage/logs/';
        $logFile = $logPath . $filename;

        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $file = new \SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logData = json_decode($line, true);
                if ($logData) {
                    $logs[] = $logData;
                }
            }
            $file->next();
        }

        return array_reverse($logs);
    }
}
?>