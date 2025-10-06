<?php
$configs = include "config.php";

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$password = $_POST['password'];

// Very basic validation
if ($title === '' || $content === '') {
    echo "<p>Title and content are required.</p>";
    echo '<p><a href="new.php">Go back</a></p>';
    exit;
}

if ($password === $configs["adminpassword"]) {
    $host = $configs["host"];
    $port = $configs["port"];
    $dbname = $configs["dbname"];
    $user = $configs["user"];
    $password = $configs["password"];
    $conn = pg_connect(
        "host=$host port=$port dbname=$dbname user=$user password=$password"
    );


    if (!$conn) {
        die("Connection failed.");
    }

    $title_escaped = pg_escape_string($conn, $title);
    $content_escaped = pg_escape_string($conn, $content);

    $query = "INSERT INTO posts (title, content) VALUES ('$title_escaped', '$content_escaped')";

    $result = pg_query($conn, $query);

    if ($result) {
        echo "Post inserted successfully!";
    } else {
        echo "Error inserting post: " . pg_last_error($conn);
    }

    pg_close($conn);
}
