<?php

declare(strict_types=1);

$expected_key = '__MIGRATION_KEY__';
$provided_key = isset($_GET['key']) ? (string) $_GET['key'] : '';
$is_cli = PHP_SAPI === 'cli';

if (!$is_cli && (!hash_equals($expected_key, $provided_key) || $expected_key === '__MIGRATION_KEY__')) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$wp_load = __DIR__ . '/wp-load.php';
if (!is_readable($wp_load)) {
    $wp_load = dirname(__DIR__, 2) . '/wp-load.php';
}
if (!is_readable($wp_load)) {
    http_response_code(500);
    echo "wp-load.php not found\n";
    exit;
}

require_once $wp_load;

if (!$is_cli) {
    header('Content-Type: application/json; charset=utf-8');
}

$payload_path = $is_cli && isset($argv[1]) ? (string) $argv[1] : __DIR__ . '/wp-content/uploads/milapro-seed/inventario-canonico.json';
if (!is_readable($payload_path)) {
    $payload_path = __DIR__ . '/inventario-canonico.json';
}
if (!is_readable($payload_path)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Payload not found'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit;
}

$payload = json_decode((string) file_get_contents($payload_path), true);
if (!is_array($payload)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Invalid payload JSON'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit;
}

if (!defined('MILAPRO_IMPORTING_SEED')) {
    define('MILAPRO_IMPORTING_SEED', true);
}

$report = [
    'ok' => true,
    'categories_created' => 0,
    'categories_updated' => 0,
    'products_created' => 0,
    'products_updated' => 0,
    'products_retired' => 0,
    'errors' => [],
];

function milapro_inventory_bool($value): int
{
    return $value ? 1 : 0;
}

function milapro_inventory_update_meta(int $post_id, array $values): void
{
    foreach ($values as $key => $value) {
        update_post_meta($post_id, '_milapro_' . $key, $value);
    }
}

function milapro_inventory_existing_post(array $product)
{
    $slugs = array_values(array_unique(array_filter([
        sanitize_title((string) ($product['oldSlug'] ?? '')),
        sanitize_title((string) ($product['slug'] ?? '')),
    ])));

    foreach ($slugs as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'products');
        if ($post) {
            return $post;
        }

        $by_meta = get_posts([
            'post_type' => 'products',
            'post_status' => ['publish', 'draft', 'private', 'pending'],
            'meta_key' => '_milapro_source_slug',
            'meta_value' => $slug,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]);
        if ($by_meta) {
            return get_post((int) $by_meta[0]);
        }
    }

    return null;
}

function milapro_inventory_merge_colors(int $post_id, array $incoming): array
{
    $existing = get_post_meta($post_id, '_milapro_colors', true);
    $existing = is_array($existing) ? $existing : [];
    $existing_by_id = [];

    foreach ($existing as $color) {
        if (!is_array($color)) {
            continue;
        }
        $id = sanitize_title((string) ($color['id'] ?? $color['name'] ?? ''));
        if ($id) {
            $existing_by_id[$id] = $color;
        }
    }

    $colors = [];
    foreach ($incoming as $color) {
        if (!is_array($color)) {
            continue;
        }
        $id = sanitize_title((string) ($color['id'] ?? $color['name'] ?? ''));
        $previous = $existing_by_id[$id] ?? [];
        $colors[] = array_merge($previous, [
            'id' => $id,
            'name' => sanitize_text_field((string) ($color['name'] ?? ($previous['name'] ?? $id))),
            'hex' => sanitize_text_field((string) ($color['hex'] ?? ($previous['hex'] ?? '#cccccc'))),
            'image' => $previous['image'] ?? ($color['image'] ?? ''),
            'available' => milapro_inventory_bool($color['available'] ?? true),
            'quantity' => $color['quantity'] ?? '',
            'price' => $color['price'] ?? '',
            'note' => sanitize_text_field((string) ($color['note'] ?? '')),
        ]);
    }

    return $colors;
}

