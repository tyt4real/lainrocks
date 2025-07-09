<!DOCTYPE html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
        name="description"
        content="lain.rocks, the c00l place on the internet" />
    <meta name="keywords" content="lain, url, shortener" />
    <meta name="author" content="tyt4real" />

    <title>lain.rocks | URL shortener</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
        crossorigin="anonymous" />
    <link rel="icon" type="image/x-icon" href="./favicon.ico" />
    <!-- <link href="./dist/config.css" rel="stylesheet" /> -->
    <!--   <link href="./index.css" rel="stylesheet" /> -->
    <link href="./special.css" rel="stylesheet" />
    <link href="https://lain.rocks/config.css" rel="stylesheet" />
    <link href="https://lain.rocks/index.css" rel="stylesheet" />
</head>

<body>
    <header class="mt-2 card">
        <h1 id="shit">lain.rocks</h1>
        <h2>URL shortener</h2>
    </header>
    <div id="app" class="container">
        <section class="mt-2 card">
            <div class="card-header">
                <h2>Shorten your URL here ;)</h2>
            </div>
            <div class="card-body text-center">
                <form action="addlink.php" method="post">
                    <label for="link">Ur link: </label>
                    <input name="link" id="link" type="text">

                    <button type="submit">Add</button>
                </form>
            </div>
        </section>
    </div>
</body>