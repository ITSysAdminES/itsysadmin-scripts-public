<?php
declare(strict_types=1);

define('WP_CLI', true);
require '/var/www/html/wp-load.php';

function audit_word_count(string $content): int
{
    $text = html_entity_decode(wp_strip_all_tags(strip_shortcodes($content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{27}-][\p{L}\p{N}]+)*/u', $text, $matches);
    return count($matches[0]);
}

function audit_link_counts(string $content, string $homeHost): array
{
    preg_match_all('/<a\b[^>]*\bhref=["\x{27}]([^"\x{27}]+)["\x{27}]/iu', $content, $matches);
    $internal = 0;
    $external = 0;

    foreach ($matches[1] as $href) {
        $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($href, '/') === 0 || strpos($href, '#') === 0) {
            ++$internal;
            continue;
        }

        $host = (string) wp_parse_url($href, PHP_URL_HOST);
        if ($host === '' || $host === $homeHost) {
            ++$internal;
        } else {
            ++$external;
        }
    }

    return ['internal' => $internal, 'external' => $external];
}

function editorial_integer(string $name, int $default, int $max): int
{
    $value = getenv($name);
    $number = $value === false || $value === '' ? $default : (int) $value;
    return max(1, min($max, $number));
}

$limit = editorial_integer('EDITORIAL_AUDIT_LIMIT', 100, 1000);
$page = editorial_integer('EDITORIAL_AUDIT_PAGE', 1, 1000000);
foreach (array_slice($argv, 1) as $argument) {
    if (strpos($argument, '--limit=') === 0) {
        $limit = max(1, min(1000, (int) substr($argument, 8)));
    } elseif (strpos($argument, '--page=') === 0) {
        $page = max(1, min(1000000, (int) substr($argument, 7)));
    } elseif ($argument !== '') {
        fwrite(STDERR, "Usage: php editorial-audit.php [--limit=N] [--page=N]\n");
        exit(2);
    }
}

$homeHost = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
$posts = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => $limit,
    'paged' => $page,
    'orderby' => 'date',
    'order' => 'DESC',
    'no_found_rows' => true,
]);

$postRows = [];
$wordCounts = [];
$withoutImage = 0;
$withoutExcerpt = 0;
$withoutInternalLinks = 0;

foreach ($posts as $post) {
    $words = audit_word_count($post->post_content);
    $links = audit_link_counts($post->post_content, $homeHost);
    $hasImage = has_post_thumbnail($post->ID) || preg_match('/<img\b/i', $post->post_content) === 1;
    $hasExcerpt = trim((string) $post->post_excerpt) !== '';
    $wordCounts[] = $words;
    $withoutImage += $hasImage ? 0 : 1;
    $withoutExcerpt += $hasExcerpt ? 0 : 1;
    $withoutInternalLinks += $links['internal'] > 0 ? 0 : 1;

    $postRows[] = [
        'id' => $post->ID,
        'title' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'date' => get_post_time('Y-m-d', false, $post),
        'slug' => $post->post_name,
        'words' => $words,
        'categories' => wp_get_post_terms($post->ID, 'category', ['fields' => 'names']),
        'tags' => wp_get_post_terms($post->ID, 'post_tag', ['fields' => 'names']),
        'featured_image' => has_post_thumbnail($post->ID),
        'any_image' => $hasImage,
        'manual_excerpt' => $hasExcerpt,
        'links' => $links,
    ];
}

sort($wordCounts);
$totalWords = array_sum($wordCounts);
$medianWords = $wordCounts === [] ? 0 : $wordCounts[(int) floor((count($wordCounts) - 1) / 2)];
$totalPublished = (int) (wp_count_posts('post')->publish ?? 0);

$categories = [];
foreach (get_categories(['hide_empty' => false]) as $category) {
    $categories[] = [
        'name' => $category->name,
        'slug' => $category->slug,
        'posts' => $category->count,
        'description' => trim((string) $category->description),
    ];
}

$pages = [];
foreach (get_pages(['post_status' => 'publish']) as $pagePost) {
    $pages[] = [
        'title' => html_entity_decode(get_the_title($pagePost), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'slug' => $pagePost->post_name,
        'words' => audit_word_count($pagePost->post_content),
    ];
}

$report = [
    'site' => [
        'name' => get_bloginfo('name'),
        'home' => home_url('/'),
        'tagline' => get_bloginfo('description'),
    ],
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'returned_posts' => count($posts),
        'total_published_posts' => $totalPublished,
        'has_more' => ($page * $limit) < $totalPublished,
    ],
    'summary' => [
        'published_posts_in_page' => count($posts),
        'published_pages' => count($pages),
        'total_article_words_in_page' => $totalWords,
        'average_words_in_page' => count($wordCounts) > 0 ? (int) round($totalWords / count($wordCounts)) : 0,
        'median_words_in_page' => $medianWords,
        'under_600_words' => count(array_filter($wordCounts, static function (int $n): bool { return $n < 600; })),
        'over_1500_words' => count(array_filter($wordCounts, static function (int $n): bool { return $n >= 1500; })),
        'without_any_image' => $withoutImage,
        'without_manual_excerpt' => $withoutExcerpt,
        'without_internal_links' => $withoutInternalLinks,
    ],
    'categories' => $categories,
    'pages' => $pages,
    'posts' => $postRows,
];

echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
