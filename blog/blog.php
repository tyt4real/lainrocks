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

    <title>lain.rocks | Blog</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="../favicon.ico" />
    <link href="../css/config.css" rel="stylesheet" />
    <link href="../css/index.css" rel="stylesheet" />
</head>
<div id="app" class="container">
    <section class="mt-2 card">
        <div class="card-body" style="text-align: center;">
            <h2>Welcome to <i>lainrocks</i>: The Blog</h2>
            <br>
            <p>This is where We'll be posting our schizo ramblings and talking about the website.</p>
        </div>
    </section>
    <?php
    $configs = include "config.php";
    $host = $configs["host"];
    $port = $configs["port"];
    $dbname = $configs["dbname"];
    $user = $configs["user"];
    $password = $configs["password"];
    $conn = pg_connect(
        "host=$host port=$port dbname=$dbname user=$user password=$password"
    );

    if (!$conn) {
        echo "Database is down, sorry man.\n";
        exit();
    }

    $query = "SELECT * FROM posts";
    $result = pg_query($conn, $query);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $postHtml = sprintf(
                '    <section class="mt-2 card">
        <div class="card-header">
            <h2>%s</h2>
        </div>
        <div class="card-body">
            <p>%s</p>
        </div>
    </section>',
                $row["title"],
                $row["content"]
            );
            echo $postHtml;
        }
    } else {
        echo 'database query didnt succeed :( \n';
    }
    ?>
</div>

</html>