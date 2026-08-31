<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_CLI') || !WP_CLI) {
    exit;
}

$base_path = getenv('MILAPRO_GALLERIES_PATH') ?: '/var/www/html/product-galleries';
$base_path = rtrim((string) $base_path, DIRECTORY_SEPARATOR);
$dry_run = getenv('MILAPRO_GALLERIES_DRY_RUN') !== '0';

$report = [
    'base_path' => $base_path,
    'dry_run' => $dry_run,
    'products_scanned' => 0,
    'products_processed' => 0,
    'products_updated' => 0,
    'images_planned' => 0,
    'images_imported' => 0,
    'images_reused' => 0,
    'images_renamed' => 0,
    'images_failed' => 0,
    'main_image_unchanged' => 0,
    'main_image_changed' => [],
    'products_pending' => [],
    'products_skipped' => [],
    'products' => [],
    'errors' => [],
];

if (!is_dir($base_path) || !is_readable($base_path)) {
    milapro_galleries_error('Gallery base path is not readable: ' . $base_path);
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$wp_products = milapro_galleries_wp_products();
$local_products = milapro_galleries_local_products($base_path);

foreach ($local_products as $local_product) {
    $report['products_scanned']++;

    if (milapro_galleries_is_noncanonical($local_product)) {
        $report['products_skipped'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => 'noncanonical_removed_previous_migration',
            'image_count' => count($local_product['images']),
        ];
        continue;
    }

    if (empty($local_product['images'])) {
        $report['products_pending'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => 'no_canonical_gallery_images',
        ];
        continue;
    }

    $match = milapro_galleries_match_product($local_product, $wp_products);
    if (!$match['post_id']) {
        $report['products_pending'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'reason' => $match['reason'],
            'candidates' => $match['candidates'],
            'image_count' => count($local_product['images']),
        ];
        continue;
    }

    $report['products_processed']++;
    $report['images_planned'] += count($local_product['images']);

    $main_before = (int) get_post_meta($match['post_id'], '_milapro_main_image', true);
    $featured_before = (int) get_post_thumbnail_id($match['post_id']);
    $attachment_ids = [];

    foreach ($local_product['images'] as $index => $image) {
        $attachment_id = milapro_galleries_import_image($image, $local_product, $match['slug'], $index, $dry_run, $report);
        if ($attachment_id) {
            $attachment_ids[] = $attachment_id;
        }
    }

    if (!$dry_run && count($attachment_ids) !== count($local_product['images'])) {
        $report['products_pending'][] = [
            'category' => $local_product['category'],
            'product' => $local_product['product'],
            'slug' => $match['slug'],
            'reason' => 'not_all_images_imported',
            'expected' => count($local_product['images']),
            'actual' => count($attachment_ids),
        ];
        continue;
    }

    if (!$dry_run) {
        $gallery = array_map(function (int $attachment_id): array {
            return ['image' => $attachment_id];
        }, $attachment_ids);

        update_post_meta($match['post_id'], '_milapro_gallery_images', $gallery);
        $report['products_updated']++;
    }

    $main_after = (int) get_post_meta($match['post_id'], '_milapro_main_image', true);
    $featured_after = (int) get_post_thumbnail_id($match['post_id']);
    $main_ok = $main_before === $main_after && $featured_before === $featured_after;

    if ($main_ok) {
        $report['main_image_unchanged']++;
    } else {
        $report['main_image_changed'][] = [
            'slug' => $match['slug'],
            'main_before' => $main_before,
            'main_after' => $main_after,
            'featured_before' => $featured_before,
            'featured_after' => $featured_after,
        ];
        milapro_galleries_error('Main image changed unexpectedly for ' . $match['slug'], $report);
    }

    $report['products'][] = [
        'category' => $local_product['category'],
        'product' => $local_product['product'],
        'slug' => $match['slug'],
        'post_id' => $match['post_id'],
        'gallery_images' => count($local_product['images']),
        'main_image' => $main_after,
        'featured_media' => $featured_after,
        'updated' => !$dry_run,
    ];
}

milapro_galleries_success($report);

function milapro_galleries_wp_products(): array
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
            'normalized_title' => milapro_galleries_normalize($post->post_title),
            'title_key' => milapro_galleries_key($post->post_title),
            'normalized_terms' => array_map('milapro_galleries_category_key', $term_names),
        ];
    }, $posts);
}

