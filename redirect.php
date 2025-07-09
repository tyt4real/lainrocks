<?php
$shorthand = $_GET['shorthand'] ?? null;
$configs = include('config.php');

if ($shorthand) {
    $host = $configs["host"];
    $port = $configs["port"];
    $dbname = $configs["dbname"];
    $user = $configs["user"];
    $password = $configs["password"];

    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

    if (!$conn) {
        echo "Database is down, sorry man.\n";
        exit;
    }

    $query = "SELECT link FROM urls WHERE shorthand = $1 LIMIT 1";
    $shorthand2 = pg_escape_string($shorthand);
    $result = pg_query_params($conn, $query, array($shorthand2));

    if ($result) {
        $rows = pg_fetch_row($result);
        if ($rows) {
            echo sprintf("Link found succesfully: %s", $rows[0]);
            header("Location: ". $rows[0]);
            die();
        } else {
            echo 'not found in the database';
        }
    } else {
        echo 'database query didnt succeed \n';
    }
} else {
    echo "No shorthand provided. Cannot redirect";
}
