<?php
function generateLinkShorthand($length = 5)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}


$regex = '/^https?:\/\/[^\s\/$.?#].[^\s]*$/i';
$link = htmlspecialchars($_POST['link']);

if (preg_match($regex, $link)) {
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

    $linkshortened = generateLinkShorthand();
    $query = "INSERT INTO urls (link, shorthand, created_at) VALUES ($1, $2, $3)";
    $result = pg_query_params($conn, $query, array($link, $linkshortened, date("Y-m-d H:i:s")));
    if ($result) {
        echo sprintf("Link added succesfully, available at: https://lain.rocks/l/%s", $linkshortened);
    } else {
        echo 'database query didnt succeed \n';
    }
} else {
    echo 'do not try anything funny man, this does not match the regex TwT';
}