function milapro_galleries_local_products(string $base_path): array
{
    $products = [];
    $categories = glob($base_path . '/*', GLOB_ONLYDIR) ?: [];
    natcasesort($categories);

    foreach ($categories as $category_path) {
        $product_paths = glob($category_path . '/*', GLOB_ONLYDIR) ?: [];
        natcasesort($product_paths);

        foreach ($product_paths as $product_path) {
            $products[] = [
                'category' => basename($category_path),
                'product' => basename($product_path),
                'path' => $product_path,
                'images' => milapro_galleries_product_images($product_path),
            ];
        }
    }

    return $products;
}

function milapro_galleries_product_images(string $product_path): array
{
    $images = [];
    foreach (milapro_galleries_slots() as $slot => $label) {
        $slot_path = milapro_galleries_find_dir($product_path, $label);
        if (!$slot_path) {
            continue;
        }

        foreach (milapro_galleries_images_recursive($slot_path) as $slot_index => $image_path) {
            $images[] = [
                'path' => $image_path,
                'slot' => $slot,
                'label' => $label,
                'slot_index' => $slot_index + 1,
                'relative_path' => milapro_galleries_relative_path($product_path, $image_path),
            ];
        }
    }

    return $images;
}

function milapro_galleries_slots(): array
{
    return [
        '2-fondo-blanco' => '2 fondo blanco',
        '3-medidas' => '3 medidas',
        '4-variantes' => '4 variantes',
    ];
}

function milapro_galleries_find_dir(string $product_path, string $expected): string
{
    $children = glob($product_path . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($children as $child) {
        if (milapro_galleries_normalize(basename($child)) === milapro_galleries_normalize($expected)) {
            return $child;
        }
    }

    return '';
}

function milapro_galleries_images_recursive(string $path): array
{
    $images = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $images[] = $file->getPathname();
        }
    }

    usort($images, 'strnatcasecmp');
    return $images;
}

function milapro_galleries_is_noncanonical(array $local_product): bool
{
    $removed = [
        'aluminio|mesa volga 2 3',
        'aluminio|sillones caspio',
        'outlet|turkana',
        'plantas|palma areca',
        'plastico|mesa cala',
        'plastico|mesa loto',
        'plastico|silla alta datura',
        'plastico|silla alta narciso',
        'plastico|silleta iris',
        'plastico|sillon anturio',
        'plastico|sillones narciso',
    ];

    return in_array(milapro_galleries_category_key($local_product['category']) . '|' . milapro_galleries_normalize($local_product['product']), $removed, true);
}

function milapro_galleries_match_product(array $local_product, array $wp_products): array
{
    $local_category = milapro_galleries_category_key($local_product['category']);
    $local_title = milapro_galleries_normalize($local_product['product']);
    $local_key = milapro_galleries_key($local_product['product']);
    $same_category = array_values(array_filter($wp_products, function (array $wp_product) use ($local_category): bool {
        return in_array($local_category, $wp_product['normalized_terms'], true);
    }));

    $exact = array_values(array_filter($same_category, function (array $wp_product) use ($local_key): bool {
        return $wp_product['title_key'] === $local_key;
    }));

    if (count($exact) === 1) {
        return ['post_id' => (int) $exact[0]['post_id'], 'slug' => $exact[0]['slug'], 'reason' => 'matched_exact', 'candidates' => [$exact[0]]];
    }

    $candidates = [];
    $local_tokens = milapro_galleries_tokens($local_title);
    foreach ($same_category as $wp_product) {
        $wp_title = $wp_product['normalized_title'];
        $wp_tokens = milapro_galleries_tokens($wp_title);

        if ($local_title === $wp_title || str_contains($local_title, $wp_title) || str_contains($wp_title, $local_title) || milapro_galleries_tokens_contained($local_tokens, $wp_tokens) || milapro_galleries_tokens_contained($wp_tokens, $local_tokens)) {
            $candidates[] = $wp_product;
        }
    }

    if (count($candidates) === 1) {
        return ['post_id' => (int) $candidates[0]['post_id'], 'slug' => $candidates[0]['slug'], 'reason' => 'matched_partial', 'candidates' => [$candidates[0]]];
    }

    return ['post_id' => 0, 'slug' => '', 'reason' => empty($candidates) ? 'no_match' : 'ambiguous_match', 'candidates' => $candidates];
}

