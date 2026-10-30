<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

// --- Locale detection ---
// 1. ?lang= query parameter (highest priority)
if (isset($_GET['lang']) && in_array($_GET['lang'], $config['locale']['supported'])) {
    $locale = $_GET['lang'];
} elseif (isset($_SESSION['locale']) && in_array($_SESSION['locale'], $config['locale']['supported'])) {
    // 2. Session-stored locale
    $locale = $_SESSION['locale'];
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    // 3. Accept-Language header fallback
    $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    preg_match('/^[s-s]*([a-z]{2}(?:-[a-z]{2})?)/i', $acceptLang, $matches);
    $headerLang = strtolower($matches[1] ?? '');
    if (in_array($headerLang, $config['locale']['supported'])) {
        $locale = $headerLang;
    } else {
        $locale = $config['locale']['default'];
    }
} else {
    $locale = $config['locale']['default'];
}

// Store in session for subsequent requests
if (!isset($_SESSION['locale'])) {
    $_SESSION['locale'] = $locale;
}

// Load translation file
$translationPath = __DIR__ . '/../locales/' . $locale . '/messages.json';
$translations = [];
if (file_exists($translationPath)) {
    $translations = json_decode(file_get_contents($translationPath), true ?? []);
}

// Helper function for translators
if (!function_exists('tr')) {
    function tr($key, $replacements = []) use ($translations) {
        $languageDict = $translations;
        $value = $languageDict[$key] ?? $key;
        // Simple replacement for {{ placeholder }}
        foreach ($replacements as $placeholder => $replacement) {
            $value = str_replace('{{' . $placeholder . '}}', $replacement, $value);
        }
        return $value;
    }
}

$loader = new \Twig\Loader\FilesystemLoader($config['templatedir']);

$twig = new \Twig\Environment($loader, [
    'cache' => $config['debug'] ? false : $config['cachedir'],
]);

// Add tr function to Twig environment
$twig->addFunction(new \Twig\TwigFunction('tr', function($key) use ($translations) {
    return tr($key, []);
}));

// Add locale global
$twig->addGlobal('locale', $locale);

return $twig;
?>