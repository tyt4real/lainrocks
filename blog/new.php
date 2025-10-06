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

    <title>lain.rocks | Post adding site :3</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="./favicon.ico" />
    <link href="../css/config.css" rel="stylesheet" />
    <link href="../css/index.css" rel="stylesheet" />
    <style>
        body {
            font-family: sans-serif;
            margin: 2em;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.5em;
            font-size: 1em;
            margin-bottom: 1em;
        }

        textarea {
            height: 200px;
            resize: vertical;
        }

        button {
            padding: 0.7em 1.5em;
            font-size: 1em;
        }
    </style>
</head>

<body>
    <section class="mt-2 card">
        <div class="card-body" style="text-align: center;">
            <form action="submit.php" method="POST">

                <label for="title">Title:</label><br>
                <input type="text" name="title" id="title" required><br>

                <label for="content">Post Content:</label><br>
                <textarea name="content" id="content" required></textarea><br>

                <label for="password">Password:</label><br>
                <input type="text" name="password" id="password" required><br>

                <button type="submit">Submit Post</button>
            </form>
        </div>
    </section>
</body>

</html>