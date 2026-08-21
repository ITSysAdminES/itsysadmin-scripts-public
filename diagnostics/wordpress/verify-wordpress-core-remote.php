<?php
declare(strict_types=1);

function configured_wordpress_root(array $argv): string
{
    $candidate = isset($argv[1]) ? trim((string) $argv[1]) : '';
    if ($candidate === '') {
        $candidate = (string) (getenv('WORDPRESS_ROOT') ?: '/var/www/html');
    }
    if ($candidate === '' || preg_match('/[\r\n]/', $candidate)) {
        throw new RuntimeException('The WordPress root path is empty or invalid.');
    }

    $root = realpath($candidate);
    if ($root === false || !is_dir($root)) {
        throw new RuntimeException('The configured WordPress root is not a directory.');
    }
    return rtrim($root, DIRECTORY_SEPARATOR);
}

function remote_timeout(): float
{
    $value = getenv('WP_REMOTE_TIMEOUT');
    $timeout = $value === false || $value === '' ? 15.0 : (float) $value;
    if ($timeout <= 0 || $timeout > 120) {
        throw new RuntimeException('WP_REMOTE_TIMEOUT must be between 0 and 120 seconds.');
    }
    return $timeout;
}

function fetch_remote_json(string $url, float $timeout): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => false,
            'header' => "Accept: application/json\r\n",
        ],
    ]);
    $payload = @file_get_contents($url, false, $context);
    if ($payload === false) {
        throw new RuntimeException('Could not fetch the official WordPress checksum manifest before the timeout.');
    }
    $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('The checksum endpoint returned an invalid JSON document.');
    }
    return $decoded;
}

function safe_core_relative_path(string $relative): ?string
{
    $relative = str_replace('\\', '/', $relative);
    if ($relative === '' || $relative[0] === '/' || preg_match('/^[A-Za-z]:\//', $relative)) {
        return null;
    }
    $parts = [];
    foreach (explode('/', $relative) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            return null;
        }
        $parts[] = $part;
    }
    return $parts ? implode('/', $parts) : null;
}

try {
    $root = configured_wordpress_root($argv);
    require $root . '/wp-includes/version.php';
    $locale = isset($wp_local_package) && is_string($wp_local_package) && $wp_local_package !== ''
        ? $wp_local_package
        : 'en_US';
    $url = sprintf(
        'https://api.wordpress.org/core/checksums/1.0/?version=%s&locale=%s',
        rawurlencode((string) $wp_version),
        rawurlencode($locale)
    );
    $manifest = fetch_remote_json($url, remote_timeout());
    $checksums = $manifest['checksums'] ?? [];
    if (!is_array($checksums) || !$checksums) {
        throw new RuntimeException('The checksum manifest is empty.');
    }

    $missing = [];
    $mismatch = [];
    $invalidCount = 0;
    foreach ($checksums as $relative => $expected) {
        $rawRelative = (string) $relative;
        $relative = safe_core_relative_path($rawRelative);
        if ($relative === null) {
            ++$invalidCount;
            continue;
        }
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($path)) {
            $missing[] = $relative;
            continue;
        }

        $actual = md5_file($path);
        if ($actual === false || !hash_equals(strtolower((string) $expected), strtolower($actual))) {
            $mismatch[] = $relative;
        }
    }

    $report = [
        'wordpress' => (string) $wp_version,
        'locale' => $locale,
        'checked' => count($checksums),
        'missing' => $missing,
        'mismatch' => $mismatch,
        'invalid_count' => $invalidCount,
        'ok' => !$missing && !$mismatch && $invalidCount === 0,
    ];
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
    exit($report['ok'] ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, 'WordPress core checksum check failed: ' . $error->getMessage() . "\n");
    exit(2);
}