function milapro_galleries_import_image(array $image, array $local_product, string $product_slug, int $index, bool $dry_run, array &$report): int
{
    $source_key = $local_product['category'] . '/' . $local_product['product'] . '/' . $image['relative_path'];
    $extension = strtolower(pathinfo($image['path'], PATHINFO_EXTENSION));
    $filename = milapro_galleries_seo_filename($local_product['category'], $local_product['product'], $image['slot'], $extension, (int) $image['slot_index']);
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_milapro_gallery_source_path',
        'meta_value' => $source_key,
    ]);

    if (!empty($existing[0])) {
        $report['images_reused']++;
        if (!$dry_run) {
            milapro_galleries_ensure_attachment_filename((int) $existing[0], $filename, $report);
        }
        return (int) $existing[0];
    }

    if ($dry_run) {
        return 0;
    }

    $tmp = wp_tempnam($filename);

    if (!$tmp || !copy($image['path'], $tmp)) {
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

    update_post_meta((int) $attachment_id, '_milapro_gallery_source_path', $source_key);
    update_post_meta((int) $attachment_id, '_milapro_gallery_product_slug', $product_slug);
    update_post_meta((int) $attachment_id, '_milapro_gallery_slot', $image['slot']);
    $report['images_imported']++;

    return (int) $attachment_id;
}

function milapro_galleries_relative_path(string $base, string $path): string
{
    $base = rtrim(str_replace('\\', '/', $base), '/') . '/';
    $path = str_replace('\\', '/', $path);
    return str_starts_with($path, $base) ? substr($path, strlen($base)) : basename($path);
}

function milapro_galleries_seo_filename(string $category, string $product, string $slot, string $extension, int $slot_index): string
{
    $slot_label = preg_replace('/^(\d)-/', '$1-', $slot) ?: $slot;
    $slot_label = preg_replace('/^(\d)-/', '0$1-', $slot_label) ?: $slot_label;
    $prefix = milapro_galleries_slug($category . '-' . $product . '-galeria-' . $slot_label);
    $suffix = $slot_index === 1 ? '' : '-' . str_pad((string) $slot_index, 2, '0', STR_PAD_LEFT);
    return $prefix . $suffix . '.' . $extension;
}

function milapro_galleries_ensure_attachment_filename(int $attachment_id, string $filename, array &$report): void
{
    $current_file = get_attached_file($attachment_id);
    if (!$current_file || !is_file($current_file) || basename($current_file) === $filename) {
        return;
    }

    $directory = dirname($current_file);
    $target = $directory . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($target)) {
        $filename = wp_unique_filename($directory, $filename);
        $target = $directory . DIRECTORY_SEPARATOR . $filename;
    }

    if (!@rename($current_file, $target)) {
        $report['errors'][] = 'Could not rename attachment ' . $attachment_id . ' to ' . $filename;
        return;
    }

    update_attached_file($attachment_id, $target);
    $metadata = wp_get_attachment_metadata($attachment_id);
    if (is_array($metadata) && !empty($metadata['file'])) {
        $metadata['file'] = trailingslashit(dirname((string) $metadata['file'])) . $filename;
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['baseurl']) && !empty($upload_dir['basedir'])) {
        $relative = ltrim(str_replace('\\', '/', str_replace($upload_dir['basedir'], '', $target)), '/');
        wp_update_post([
            'ID' => $attachment_id,
            'guid' => trailingslashit($upload_dir['baseurl']) . $relative,
            'post_title' => pathinfo($filename, PATHINFO_FILENAME),
            'post_name' => sanitize_title(pathinfo($filename, PATHINFO_FILENAME)),
        ]);
    }

    $report['images_renamed']++;
}

function milapro_galleries_category_key(string $value): string
{
    $key = milapro_galleries_key($value);
    $aliases = [
        'descuentos' => 'outlet',
        'outlet' => 'outlet',
        'aluminio' => 'aluminio',
        'plantas' => 'plantas',
        'plastico' => 'plastico',
        'plasticos' => 'plastico',
        'ratan' => 'ratan',
    ];

    return $aliases[$key] ?? $key;
}

function milapro_galleries_key(string $value): string
{
    return str_replace(' ', '', milapro_galleries_normalize($value));
}

function milapro_galleries_normalize(string $value): string
{
    return str_replace('-', ' ', milapro_galleries_slug($value));
}

function milapro_galleries_slug(string $value): string
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

function milapro_galleries_tokens(string $value): array
{
    return array_values(array_filter(explode(' ', $value), function (string $token): bool {
        return $token !== '';
    }));
}

function milapro_galleries_tokens_contained(array $needles, array $haystack): bool
{
    if (empty($needles) || empty($haystack)) {
        return false;
    }

    return count(array_diff($needles, $haystack)) === 0;
}

function milapro_galleries_error(string $message, array $report = []): void
{
    if ($report) {
        WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    WP_CLI::error($message);
}

function milapro_galleries_success(array $report): void
{
    WP_CLI::success(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
