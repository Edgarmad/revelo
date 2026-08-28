<?php
/**
 * Plugin Name: Milapro Headless CMS
 * Description: Content models and rebuild webhook for the Milapro headless WordPress CMS.
 * Version: 0.1.1
 * Author: Milapro Home
 * Text Domain: milapro-headless-cms
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

define('MILAPRO_HEADLESS_VERSION', '0.1.1');
define('MILAPRO_HEADLESS_PLUGIN_FILE', __FILE__);

$milapro_plugin_dir = plugin_dir_path(__FILE__);
$milapro_plugin_dir_candidates = [
    $milapro_plugin_dir,
    $milapro_plugin_dir . 'milapro-headless-cms/',
    dirname($milapro_plugin_dir) . '/milapro-headless-cms/',
];

foreach ($milapro_plugin_dir_candidates as $milapro_plugin_dir_candidate) {
    if (is_readable($milapro_plugin_dir_candidate . 'includes/class-seed-validator.php')) {
        $milapro_plugin_dir = trailingslashit($milapro_plugin_dir_candidate);
        break;
    }
}

define('MILAPRO_HEADLESS_PLUGIN_DIR', $milapro_plugin_dir);

$milapro_required_files = [
    MILAPRO_HEADLESS_PLUGIN_DIR . 'includes/class-seed-validator.php',
    MILAPRO_HEADLESS_PLUGIN_DIR . 'includes/class-seed-media-importer.php',
    MILAPRO_HEADLESS_PLUGIN_DIR . 'includes/class-seed-importer.php',
    MILAPRO_HEADLESS_PLUGIN_DIR . 'includes/class-seed-import-admin.php',
];

foreach ($milapro_required_files as $milapro_required_file) {
    if (is_readable($milapro_required_file)) {
        require_once $milapro_required_file;
    }
}

add_action('init', 'milapro_register_content_models');
add_action('acf/init', 'milapro_register_acf_fields');
add_action('rest_api_init', 'milapro_register_rest_fields');
add_action('rest_api_init', 'milapro_register_rest_routes');
add_action('admin_menu', 'milapro_register_admin_pages');
add_action('add_meta_boxes', 'milapro_register_meta_boxes');
add_action('admin_enqueue_scripts', 'milapro_enqueue_admin_assets');
add_action('admin_head-edit.php', 'milapro_render_manual_deploy_button_script');
add_action('admin_post_milapro_manual_deploy', 'milapro_handle_manual_deploy');
add_action('admin_post_milapro_save_home_banners', 'milapro_handle_save_home_banners');
add_action('admin_notices', 'milapro_manual_deploy_notice');
add_action('save_post_products', 'milapro_save_product_meta');
add_action('save_post_reels', 'milapro_save_reel_meta');
add_action('save_post_blogs', 'milapro_save_blog_meta');
add_action('product_category_add_form_fields', 'milapro_category_add_fields');
add_action('product_category_edit_form_fields', 'milapro_category_edit_fields');
add_action('created_product_category', 'milapro_save_category_fields');
add_action('edited_product_category', 'milapro_save_category_fields');
if (class_exists('Milapro_Seed_Import_Admin')) {
    add_action('plugins_loaded', ['Milapro_Seed_Import_Admin', 'init']);
} else {
    add_action('admin_notices', 'milapro_missing_importer_files_notice');
}

function milapro_missing_importer_files_notice(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $files = [
        'class-seed-validator.php',
        'class-seed-media-importer.php',
        'class-seed-importer.php',
        'class-seed-import-admin.php',
    ];

    echo '<div class="notice notice-error"><p><strong>Milapro Headless CMS:</strong> faltan archivos internos del importador.</p>';
    echo '<p>Ruta detectada: <code>' . esc_html(MILAPRO_HEADLESS_PLUGIN_DIR) . '</code></p>';
    echo '<ul>';
    foreach ($files as $file) {
        $path = MILAPRO_HEADLESS_PLUGIN_DIR . 'includes/' . $file;
        echo '<li><code>includes/' . esc_html($file) . '</code>: ' . (is_readable($path) ? 'OK' : 'NO ENCONTRADO') . '</li>';
    }
    echo '</ul></div>';
}

function milapro_register_content_models(): void
{
    register_taxonomy('product_category', ['products'], [
        'labels' => [
            'name' => 'Product Categories',
            'singular_name' => 'Product Category',
            'menu_name' => 'Product Categories',
        ],
        'public' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rest_base' => 'product_category',
        'rewrite' => ['slug' => 'product-category'],
    ]);

    register_post_type('products', [
        'labels' => [
            'name' => 'Products',
            'singular_name' => 'Product',
            'add_new_item' => 'Add New Product',
            'edit_item' => 'Edit Product',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-products',
        'show_in_rest' => true,
        'rest_base' => 'products',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'taxonomies' => ['product_category'],
        'rewrite' => ['slug' => 'products'],
    ]);

    register_post_type('reels', [
        'labels' => [
            'name' => 'Reels',
            'singular_name' => 'Reel',
            'add_new_item' => 'Add New Reel',
            'edit_item' => 'Edit Reel',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'show_in_rest' => true,
        'rest_base' => 'reels',
        'supports' => ['title', 'thumbnail', 'revisions'],
        'rewrite' => ['slug' => 'reels'],
    ]);

    register_post_type('blogs', [
        'labels' => [
            'name' => 'Blogs',
            'singular_name' => 'Blog',
            'add_new_item' => 'Add New Blog',
            'edit_item' => 'Edit Blog',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-welcome-write-blog',
        'show_in_rest' => true,
        'rest_base' => 'blogs',
        'supports' => ['title', 'excerpt', 'thumbnail', 'revisions'],
        'rewrite' => ['slug' => 'blogs'],
    ]);
}

function milapro_register_acf_fields(): void
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_milapro_product_fields',
        'title' => 'Product Details',
        'fields' => [
            ['key' => 'field_product_price', 'label' => 'Price', 'name' => 'price', 'type' => 'number', 'required' => 1, 'min' => 0, 'step' => '0.01'],
            ['key' => 'field_product_compare_at_price', 'label' => 'Compare At Price', 'name' => 'compare_at_price', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['key' => 'field_product_sku', 'label' => 'SKU', 'name' => 'sku', 'type' => 'text'],
            ['key' => 'field_product_available', 'label' => 'Available', 'name' => 'available', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1],
            ['key' => 'field_product_featured', 'label' => 'Featured', 'name' => 'featured', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1],
            ['key' => 'field_product_display_order', 'label' => 'Display Order', 'name' => 'display_order', 'type' => 'number', 'default_value' => 999, 'min' => 0],
            ['key' => 'field_product_collection', 'label' => 'Collection', 'name' => 'collection', 'type' => 'text'],
            ['key' => 'field_product_brand', 'label' => 'Brand', 'name' => 'brand', 'type' => 'text', 'default_value' => 'Milapro Home'],
            ['key' => 'field_product_dimensions', 'label' => 'Dimensions', 'name' => 'dimensions', 'type' => 'text'],
            ['key' => 'field_product_keywords', 'label' => 'Search Keywords', 'name' => 'keywords', 'type' => 'textarea', 'instructions' => 'Internal search terms separated by commas or line breaks. Example: sala, sofa, couch, living room.'],
            [
                'key' => 'field_product_gallery_images',
                'label' => 'Gallery Images',
                'name' => 'gallery_images',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Add Gallery Image',
                'instructions' => 'Add one or more product detail images. The first image is usually the featured image.',
                'sub_fields' => [
                    ['key' => 'field_product_gallery_image', 'label' => 'Image', 'name' => 'image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium'],
                ],
            ],
            [
                'key' => 'field_product_colors',
                'label' => 'Colors',
                'name' => 'colors',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Color',
                'sub_fields' => [
                    ['key' => 'field_product_color_id', 'label' => 'ID', 'name' => 'id', 'type' => 'text'],
                    ['key' => 'field_product_color_name', 'label' => 'Name', 'name' => 'name', 'type' => 'text'],
                    ['key' => 'field_product_color_hex', 'label' => 'Hex', 'name' => 'hex', 'type' => 'color_picker'],
                    ['key' => 'field_product_color_available', 'label' => 'Available', 'name' => 'available', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1],
                    ['key' => 'field_product_color_image', 'label' => 'Image', 'name' => 'image', 'type' => 'image', 'return_format' => 'array'],
                ],
            ],
            [
                'key' => 'field_product_variants',
                'label' => 'Variants',
                'name' => 'variants',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Add Variant',
                'sub_fields' => [
                    ['key' => 'field_product_variant_name', 'label' => 'Name', 'name' => 'name', 'type' => 'text'],
                    ['key' => 'field_product_variant_price', 'label' => 'Price', 'name' => 'price', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
                    ['key' => 'field_product_variant_compare_at_price', 'label' => 'Compare At Price', 'name' => 'compare_at_price', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
                    ['key' => 'field_product_variant_sku', 'label' => 'SKU', 'name' => 'sku', 'type' => 'text'],
                    ['key' => 'field_product_variant_available', 'label' => 'Available', 'name' => 'available', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1],
                    ['key' => 'field_product_variant_image', 'label' => 'Image', 'name' => 'image', 'type' => 'image', 'return_format' => 'array'],
                ],
            ],
            [
                'key' => 'field_product_specifications',
                'label' => 'Specifications',
                'name' => 'specifications',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Specification',
                'sub_fields' => [
                    ['key' => 'field_product_spec_label', 'label' => 'Label', 'name' => 'label', 'type' => 'text'],
                    ['key' => 'field_product_spec_value', 'label' => 'Value', 'name' => 'value', 'type' => 'text'],
                ],
            ],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'products']]],
        'show_in_rest' => 1,
    ]);

    acf_add_local_field_group([
        'key' => 'group_milapro_product_category_fields',
        'title' => 'Category Details',
        'fields' => [
            ['key' => 'field_category_image', 'label' => 'Category Image', 'name' => 'category_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium'],
            ['key' => 'field_category_eyebrow', 'label' => 'Eyebrow', 'name' => 'eyebrow', 'type' => 'text'],
            ['key' => 'field_category_display_order', 'label' => 'Display Order', 'name' => 'display_order', 'type' => 'number', 'default_value' => 999, 'min' => 0],
            ['key' => 'field_category_featured', 'label' => 'Featured', 'name' => 'featured', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1],
        ],
        'location' => [[['param' => 'taxonomy', 'operator' => '==', 'value' => 'product_category']]],
        'show_in_rest' => 1,
    ]);

    acf_add_local_field_group([
        'key' => 'group_milapro_reel_fields',
        'title' => 'Reel Details',
        'fields' => [
            ['key' => 'field_reel_video_url', 'label' => 'Video URL', 'name' => 'video_url', 'type' => 'url', 'required' => 1],
            ['key' => 'field_reel_cover_image', 'label' => 'Cover Image', 'name' => 'cover_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium'],
            ['key' => 'field_reel_platform', 'label' => 'Platform', 'name' => 'platform', 'type' => 'select', 'choices' => ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'other' => 'Other'], 'default_value' => 'instagram'],
            ['key' => 'field_reel_display_order', 'label' => 'Display Order', 'name' => 'display_order', 'type' => 'number', 'default_value' => 999, 'min' => 0],
            ['key' => 'field_reel_is_visible', 'label' => 'Visible', 'name' => 'is_visible', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'reels']]],
        'show_in_rest' => 1,
    ]);
}

function milapro_register_rest_fields(): void
{
    register_rest_field('products', 'product_details', [
        'get_callback' => function (array $post): array {
            return milapro_product_details((int) $post['id']);
        },
        'schema' => ['type' => 'object'],
    ]);

    register_rest_field('product_category', 'category_image_url', [
        'get_callback' => function (array $term): string {
            $image = get_term_meta((int) $term['id'], '_milapro_category_image', true);
            if (!$image && function_exists('get_field')) {
                $image = get_field('category_image', 'product_category_' . $term['id']);
            }
            return milapro_media_url($image);
        },
        'schema' => ['type' => 'string'],
    ]);

    register_rest_field('product_category', 'category_details', [
        'get_callback' => function (array $term): array {
            $term_id = (int) $term['id'];
            return [
                'eyebrow' => (string) get_term_meta($term_id, '_milapro_eyebrow', true),
                'display_order' => (int) (get_term_meta($term_id, '_milapro_display_order', true) ?: 999),
                'featured' => milapro_meta_bool(get_term_meta($term_id, '_milapro_featured', true), true),
                'category_image' => (int) get_term_meta($term_id, '_milapro_category_image', true),
            ];
        },
        'schema' => ['type' => 'object'],
    ]);

    register_rest_field('reels', 'reel_details', [
        'get_callback' => function (array $post): array {
            $post_id = (int) $post['id'];
            return [
                'video_url' => (string) get_post_meta($post_id, '_milapro_video_url', true),
                'cover_image' => (int) get_post_meta($post_id, '_milapro_cover_image', true),
                'platform' => (string) (get_post_meta($post_id, '_milapro_platform', true) ?: 'instagram'),
                'display_order' => (int) (get_post_meta($post_id, '_milapro_display_order', true) ?: 999),
                'is_visible' => milapro_meta_bool(get_post_meta($post_id, '_milapro_is_visible', true), true),
            ];
        },
        'schema' => ['type' => 'object'],
    ]);

    register_rest_field('reels', 'cover_image_url', [
        'get_callback' => function (array $post): string {
            $image = get_post_meta((int) $post['id'], '_milapro_cover_image', true);
            if (!$image && function_exists('get_field')) {
                $image = get_field('cover_image', $post['id']);
            }
            return milapro_media_url($image) ?: get_the_post_thumbnail_url($post['id'], 'large') ?: '';
        },
        'schema' => ['type' => 'string'],
    ]);

    register_rest_field('products', 'gallery_urls', [
        'get_callback' => function (array $post): array {
            return array_values(array_filter(array_map(function ($item): string {
                if (is_array($item) && array_key_exists('image', $item)) {
                    return milapro_media_url($item['image']);
                }

                return milapro_media_url($item);
            }, milapro_product_gallery_images((int) $post['id']))));
        },
        'schema' => ['type' => 'array', 'items' => ['type' => 'string']],
    ]);

    register_rest_field('products', 'main_image_url', [
        'get_callback' => function (array $post): string {
            return milapro_product_main_image_url((int) $post['id']);
        },
        'schema' => ['type' => 'string'],
    ]);

    register_rest_field('blogs', 'blog_details', [
        'get_callback' => function (array $post): array {
            return milapro_blog_details((int) $post['id']);
        },
        'schema' => ['type' => 'object'],
    ]);

    register_rest_field('blogs', 'blog_image_url', [
        'get_callback' => function (array $post): string {
            return milapro_blog_image_url((int) $post['id']);
        },
        'schema' => ['type' => 'string'],
    ]);
}

function milapro_register_rest_routes(): void
{
    register_rest_route('milapro/v1', '/home-banners', [
        'methods' => 'GET',
        'callback' => function (): array {
            return milapro_home_banners_for_rest();
        },
        'permission_callback' => '__return_true',
    ]);
}

function milapro_register_admin_pages(): void
{
    add_submenu_page(
        'edit.php?post_type=products',
        'Home Banners',
        'Home Banners',
        'edit_posts',
        'milapro-home-banners',
        'milapro_render_home_banners_page'
    );
}

function milapro_home_banner_slots(): array
{
    return [
        'hero_banner' => [
            'label' => 'Hero banner',
            'eyebrow' => 'Oferta de verano',
            'title' => 'Hasta 50% de descuento',
            'text' => '',
        ],
        'popup_banner' => [
            'label' => 'Popup banner',
            'eyebrow' => 'Oferta por tiempo limitado',
            'title' => 'Obtén hasta 50% OFF',
            'text' => 'Déjanos tu correo y recibe promociones, novedades y asesoría para renovar tus espacios exteriores.',
        ],
        'carousel_banner' => [
            'label' => 'Carousel banner',
            'eyebrow' => 'Oferta de verano',
            'title' => 'Hasta 50% de descuento',
            'text' => '',
        ],
    ];
}

function milapro_default_home_banner_images(): array
{
    return [
        'hero_banner' => MILAPRO_HEADLESS_PLUGIN_DIR . 'assets/default-hero-bg.png',
        'popup_banner' => MILAPRO_HEADLESS_PLUGIN_DIR . 'assets/default-popup-banner.jpeg',
        'carousel_banner' => MILAPRO_HEADLESS_PLUGIN_DIR . 'assets/default-hero-bg.png',
    ];
}

function milapro_home_banners(): array
{
    $saved = get_option('milapro_home_banners', []);
    $saved = is_array($saved) ? $saved : [];
    $saved = milapro_ensure_default_home_banner_images($saved);
    $banners = [];

    foreach (milapro_home_banner_slots() as $key => $defaults) {
        $banner = is_array($saved[$key] ?? null) ? $saved[$key] : [];
        $banners[$key] = [
            'eyebrow' => (string) ($banner['eyebrow'] ?? $defaults['eyebrow']),
            'title' => (string) ($banner['title'] ?? $defaults['title']),
            'text' => (string) ($banner['text'] ?? $defaults['text']),
            'image' => (int) ($banner['image'] ?? 0),
        ];
    }

    return $banners;
}

function milapro_ensure_default_home_banner_images(array $saved): array
{
    $imported = get_option('milapro_home_banner_defaults_imported', []);
    $imported = is_array($imported) ? $imported : [];
    $changed = false;

    foreach (milapro_default_home_banner_images() as $key => $path) {
        if (!empty($saved[$key]['image']) || !empty($imported[$key])) {
            continue;
        }

        $attachment_id = milapro_import_home_banner_default_image($key, $path);
        if (!$attachment_id) {
            continue;
        }

        if (!isset($saved[$key]) || !is_array($saved[$key])) {
            $saved[$key] = [];
        }

        $saved[$key]['image'] = $attachment_id;
        $imported[$key] = true;
        $changed = true;
    }

    if ($changed) {
        update_option('milapro_home_banners', $saved, false);
        update_option('milapro_home_banner_defaults_imported', $imported, false);
    }

    return $saved;
}

function milapro_import_home_banner_default_image(string $key, string $path): int
{
    if (!is_readable($path)) {
        return 0;
    }

    $source = 'home-banner-default:' . $key;
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_milapro_home_banner_source',
        'meta_value' => $source,
    ]);

    if (!empty($existing[0])) {
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = wp_tempnam(basename($path));
    if (!$tmp || !copy($path, $tmp)) {
        return 0;
    }

    $file = [
        'name' => basename($path),
        'type' => wp_check_filetype(basename($path))['type'] ?? '',
        'tmp_name' => $tmp,
        'error' => 0,
        'size' => filesize($tmp),
    ];

    $attachment_id = media_handle_sideload($file, 0, 'Milapro ' . str_replace('_', ' ', $key));
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }

    update_post_meta((int) $attachment_id, '_milapro_home_banner_source', $source);
    return (int) $attachment_id;
}

function milapro_home_banners_for_rest(): array
{
    return array_map(function (array $banner): array {
        return [
            'eyebrow' => $banner['eyebrow'],
            'title' => $banner['title'],
            'text' => $banner['text'],
            'image' => $banner['image'],
            'image_url' => milapro_media_url($banner['image']),
        ];
    }, milapro_home_banners());
}

function milapro_product_details(int $post_id): array
{
    return [
        'price' => (float) get_post_meta($post_id, '_milapro_price', true),
        'compare_at_price' => get_post_meta($post_id, '_milapro_compare_at_price', true),
        'sku' => (string) get_post_meta($post_id, '_milapro_sku', true),
        'available' => milapro_meta_bool(get_post_meta($post_id, '_milapro_available', true), true),
        'featured' => milapro_meta_bool(get_post_meta($post_id, '_milapro_featured', true), false),
        'display_order' => (int) (get_post_meta($post_id, '_milapro_display_order', true) ?: 999),
        'collection' => (string) get_post_meta($post_id, '_milapro_collection', true),
        'brand' => (string) (get_post_meta($post_id, '_milapro_brand', true) ?: 'Milapro Home'),
        'dimensions' => (string) get_post_meta($post_id, '_milapro_dimensions', true),
        'keywords' => (string) (get_post_meta($post_id, '_milapro_keywords', true) ?: (function_exists('get_field') ? get_field('keywords', $post_id) : '')),
        'colors' => array_map(function ($color): array {
            if (is_array($color) && isset($color['image'])) {
                $color['image'] = milapro_media_url($color['image']);
            }
            return is_array($color) ? $color : [];
        }, milapro_meta_array($post_id, '_milapro_colors')),
        'variants' => milapro_meta_array($post_id, '_milapro_variants'),
        'specifications' => milapro_meta_array($post_id, '_milapro_specifications'),
        'gallery_images' => milapro_product_gallery_images($post_id),
        'main_image' => (int) get_post_meta($post_id, '_milapro_main_image', true),
        'main_image_url' => milapro_product_main_image_url($post_id),
    ];
}

function milapro_product_gallery_images(int $post_id): array
{
    $gallery = get_post_meta($post_id, '_milapro_gallery_images', true);
    if (is_array($gallery) && !empty($gallery)) {
        return milapro_normalize_gallery_images($gallery);
    }

    if (function_exists('get_field')) {
        $acf_gallery = get_field('gallery_images', $post_id);
        if (is_array($acf_gallery) && !empty($acf_gallery)) {
            return milapro_normalize_gallery_images($acf_gallery);
        }
    }

    return [];
}

function milapro_normalize_gallery_images(array $gallery): array
{
    return array_values(array_filter(array_map(function ($item): array {
        $image = is_array($item) && array_key_exists('image', $item) ? $item['image'] : $item;
        $image_id = milapro_attachment_id_from_value($image);
        return ['image' => $image_id];
    }, $gallery), function ($item): bool {
        return $item['image'] > 0;
    }));
}

function milapro_attachment_id_from_value($image): int
{
    if (is_numeric($image)) {
        return (int) $image;
    }

    if (is_array($image)) {
        return (int) ($image['ID'] ?? $image['id'] ?? 0);
    }

    return 0;
}

function milapro_product_main_image_url(int $post_id): string
{
    $image = (int) get_post_meta($post_id, '_milapro_main_image', true);
    return milapro_media_url($image) ?: get_the_post_thumbnail_url($post_id, 'large') ?: '';
}

function milapro_blog_details(int $post_id): array
{
    return [
        'eyebrow' => (string) (get_post_meta($post_id, '_milapro_blog_eyebrow', true) ?: 'Milapro Home'),
        'intro_text' => (string) get_post_meta($post_id, '_milapro_blog_intro_text', true),
        'body_text' => (string) get_post_meta($post_id, '_milapro_blog_body_text', true),
        'image' => (int) get_post_meta($post_id, '_milapro_blog_image', true),
        'display_order' => (int) (get_post_meta($post_id, '_milapro_blog_display_order', true) ?: 999),
        'is_visible' => milapro_meta_bool(get_post_meta($post_id, '_milapro_blog_is_visible', true), true),
    ];
}

function milapro_blog_image_url(int $post_id): string
{
    $image = (int) get_post_meta($post_id, '_milapro_blog_image', true);
    return milapro_media_url($image) ?: get_the_post_thumbnail_url($post_id, 'large') ?: '';
}

function milapro_seed_product_for_post(int $post_id): array
{
    $post = get_post($post_id);
    if (!$post) {
        return [];
    }

    $slug = (string) (get_post_meta($post_id, '_milapro_source_slug', true) ?: $post->post_name);
    if (!$slug) {
        return [];
    }

    $products = milapro_seed_products_by_slug();
    return $products[$slug] ?? [];
}

function milapro_seed_products_by_slug(): array
{
    static $products = null;
    if ($products !== null) {
        return $products;
    }

    $products = [];
    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['basedir'])) {
        $path = trailingslashit($upload_dir['basedir']) . 'milapro-seed/seed.json';
        if (is_readable($path)) {
            $seed = json_decode((string) file_get_contents($path), true);
            foreach (($seed['products'] ?? []) as $product) {
                if (is_array($product) && !empty($product['slug'])) {
                    $products[sanitize_title($product['slug'])] = $product;
                }
            }
        }
    }

    return $products;
}

function milapro_meta_array(int $post_id, string $key): array
{
    $value = get_post_meta($post_id, $key, true);
    return is_array($value) ? $value : [];
}

function milapro_meta_bool($value, bool $fallback = false): bool
{
    if ($value === '' || $value === null) return $fallback;
    return !in_array($value, [false, 0, '0', 'false'], true);
}

function milapro_register_meta_boxes(): void
{
    add_meta_box('milapro_product_details', 'Product Details', 'milapro_render_product_meta_box', 'products', 'normal', 'high');
    add_meta_box('milapro_reel_details', 'Reel Details', 'milapro_render_reel_meta_box', 'reels', 'normal', 'high');
    add_meta_box('milapro_blog_details', 'Blog Details', 'milapro_render_blog_meta_box', 'blogs', 'normal', 'high');
}

function milapro_enqueue_admin_assets(string $hook): void
{
    wp_enqueue_media();
    wp_add_inline_script('jquery-core', milapro_admin_script());
    wp_add_inline_style('wp-admin', milapro_admin_styles());
}

function milapro_render_manual_deploy_button_script(): void
{
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['products', 'reels', 'blogs'], true) || !current_user_can('edit_posts')) {
        return;
    }

    $url = add_query_arg([
        'action' => 'milapro_manual_deploy',
        'post_type' => $screen->post_type,
        '_wpnonce' => wp_create_nonce('milapro_manual_deploy_' . $screen->post_type),
    ], admin_url('admin-post.php'));
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var addNewButton = document.querySelector('.wrap .page-title-action');
      if (!addNewButton || document.querySelector('[data-milapro-deploy-button]')) return;
      var deployButton = document.createElement('a');
      deployButton.href = <?php echo wp_json_encode($url); ?>;
      deployButton.className = 'page-title-action';
      deployButton.dataset.milaproDeployButton = '1';
      deployButton.textContent = 'Deploy';
      addNewButton.insertAdjacentElement('afterend', deployButton);
    });
    </script>
    <?php
}

function milapro_handle_manual_deploy(): void
{
    $post_type = sanitize_key($_GET['post_type'] ?? 'products');
    if (!in_array($post_type, ['products', 'reels', 'blogs', 'home_banners'], true)) {
        $post_type = 'products';
    }

    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized.', 403);
    }

    check_admin_referer('milapro_manual_deploy_' . $post_type);

    $result = milapro_send_rebuild_webhook($post_type, 'manual-deploy', 'manual');
    $redirect_url = $post_type === 'home_banners'
        ? admin_url('edit.php?post_type=products&page=milapro-home-banners')
        : add_query_arg('post_type', $post_type, admin_url('edit.php'));

    if (is_wp_error($result)) {
        $redirect_url = add_query_arg([
            'milapro_deploy' => 'error',
            'milapro_deploy_message' => $result->get_error_message(),
        ], $redirect_url);
    } else {
        $redirect_url = add_query_arg('milapro_deploy', 'success', $redirect_url);
    }

    wp_safe_redirect($redirect_url);
    exit;
}

function milapro_render_home_banners_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized.', 403);
    }

    $banners = milapro_home_banners();
    $deploy_url = add_query_arg([
        'action' => 'milapro_manual_deploy',
        'post_type' => 'home_banners',
        '_wpnonce' => wp_create_nonce('milapro_manual_deploy_home_banners'),
    ], admin_url('admin-post.php'));
    ?>
    <div class="wrap">
        <h1>Home Banners</h1>
        <p>Actualiza solo los textos e imagenes de los banners fijos del home. Guardar no inicia deploy automatico.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="milapro-fields">
            <input type="hidden" name="action" value="milapro_save_home_banners">
            <?php wp_nonce_field('milapro_save_home_banners', 'milapro_home_banners_nonce'); ?>
            <?php foreach (milapro_home_banner_slots() as $key => $slot): ?>
                <?php $banner = $banners[$key]; ?>
                <div class="milapro-banner-card">
                    <h2><?php echo esc_html($slot['label']); ?></h2>
                    <div class="milapro-grid">
                        <?php milapro_input('Label text', 'milapro_home_banners[' . $key . '][eyebrow]', $banner['eyebrow']); ?>
                        <?php milapro_input('Title text', 'milapro_home_banners[' . $key . '][title]', $banner['title']); ?>
                    </div>
                    <?php milapro_textarea('Secondary text', 'milapro_home_banners[' . $key . '][text]', $banner['text'], 'Solo se usa cuando el banner actual ya muestra texto secundario.'); ?>
                    <label class="milapro-field">Image</label>
                    <div class="milapro-media-row milapro-media-preview-row">
                        <input type="hidden" name="milapro_home_banners[<?php echo esc_attr($key); ?>][image]" value="<?php echo esc_attr((string) $banner['image']); ?>" data-media-input>
                        <?php echo milapro_media_preview_markup($banner['image']); ?>
                        <button type="button" class="button" data-media-select>Select Image</button>
                        <button type="button" class="button" data-media-remove>Remove Image</button>
                        <span data-media-label><?php echo $banner['image'] ? esc_html(basename(get_attached_file($banner['image']))) : 'No image selected'; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <p class="submit">
                <button type="submit" class="button button-primary">Save banners</button>
                <a class="button" href="<?php echo esc_url($deploy_url); ?>">Deploy</a>
            </p>
        </form>
    </div>
    <?php
}

function milapro_handle_save_home_banners(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized.', 403);
    }

    if (!isset($_POST['milapro_home_banners_nonce']) || !wp_verify_nonce($_POST['milapro_home_banners_nonce'], 'milapro_save_home_banners')) {
        wp_die('Invalid nonce.', 403);
    }

    $input = $_POST['milapro_home_banners'] ?? [];
    $input = is_array($input) ? wp_unslash($input) : [];
    $banners = [];

    foreach (milapro_home_banner_slots() as $key => $slot) {
        $banner = is_array($input[$key] ?? null) ? $input[$key] : [];
        $banners[$key] = [
            'eyebrow' => sanitize_text_field($banner['eyebrow'] ?? $slot['eyebrow']),
            'title' => sanitize_text_field($banner['title'] ?? $slot['title']),
            'text' => sanitize_textarea_field($banner['text'] ?? $slot['text']),
            'image' => (int) ($banner['image'] ?? 0),
        ];
    }

    update_option('milapro_home_banners', $banners, false);
    wp_safe_redirect(add_query_arg('milapro_banners_saved', '1', admin_url('edit.php?post_type=products&page=milapro-home-banners')));
    exit;
}

function milapro_manual_deploy_notice(): void
{
    if (!empty($_GET['milapro_banners_saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Milapro:</strong> banners saved.</p></div>';
    }

    if (empty($_GET['milapro_deploy'])) {
        return;
    }

    $status = sanitize_key($_GET['milapro_deploy']);
    if ($status === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Milapro:</strong> deploy started successfully.</p></div>';
        return;
    }

    if ($status === 'error') {
        $message = sanitize_text_field(wp_unslash($_GET['milapro_deploy_message'] ?? 'Could not start deploy.'));
        echo '<div class="notice notice-error is-dismissible"><p><strong>Milapro:</strong> ' . esc_html($message) . '</p></div>';
    }
}

function milapro_render_product_meta_box(WP_Post $post): void
{
    wp_nonce_field('milapro_save_product_meta', 'milapro_product_nonce');
    $details = milapro_product_details($post->ID);
    ?>
    <div class="milapro-fields">
        <div class="milapro-grid">
            <?php milapro_input('Price', 'milapro_price', $details['price'], 'number', '0.01'); ?>
            <?php milapro_input('Compare At Price', 'milapro_compare_at_price', $details['compare_at_price'], 'number', '0.01'); ?>
            <?php milapro_input('SKU', 'milapro_sku', $details['sku']); ?>
            <?php milapro_input('Collection', 'milapro_collection', $details['collection']); ?>
            <?php milapro_input('Brand', 'milapro_brand', $details['brand']); ?>
            <?php milapro_input('Dimensions', 'milapro_dimensions', $details['dimensions']); ?>
            <?php milapro_input('Display Order', 'milapro_display_order', $details['display_order'], 'number', '1'); ?>
            <?php milapro_checkbox('Available', 'milapro_available', $details['available']); ?>
            <?php milapro_checkbox('Featured', 'milapro_featured', $details['featured']); ?>
        </div>
        <?php milapro_textarea('Search Keywords', 'milapro_keywords', $details['keywords'], 'Internal search terms separated by commas or line breaks. Example: sala, sofa, couch, living room.'); ?>
        <?php milapro_main_image_field((int) $details['main_image'], (string) $details['main_image_url']); ?>
        <?php milapro_gallery_field($details['gallery_images']); ?>
        <?php milapro_colors_field($details['colors']); ?>
        <?php milapro_variants_field($details['variants']); ?>
        <?php milapro_specs_field($details['specifications']); ?>
    </div>
    <?php
}

function milapro_render_reel_meta_box(WP_Post $post): void
{
    wp_nonce_field('milapro_save_reel_meta', 'milapro_reel_nonce');
    $cover = (int) get_post_meta($post->ID, '_milapro_cover_image', true);
    ?>
    <div class="milapro-fields">
        <?php milapro_input('Video URL', 'milapro_video_url', get_post_meta($post->ID, '_milapro_video_url', true), 'url'); ?>
        <?php milapro_input('Platform', 'milapro_platform', get_post_meta($post->ID, '_milapro_platform', true) ?: 'instagram'); ?>
        <?php milapro_input('Display Order', 'milapro_display_order', get_post_meta($post->ID, '_milapro_display_order', true) ?: 999, 'number', '1'); ?>
        <?php milapro_checkbox('Visible', 'milapro_is_visible', milapro_meta_bool(get_post_meta($post->ID, '_milapro_is_visible', true), true)); ?>
        <label class="milapro-field">Cover Image</label>
        <div class="milapro-media-row milapro-media-preview-row">
            <input type="hidden" name="milapro_cover_image" value="<?php echo esc_attr((string) $cover); ?>" data-media-input>
            <?php echo milapro_media_preview_markup($cover); ?>
            <button type="button" class="button" data-media-select>Select Image</button>
            <button type="button" class="button" data-media-remove>Remove Image</button>
            <span data-media-label><?php echo $cover ? esc_html(basename(get_attached_file($cover))) : 'No image selected'; ?></span>
        </div>
    </div>
    <?php
}

function milapro_render_blog_meta_box(WP_Post $post): void
{
    wp_nonce_field('milapro_save_blog_meta', 'milapro_blog_nonce');
    $details = milapro_blog_details($post->ID);
    ?>
    <div class="milapro-fields">
        <div class="milapro-grid">
            <?php milapro_input('Eyebrow / Category label', 'milapro_blog_eyebrow', $details['eyebrow']); ?>
            <?php milapro_input('Display Order', 'milapro_blog_display_order', $details['display_order'], 'number', '1'); ?>
            <?php milapro_checkbox('Visible', 'milapro_blog_is_visible', $details['is_visible']); ?>
        </div>
        <?php milapro_textarea('Intro text', 'milapro_blog_intro_text', $details['intro_text'], 'Shown on blog cards and below the H1.'); ?>
        <?php milapro_textarea('Body text', 'milapro_blog_body_text', $details['body_text'], 'Final content paragraph in the current blog detail layout.'); ?>
        <label class="milapro-field">Blog Image</label>
        <div class="milapro-media-row milapro-media-preview-row">
            <input type="hidden" name="milapro_blog_image" value="<?php echo esc_attr((string) $details['image']); ?>" data-media-input>
            <?php echo milapro_media_preview_markup($details['image']); ?>
            <button type="button" class="button" data-media-select>Select Image</button>
            <button type="button" class="button" data-media-remove>Remove Image</button>
            <span data-media-label><?php echo $details['image'] ? esc_html(basename(get_attached_file($details['image']))) : 'No image selected'; ?></span>
        </div>
    </div>
    <?php
}

function milapro_input(string $label, string $name, $value, string $type = 'text', string $step = ''): void
{
    printf('<label class="milapro-field"><span>%s</span><input type="%s" name="%s" value="%s" %s></label>', esc_html($label), esc_attr($type), esc_attr($name), esc_attr((string) $value), $step ? 'step="' . esc_attr($step) . '"' : '');
}

function milapro_textarea(string $label, string $name, $value, string $description = ''): void
{
    printf('<label class="milapro-field"><span>%s</span><textarea name="%s" rows="3">%s</textarea>%s</label>', esc_html($label), esc_attr($name), esc_textarea((string) $value), $description ? '<small>' . esc_html($description) . '</small>' : '');
}

function milapro_checkbox(string $label, string $name, bool $checked): void
{
    printf('<label class="milapro-field milapro-checkbox"><input type="checkbox" name="%s" value="1" %s> <span>%s</span></label>', esc_attr($name), checked($checked, true, false), esc_html($label));
}

function milapro_media_preview_markup($image = 0): string
{
    $url = is_numeric($image) ? ((int) $image ? wp_get_attachment_image_url((int) $image, 'thumbnail') : '') : milapro_admin_preview_url(milapro_media_url($image));
    $image_style = $url ? '' : ' style="display:none"';
    $empty_style = $url ? ' style="display:none"' : '';

    return '<span class="milapro-media-preview"><img src="' . esc_url($url ?: '') . '" alt="" data-media-preview' . $image_style . '><span data-media-empty' . $empty_style . '>No image selected</span></span>';
}

function milapro_admin_preview_url(string $url): string
{
    if ($url === '' || !str_starts_with($url, '/')) {
        return $url;
    }

    $public_site_url = defined('MILAPRO_PUBLIC_SITE_URL') ? MILAPRO_PUBLIC_SITE_URL : 'https://www.milaprohome.com';
    return untrailingslashit($public_site_url) . $url;
}

function milapro_main_image_field(int $image_id = 0, string $preview_url = ''): void
{
    echo '<h3>Main Image</h3><div class="milapro-media-row milapro-media-preview-row"><input type="hidden" name="milapro_main_image" value="' . esc_attr((string) $image_id) . '" data-media-input>' . milapro_media_preview_markup($image_id ?: $preview_url) . '<button type="button" class="button" data-media-select>Select Main Image</button> <button type="button" class="button" data-media-remove>Remove Image</button> <span data-media-label>' . esc_html($image_id ? basename(get_attached_file($image_id)) : ($preview_url ? basename($preview_url) : 'No image selected')) . '</span></div>';
}

function milapro_gallery_field(array $items): void
{
    echo '<h3>Gallery Images</h3><div class="milapro-repeat" data-repeat="gallery">';
    foreach ($items as $item) milapro_gallery_row($item['image'] ?? $item);
    echo '</div><button type="button" class="button" data-add-row="gallery">Add Gallery Image</button>';
}

function milapro_gallery_row($image = 0): void
{
    $label = is_numeric($image) && (int) $image ? basename(get_attached_file((int) $image)) : ($image ? basename((string) $image) : 'No image selected');
    echo '<div class="milapro-row milapro-gallery-row" data-row="gallery"><input type="hidden" name="milapro_gallery_images[]" value="' . esc_attr((string) $image) . '" data-media-input>' . milapro_media_preview_markup($image) . '<button type="button" class="button" data-media-select>Select Image</button> <span data-media-label>' . esc_html($label) . '</span> <button type="button" class="button" data-move-row="up">Move up</button> <button type="button" class="button" data-move-row="down">Move down</button> <button type="button" class="button-link-delete" data-remove-row>Remove</button></div>';
}

function milapro_colors_field(array $items): void
{
    echo '<h3>Colors</h3><div class="milapro-repeat" data-repeat="colors">';
    foreach ($items as $item) milapro_color_row($item);
    echo '</div><button type="button" class="button" data-add-row="colors">Add Color</button>';
}

function milapro_color_row(array $item = []): void
{
    $available = milapro_meta_bool($item['available'] ?? true, true);
    echo '<div class="milapro-row milapro-row-grid" data-row="colors"><input name="milapro_colors_name[]" placeholder="Name" value="' . esc_attr($item['name'] ?? '') . '"><input name="milapro_colors_hex[]" type="color" value="' . esc_attr($item['hex'] ?? '#cccccc') . '"><select name="milapro_colors_available[]"><option value="1" ' . selected($available, true, false) . '>Available</option><option value="0" ' . selected($available, false, false) . '>Not available</option></select><button type="button" class="button-link-delete" data-remove-row>Remove</button></div>';
}

function milapro_variants_field(array $items): void
{
    echo '<h3>Variants</h3><div class="milapro-repeat" data-repeat="variants">';
    foreach ($items as $item) milapro_variant_row($item);
    echo '</div><button type="button" class="button" data-add-row="variants">Add Variant</button>';
}

function milapro_variant_row(array $item = []): void
{
    echo '<div class="milapro-row milapro-row-grid" data-row="variants"><input name="milapro_variants_name[]" placeholder="Name" value="' . esc_attr($item['name'] ?? '') . '"><input name="milapro_variants_price[]" type="number" step="0.01" placeholder="Price" value="' . esc_attr((string) ($item['price'] ?? '')) . '"><input name="milapro_variants_compare_at_price[]" type="number" step="0.01" placeholder="Compare at" value="' . esc_attr((string) ($item['compare_at_price'] ?? '')) . '"><input name="milapro_variants_sku[]" placeholder="SKU" value="' . esc_attr($item['sku'] ?? '') . '"><button type="button" class="button-link-delete" data-remove-row>Remove</button></div>';
}

function milapro_specs_field(array $items): void
{
    echo '<h3>Specifications</h3><div class="milapro-repeat" data-repeat="specs">';
    foreach ($items as $item) milapro_spec_row($item);
    echo '</div><button type="button" class="button" data-add-row="specs">Add Specification</button>';
}

function milapro_spec_row(array $item = []): void
{
    echo '<div class="milapro-row milapro-row-grid" data-row="specs"><input name="milapro_specs_label[]" placeholder="Label" value="' . esc_attr($item['label'] ?? '') . '"><input name="milapro_specs_value[]" placeholder="Value" value="' . esc_attr($item['value'] ?? '') . '"><button type="button" class="button-link-delete" data-remove-row>Remove</button></div>';
}

function milapro_save_product_meta(int $post_id): void
{
    if (!isset($_POST['milapro_product_nonce']) || !wp_verify_nonce($_POST['milapro_product_nonce'], 'milapro_save_product_meta') || defined('DOING_AUTOSAVE')) return;
    foreach (['price', 'compare_at_price', 'sku', 'collection', 'brand', 'dimensions', 'display_order'] as $key) {
        update_post_meta($post_id, '_milapro_' . $key, sanitize_text_field(wp_unslash($_POST['milapro_' . $key] ?? '')));
    }
    update_post_meta($post_id, '_milapro_keywords', sanitize_textarea_field(wp_unslash($_POST['milapro_keywords'] ?? '')));
    update_post_meta($post_id, '_milapro_available', isset($_POST['milapro_available']) ? '1' : '0');
    update_post_meta($post_id, '_milapro_featured', isset($_POST['milapro_featured']) ? '1' : '0');
    $main_image = (int) ($_POST['milapro_main_image'] ?? 0);
    update_post_meta($post_id, '_milapro_main_image', $main_image);
    if ($main_image) set_post_thumbnail($post_id, $main_image);
    $gallery_images = array_map(function ($image) {
        return ['image' => (int) wp_unslash($image)];
    }, $_POST['milapro_gallery_images'] ?? []);
    update_post_meta($post_id, '_milapro_gallery_images', array_values(array_filter($gallery_images, function ($item) {
        return $item['image'] > 0;
    })));
    update_post_meta($post_id, '_milapro_colors', milapro_collect_colors());
    update_post_meta($post_id, '_milapro_variants', milapro_collect_variants());
    update_post_meta($post_id, '_milapro_specifications', milapro_collect_specs());
}

function milapro_save_reel_meta(int $post_id): void
{
    if (!isset($_POST['milapro_reel_nonce']) || !wp_verify_nonce($_POST['milapro_reel_nonce'], 'milapro_save_reel_meta') || defined('DOING_AUTOSAVE')) return;
    update_post_meta($post_id, '_milapro_video_url', esc_url_raw(wp_unslash($_POST['milapro_video_url'] ?? '')));
    update_post_meta($post_id, '_milapro_platform', sanitize_text_field(wp_unslash($_POST['milapro_platform'] ?? 'instagram')));
    update_post_meta($post_id, '_milapro_display_order', (int) ($_POST['milapro_display_order'] ?? 999));
    update_post_meta($post_id, '_milapro_is_visible', isset($_POST['milapro_is_visible']) ? '1' : '0');
    update_post_meta($post_id, '_milapro_cover_image', (int) ($_POST['milapro_cover_image'] ?? 0));
}

function milapro_save_blog_meta(int $post_id): void
{
    if (!isset($_POST['milapro_blog_nonce']) || !wp_verify_nonce($_POST['milapro_blog_nonce'], 'milapro_save_blog_meta') || defined('DOING_AUTOSAVE')) return;
    update_post_meta($post_id, '_milapro_blog_eyebrow', sanitize_text_field(wp_unslash($_POST['milapro_blog_eyebrow'] ?? 'Milapro Home')));
    update_post_meta($post_id, '_milapro_blog_intro_text', sanitize_textarea_field(wp_unslash($_POST['milapro_blog_intro_text'] ?? '')));
    update_post_meta($post_id, '_milapro_blog_body_text', sanitize_textarea_field(wp_unslash($_POST['milapro_blog_body_text'] ?? '')));
    update_post_meta($post_id, '_milapro_blog_display_order', (int) ($_POST['milapro_blog_display_order'] ?? 999));
    update_post_meta($post_id, '_milapro_blog_is_visible', isset($_POST['milapro_blog_is_visible']) ? '1' : '0');

    $image = (int) ($_POST['milapro_blog_image'] ?? 0);
    update_post_meta($post_id, '_milapro_blog_image', $image);
    if ($image) set_post_thumbnail($post_id, $image);
}

function milapro_collect_colors(): array
{
    $names = $_POST['milapro_colors_name'] ?? [];
    $hexes = $_POST['milapro_colors_hex'] ?? [];
    $items = [];
    foreach ($names as $i => $name) {
        $name = sanitize_text_field(wp_unslash($name));
        if (!$name) continue;
        $items[] = ['id' => sanitize_title($name), 'name' => $name, 'hex' => sanitize_hex_color($hexes[$i] ?? '#cccccc') ?: '#cccccc', 'available' => milapro_meta_bool($_POST['milapro_colors_available'][$i] ?? '1', true)];
    }
    return $items;
}

function milapro_collect_variants(): array
{
    $names = $_POST['milapro_variants_name'] ?? [];
    $items = [];
    foreach ($names as $i => $name) {
        $name = sanitize_text_field(wp_unslash($name));
        if (!$name) continue;
        $items[] = ['name' => $name, 'price' => sanitize_text_field(wp_unslash($_POST['milapro_variants_price'][$i] ?? '')), 'compare_at_price' => sanitize_text_field(wp_unslash($_POST['milapro_variants_compare_at_price'][$i] ?? '')), 'sku' => sanitize_text_field(wp_unslash($_POST['milapro_variants_sku'][$i] ?? ''))];
    }
    return $items;
}

function milapro_collect_specs(): array
{
    $labels = $_POST['milapro_specs_label'] ?? [];
    $items = [];
    foreach ($labels as $i => $label) {
        $label = sanitize_text_field(wp_unslash($label));
        $value = sanitize_text_field(wp_unslash($_POST['milapro_specs_value'][$i] ?? ''));
        if (!$label && !$value) continue;
        $items[] = ['label' => $label, 'value' => $value];
    }
    return $items;
}

function milapro_category_add_fields(): void
{
    echo '<div class="form-field"><label>Category Image</label><input type="hidden" name="milapro_category_image" data-media-input><button type="button" class="button" data-media-select>Select Image</button> <span data-media-label>No image selected</span></div>';
    echo '<div class="form-field"><label>Eyebrow</label><input name="milapro_eyebrow"></div>';
    echo '<div class="form-field"><label>Display Order</label><input name="milapro_display_order" type="number" value="999"></div>';
    echo '<div class="form-field"><label><input name="milapro_featured" type="checkbox" value="1" checked> Featured</label></div>';
}

function milapro_category_edit_fields(WP_Term $term): void
{
    $image = (int) get_term_meta($term->term_id, '_milapro_category_image', true);
    echo '<tr class="form-field"><th><label>Category Image</label></th><td><input type="hidden" name="milapro_category_image" value="' . esc_attr((string) $image) . '" data-media-input><button type="button" class="button" data-media-select>Select Image</button> <span data-media-label>' . esc_html($image ? basename(get_attached_file($image)) : 'No image selected') . '</span></td></tr>';
    echo '<tr class="form-field"><th><label>Eyebrow</label></th><td><input name="milapro_eyebrow" value="' . esc_attr(get_term_meta($term->term_id, '_milapro_eyebrow', true)) . '"></td></tr>';
    echo '<tr class="form-field"><th><label>Display Order</label></th><td><input name="milapro_display_order" type="number" value="' . esc_attr(get_term_meta($term->term_id, '_milapro_display_order', true) ?: '999') . '"></td></tr>';
    echo '<tr class="form-field"><th><label>Featured</label></th><td><label><input name="milapro_featured" type="checkbox" value="1" ' . checked(milapro_meta_bool(get_term_meta($term->term_id, '_milapro_featured', true), true), true, false) . '> Featured</label></td></tr>';
}

function milapro_save_category_fields(int $term_id): void
{
    update_term_meta($term_id, '_milapro_category_image', (int) ($_POST['milapro_category_image'] ?? 0));
    update_term_meta($term_id, '_milapro_eyebrow', sanitize_text_field(wp_unslash($_POST['milapro_eyebrow'] ?? '')));
    update_term_meta($term_id, '_milapro_display_order', (int) ($_POST['milapro_display_order'] ?? 999));
    update_term_meta($term_id, '_milapro_featured', isset($_POST['milapro_featured']) ? '1' : '0');
}

function milapro_admin_script(): string
{
    return <<<'JS'
jQuery(function($) {
  $(document).on('click', '[data-media-select]', function(e) {
    e.preventDefault();
    const row = $(this).closest('.milapro-row, .milapro-media-row, .form-field, td');
    const frame = wp.media({ title: 'Select image', multiple: false, library: { type: 'image' } });
    frame.on('select', function() {
      const attachment = frame.state().get('selection').first().toJSON();
      row.find('[data-media-input]').val(attachment.id);
      row.find('[data-media-label]').text(attachment.filename || attachment.title || attachment.url);
      row.find('[data-media-preview]').attr('src', attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url).show();
      row.find('[data-media-empty]').hide();
    });
    frame.open();
  });
  $(document).on('click', '[data-media-remove]', function(e) {
    e.preventDefault();
    const row = $(this).closest('.milapro-row, .milapro-media-row, .form-field, td');
    row.find('[data-media-input]').val('');
    row.find('[data-media-label]').text('No image selected');
    row.find('[data-media-preview]').attr('src', '').hide();
    row.find('[data-media-empty]').show();
  });
  $(document).on('click', '[data-remove-row]', function() { $(this).closest('.milapro-row').remove(); });
  $(document).on('click', '[data-move-row]', function(e) {
    e.preventDefault();
    const row = $(this).closest('.milapro-row');
    if ($(this).data('move-row') === 'up') {
      row.prev('.milapro-row').before(row);
    } else {
      row.next('.milapro-row').after(row);
    }
  });
  $(document).on('click', '[data-add-row]', function() {
    const type = $(this).data('add-row');
    const target = $('[data-repeat="' + type + '"]');
    const rows = {
      gallery: '<div class="milapro-row milapro-gallery-row" data-row="gallery"><input type="hidden" name="milapro_gallery_images[]" data-media-input><span class="milapro-media-preview"><img src="" alt="" data-media-preview style="display:none"><span data-media-empty>No image selected</span></span><button type="button" class="button" data-media-select>Select Image</button> <span data-media-label>No image selected</span> <button type="button" class="button" data-move-row="up">Move up</button> <button type="button" class="button" data-move-row="down">Move down</button> <button type="button" class="button-link-delete" data-remove-row>Remove</button></div>',
      colors: '<div class="milapro-row milapro-row-grid" data-row="colors"><input name="milapro_colors_name[]" placeholder="Name"><input name="milapro_colors_hex[]" type="color" value="#cccccc"><select name="milapro_colors_available[]"><option value="1">Available</option><option value="0">Not available</option></select><button type="button" class="button-link-delete" data-remove-row>Remove</button></div>',
      variants: '<div class="milapro-row milapro-row-grid" data-row="variants"><input name="milapro_variants_name[]" placeholder="Name"><input name="milapro_variants_price[]" type="number" step="0.01" placeholder="Price"><input name="milapro_variants_compare_at_price[]" type="number" step="0.01" placeholder="Compare at"><input name="milapro_variants_sku[]" placeholder="SKU"><button type="button" class="button-link-delete" data-remove-row>Remove</button></div>',
      specs: '<div class="milapro-row milapro-row-grid" data-row="specs"><input name="milapro_specs_label[]" placeholder="Label"><input name="milapro_specs_value[]" placeholder="Value"><button type="button" class="button-link-delete" data-remove-row>Remove</button></div>'
    };
    target.append(rows[type]);
  });
});
JS;
}

function milapro_admin_styles(): string
{
    return '.milapro-fields{display:grid;gap:18px}.milapro-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.milapro-field{display:grid;gap:6px;font-weight:600}.milapro-field input,.milapro-field textarea{font-weight:400}.milapro-field small{font-weight:400;color:#646970}.milapro-checkbox{display:flex;align-items:center;gap:8px}.milapro-repeat{display:grid;gap:10px;margin-bottom:10px}.milapro-row,.milapro-banner-card{padding:10px;border:1px solid #dcdcde;background:#fff}.milapro-banner-card{display:grid;gap:14px;max-width:980px;padding:18px}.milapro-banner-card h2{margin:0}.milapro-gallery-row,.milapro-media-preview-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.milapro-media-preview{display:inline-flex;align-items:center;justify-content:center;width:76px;height:76px;border:1px solid #dcdcde;background:#f6f7f7;color:#646970;font-size:12px;text-align:center;overflow:hidden}.milapro-media-preview img{width:100%;height:100%;object-fit:cover}.milapro-row-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;align-items:center}@media(max-width:900px){.milapro-grid,.milapro-row-grid{grid-template-columns:1fr}}';
}

function milapro_media_url($image): string
{
    if (!$image) {
        return '';
    }

    if (is_numeric($image)) {
        return wp_get_attachment_image_url((int) $image, 'large') ?: '';
    }

    if (is_array($image)) {
        if (!empty($image['sizes']['large'])) {
            return is_array($image['sizes']['large']) ? ($image['sizes']['large']['url'] ?? '') : $image['sizes']['large'];
        }

        return $image['url'] ?? $image['source_url'] ?? '';
    }

    if (is_string($image)) {
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '/')) {
            return $image;
        }
    }

    return '';
}

function milapro_trigger_rebuild_for_post(int $post_id, WP_Post $post, bool $update): void
{
    if (defined('MILAPRO_IMPORTING_SEED') && MILAPRO_IMPORTING_SEED) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!in_array($post->post_type, ['products', 'reels', 'post'], true)) {
        return;
    }

    // Only published content affects the public Astro site.
    if ($post->post_status !== 'publish') {
        return;
    }

    milapro_send_rebuild_webhook($post->post_type, (string) $post_id, $update ? 'updated' : 'created');
}

function milapro_trigger_rebuild_for_term(int $term_id, int $tt_id): void
{
    if (defined('MILAPRO_IMPORTING_SEED') && MILAPRO_IMPORTING_SEED) {
        return;
    }

    milapro_send_rebuild_webhook('product_category', (string) $term_id, 'saved');
}

function milapro_send_rebuild_webhook(string $content_type, string $content_id, string $action)
{
    static $sent = false;

    // Multiple WordPress hooks can fire during one save; one rebuild per request is enough.
    if ($sent) {
        return true;
    }

    $webhook_url = defined('MILAPRO_REBUILD_WEBHOOK_URL') ? MILAPRO_REBUILD_WEBHOOK_URL : '';
    $secret = defined('MILAPRO_REBUILD_WEBHOOK_SECRET') ? MILAPRO_REBUILD_WEBHOOK_SECRET : '';

    if (!$webhook_url || !$secret) {
        return new WP_Error('milapro_rebuild_not_configured', 'Deploy webhook is not configured.');
    }

    $sent = true;
    $response = wp_remote_post($webhook_url, [
        'timeout' => 8,
        'headers' => [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer ' . $secret,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Milapro-Headless-CMS',
        ],
        'body' => wp_json_encode([
            'event_type' => 'wordpress_content_changed',
        ]),
    ]);

    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Milapro rebuild webhook] ' . $response->get_error_message());
        }

        return new WP_Error('milapro_rebuild_request_failed', 'Could not start deploy.');
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code >= 400) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Milapro rebuild webhook] GitHub returned HTTP ' . $status_code . ': ' . wp_remote_retrieve_body($response));
        }

        return new WP_Error('milapro_rebuild_github_error', 'GitHub returned HTTP ' . $status_code . '.');
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Milapro rebuild webhook] Repository dispatch sent for ' . $content_type . ' ' . $content_id . ' (' . $action . ').');
    }

    return true;
}
