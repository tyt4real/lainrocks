<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
        name="description"
        content="lain.rocks, the c00l place on the internet" />
    <meta name="keywords" content="lain" />
    <meta name="author" content="tyt4real" />

    <title>lain.rocks | Webring</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
        crossorigin="anonymous" />
    <link rel="icon" type="image/x-icon" href="./favicon.ico" />
    <!-- <link href="./dist/config.css" rel="stylesheet" /> 
     <link href="./dist/index.css" rel="stylesheet" /> -->

    <link href="./css/config.css" rel="stylesheet" />
    <link href="./css/index.css" rel="stylesheet" />
</head>

<body>
    <div id="app" class="container">
        <section class="mt-2 card">
            <div class="card-header">
                <h2>Lainring</h2>
            </div>
            <div class="card-body">
                <?php
                //echo "<p>The copy of the Lainchan Webring found on the <a href='https://lainchan.org/%CE%A9/index.html'>/omega/</a> board will be put here when I compile one</p>"
                ?>
                <?php
                // turn off warnings for undefined array keys so I can see actualy real issues
                error_reporting(E_ALL);
                //error_reporting(E_ERROR | E_PARSE | E_NOTICE);
                $json = file_get_contents("lainring.json");

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
                        printf("<a href='%s' title='%s'><img class='banner' src='images/%s' alt='%s'></a>\n", $value['url'], htmlentities($lc), $value['img'], htmlentities($lc));
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
                ?>
                <p>I stole this version of the webring from <a href="https://0x19.org/">0x19.org</a>, in the future I plan to maintain my own version of the webring :3</p>
            </div>
        </section>
    </div>
</body>

</html>