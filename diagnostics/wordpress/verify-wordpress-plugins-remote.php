<?php
declare(strict_types=1);

function configured_plugin_root(array $argv): string
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

function plugin_remote_timeout(): float
{
    $value = getenv('WP_REMOTE_TIMEOUT');
    $timeout = $value === false || $value === '' ? 15.0 : (float) $value;
    if ($timeout <= 0 || $timeout > 120) {
        throw new RuntimeException('WP_REMOTE_TIMEOUT must be between 0 and 120 seconds.');
    }
    return $timeout;
}

function fetch_plugin_manifest(string $url, float $timeout): array
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
        throw new RuntimeException('manifest-unavailable');
    }
    $manifest = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($manifest) || !isset($manifest['files']) || !is_array($manifest['files'])) {
        throw new RuntimeException('invalid-manifest');
    }
    return $manifest;
}

function safe_plugin_relative(string $relative): ?string
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

function scan_extra_plugin_files(string $pluginDir, array $expected): ?array
{
    if (!is_dir($pluginDir)) {
        return [];
    }
    $extra = [];
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($pluginDir) + 1);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            if (!isset($expected[$relative])) {
                $extra[] = $relative;
            }
        }
    } catch (Throwable $error) {
        return null;
    }
    sort($extra);
    return $extra;
}

try {
    $root = configured_plugin_root($argv);
    define('WP_CLI', true);
    require $root . '/wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $timeout = plugin_remote_timeout();
    $pluginsRoot = rtrim(ABSPATH, DIRECTORY_SEPARATOR) . '/wp-content/plugins';
    $results = [];
    $hardFailures = 0;

    foreach (get_plugins() as $pluginFile => $data) {
        $pluginFile = str_replace('\\', '/', (string) $pluginFile);
        $pluginDirRelative = dirname($pluginFile);
        $version = (string) ($data['Version'] ?? '');
        $slug = $pluginDirRelative === '.' ? pathinfo($pluginFile, PATHINFO_FILENAME) : basename($pluginDirRelative);
        if ($slug === '' || $version === '') {
            $results[] = [
                'plugin' => $slug ?: $pluginFile,
                'version' => $version,
                'status' => 'unverified',
                'coverage' => 'none',
                'reason' => 'missing-plugin-directory-or-version',
            ];
            continue;
        }
        if ($pluginDirRelative === '.') {
            $results[] = [
                'plugin' => $slug,
                'version' => $version,
                'status' => 'unverified',
                'coverage' => 'none',
                'reason' => 'single-file-plugin',
            ];
            continue;
        }

        $url = sprintf(
            'https://downloads.wordpress.org/plugin-checksums/%s/%s.json',
            rawurlencode($slug),
            rawurlencode($version)
        );
        try {
            $manifest = fetch_plugin_manifest($url, $timeout);
        } catch (Throwable $error) {
            $results[] = [
                'plugin' => $slug,
                'version' => $version,
                'status' => $error->getMessage() === 'invalid-manifest' ? 'invalid-manifest' : 'unverified',
                'coverage' => 'none',
                'reason' => $error->getMessage() === 'invalid-manifest' ? 'manifest-invalid' : 'third-party-or-manifest-unavailable',
            ];
            if ($error->getMessage() === 'invalid-manifest') {
                ++$hardFailures;
            }
            continue;
        }

        $missing = [];
        $mismatch = [];
        $expected = [];
        foreach ($manifest['files'] as $relative => $hashes) {
            $safe = safe_plugin_relative((string) $relative);
            if ($safe === null || !is_array($hashes) || !isset($hashes['md5'])) {
                ++$hardFailures;
                continue;
            }
            $expected[$safe] = strtolower((string) $hashes['md5']);
            $path = $pluginsRoot . '/' . $pluginDirRelative . '/' . str_replace('/', DIRECTORY_SEPARATOR, $safe);
            if (!is_file($path)) {
                $missing[] = $safe;
                continue;
            }
            $actual = md5_file($path);
            if ($actual === false || !hash_equals($expected[$safe], strtolower($actual))) {
                $mismatch[] = $safe;
            }
        }

        $pluginDir = $pluginsRoot . '/' . $pluginDirRelative;
        $extra = scan_extra_plugin_files($pluginDir, $expected);
        $status = $missing || $mismatch ? 'failed' : 'ok';
        if ($status === 'failed') {
            ++$hardFailures;
        }
        $results[] = [
            'plugin' => $slug,
            'version' => $version,
            'checked' => count($expected),
            'missing' => $missing,
            'mismatch' => $mismatch,
            'extra_files' => $extra,
            'extra_scan' => $extra === null ? 'unavailable' : 'complete',
            'status' => $status,
            'coverage' => 'official-plugin-manifest',
        ];
    }

    echo wp_json_encode([
        'note' => 'Official manifests do not cover every third-party or modified plugin; unverified entries require separate review.',
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
    exit($hardFailures > 0 ? 1 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, 'WordPress plugin checksum check failed: ' . $error->getMessage() . "\n");
    exit(2);
}
