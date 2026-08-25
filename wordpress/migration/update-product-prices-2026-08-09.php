<?php
/**
 * Updates product prices from the August 2026 verified price list.
 *
 * Run with WP-CLI from Docker/local WordPress:
 * docker compose --profile tools run --rm wpcli wp eval-file /var/www/html/migration/update-product-prices-2026-08-09.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$price_updates = [
    'aluminio-bahia-esquinera' => 61800.00,
    'aluminio-caspio' => 48700.00,
    'aluminio-caspio-esquinero' => 71670.00,
    'aluminio-llona-4-ax' => 63200.00,
    'aluminio-llona-5-ax' => 75800.00,
    'aluminio-llona-esquinero' => 59400.00,
    'aluminio-malaui' => 58810.00,
    'aluminio-mesa-volga-1-6' => 25740.00,
    'aluminio-mesa-volga-2-3' => 29570.00,
    'aluminio-onega' => 79200.00,
    'aluminio-onega-esquinero' => 92289.99,
    'aluminio-ontario-beige' => 81150.00,
    'aluminio-ontario-gris' => 81150.00,
    'aluminio-silla-caspio' => 6400.00,
    'aluminio-silla-malaui' => 7260.00,
    'aluminio-sillon-ind-onrario-beige' => 19980.00,
    'aluminio-sillon-ind-ontario-gris' => 19980.00,
    'aluminio-sillones-casio' => 25000.00,
    'outlet-ladoga' => 42999.00,
    'outlet-liena' => 42999.00,
    'outlet-nyasa' => 29200.00,
    'outlet-turkana' => 42999.00,
    'plastico-aster-mesa-con-almacenamiento' => 1799.00,
    'plastico-camastro-raflessia' => 5999.00,
    'plastico-gerbera-con-apoyabrazos' => 1799.00,
    'plastico-mecedora-iris' => 3399.00,
    'plastico-mesa-auxiliar-narciso' => 1399.01,
    'plastico-mesa-cala' => 5499.00,
    'plastico-mesa-leman' => 18650.00,
    'plastico-mesa-licerna' => 11650.00,
    'plastico-mesa-loto' => 2199.00,
    'plastico-mesa-nilo-1-6m' => 15850.00,
    'plastico-mesa-nilo-2m' => 19850.00,
    'plastico-narciso-con-aopyabrazos' => 1299.00,
    'plastico-set-craspedia' => 17989.00,
    'plastico-set-iris' => 17989.00,
    'plastico-silla-alta-narciso' => 1499.00,
    'plastico-silla-alta-zinnia' => 1699.00,
    'plastico-silla-calendula' => 1899.00,
    'plastico-silla-dalia' => 1799.00,
    'plastico-silla-gerbera' => 1699.01,
    'plastico-silla-narciso' => 1199.00,
    'plastico-silla-peonia' => 1699.01,
    'plastico-silla-zinnia' => 1199.00,
    'ratan-barcelona-2' => 22500.00,
    'ratan-barcelona-4' => 45000.00,
    'ratan-barcelona-6' => 65000.00,
    'ratan-bilbao' => 39500.00,
    'ratan-granada' => 23999.00,
    'ratan-ibiza' => 72500.00,
    'ratan-madrid' => 51500.00,
    'ratan-malaga' => 58000.00,
    'ratan-malaga-petit' => 49500.00,
    'ratan-marbella' => 46500.00,
    'ratan-sevilla' => 33500.00,
];

$report = [
    'updated' => 0,
    'unchanged' => 0,
    'missing' => [],
];

$discount_term = get_term_by('slug', 'descuentos', 'product_category');

foreach ($price_updates as $slug => $price) {
    $posts = get_posts([
        'name' => $slug,
        'post_type' => 'products',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if (!$posts) {
        $posts = get_posts([
            'post_type' => 'products',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_milapro_source_slug',
            'meta_value' => $slug,
        ]);
    }

    if (!$posts) {
        $report['missing'][] = $slug;
        continue;
    }

    $post_id = (int) $posts[0];
    $current_price = (float) get_post_meta($post_id, '_milapro_price', true);
    $current_compare_at = get_post_meta($post_id, '_milapro_compare_at_price', true);
    $needs_update = abs($current_price - $price) > 0.001 || $current_compare_at !== '';

    if (!$needs_update) {
        $report['unchanged']++;
        continue;
    }

    update_post_meta($post_id, '_milapro_price', $price);
    delete_post_meta($post_id, '_milapro_compare_at_price');

    if ($discount_term && !is_wp_error($discount_term)) {
        wp_remove_object_terms($post_id, (int) $discount_term->term_id, 'product_category');
    }

    clean_post_cache($post_id);
    $report['updated']++;
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::success(wp_json_encode($report));
} else {
    wp_send_json_success($report);
}
