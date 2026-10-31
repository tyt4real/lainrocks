<?php
require_once './vendor/autoload.php';
require_once './scripts/i18n.php';
require_once './scripts/registerfunctionstwig.php';

session_start();

$config   = require __DIR__ . '/config.php';
$pageSlug = $_GET['page'] ?? 'home';

$locale = detectLocale();
$translations = loadTranslations($locale);

if ($pageSlug === 'account') {
    require_once './scripts/account.php';
    exit;
}

if (!isset($config['pages'][$pageSlug])) {
    http_response_code(404);
    $pageSlug = 'home'; // fallback
}

$pageConfig = $config['pages'][$pageSlug];


$twig = registerWithTwig($locale, $translations);

echo $twig->render($pageConfig['template'], [
    'current_page' => $pageSlug,
    'pages'        => $config['pages'],
    'locale'       => $locale,
    'translations' => $translations,
]);
