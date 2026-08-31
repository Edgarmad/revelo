<?php

if (!defined('ABSPATH')) {
    exit;
}

if ((!defined('WP_CLI') || !WP_CLI) && !defined('MILAPRO_FONDO_REAL_WEB_RUN')) {
    exit;
}

$base_path = getenv('MILAPRO_FONDO_REAL_PATH') ?: (defined('MILAPRO_FONDO_REAL_PATH') ? MILAPRO_FONDO_REAL_PATH : '/var/www/html/fondo-real');
$base_path = rtrim((string) $base_path, DIRECTORY_SEPARATOR);

$report = [
    'base_path' => $base_path,
    'products_scanned' => 0,
    'products_processed' => 0,
    'products_updated' => 0,
    'images_imported' => 0,
    'images_reused' => 0,
    'images_failed' => 0,
    'products_deleted' => [],
    'products_delete_missing' => [],
    'products_pending' => [],
    'products_skipped' => [],
    'errors' => [],
];

if (!is_dir($base_path) || !is_readable($base_path)) {
    milapro_fondo_real_error('Fondo real base path is not readable: ' . $base_path);
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

milapro_fondo_real_delete_removed_products($report);

$wp_products = milapro_fondo_real_wp_products();
$local_products = milapro_fondo_real_local_products($base_path);

foreach ($local_products as $local_product) {
    $report['products_scanned']++;

    if (!$local_product['has_fondo_real']) {
        $report['products_skipped'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => 'missing_fondo_real',
        ];
        continue;
    }

    if (empty($local_product['images'])) {
        $report['products_pending'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => 'empty_fondo_real',
        ];
        continue;
    }

    $match = milapro_fondo_real_match_product($local_product, $wp_products);
    if (!$match['post_id']) {
        $report['products_pending'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => $match['reason'],
            'candidates' => $match['candidates'],
        ];
        continue;
    }

    $report['products_processed']++;
    $attachment_ids = [];

    foreach ($local_product['images'] as $index => $image_path) {
        $attachment_id = milapro_fondo_real_import_image($image_path, $local_product, $match['slug'], $index, $report);
        if ($attachment_id) {
            $attachment_ids[] = $attachment_id;
        }
    }

    if (empty($attachment_ids)) {
        $report['products_pending'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => 'all_images_failed',
        ];
        continue;
    }

    $main_image_id = (int) $attachment_ids[0];
    update_post_meta($match['post_id'], '_milapro_main_image', $main_image_id);
    set_post_thumbnail($match['post_id'], $main_image_id);

    $gallery = array_map(function (int $attachment_id): array {
        return ['image' => $attachment_id];
    }, array_slice($attachment_ids, 1));

    update_post_meta($match['post_id'], '_milapro_gallery_images', $gallery);
    $report['products_updated']++;
}

milapro_fondo_real_success($report);

function milapro_fondo_real_delete_removed_products(array &$report): void
{
    $slugs = [
        'aluminio-mesa-volga-2-3',
        'outlet-turkana',
        'plantas-palma-areca',
        'plastico-mesa-cala',
        'plastico-mesa-loto',
        'plastico-silleta-iris',
        'plastico-sillon-anturio',
        'plastico-sillones-narciso',
        'plastico-silla-alta-datura',
        'plastico-silla-alta-narciso',
        'aluminio-sillones-casio',
    ];

    foreach ($slugs as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'products');
        if (!$post) {
            $report['products_delete_missing'][] = $slug;
            continue;
        }

        wp_delete_post((int) $post->ID, true);
        $report['products_deleted'][] = $slug;
    }
}

