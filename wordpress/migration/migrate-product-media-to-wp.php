<?php

if (!defined('ABSPATH')) {
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
    WP_CLI::error('Seed file not found. Run npm run wp:seed:build first.');
}

$seed = json_decode((string) file_get_contents($seed_path), true);
if (!is_array($seed)) {
    WP_CLI::error('Seed file is not valid JSON: ' . $seed_path);
}

if (!class_exists('Milapro_Seed_Media_Importer')) {
    WP_CLI::error('Milapro Headless CMS plugin classes are not loaded. Activate the plugin first.');
}

$report = [
    'products_processed' => 0,
    'products_updated' => 0,
    'missing_products' => [],
    'images_imported' => 0,
    'images_failed' => 0,
    'images_skipped' => 0,
    'errors' => [],
];

$media = new Milapro_Seed_Media_Importer($report);

foreach (($seed['products'] ?? []) as $product) {
    if (!is_array($product)) {
        continue;
    }

    $slug = sanitize_title($product['slug'] ?? '');
    if (!$slug) {
        continue;
    }

    $post_id = milapro_migration_find_product($slug);
    if (!$post_id) {
        $report['missing_products'][] = $slug;
        continue;
    }

    $report['products_processed']++;
    $title = (string) ($product['name'] ?? $slug);
    $main_image_id = $media->attachment($product['sourceImage'] ?? null, $title);

    if ($main_image_id) {
        update_post_meta($post_id, '_milapro_main_image', $main_image_id);
        set_post_thumbnail($post_id, $main_image_id);
    }

    $gallery = [];
    foreach (($product['gallery'] ?? []) as $image) {
        $image_id = $media->attachment(is_string($image) ? $image : null, $title);
        if ($image_id) {
            $gallery[] = ['image' => $image_id];
        }
    }

    if (empty($gallery) && $main_image_id) {
        $gallery[] = ['image' => $main_image_id];
    }

    update_post_meta($post_id, '_milapro_gallery_images', $gallery);

    $colors = [];
    foreach (($product['colors'] ?? []) as $color) {
        if (!is_array($color)) {
            continue;
        }

        $color_image_id = $media->attachment($color['image'] ?? null, $title . ' ' . ($color['name'] ?? ''));
        if ($color_image_id) {
            $color['image'] = $color_image_id;
        }
        $colors[] = $color;
    }

    if (!empty($colors)) {
        update_post_meta($post_id, '_milapro_colors', $colors);
    }

    $report['products_updated']++;
}

WP_CLI::success(wp_json_encode($report));

function milapro_migration_find_product(string $slug): int
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
