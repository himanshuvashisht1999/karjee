<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get current language from session or cookie, default to 'en'
 */
function getCurrentLanguage()
{
    if (isset($_GET['lang'])) {
        $_SESSION['app_lang'] = $_GET['lang'];
        setcookie('app_lang', $_GET['lang'], time() + (86400 * 30), "/");
        return $_GET['lang'];
    }

    if (isset($_SESSION['app_lang'])) {
        return $_SESSION['app_lang'];
    }

    if (isset($_COOKIE['app_lang'])) {
        return $_COOKIE['app_lang'];
    }

    return 'en';
}

/**
 * Load translation strings for the current language
 */
function loadTranslations()
{
    static $translations = null;

    if ($translations === null) {
        $lang = getCurrentLanguage();
        // Determine the root path relative to this file
        $langFile = dirname(__DIR__) . "/languages/{$lang}.json";

        if (file_exists($langFile)) {
            $json = file_get_contents($langFile);
            $translations = json_decode($json, true);
        } else {
            // Fallback to English
            $fallbackFile = dirname(__DIR__) . "/languages/en.json";
            if (file_exists($fallbackFile)) {
                $json = file_get_contents($fallbackFile);
                $translations = json_decode($json, true);
            } else {
                $translations = [];
            }
        }
    }

    return $translations;
}

/**
 * Translate a key. If not found, returns the key itself.
 */
function _t($key)
{
    $translations = loadTranslations();
    return $translations[$key] ?? $key;
}
?>