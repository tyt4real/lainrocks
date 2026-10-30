<?php
require_once './vendor/autoload.php';
require_once './scripts/registerfunctionstwig.php';

session_start();

$config   = require __DIR__ . '/config.php';
$pageSlug = $_GET['page'] ?? 'home';

if ($pageSlug === 'account') {
    require_once './scripts/account.php';
    exit;
}

if (!isset($config['pages'][$pageSlug])) {
    http_response_code(404);
    $pageSlug = 'home'; // fallback
}

$pageConfig = $config['pages'][$pageSlug];



$twig = registerWithTwig();

echo $twig->render($pageConfig['template'], [
    'current_page' => $pageSlug,
    'pages'        => $config['pages'],
]);