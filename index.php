<?php 
require_once './vendor/autoload.php';
$config = require __DIR__ . '/config.php';

$pageSlug = $_GET['page'] ?? 'home';

$loader = new \Twig\Loader\FilesystemLoader($config['templatedir']);

if($config['debug'] == true) {
  $twig = new \Twig\Environment($loader, [
    'cache' => false,
  ]);
} else {
  $twig = new \Twig\Environment($loader, [
    'cache' => $config['cachedir'],
]);
}

if (!isset($config['pages'][$pageSlug])) {
    http_response_code(404);
    $pageSlug = 'home'; //fallback
}
$pageConfig = $config['pages'][$pageSlug];

echo $twig->render($pageConfig['template'], [
    'current_page' => $pageSlug,
    'pages' => $config['pages'],
]);


?>