function milapro_fondo_real_wp_products(): array
{
    $posts = get_posts([
        'post_type' => 'products',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);

    return array_map(function (WP_Post $post): array {
        $terms = get_the_terms($post->ID, 'product_category');
        $term_names = is_array($terms) ? array_map(function (WP_Term $term): string {
            return $term->name;
        }, $terms) : [];

        return [
            'post_id' => (int) $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'normalized_title' => milapro_fondo_real_normalize($post->post_title),
            'terms' => $term_names,
            'normalized_terms' => array_map('milapro_fondo_real_normalize', $term_names),
        ];
    }, $posts);
}

function milapro_fondo_real_local_products(string $base_path): array
{
    $products = [];
    $categories = glob($base_path . '/*', GLOB_ONLYDIR) ?: [];
    natcasesort($categories);

    foreach ($categories as $category_path) {
        $product_paths = glob($category_path . '/*', GLOB_ONLYDIR) ?: [];
        natcasesort($product_paths);

        foreach ($product_paths as $product_path) {
            $fondo_real_path = milapro_fondo_real_dir($product_path);
            $has_fondo_real = $fondo_real_path !== '';
            $images = $has_fondo_real ? milapro_fondo_real_images($fondo_real_path) : [];

            $products[] = [
                'category' => basename($category_path),
                'product' => basename($product_path),
                'path' => $product_path,
                'fondo_real_path' => $fondo_real_path,
                'has_fondo_real' => $has_fondo_real,
                'images' => $images,
            ];
        }
    }

    return $products;
}

function milapro_fondo_real_dir(string $product_path): string
{
    $expected = $product_path . DIRECTORY_SEPARATOR . '1 fondo real';
    if (is_dir($expected)) {
        return $expected;
    }

    $children = glob($product_path . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($children as $child) {
        if (strtolower(basename($child)) === '1 fondo real') {
            return $child;
        }
    }

    return '';
}

function milapro_fondo_real_images(string $path): array
{
    $files = glob($path . '/*') ?: [];
    $images = array_values(array_filter($files, function (string $file): bool {
        return is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }));

    usort($images, 'strnatcasecmp');
    return $images;
}

function milapro_fondo_real_match_product(array $local_product, array $wp_products): array
{
    $local_category = milapro_fondo_real_normalize($local_product['category']);
    $local_name = milapro_fondo_real_normalize($local_product['product']);
    $local_tokens = milapro_fondo_real_tokens($local_name);
    $candidates = [];

    foreach ($wp_products as $wp_product) {
        if (!in_array($local_category, $wp_product['normalized_terms'], true)) {
            continue;
        }

        $wp_name = $wp_product['normalized_title'];
        $wp_tokens = milapro_fondo_real_tokens($wp_name);
        $score = 0;

        if ($local_name === $wp_name) {
            $score = 100;
        } elseif (str_contains($local_name, $wp_name) || str_contains($wp_name, $local_name)) {
            $score = 80;
        } elseif (milapro_fondo_real_tokens_contained($local_tokens, $wp_tokens) || milapro_fondo_real_tokens_contained($wp_tokens, $local_tokens)) {
            $score = 70;
        }

        if ($score > 0) {
            $candidates[] = [
                'post_id' => $wp_product['post_id'],
                'title' => $wp_product['title'],
                'slug' => $wp_product['slug'],
                'score' => $score,
            ];
        }
    }

    usort($candidates, function (array $a, array $b): int {
        return $b['score'] <=> $a['score'];
    });

    if (empty($candidates)) {
        return ['post_id' => 0, 'slug' => '', 'reason' => 'no_match', 'candidates' => []];
    }

    $best = $candidates[0];
    $ties = array_values(array_filter($candidates, function (array $candidate) use ($best): bool {
        return $candidate['score'] === $best['score'];
    }));

    if (count($ties) > 1) {
        return ['post_id' => 0, 'slug' => '', 'reason' => 'ambiguous_match', 'candidates' => $ties];
    }

    return ['post_id' => (int) $best['post_id'], 'slug' => $best['slug'], 'reason' => 'matched', 'candidates' => [$best]];
}

function milapro_fondo_real_import_image(string $source_path, array $local_product, string $product_slug, int $index, array &$report): int
{
    $source_key = $local_product['category'] . '/' . $local_product['product'] . '/1 fondo real/' . basename($source_path);
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_milapro_fondo_real_source_path',
        'meta_value' => $source_key,
    ]);

    if (!empty($existing[0])) {
        $report['images_reused']++;
        return (int) $existing[0];
    }

    $extension = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
    $filename = milapro_fondo_real_seo_filename($local_product['category'], $local_product['product'], $extension, $index);
    $tmp = wp_tempnam($filename);

    if (!$tmp || !copy($source_path, $tmp)) {
        $report['images_failed']++;
        $report['errors'][] = 'Could not copy image: ' . $source_key;
        return 0;
    }

    $renamed_tmp = dirname($tmp) . DIRECTORY_SEPARATOR . $filename;
    if (!@rename($tmp, $renamed_tmp)) {
        $renamed_tmp = $tmp;
    }

    $file = [
        'name' => $filename,
        'type' => wp_check_filetype($filename)['type'] ?? '',
        'tmp_name' => $renamed_tmp,
        'error' => 0,
        'size' => filesize($renamed_tmp),
    ];

    $attachment_id = media_handle_sideload($file, 0, $local_product['product']);
    if (is_wp_error($attachment_id)) {
        @unlink($renamed_tmp);
        $report['images_failed']++;
        $report['errors'][] = $source_key . ': ' . $attachment_id->get_error_message();
        return 0;
    }

    update_post_meta((int) $attachment_id, '_milapro_fondo_real_source_path', $source_key);
    update_post_meta((int) $attachment_id, '_milapro_fondo_real_product_slug', $product_slug);
    update_post_meta((int) $attachment_id, '_milapro_source_path', $source_key);
    $report['images_imported']++;

    return (int) $attachment_id;
}

function milapro_fondo_real_seo_filename(string $category, string $product, string $extension, int $index): string
{
    $base = milapro_fondo_real_slug($category . '-' . $product . '-fondo-real');
    $suffix = $index === 0 ? '' : '-' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    return $base . $suffix . '.' . $extension;
}

function milapro_fondo_real_normalize(string $value): string
{
    $value = milapro_fondo_real_slug($value);
    return str_replace('-', ' ', $value);
}

function milapro_fondo_real_slug(string $value): string
{
    $value = strtr($value, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
    $value = strtolower($value);
    if (function_exists('remove_accents')) {
        $value = remove_accents($value);
    }

    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (is_string($converted)) {
        $value = $converted;
    }

    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    $value = preg_replace('/-+/', '-', $value) ?: '';
    return trim($value, '-');
}

function milapro_fondo_real_tokens(string $value): array
{
    return array_values(array_filter(explode(' ', $value), function (string $token): bool {
        return $token !== '';
    }));
}

function milapro_fondo_real_tokens_contained(array $needles, array $haystack): bool
{
    if (empty($needles) || empty($haystack)) {
        return false;
    }

    return count(array_diff($needles, $haystack)) === 0;
}

function milapro_fondo_real_error(string $message): void
{
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::error($message);
    }

    status_header(500);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode(['error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function milapro_fondo_real_success(array $report): void
{
    $json = wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::success($json);
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo $json;
}
