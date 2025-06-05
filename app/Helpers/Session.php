<?php

namespace App\Helpers;

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public static function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function isLoggedIn()
    {
        return self::has('user_id') && self::has('user_type');
    }

    public static function isMegaAdmin()
    {
        return self::get('user_type') === 'MegaAdmin';
    }

    public static function isAdmin()
    {
        return in_array(self::get('user_type'), ['MegaAdmin', 'Admin']);
    }

    public static function getUserId()
    {
        return self::get('user_id');
    }

    public static function getUserType()
    {
        return self::get('user_type');
    }

    public static function getUserName()
    {
        return self::get('user_name', 'Utilisateur');
    }

    public static function setFlash($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash($type = null)
    {
        if ($type) {
            $message = $_SESSION['flash'][$type] ?? null;
            unset($_SESSION['flash'][$type]);
            return $message;
        }

        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    public static function hasFlash($type = null)
    {
        if ($type) {
            return isset($_SESSION['flash'][$type]);
        }
        return !empty($_SESSION['flash']);
    }

    public static function generateCsrfToken()
    {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    public static function verifyCsrfToken($token)
    {
        return hash_equals(self::get('csrf_token', ''), $token);
    }
}
?>