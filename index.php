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

$slugLabels = [
    'home' => 'nav.home',
    'about' => 'nav.about',
    'LainOS' => 'nav.lainos',
    'Jabber Server' => 'nav.jabber',
    'account' => 'nav.account',
];

$manualLinks = [
    ['href' => './xmpp/register', 'label' => 'nav.jabber_registration', 'target' => '_blank'],
    ['href' => './xmpp/conversejs/', 'label' => 'nav.converse_js', 'target' => '_blank'],
    ['href' => './sites/tos.html', 'label' => 'nav.tos_en', 'target' => '_blank'],
    ['href' => './sites/tos.ru.html', 'label' => 'nav.tos_ru', 'target' => '_blank'],
    ['href' => 'https://forgejo.lain.rocks/', 'label' => 'nav.forgejo', 'target' => '_blank'],
];

$dynamicLinks = [];
foreach ($config['pages'] as $slug => $pageData) {
    $key = $slugLabels[$slug] ?? 'nav.' . strtolower($slug);
    $dynamicLinks[] = [
        'href' => '?page=' . urlencode($slug),
        'label' => t($translations, $key),
        'target' => '_self',
    ];
}

$navbarLinks = array_merge($dynamicLinks, $manualLinks);
foreach ($navbarLinks as &$link) {
    $link['label'] = str_starts_with($link['label'], 'nav.') ? t($translations, $link['label']) : $link['label'];
}
unset($link);

echo $twig->render($pageConfig['template'], [
    'current_page' => $pageSlug,
    'pages'        => $config['pages'],
    'locale'       => $locale,
    'translations' => $translations,
    'navbarLinks'  => $navbarLinks,
]);
