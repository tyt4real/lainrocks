<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

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

return $twig;
?>