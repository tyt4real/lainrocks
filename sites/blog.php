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

    <title>lain.rocks | Blog</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
        crossorigin="anonymous" />
    <link rel="icon" type="image/x-icon" href="../favicon.ico" />
    <link href="../css/config.css" rel="stylesheet" />
    <link href="../css/index.css" rel="stylesheet" />
</head>

<body>
    <?php
    // config
    $directory = '../blog';
    $extension = 'md';


    function parseMarkdown($text)
    {

        $text = htmlspecialchars($text); // Prevent XSS
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text); // Bold
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);             // Italic
        $text = preg_replace('/\#\#\# (.*?)\n/', '<h3>$1</h3>' . "\n", $text); // H3
        $text = preg_replace('/\#\# (.*?)\n/', '<h2>$1</h2>' . "\n", $text);   // H2
        $text = preg_replace('/\# (.*?)\n/', '<h1>$1</h1>' . "\n", $text);     // H1
        $text = preg_replace('/\n\* (.*?)\n/', '<ul><li>$1</li></ul>' . "\n", $text); // One bullet
        $text = preg_replace('/\* (.*?)\n/', '<li>$1</li>' . "\n", $text);     // Bullet list item
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text); // Links
        $text = nl2br($text); // Convert remaining line breaks
        return $text;
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
    ?>
    <div id="app" class="container">
    <header class="mt-2 card">
        <h2>Welcome to <i>lain.rocks</i>: The Blog</h2>
        <p>This is the place where we'll be posting out schizo ramblings and info about the site.</p>
    </header>
    <?php foreach ($markdownData as $md): ?>
        <section class="mt-2 card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($md['filename']); ?></h2>
            </div>
            <div class="card-body">
                <?php echo $md['html']; ?>
            </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>