<?php
function registerWithTwig() {
    $twig = require __DIR__ . '/twigInstance.php';

    // Register Twig function "test"
    $twig->addFunction(new \Twig\TwigFunction('renderBlog', function () {
        // config
        $directory = '../blog';
        $extension = 'md';

        // local helper
        if (!function_exists('parseMarkdown')) {
            function parseMarkdown(string $text): string {
                $text = htmlspecialchars($text); // Prevent XSS
                $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
                $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
                $text = preg_replace('/\#\#\# (.*?)\n/', '<h3>$1</h3>' . "\n", $text);
                $text = preg_replace('/\#\# (.*?)\n/', '<h2>$1</h2>' . "\n", $text);
                $text = preg_replace('/\# (.*?)\n/', '<h1>$1</h1>' . "\n", $text);
                $text = preg_replace('/\n\* (.*?)\n/', '<ul><li>$1</li></ul>' . "\n", $text);
                $text = preg_replace('/\* (.*?)\n/', '<li>$1</li>' . "\n", $text);
                $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
                $text = nl2br($text);
                return $text;
            }
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

        // Build HTML but DON'T echo — return it instead
        $output = '';
        foreach ($markdownData as $md) {
            $output .= '
            <section class="mt-2 card">
                <div class="card-header">
                    <h2>' . htmlspecialchars($md['filename']) . '</h2>
                </div>
                <div class="card-body">
                    ' . $md['html'] . '
                </div>
            </section>';
        }

        return $output; // ✅ Return instead of echo
    }, ['is_safe' => ['html']]));

    return $twig;
}
