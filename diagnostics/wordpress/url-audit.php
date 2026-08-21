<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require '/var/www/html/wp-load.php';

global $wpdb;

$args = array_slice($argv, 1);
$includeOptions = false;
$includeComments = false;
$needles = [];
foreach ($args as $arg) {
    if ($arg === '--include-options') {
        $includeOptions = true;
    } elseif ($arg === '--include-comments') {
        $includeComments = true;
    } elseif (strpos($arg, '--') === 0) {
        fwrite(STDERR, "Unknown option: {$arg}\n");
        exit(2);
    } else {
        $needles[] = $arg;
    }
}
if ($needles === []) {
    fwrite(STDERR, "Usage: php url-audit.php [--include-options] [--include-comments] URL [URL...]\n");
    exit(2);
}

$columns = [
    'posts.post_title' => [$wpdb->posts, 'post_title'],
    'posts.post_content' => [$wpdb->posts, 'post_content'],
    'posts.post_excerpt' => [$wpdb->posts, 'post_excerpt'],
    'posts.guid' => [$wpdb->posts, 'guid'],
    'postmeta.meta_value' => [$wpdb->postmeta, 'meta_value'],
    'termmeta.meta_value' => [$wpdb->termmeta, 'meta_value'],
];
if ($includeOptions) {
    $columns['options.option_value'] = [$wpdb->options, 'option_value'];
    fwrite(STDERR, "Warning: options may contain secrets or other sensitive configuration; counts only are returned.\n");
}
if ($includeComments) {
    $columns['comments.comment_content'] = [$wpdb->comments, 'comment_content'];
    $columns['comments.comment_author_url'] = [$wpdb->comments, 'comment_author_url'];
    fwrite(STDERR, "Warning: comments may contain personal or sensitive data; counts only are returned.\n");
}

$result = [];
foreach ($needles as $needle) {
    $counts = [];
    foreach ($columns as $label => [$table, $column]) {
        $like = '%' . $wpdb->esc_like($needle) . '%';
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE {$column} LIKE %s",
            $like
        );
        $count = (int) $wpdb->get_var($sql);
        if ($count > 0) {
            $counts[$label] = $count;
        }
    }
    $result[$needle] = $counts;
}

echo json_encode([
    'scopes' => array_keys($columns),
    'results' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
