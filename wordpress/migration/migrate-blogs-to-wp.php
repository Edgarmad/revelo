<?php
/**
 * One-time migration for the current hardcoded Astro blogs into the Milapro blogs CPT.
 *
 * Run with:
 * docker compose --profile tools run --rm wpcli wp eval-file /var/www/html/migration/migrate-blogs-to-wp.php
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!post_type_exists('blogs')) {
    echo wp_json_encode(['error' => 'The blogs post type is not registered. Activate/update milapro-headless-cms first.'], JSON_PRETTY_PRINT) . PHP_EOL;
    return;
}

$blogs = [
    [
        'title' => 'Cómo elegir muebles de exterior para una terraza activa',
        'slug' => 'como-elegir-muebles-exterior-terraza-activa',
        'eyebrow' => 'Guía de exterior',
        'image' => '/client-images/decoracion/decoracion-08.jpg',
        'intro_text' => 'Ideas para configurar zonas de descanso, conversación y juego sin perder orden visual.',
        'body_text' => implode("\n\n", [
            'Una terraza activa necesita piezas que acompañen distintos momentos del día: descanso, conversación, comida y juego. Antes de elegir muebles, define cuántas personas usarán el espacio con frecuencia y qué actividades quieres priorizar.',
            'Para mantener orden visual, agrupa por zonas. Una sala modular puede marcar el área social, una mesa auxiliar resuelve apoyo diario y una sombrilla o pérgola ayuda a controlar el sol sin recargar el ambiente.',
            'Elige materiales resistentes al exterior y telas fáciles de limpiar. En espacios familiares o de uso constante, conviene priorizar estructuras firmes, cojines removibles y colores neutros que puedan renovarse con accesorios.',
        ]),
    ],
    [
        'title' => 'Materiales resistentes para sol, humedad y uso diario',
        'slug' => 'materiales-resistentes-sol-humedad-uso-diario',
        'eyebrow' => 'Milapro Home',
        'image' => '/client-images/decoracion/decoracion-09.jpg',
        'intro_text' => 'Ratán sintético, aluminio y plástico de fácil mantenimiento para espacios exteriores.',
        'body_text' => implode("\n\n", [
            'Los muebles de exterior están expuestos a sol, humedad, polvo y movimiento constante. Por eso, la selección del material determina tanto la durabilidad como la facilidad de mantenimiento.',
            'El ratán sintético ofrece una apariencia cálida con buena resistencia para terrazas y jardines. El aluminio aporta ligereza y estabilidad frente a la corrosión, mientras que los plásticos de calidad son prácticos para áreas de alto uso.',
            'La clave está en combinar estructura, tejido y cuidado. Limpiezas periódicas, protección en temporadas de lluvia intensa y una ubicación bien ventilada ayudan a conservar mejor cada pieza.',
        ]),
    ],
    [
        'title' => 'Ideas para proyectos a la medida',
        'slug' => 'ideas-para-proyectos-a-la-medida',
        'eyebrow' => 'Proyectos',
        'image' => '/client-images/decoracion/decoracion-10.jpg',
        'intro_text' => 'Soluciones para arquitectos, hoteles, restaurantes y hogares que buscan diseño funcional.',
        'body_text' => implode("\n\n", [
            'Los proyectos a la medida permiten que cada pieza responda al uso real del espacio. En hoteles, restaurantes y hogares amplios, el mobiliario debe equilibrar estética, circulación, resistencia y mantenimiento.',
            'Antes de producir o seleccionar piezas, conviene revisar medidas, recorridos, exposición solar y cantidad de usuarios. Esta información ayuda a definir formatos, módulos, alturas y materiales adecuados.',
            'Una propuesta funcional también considera continuidad visual. Repetir tonos, texturas o líneas de diseño crea ambientes coherentes sin perder flexibilidad para futuras ampliaciones.',
        ]),
    ],
];

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$report = [
    'created' => 0,
    'updated' => 0,
    'images_imported' => 0,
    'images_reused' => 0,
    'image_errors' => [],
    'blogs' => [],
];

foreach ($blogs as $index => $blog) {
    $post_id = milapro_migrate_blog_find_post($blog['slug']);
    $post_data = [
        'post_type' => 'blogs',
        'post_status' => 'publish',
        'post_title' => $blog['title'],
        'post_name' => $blog['slug'],
        'post_excerpt' => $blog['intro_text'],
    ];

    if ($post_id) {
        $post_data['ID'] = $post_id;
        $result = wp_update_post($post_data, true);
        $report['updated']++;
    } else {
        $result = wp_insert_post($post_data, true);
        $report['created']++;
    }

    if (is_wp_error($result)) {
        $report['blogs'][] = ['slug' => $blog['slug'], 'error' => $result->get_error_message()];
        continue;
    }

    $post_id = (int) $result;
    update_post_meta($post_id, '_milapro_blog_eyebrow', $blog['eyebrow']);
    update_post_meta($post_id, '_milapro_blog_intro_text', $blog['intro_text']);
    update_post_meta($post_id, '_milapro_blog_body_text', $blog['body_text']);
    update_post_meta($post_id, '_milapro_blog_display_order', $index + 1);
    update_post_meta($post_id, '_milapro_blog_is_visible', '1');

    $image_id = milapro_migrate_blog_image($blog['image'], $blog['title'], $report);
    if ($image_id) {
        update_post_meta($post_id, '_milapro_blog_image', $image_id);
        set_post_thumbnail($post_id, $image_id);
    }

    $report['blogs'][] = ['id' => $post_id, 'slug' => $blog['slug'], 'image' => $image_id];
}

echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function milapro_migrate_blog_find_post(string $slug): int
{
    $posts = get_posts([
        'post_type' => 'blogs',
        'post_status' => 'any',
        'name' => $slug,
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    return empty($posts[0]) ? 0 : (int) $posts[0];
}

function milapro_migrate_blog_image(string $source_path, string $title, array &$report): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_milapro_blog_source_path',
        'meta_value' => $source_path,
    ]);

    if (!empty($existing[0])) {
        $report['images_reused']++;
        return (int) $existing[0];
    }

    $local_path = '/var/www/html/migration-public' . $source_path;
    if (is_readable($local_path)) {
        $tmp = wp_tempnam(basename($local_path));
        if (!$tmp || !copy($local_path, $tmp)) {
            $report['image_errors'][] = ['source' => $source_path, 'error' => 'Could not copy local file.'];
            return 0;
        }
    } else {
        $base_url = defined('MILAPRO_PUBLIC_SITE_URL') ? MILAPRO_PUBLIC_SITE_URL : 'https://www.milaprohome.com';
        $tmp = download_url(untrailingslashit($base_url) . $source_path, 20);
        if (is_wp_error($tmp)) {
            $report['image_errors'][] = ['source' => $source_path, 'error' => $tmp->get_error_message()];
            return 0;
        }
    }

    $file = [
        'name' => basename($source_path),
        'type' => wp_check_filetype(basename($source_path))['type'] ?? '',
        'tmp_name' => $tmp,
        'error' => 0,
        'size' => filesize($tmp),
    ];

    $attachment_id = media_handle_sideload($file, 0, $title);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        $report['image_errors'][] = ['source' => $source_path, 'error' => $attachment_id->get_error_message()];
        return 0;
    }

    update_post_meta((int) $attachment_id, '_milapro_blog_source_path', $source_path);
    $report['images_imported']++;
    return (int) $attachment_id;
}
