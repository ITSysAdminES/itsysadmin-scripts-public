<?php
declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php verify-wordpress-integrity.php <root> <manifest> <core|plugin>\n");
    exit(2);
}

[$script, $rootInput, $manifestPath, $type] = $argv;
$root = realpath($rootInput);
if ($root === false || !is_dir($root)) {
    fwrite(STDERR, "The root must be an existing directory.\n");
    exit(2);
}
$root = rtrim($root, DIRECTORY_SEPARATOR);
$manifestRealPath = realpath($manifestPath);
if ($manifestRealPath === false || !is_file($manifestRealPath)) {
    fwrite(STDERR, "The manifest must be an existing file.\n");
    exit(2);
}
$manifest = json_decode((string) file_get_contents($manifestRealPath), true, 512, JSON_THROW_ON_ERROR);

function safe_relative_path(string $relative): ?string
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

function path_inside_root(string $root, string $relative): ?string
{
    $safe = safe_relative_path($relative);
    if ($safe === null) {
        return null;
    }
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safe);
    $probe = $path;
    while (!file_exists($probe)) {
        $parent = dirname($probe);
        if ($parent === $probe) {
            return null;
        }
        $probe = $parent;
    }
    $probeReal = realpath($probe);
    $prefix = $root . DIRECTORY_SEPARATOR;
    if ($probeReal === false || ($probeReal !== $root && strpos($probeReal, $prefix) !== 0)) {
        return null;
    }
    return $path;
}

if ($type === 'core') {
    $files = $manifest['checksums'] ?? [];
} elseif ($type === 'plugin') {
    $files = [];
    foreach (($manifest['files'] ?? []) as $path => $hashes) {
        if (isset($hashes['md5'])) {
            $files[$path] = $hashes['md5'];
        }
    }
} else {
    fwrite(STDERR, "Unknown manifest type.\n");
    exit(2);
}

$missing = [];
$mismatch = [];
$invalidCount = 0;
foreach ($files as $relative => $expected) {
    $safe = safe_relative_path((string) $relative);
    $path = $safe === null ? null : path_inside_root($root, $safe);
    if ($path === null) {
        ++$invalidCount;
        continue;
    }
    if (!is_file($path)) {
        $missing[] = $safe;
        continue;
    }
    $actual = md5_file($path);
    if (!hash_equals(strtolower((string) $expected), strtolower((string) $actual))) {
        $mismatch[] = $safe;
    }
}

$report = [
    'root' => basename($root) ?: 'configured-root',
    'type' => $type,
    'checked' => count($files),
    'missing' => $missing,
    'mismatch' => $mismatch,
    'invalid_count' => $invalidCount,
    'ok' => !$missing && !$mismatch && $invalidCount === 0,
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
exit($report['ok'] ? 0 : 1);
