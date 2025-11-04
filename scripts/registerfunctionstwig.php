<?php
function registerWithTwig()
{
    $twig = require __DIR__ . '/twigInstance.php';

    $twig->addFunction(new \Twig\TwigFunction('renderBlog', function () {
        // config
        $directory = '../blog';
        $extension = 'md';

        if (!function_exists('parseMarkdown')) {
            function parseMarkdown(string $text): string
            {
                $text = htmlspecialchars($text); // Prevent XSS
                $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
                $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
                $text = preg_replace('/\#\#\# (.*?)\n/', '<h3>$1</h3>' . "\n", $text);
                $text = preg_replace('/\#\# (.*?)\n/', '<h2>$1</h2>' . "\n", $text);
                $text = preg_replace('/\# (.*?)\n/', '<h1>$1</h1>' . "\n", $text);
                $text = preg_replace('/\n\* (.*?)\n/', '<ul><li>$1</li></ul>' . "\n", $text);
                $text = preg_replace('/\* (.*?)\n/', '<li>$1</li>' . "\n", $text);
                $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
                $text = nl2br($text);
                return $text;
            }
        }

        $files = scandir($directory);
        $markdownData = [];

        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (is_file($path) && pathinfo($file, PATHINFO_EXTENSION) === $extension) {
                $name = str_replace('--', '  ', pathinfo($file, PATHINFO_FILENAME));
                $content = file_get_contents($path);
                $markdownData[] = [
                    'filename' => $name,
                    'html' => parseMarkdown($content)
                ];
            }
        }

        // Build HTML but DON'T echo — return it instead
        $output = '';
        foreach ($markdownData as $md) {
            $output .= '
            <section class="mt-2 card">
                <div class="card-header">
                    <h2>' . htmlspecialchars($md['filename']) . '</h2>
                </div>
                <div class="card-body">
                    ' . $md['html'] . '
                </div>
            </section>';
        }

        return $output; // ✅ Return instead of echo
    }, ['is_safe' => ['html']]));

    $twig->addFunction(new \Twig\TwigFunction('isSiteOnline', function ($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || !checkdnsrr($host, 'A')) {
            echo '❌';
        }

        $fp = @fsockopen($host, 80, $errno, $errstr, 5);
        if (!$fp) {
            echo '❌';
        }
        fclose($fp);
        echo '✅';
    }));

    $twig->addFunction(new \Twig\TwigFunction('renderServerStats', function () {
        // CPU load
        $load = sys_getloadavg();
        $cpuLoad = $load[0];

        // RAM usage
        $memoryUsed = memory_get_usage();

        // Disk space
        $free = disk_free_space("/");
        $total = disk_total_space("/");
        $percentageFree = ($free / $total) * 100;

        // Build HTML output
        $output = '<ul>';
        $output .= '<li>System load: ' . htmlspecialchars($cpuLoad) . '</li>';
        $output .= '<li>Used memory: ' . number_format($memoryUsed) . ' bytes</li>';
        $output .= '<li>Free disk space: ' . number_format($free) . ' bytes | Total disk space: ' . number_format($total) . ' bytes | Percent free: ' . number_format($percentageFree, 2) . '%</li>';
        $output .= '</ul>';

        echo $output;
    }));

    $twig->addFunction(new \Twig\TwigFunction('renderLainring', function () {
        // turn off warnings for undefined array keys so I can see actualy real issues
        error_reporting(E_ALL);
        //error_reporting(E_ERROR | E_PARSE | E_NOTICE);
        $json = file_get_contents("special/lainring.json");

        if ($json === false) {
            echo "cannot open lainring.json";
            exit;
        }

        $data = json_decode($json, true);

        if ($data === null) {
            echo "cannot decode lainring.json";
            exit;
        }


        printf("<p> Last updated: %s</p>", gmdate("Y-m-d\TH:i:s+00:00", $data["updated"]));

        printf("<h2>www</h2>\n");

        foreach ($data['items'] as $key => $value) {
            if ($value['online'] == "true") {
                if (array_key_exists('locale', $value)) {
                    if ($value['title'] !== null) {
                        $lc = $value['title'] . " [" . $value['locale'] . "]";
                    } else {
                        $lc = $value['url'] . " [" . $value['locale'] . "]";
                    }
                } else {
                    if ($value['title'] !== null) {
                        $lc = $value['title'];
                    } else {
                        $lc = $value['url'];
                    }
                }
                printf("<a href='%s' title='%s'><img class='banner' src='../images/%s' alt='%s'></a>\n", $value['url'], htmlentities($lc), $value['img'], htmlentities($lc));
            }
        }


        printf("<h2>RSS feeds</h2>\n");
        printf("<p><a href='opml.php'>OPML format</a></p>\n");
        printf("<p>URL list for newsboat: </p>\n");
        printf("<pre><code>");
        foreach ($data['items'] as $key => $value) {
            if ($value['online'] == "true") {
                if (array_key_exists('feed', $value)) {
                    printf("%s\n", $value['feed']);
                }
            }
        }
        printf("</code></pre>\n");


        printf("<h2>Tor</h2>\n");
        foreach ($data['items'] as $key => $value) {
            if ($value['online'] == "true") {
                if (array_key_exists("tor", $value)) {
                    printf("<a href='%s'><img class='banner' src='images/%s' alt='%s'></a>", $value['tor'], $value['img'], $value['title']);
                }
            }
        }

        printf("<h2>i2p</h2>\n");
        foreach ($data['items'] as $key => $value) {
            if ($value['online'] == "true") {
                if (array_key_exists("i2p", $value)) {
                    printf("<a href='%s'><img class='banner' src='images/%s' alt='%s'></a>", $value['i2p'], $value['img'], $value['title']);
                }
            }
        }

        printf("<h2>Offline, abandoned, domain sniped, etc</h1>");
        foreach ($data['items'] as $key => $value) {
            if ($value['online'] == "false") {
                if (array_key_exists('locale', $value)) {
                    if ($value['title'] !== null) {
                        $lc = $value['title'] . " [" . $value['locale'] . "]";
                    } else {
                        $lc = $value['url'] . " [" . $value['locale'] . "]";
                    }
                } else {
                    if ($value['title'] !== null) {
                        $lc = $value['title'];
                    } else {
                        $lc = $value['url'];
                    }
                }
                printf("<a href='%s' title='%s'><img class='banner' src='images/%s' alt='%s'></a>\n", $value['url'], htmlentities($lc), $value['img'], htmlentities($lc));
            }
        }
    }));

    $twig->addFunction(new \Twig\TwigFunction('renderNavbar', function () {
        // Example dynamic links (could come from database, config, etc.)
        // Dynamic links (can be generated automatically)
        // Manual links (optional)
        $config = include 'config.php';

        $pages = $config['pages'];

        $manualLinks = [
            ['href' => './xmpp/register', 'label' => 'XMPP Registration', 'target' => '_blank'],
            ['href' => './xmpp/conversejs/', 'label' => 'Converse.JS', 'target' => '_blank'],
            ['href' => './sites/tos.html', 'label' => 'Terms Of Service [EN]', 'target' => '_blank'],
            ['href' => './sites/tos.ru.html', 'label' => 'Terms Of Service [RU]', 'target' => '_blank'],
            ['href' => './searxng', 'label' => 'SearXNG', 'target' => '_blank'],
            ['href' => 'https://stats.uptimerobot.com/6eWo4s81Co', 'label' => 'Uptime Monitoring', 'target' => '_blank'],
        ];

        // Build dynamic links from pages config
        $dynamicLinks = [];
        foreach ($pages as $slug => $pageData) {
            $dynamicLinks[] = [
                'href' => '?page=' . urlencode($slug),
                'label' => ucfirst($slug),
                'target' => '_self'
            ];
        }

        // Merge dynamic + manual links
        $navbarLinks = array_merge($dynamicLinks, $manualLinks);

        // Render HTML
        echo '<div style="text-align: center">';
        echo '  <div id="navbar" class="content">';
        echo '    <div class="mt-2 card">';
        echo '      <div class="card-body">';

        $lastIndex = count($navbarLinks) - 1;
        foreach ($navbarLinks as $index => $link) {
            echo '<a href="' . htmlspecialchars($link['href']) . '" target="' . htmlspecialchars($link['target']) . '">'
                . htmlspecialchars($link['label']) . '</a>';
            if ($index !== $lastIndex) {
                echo ' | ';
            }
        }

        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }));

    $twig->addFunction(new \Twig\TwigFunction('checkCommit', function () {
        $fetchheadfile = '.git/FETCH_HEAD';
        if (file_exists($fetchheadfile)) {
            $headfile = file_get_contents($fetchheadfile);
            $shortCommitTag = substr($headfile, 0, 6);
            $fullCommitTag = substr($headfile, 0, 40);
            $format = "<a href='https://github.com/tyt4real/lainrocks/commit/%s'>%s</a>";
            echo sprintf($format, $fullCommitTag, $shortCommitTag);
        } else {
            echo ("Can't read latest commit.");
        }
    }));
    $twig->addFunction(new \Twig\TwigFunction('calculateMirrorDiskSpace', function () {
        $config = include 'config.php';
        $path = $config['mirrorpath'];
        $mirrorSizeInBytes = 0;
        if(is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $mirrorSizeInBytes = $mirrorSizeInBytes + $file->getSize();
            }
        }
        echo ($mirrorSizeInBytes / (1024 * 1024) . "MB");
        } else {
            echo "Mirror directory does not exist";
        }

    }));
    return $twig;
}
