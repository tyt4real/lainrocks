<?php
return [
    'debug' => true,
    'templatedir' => './sites',
    'cachedir' => './cache',
    'mirrorpath' => '/var/www/lainos',
    'updatelog' => '/var/www/lainos/update-repo.log',
    //pages nav configuration
    'pages' => [
        'home' => [
            'template' => 'app.php.twig',       
        ],
        'about' => [
            'template' => 'aboutme.html.twig',
        ],
        /*'blog' => [
            'template' => 'blog.php.twig',
        ],*/
        'LainOS' => [
            'template' => 'repositories.php.twig',
        ],
        /*'webring' => [
            'template' => 'webring.php.twig',
        ],
        'serverstats' => [
            'template' => 'serverstats.php.twig',
        ],*/
        'Jabber Server' => [
            'template' => 'xmpp.twig',
        ],
        'account' => [
            'template' => null, // handled by account.php
        ],
    ],

    'lldap' => [
        'url'            => 'http',
        'ldap_url'       => 'ldap',
        'base_dn'        => 'dc=lain,dc=rocks',
        'admin_dn'       => 'uid=admin,ou=people,dc=lain,dc=rocks',
        'admin_pw'       => '',
        'default_groups' => ['mail_users', 'nextcloud_users', 'forgejo_users'],
    ],
];