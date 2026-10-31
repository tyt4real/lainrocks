<?php

function detectLocale(): string
{
    $supported = ['en', 'ru', 'fr', 'pl'];
    $default   = 'en';

    // 1. explicit session choice
    if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], $supported, true)) {
        return $_SESSION['locale'];
    }

    // 2. URL override (?lang=xx or ?locale=xx) — persists in session
    $lang = $_GET['lang'] ?? $_GET['locale'] ?? '';
    if ($lang !== '' && in_array($lang, $supported, true)) {
        $_SESSION['locale'] = $lang;
        return $lang;
    }

    // 3. Accept-Language header
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($accept !== '') {
        preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $accept, $matches);
        $locales = $matches[1];
        $qs      = $matches[2];

        $ranked = [];
        foreach ($locales as $i => $loc) {
            $base = strtolower(substr($loc, 0, 2));
            $q    = $qs[$i] ?? '1';
            if (in_array($base, $supported, true)) {
                $ranked[$base] = (float) $q;
            }
        }
        arsort($ranked);
        if ($ranked !== []) {
            return key($ranked);
        }
    }

    return $default;
}

function loadTranslations(string $locale): array
{
    $path = __DIR__ . '/../translations/' . $locale . '.json';
    if (!file_exists($path)) {
        $locale = 'en';
        $path   = __DIR__ . '/../translations/en.json';
    }
    $json = file_get_contents($path);
    if ($json === false) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function t(array $translations, string $key, array $params = []): string
{
    $keys = explode('.', $key);
    $value = $translations;
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $key;
        }
        $value = $value[$k];
    }
    if (!is_string($value)) {
        return $key;
    }
    foreach ($params as $k => $v) {
        $value = str_replace('{' . $k . '}', (string) $v, $value);
    }
    return $value;
}
