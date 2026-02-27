<?php

/**
 * Minimal .env loader.
 *
 * This will parse a .env file in the project root and
 * populate $_ENV and $_SERVER so getenv() works.
 */
function load_env(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name  = trim($parts[0]);
        $value = trim($parts[1], " \t\n\r\0\x0B\"'");

        if ($name === '') {
            continue;
        }

        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
        putenv("$name=$value");
    }
}

// Auto-load .env from project root when included.
$root = __DIR__ . DIRECTORY_SEPARATOR . '.env';
if (!isset($_ENV['_ENV_LOADED'])) {
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    load_env($envPath);
    $_ENV['_ENV_LOADED'] = true;
}

