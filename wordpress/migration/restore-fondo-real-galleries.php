<?php

if (!defined('ABSPATH')) {
    exit;
}

if ((!defined('WP_CLI') || !WP_CLI) && !defined('MILAPRO_FONDO_REAL_WEB_RUN')) {
    exit;
}

$seed_paths = [
    '/var/www/html/migration/seed.json',
    __DIR__ . '/seed.json',
];

$seed_path = '';
foreach ($seed_paths as $candidate) {
    if (is_readable($candidate)) {
        $seed_path = $candidate;
        break;
    }
}

if (!$seed_path) {
    milapro_restore_galleries_error('Seed file not found.');
}

$seed = json_decode((string) file_get_contents($seed_path), true);
if (!is_array($seed)) {
    milapro_restore_galleries_error('Seed file is not valid JSON: ' . $seed_path);
}

if (!class_exists('Milapro_Seed_Media_Importer')) {
    milapro_restore_galleries_error('Milapro media importer is not available. Activate the plugin first.');
}

$report = [
    'seed_path' => $seed_path,
    'products_scanned' => 0,
    'products_updated' => 0,
    'products_missing' => [],
    'gallery_images_restored' => 0,
    'gallery_images_reused_or_imported' => 0,
    'images_imported' => 0,
    'images_skipped' => 0,
    'images_failed' => 0,
    'errors' => [],
];

$media = new Milapro_Seed_Media_Importer($report);

foreach (($seed['products'] ?? []) as $product) {
    if (!is_array($product) || empty($product['slug'])) {
        continue;
    }

    $slug = sanitize_title((string) $product['slug']);
    $post_id = milapro_restore_galleries_find_product($slug);
    if (!$post_id) {
        $report['products_missing'][] = $slug;
        continue;
    }

    $report['products_scanned']++;
    $main_image_id = (int) get_post_meta($post_id, '_milapro_main_image', true);
    $current_gallery_ids = milapro_restore_galleries_ids(get_post_meta($post_id, '_milapro_gallery_images', true));
    $seed_gallery_ids = [];

    foreach (($product['gallery'] ?? []) as $source) {
        if (!is_string($source) || trim($source) === '') {
            continue;
        }

        $attachment_id = milapro_restore_galleries_attachment_id($source);
        if (!$attachment_id) {
            $attachment_id = $media->attachment($source, (string) ($product['name'] ?? $slug));
        }

        if ($attachment_id) {
            $seed_gallery_ids[] = (int) $attachment_id;
            $report['gallery_images_reused_or_imported']++;
        }
    }

    $gallery_ids = [];
    foreach (array_merge($current_gallery_ids, $seed_gallery_ids) as $attachment_id) {
        $attachment_id = (int) $attachment_id;
        if ($attachment_id <= 0 || $attachment_id === $main_image_id || in_array($attachment_id, $gallery_ids, true)) {
            continue;
        }

        $gallery_ids[] = $attachment_id;
    }

    update_post_meta($post_id, '_milapro_gallery_images', array_map(function (int $attachment_id): array {
        return ['image' => $attachment_id];
    }, $gallery_ids));

    $report['gallery_images_restored'] += count($gallery_ids);
    $report['products_updated']++;
}

milapro_restore_galleries_success($report);

function milapro_restore_galleries_find_product(string $slug): int
{
    $post = get_page_by_path($slug, OBJECT, 'products');
    if ($post) {
        return (int) $post->ID;
    }

    $by_meta = get_posts([
        'post_type' => 'products',
        'post_status' => 'any',
        'meta_key' => '_milapro_source_slug',
        'meta_value' => $slug,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    return $by_meta ? (int) $by_meta[0] : 0;
}

function milapro_restore_galleries_ids($gallery): array
{
    if (!is_array($gallery)) {
        return [];
    }

    return array_values(array_filter(array_map(function ($item): int {
        if (is_array($item)) {
            $item = $item['image'] ?? 0;
        }

        if (is_array($item)) {
            return (int) ($item['ID'] ?? $item['id'] ?? 0);
        }

        return is_numeric($item) ? (int) $item : 0;
    }, $gallery), function (int $attachment_id): bool {
        return $attachment_id > 0;
    }));
}

function milapro_restore_galleries_attachment_id(string $source): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => '_milapro_source_path',
        'meta_value' => $source,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    return !empty($existing[0]) ? (int) $existing[0] : 0;
}

function milapro_restore_galleries_error(string $message): void
{
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::error($message);
    }

    status_header(500);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode(['error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function milapro_restore_galleries_success(array $report): void
{
    $json = wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
        WP_CLI::success($json);
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo $json;
}
