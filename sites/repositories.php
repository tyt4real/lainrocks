<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
        name="description"
        content="lain.rocks, the c00l place on the internet" />
    <meta name="keywords" content="lain, xmpp, xmpp server, im" />
    <meta name="author" content="tyt4real" />

    <title>lain.rocks | LainOS repositories</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico" />
    <link href="../css/config.css" rel="stylesheet" />
    <link href="../css/index.css" rel="stylesheet" />
</head>

<body>
    <script src="./js/styleinit.js"></script>
    <script src="./js/themeManager.js"></script>
    <div id="header">
        <h1>LainOS software repositories</h1>
    </div>
    <div id="app" class="container">
        <section class="mt-2 card">
            <div class="card-header">
                <h2>LainOS software mirror list</h2>
            </div>
            <?php
            function checkSiteStatus($url) {
                $host = parse_url($url, PHP_URL_HOST);
                if (!$host || !checkdnsrr($host, 'A')) {
                    return '❌';
                }

                $fp = @fsockopen($host, 80, $errno, $errstr, 5);
                if (!$fp) {
                    return '❌';
                }
                fclose($fp);
                return '✅';
            }
            ?>
            <div class="card-body">
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Mirror URL</th>
                        <th>Tor Mirror URL</th>
                        <th>Description</th>
                        <th>Is online?</th>
                    </tr>
                    <tr>
                        <td>Wired Repo</td>
                        <td><a href="https://lain.rocks/repos/wired/">https://lain.rocks/repos/wired/</a></td>
                        <td><a href="https://lainrbhc4y67y4qfarb637f5t5m4kfsw7jkbocs3loazvtksxgtf6oid.onion/repos/wired/">https://lainrbhc4y67y4qfarb637f5t5m4kfsw7jkbocs3loazvtksxgtf6oid.onion/repos/wired/</a></td>
                        <td>Repository for the hacking tools provided with LainOS</td>
                        <td><?php echo checkSiteStatus("https://lain.rocks/repos/wired/"); ?></td>
                    </tr>
                    <tr>
                        <td>Lain Repo</td>
                        <td><a href="https://lain.rocks/repos/lain/">https://lain.rocks/repos/lain/</a></td>
                        <td><a href="https://lainrbhc4y67y4qfarb637f5t5m4kfsw7jkbocs3loazvtksxgtf6oid.onion/repos/lain/">https://lainrbhc4y67y4qfarb637f5t5m4kfsw7jkbocs3loazvtksxgtf6oid.onion/repos/lain/</a></td>
                        <td>Repository for the general software running on LainOS</td>
                        <td><?php echo checkSiteStatus("https://lain.rocks/repos/lain/"); ?></td>
                    </tr>
                </table>
            </div>
        </section>

        <footer>
            <div style="text-align: center; bottom: 0;">
                <p>Let's all love Lain. Copyleft 2025</p>
                <p>For all abuse complaints, contact us here: <a href="mailto:tyt4real@protonmail.com">tyt4real@protonmail.com</a></p>
            </div>
        </footer>
    </div>
</body>

</html>