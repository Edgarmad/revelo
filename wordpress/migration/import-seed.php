<?php

if (!defined('ABSPATH')) {
    exit;
}

$seed_path = '/var/www/html/migration/seed.json';
if (!file_exists($seed_path)) {
    WP_CLI::error('Seed file not found. Run npm run wp:seed:build first.');
}

$seed = json_decode(file_get_contents($seed_path), true);
if (!is_array($seed)) {
    WP_CLI::error('Seed file is not valid JSON.');
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function milapro_seed_bool($value): int
{
    return $value ? 1 : 0;
}

function milapro_seed_path(?string $source): ?string
{
    if (!$source) {
        return null;
    }

    if (str_starts_with($source, 'style:')) {
        return '/var/www/html/migration-style-images/' . substr($source, 6);
    }

    return '/var/www/html/migration-public/' . ltrim($source, '/');
}

function milapro_seed_attachment(?string $source, string $title = ''): int
{
    $path = milapro_seed_path($source);
    if (!$path || !file_exists($path)) {
        WP_CLI::warning('Missing media file: ' . ($source ?: 'empty'));
        return 0;
    }

    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => '_milapro_source_path',
        'meta_value' => $source,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $uploads = wp_upload_dir();
    $filename = wp_unique_filename($uploads['path'], basename($path));
    $target = trailingslashit($uploads['path']) . $filename;

    if (!copy($path, $target)) {
        WP_CLI::warning('Could not copy media file: ' . $source);
        return 0;
    }

    $filetype = wp_check_filetype($filename, null);
    $attachment_id = wp_insert_attachment([
        'guid' => trailingslashit($uploads['url']) . $filename,
        'post_mime_type' => $filetype['type'],
        'post_title' => $title ?: preg_replace('/\.[^.]+$/', '', $filename),
        'post_content' => '',
        'post_status' => 'inherit',
    ], $target);

    if (is_wp_error($attachment_id)) {
        WP_CLI::warning($attachment_id->get_error_message());
        return 0;
    }

    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $target));
    update_post_meta($attachment_id, '_milapro_source_path', $source);

    return (int) $attachment_id;
}

function milapro_seed_update_field(string $field_name, $value, $target): void
{
    $meta_key = '_milapro_' . $field_name;

    if (is_string($target) && str_starts_with($target, 'product_category_')) {
        $term_id = (int) substr($target, strlen('product_category_'));
        update_term_meta($term_id, $meta_key, $value);
        return;
    }

    update_post_meta((int) $target, $meta_key, $value);
}

function milapro_seed_upsert_post(string $post_type, string $slug, array $post_data): int
{
    $existing = get_page_by_path($slug, OBJECT, $post_type);
    $post_data['post_type'] = $post_type;
    $post_data['post_name'] = $slug;
    $post_data['post_status'] = 'publish';

    if ($existing) {
        $post_data['ID'] = $existing->ID;
        $result = wp_update_post($post_data, true);
    } else {
        $result = wp_insert_post($post_data, true);
    }

    if (is_wp_error($result)) {
        WP_CLI::error($result->get_error_message());
    }

    return (int) $result;
}

$category_ids = [];
foreach ($seed['categories'] ?? [] as $category) {
    $term = term_exists($category['slug'], 'product_category');

    if (!$term) {
        $term = wp_insert_term($category['name'], 'product_category', [
            'slug' => $category['slug'],
            'description' => $category['description'],
        ]);
    } else {
        wp_update_term((int) $term['term_id'], 'product_category', [
            'name' => $category['name'],
            'description' => $category['description'],
        ]);
    }

    if (is_wp_error($term)) {
        WP_CLI::error($term->get_error_message());
    }

    $term_id = (int) $term['term_id'];
    $category_ids[$category['slug']] = $term_id;
    $image_id = milapro_seed_attachment($category['sourceImage'] ?? null, $category['name']);
    $target = 'product_category_' . $term_id;
    milapro_seed_update_field('category_image', $image_id, $target);
    milapro_seed_update_field('eyebrow', $category['eyebrow'] ?? '', $target);
    milapro_seed_update_field('display_order', (int) ($category['displayOrder'] ?? 999), $target);
    milapro_seed_update_field('featured', milapro_seed_bool($category['featured'] ?? true), $target);
}