foreach (($payload['categories'] ?? []) as $category) {
    $slug = sanitize_title((string) ($category['slug'] ?? ''));
    if (!$slug) {
        continue;
    }

    $term = term_exists($slug, 'product_category');
    $args = [
        'slug' => $slug,
        'description' => wp_kses_post((string) ($category['description'] ?? '')),
    ];
    if (!$term) {
        $result = wp_insert_term(sanitize_text_field((string) ($category['name'] ?? $slug)), 'product_category', $args);
        if (is_wp_error($result)) {
            $report['errors'][] = $result->get_error_message();
            continue;
        }
        $term_id = (int) $result['term_id'];
        $report['categories_created']++;
    } else {
        $result = wp_update_term((int) $term['term_id'], 'product_category', ['name' => sanitize_text_field((string) ($category['name'] ?? $slug))] + $args);
        if (is_wp_error($result)) {
            $report['errors'][] = $result->get_error_message();
            continue;
        }
        $term_id = (int) $term['term_id'];
        $report['categories_updated']++;
    }

    update_term_meta($term_id, '_milapro_source_slug', $slug);
    update_term_meta($term_id, '_milapro_display_order', (int) ($category['displayOrder'] ?? 999));
    update_term_meta($term_id, '_milapro_featured', milapro_inventory_bool($category['featured'] ?? true));
}

foreach (($payload['products'] ?? []) as $product) {
    $slug = sanitize_title((string) ($product['slug'] ?? ''));
    if (!$slug) {
        continue;
    }

    $existing = milapro_inventory_existing_post($product);
    $post_data = [
        'post_type' => 'products',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => sanitize_text_field((string) ($product['name'] ?? $slug)),
        'post_excerpt' => wp_kses_post((string) ($product['shortDescription'] ?? '')),
        'post_content' => wp_kses_post((string) ($product['description'] ?? '')),
    ];

    if ($existing) {
        $result = wp_update_post(['ID' => $existing->ID] + $post_data, true);
        $created = false;
    } else {
        $result = wp_insert_post($post_data, true);
        $created = true;
    }

    if (is_wp_error($result)) {
        $report['errors'][] = $result->get_error_message();
        continue;
    }

    $post_id = (int) $result;
    $term_ids = [];
    foreach (($product['categories'] ?? []) as $category_slug) {
        $term = term_exists(sanitize_title((string) $category_slug), 'product_category');
        if ($term && !is_wp_error($term)) {
            $term_ids[] = (int) $term['term_id'];
        }
    }
    wp_set_object_terms($post_id, $term_ids, 'product_category');

    milapro_inventory_update_meta($post_id, [
        'source_slug' => $slug,
        'previous_slug' => sanitize_title((string) ($product['oldSlug'] ?? '')),
        'price' => $product['price'] ?? '',
        'compare_at_price' => $product['compareAtPrice'] ?? '',
        'sku' => sanitize_text_field((string) ($product['sku'] ?? '')),
        'available' => milapro_inventory_bool($product['available'] ?? true),
        'featured' => milapro_inventory_bool($product['featured'] ?? false),
        'sale' => milapro_inventory_bool(false),
        'display_order' => (int) ($product['displayOrder'] ?? 999),
        'collection' => sanitize_text_field((string) ($product['collection'] ?? '')),
        'brand' => sanitize_text_field((string) ($product['brand'] ?? 'Milapro Home')),
        'dimensions' => sanitize_text_field((string) ($product['dimensions'] ?? 'Consultar disponibilidad')),
        'keywords' => sanitize_text_field((string) ($product['category'] ?? '')),
        'colors' => milapro_inventory_merge_colors($post_id, $product['colors'] ?? []),
        'variants' => $product['variants'] ?? [],
        'inventory_quantity' => $product['inventoryQuantity'] ?? '',
        'inventory_notes' => sanitize_text_field((string) ($product['notes'] ?? '')),
        'specifications' => $product['specifications'] ?? [],
    ]);

    $created ? $report['products_created']++ : $report['products_updated']++;
}

foreach (($payload['retireSlugs'] ?? []) as $slug) {
    $slug = sanitize_title((string) $slug);
    $post = get_page_by_path($slug, OBJECT, 'products');
    if (!$post) {
        continue;
    }
    $result = wp_update_post(['ID' => $post->ID, 'post_status' => 'draft'], true);
    if (is_wp_error($result)) {
        $report['errors'][] = $result->get_error_message();
        continue;
    }
    $report['products_retired']++;
}

flush_rewrite_rules(false);

if ($report['errors']) {
    $report['ok'] = false;
    http_response_code(500);
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
