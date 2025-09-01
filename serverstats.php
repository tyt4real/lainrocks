<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
        name="description"
        content="lain.rocks, the c00l place on the internet" />
    <meta name="keywords" content="lain" />
    <meta name="author" content="tyt4real" />

    <title>lain.rocks | Server statistics</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
        crossorigin="anonymous" />
    <link rel="icon" type="image/x-icon" href="./favicon.ico" />
    <link href="./css/config.css" rel="stylesheet" />
    <link href="./css/index.css" rel="stylesheet" />
</head>

<body>
    <div id="app" class="container">
        <section class="mt-2 card">
            <div class="card-header">
                <h2>Server statistics as of <i>right</i> now</h2>
            </div>
            <div class="card-body">
                <ul>
                    <!-- CPU Usage -->
                    <li>
                        System load: <?php
                                        $load = sys_getloadavg();
                                        print_r($load[0]);
                                        ?>
                    </li>
                    <!-- RAM Usage -->
                    <li>
                        <?php
                        $memory_used = memory_get_usage();
                        echo "Used memory: " . $memory_used . " bytes";
                        ?>
                    </li>
                    <!-- Disk Space Usage -->
                    <li>
                        <?php
                        $free = disk_free_space("/");
                        $total = disk_total_space("/");

                        echo "Free disk space: " . $free . " ";
                        echo "Total disk space: " . $total . " ";
                        $percentage = ($free / $total) * 100;
                        echo "Percent free: " . $percentage . "%";
                        ?>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</body>

</html>