$seed_product_slugs = [];
foreach ($seed['products'] ?? [] as $product) {
    $seed_product_slugs[] = $product['slug'];
    $post_id = milapro_seed_upsert_post('products', $product['slug'], [
        'post_title' => $product['name'],
        'post_excerpt' => $product['shortDescription'],
        'post_content' => $product['description'],
    ]);

    $main_image_id = milapro_seed_attachment($product['sourceImage'] ?? null, $product['name']);
    if ($main_image_id) {
        set_post_thumbnail($post_id, $main_image_id);
    }

    $gallery_ids = [];
    foreach ($product['gallery'] ?? [] as $image) {
        $image_id = milapro_seed_attachment($image, $product['name']);
        if ($image_id) {
            $gallery_ids[] = $image_id;
        }
    }

    $term_ids = [];
    foreach ($product['categories'] ?? [] as $slug) {
        if (isset($category_ids[$slug])) {
            $term_ids[] = $category_ids[$slug];
        }
    }
    wp_set_object_terms($post_id, $term_ids, 'product_category');

    milapro_seed_update_field('price', $product['price'], $post_id);
    milapro_seed_update_field('compare_at_price', $product['compareAtPrice'] ?? '', $post_id);
    milapro_seed_update_field('sku', $product['sku'], $post_id);
    milapro_seed_update_field('available', milapro_seed_bool($product['available'] ?? true), $post_id);
    milapro_seed_update_field('featured', milapro_seed_bool($product['featured'] ?? false), $post_id);
    milapro_seed_update_field('display_order', (int) ($product['displayOrder'] ?? 999), $post_id);
    milapro_seed_update_field('collection', $product['collection'] ?? '', $post_id);
    milapro_seed_update_field('brand', $product['brand'] ?? 'Milapro Home', $post_id);
    milapro_seed_update_field('dimensions', $product['dimensions'] ?? '', $post_id);
    milapro_seed_update_field('main_image', $main_image_id, $post_id);
    milapro_seed_update_field('gallery_images', array_map(fn ($image_id) => ['image' => $image_id], $gallery_ids), $post_id);
    $colors = [];
    foreach ($product['colors'] ?? [] as $color) {
        $color_image_id = milapro_seed_attachment($color['image'] ?? null, $product['name'] . ' ' . ($color['name'] ?? ''));
        $color['image'] = $color_image_id ?: ($color['image'] ?? '');
        $colors[] = $color;
    }
    milapro_seed_update_field('colors', $colors, $post_id);
    milapro_seed_update_field('variants', [], $post_id);
    milapro_seed_update_field('specifications', $product['specifications'] ?? [], $post_id);
}

foreach (get_posts(['post_type' => 'products', 'post_status' => 'publish', 'numberposts' => -1]) as $existing_product) {
    if (!in_array($existing_product->post_name, $seed_product_slugs, true)) {
        wp_update_post(['ID' => $existing_product->ID, 'post_status' => 'draft']);
    }
}

foreach ($seed['reels'] ?? [] as $reel) {
    $post_id = milapro_seed_upsert_post('reels', $reel['slug'], [
        'post_title' => $reel['title'],
        'post_content' => '',
    ]);

    $cover_id = milapro_seed_attachment($reel['sourceImage'] ?? null, $reel['title']);
    if ($cover_id) {
        set_post_thumbnail($post_id, $cover_id);
    }

    milapro_seed_update_field('video_url', $reel['videoUrl'] ?? '', $post_id);
    milapro_seed_update_field('cover_image', $cover_id, $post_id);
    milapro_seed_update_field('platform', $reel['platform'] ?? 'instagram', $post_id);
    milapro_seed_update_field('display_order', (int) ($reel['displayOrder'] ?? 999), $post_id);
    milapro_seed_update_field('is_visible', milapro_seed_bool($reel['visible'] ?? true), $post_id);
}

foreach ($seed['blogs'] ?? [] as $blog) {
    $term = term_exists($blog['category'], 'category');
    if (!$term) {
        $term = wp_insert_term($blog['category'], 'category');
    }
    $term_id = is_wp_error($term) ? 0 : (int) $term['term_id'];

    $post_id = milapro_seed_upsert_post('post', $blog['slug'], [
        'post_title' => $blog['title'],
        'post_excerpt' => $blog['excerpt'],
        'post_content' => $blog['content'],
    ]);

    if ($term_id) {
        wp_set_post_terms($post_id, [$term_id], 'category');
    }

    $image_id = milapro_seed_attachment($blog['sourceImage'] ?? null, $blog['title']);
    if ($image_id) {
        set_post_thumbnail($post_id, $image_id);
    }
}

flush_rewrite_rules();

WP_CLI::success(sprintf(
    'Imported %d categories, %d products, %d reels and %d blogs.',
    count($seed['categories'] ?? []),
    count($seed['products'] ?? []),
    count($seed['reels'] ?? []),
    count($seed['blogs'] ?? [])
));
