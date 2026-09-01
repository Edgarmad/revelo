<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Temporary wp-admin tool to import the 2026 umbrella products from FTP assets.
 */
class Milapro_Umbrella_Migration_Admin
{
    private const NONCE_ACTION = 'milapro_umbrella_migration';
    private const CATEGORY_ID = 12;

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('wp_ajax_milapro_umbrella_status', [self::class, 'status']);
        add_action('wp_ajax_milapro_umbrella_extract', [self::class, 'extract']);
        add_action('wp_ajax_milapro_umbrella_dryrun', [self::class, 'dryrun']);
        add_action('wp_ajax_milapro_umbrella_migrate', [self::class, 'migrate']);
        add_action('wp_ajax_milapro_umbrella_cleanup', [self::class, 'cleanup']);
    }

    public static function menu(): void
    {
        add_management_page('Migración Sombrillas MilaPro', 'Migración Sombrillas MilaPro', 'manage_options', 'milapro-umbrella-migration', [self::class, 'render']);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.'));
        }

        $nonce = wp_create_nonce(self::NONCE_ACTION);
        ?>
        <div class="wrap" style="max-width:980px">
            <h1>Migración Sombrillas MilaPro</h1>
            <p>Herramienta temporal. Importa solo las carpetas incluidas en <code>wp-content/uploads/milapro-sombrillas/sombrillas-source.zip</code>, asigna categoría <code>product_category</code> ID 12, precio, cantidades e imágenes SEO.</p>
            <div style="background:#fff;border:1px solid #dcdcde;padding:18px;margin:16px 0;display:flex;gap:10px;flex-wrap:wrap">
                <button type="button" class="button" id="mum-status">1. Ver estado</button>
                <button type="button" class="button" id="mum-extract">2. Extraer ZIP</button>
                <button type="button" class="button" id="mum-dryrun">3. Dry-run</button>
                <button type="button" class="button button-primary" id="mum-migrate">4. Ejecutar migración real</button>
                <button type="button" class="button" id="mum-cleanup">5. Limpiar temporales</button>
            </div>
            <pre id="mum-log" style="background:#111;color:#f4f4f4;min-height:200px;max-height:520px;overflow:auto;padding:12px;white-space:pre-wrap">Listo.</pre>
        </div>
        <script>
        (function () {
            var nonce = <?php echo wp_json_encode($nonce); ?>;
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var log = document.getElementById('mum-log');

            function call(action) {
                var body = new FormData();
                body.append('action', action);
                body.append('nonce', nonce);
                return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
                    .then(function (r) { return r.text(); })
                    .then(function (text) {
                        try { return JSON.parse(text); }
                        catch (e) { throw new Error('Respuesta no-JSON: ' + text.slice(0, 300)); }
                    });
            }

            function run(action) {
                log.textContent = 'Ejecutando ' + action + '...';
                call(action)
                    .then(function (data) { log.textContent = JSON.stringify(data, null, 2); })
                    .catch(function (err) { log.textContent = 'Error: ' + err.message; });
            }

            document.getElementById('mum-status').addEventListener('click', function () { run('milapro_umbrella_status'); });
            document.getElementById('mum-extract').addEventListener('click', function () { run('milapro_umbrella_extract'); });
            document.getElementById('mum-dryrun').addEventListener('click', function () { run('milapro_umbrella_dryrun'); });
            document.getElementById('mum-migrate').addEventListener('click', function () {
                if (window.confirm('Esto creará o actualizará los productos de sombrillas disponibles en el ZIP. Continuar?')) run('milapro_umbrella_migrate');
            });
            document.getElementById('mum-cleanup').addEventListener('click', function () { run('milapro_umbrella_cleanup'); });
        })();
        </script>
        <?php
    }

    private static function guard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
    }

    private static function base_dir(): string
    {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . 'milapro-sombrillas';
    }

    public static function status(): void
    {
        self::guard();
        $dir = self::base_dir();
        wp_send_json_success([
            'dir' => $dir,
            'zip_exists' => file_exists($dir . '/sombrillas-source.zip'),
            'extracted_exists' => is_dir($dir . '/source'),
            'catalog_products' => count(self::catalog()),
        ]);
    }

    public static function extract(): void
    {
        self::guard();
        $dir = self::base_dir();
        $zip_path = $dir . '/sombrillas-source.zip';
        if (!file_exists($zip_path)) {
            wp_send_json_error(['message' => 'zip_not_found', 'path' => $zip_path], 400);
        }

        $target = $dir . '/source';
        if (is_dir($target)) {
            self::rrmdir($target);
        }
        wp_mkdir_p($target);

        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            wp_send_json_error(['message' => 'zip_open_failed'], 500);
        }
        $zip->extractTo($target);
        $count = $zip->numFiles;
        $zip->close();

        wp_send_json_success(['extracted_entries' => $count, 'target' => $target]);
    }

    public static function dryrun(): void
    {
        self::guard();
        wp_send_json_success(['report' => self::run(true)]);
    }

    public static function migrate(): void
    {
        self::guard();
        if (!defined('MILAPRO_IMPORTING_SEED')) {
            define('MILAPRO_IMPORTING_SEED', true);
        }
        wp_send_json_success(['report' => self::run(false)]);
    }

    public static function cleanup(): void
    {
        self::guard();
        $dir = self::base_dir();
        $removed = [];
        if (is_dir($dir . '/source')) {
            self::rrmdir($dir . '/source');
            $removed[] = 'source/';
        }
        wp_send_json_success(['removed' => $removed]);
    }

    private static function run(bool $dry_run): array
    {
        $base_path = self::base_dir() . '/source';
        $report = [
            'base_path' => $base_path,
            'dry_run' => $dry_run,
            'products_total' => 0,
            'products_created' => 0,
            'products_updated' => 0,
            'images_planned' => 0,
            'images_imported' => 0,
            'images_reused' => 0,
            'images_failed' => 0,
            'products' => [],
            'products_pending' => [],
            'errors' => [],
        ];

        if (!is_dir($base_path) || !is_readable($base_path)) {
            $report['errors'][] = 'Source path is not readable: ' . $base_path;
            return $report;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $folders = self::source_folders($base_path);
        foreach (self::catalog() as $item) {
            $folder = self::match_folder($item, $folders);
            if (!$folder) {
                continue;
            }

            $report['products_total']++;
            $images = self::product_images($folder);
            $report['images_planned'] += count($images);
            if (empty($images)) {
                $report['products_pending'][] = ['model' => $item['model'], 'folder' => basename($folder), 'reason' => 'no_images'];
                continue;
            }

            $slug = self::slug(basename($folder));
            $existing = get_page_by_path($slug, OBJECT, 'products');
            $post_data = [
                'post_type' => 'products',
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_title' => sanitize_text_field(basename($folder)),
                'post_excerpt' => 'Sombrilla Milapro Home disponible por pedido.',
                'post_content' => 'Sombrilla Milapro Home. Precio y existencias actualizados desde inventario 2026.',
            ];

            $post_id = $existing ? (int) $existing->ID : 0;
            if (!$dry_run) {
                $result = $existing ? wp_update_post(['ID' => $post_id] + $post_data, true) : wp_insert_post($post_data, true);
                if (is_wp_error($result)) {
                    $report['products_pending'][] = ['model' => $item['model'], 'folder' => basename($folder), 'reason' => $result->get_error_message()];
                    continue;
                }
                $post_id = (int) $result;
                wp_set_object_terms($post_id, [self::CATEGORY_ID], 'product_category');
                self::save_product_meta($post_id, $slug, $item);
            }

            $attachment_ids = [];
            foreach ($images as $index => $image) {
                $attachment_id = self::import_image($image, basename($folder), $slug, $index, $dry_run, $report);
                if ($attachment_id) {
                    $attachment_ids[] = $attachment_id;
                }
            }

            if (!$dry_run && $post_id && !empty($attachment_ids)) {
                update_post_meta($post_id, '_milapro_main_image', $attachment_ids[0]);
                set_post_thumbnail($post_id, $attachment_ids[0]);
                update_post_meta($post_id, '_milapro_gallery_images', array_map(function (int $id): array {
                    return ['image' => $id];
                }, array_slice($attachment_ids, 1)));
                clean_post_cache($post_id);
            }

            $report[$existing ? 'products_updated' : 'products_created']++;
            $report['products'][] = [
                'model' => $item['model'],
                'folder' => basename($folder),
                'slug' => $slug,
                'post_id' => $post_id,
                'price' => $item['price'],
                'stock_total' => self::stock_total($item['quantity']),
                'image_count' => count($images),
                'updated' => !$dry_run,
            ];
        }

        return $report;
    }

    private static function catalog(): array
    {
        return [
            ['model' => 'BANANA', 'quantity' => ['GRIS' => 4], 'price' => 3360],
            ['model' => 'SOMBRILLA CON ILUMINACION LED 2.7M', 'quantity' => ['GRIS' => 13], 'price' => 5480],
            ['model' => 'SOMBRILLA CUADRADA CON ONDAS', 'quantity' => ['KHAKI' => 3, 'ROJO' => 5, 'VERDE' => 5, 'CAFE' => 5, 'AZUL' => 4], 'price' => 3580],
            ['model' => 'ROMA 4X4', 'quantity' => ['TOTAL' => 4], 'price' => 22600],
            ['model' => 'BASE CIRCULAR', 'quantity' => ['TOTAL' => 21], 'price' => 800],
            ['model' => 'BASE RECTANGULAR', 'quantity' => ['TOTAL' => 27], 'price' => 1600],
            ['model' => 'BASE CUADRADA', 'quantity' => ['TOTAL' => 8], 'price' => 4000],
        ];
    }

    private static function source_folders(string $base_path): array
    {
        $folders = [];
        foreach (glob($base_path . '/*', GLOB_ONLYDIR) ?: [] as $folder) {
            $folders[] = $folder;
        }
        natcasesort($folders);
        return array_values($folders);
    }

    private static function match_folder(array $item, array $folders): string
    {
        $model_key = self::match_key($item['model']);
        foreach ($folders as $folder) {
            $folder_key = self::match_key(basename($folder));
            if ($folder_key === $model_key || str_contains($folder_key, $model_key) || str_contains($model_key, $folder_key)) {
                return $folder;
            }
        }
        return '';
    }

    private static function product_images(string $folder): array
    {
        $files = array_values(array_filter(glob($folder . '/*') ?: [], function (string $file): bool {
            return is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
        }));
        usort($files, 'strnatcasecmp');

        $main = [];
        $gallery = [];
        foreach ($files as $file) {
            if (self::slug(pathinfo($file, PATHINFO_FILENAME)) === 'main') {
                $main[] = $file;
            } else {
                $gallery[] = $file;
            }
        }
        return array_merge($main, $gallery);
    }

    private static function save_product_meta(int $post_id, string $slug, array $item): void
    {
        $quantity = $item['quantity'];
        $stock_total = self::stock_total($quantity);
        update_post_meta($post_id, '_milapro_source_slug', $slug);
        update_post_meta($post_id, '_milapro_price', $item['price']);
        delete_post_meta($post_id, '_milapro_compare_at_price');
        update_post_meta($post_id, '_milapro_sku', strtoupper(str_replace('-', '-', $slug)));
        update_post_meta($post_id, '_milapro_available', $stock_total > 0 ? '1' : '0');
        update_post_meta($post_id, '_milapro_featured', '0');
        update_post_meta($post_id, '_milapro_sale', '0');
        update_post_meta($post_id, '_milapro_display_order', 999);
        update_post_meta($post_id, '_milapro_collection', 'Sombrillas');
        update_post_meta($post_id, '_milapro_brand', 'Milapro Home');
        update_post_meta($post_id, '_milapro_keywords', 'sombrilla, terraza, jardin, exterior');
        update_post_meta($post_id, '_milapro_stock_quantity', $stock_total);
        update_post_meta($post_id, '_milapro_colors', self::colors($quantity));
        update_post_meta($post_id, '_milapro_variants', self::variants($quantity, (float) $item['price']));
        update_post_meta($post_id, '_milapro_specifications', [
            ['label' => 'Existencia total', 'value' => (string) $stock_total],
            ['label' => 'Existencia por variante', 'value' => self::quantity_label($quantity)],
        ]);
    }

    private static function import_image(string $path, string $folder_name, string $product_slug, int $index, bool $dry_run, array &$report): int
    {
        $source_key = 'sombrillas/' . $folder_name . '/' . basename($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $filename = self::seo_filename($folder_name, $extension, $index);
        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_milapro_umbrella_source_path',
            'meta_value' => $source_key,
        ]);

        if (!empty($existing[0])) {
            $report['images_reused']++;
            return (int) $existing[0];
        }

        if ($dry_run) {
            return 0;
        }

        $tmp = wp_tempnam($filename);
        if (!$tmp || !copy($path, $tmp)) {
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

        $attachment_id = media_handle_sideload($file, 0, $folder_name);
        if (is_wp_error($attachment_id)) {
            @unlink($renamed_tmp);
            $report['images_failed']++;
            $report['errors'][] = $source_key . ': ' . $attachment_id->get_error_message();
            return 0;
        }

        update_post_meta((int) $attachment_id, '_milapro_umbrella_source_path', $source_key);
        update_post_meta((int) $attachment_id, '_milapro_umbrella_product_slug', $product_slug);
        update_post_meta((int) $attachment_id, '_milapro_umbrella_image_role', $index === 0 ? 'main' : 'gallery');
        $report['images_imported']++;
        return (int) $attachment_id;
    }

    private static function seo_filename(string $folder_name, string $extension, int $index): string
    {
        $role = $index === 0 ? 'principal' : 'galeria-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        return self::slug($folder_name . '-' . $role) . '.' . $extension;
    }

    private static function stock_total(array $quantity): int
    {
        return array_sum(array_map('intval', array_values($quantity)));
    }

    private static function colors(array $quantity): array
    {
        $colors = [];
        foreach ($quantity as $name => $stock) {
            if ($name === 'TOTAL') {
                continue;
            }
            $colors[] = ['id' => self::slug($name), 'name' => $name, 'hex' => self::color_hex($name), 'available' => (int) $stock > 0, 'quantity' => (int) $stock];
        }
        return $colors;
    }

    private static function variants(array $quantity, float $price): array
    {
        $variants = [];
        foreach ($quantity as $name => $stock) {
            $variants[] = ['name' => $name, 'price' => $price, 'compare_at_price' => '', 'sku' => '', 'available' => (int) $stock > 0, 'quantity' => (int) $stock];
        }
        return $variants;
    }

    private static function quantity_label(array $quantity): string
    {
        $parts = [];
        foreach ($quantity as $name => $stock) {
            $parts[] = $name . ': ' . (int) $stock;
        }
        return implode(', ', $parts);
    }

    private static function color_hex(string $name): string
    {
        $map = ['KHAKI' => '#c3b091', 'ROJO' => '#b91c1c', 'VERDE' => '#2f6b3f', 'CAFE' => '#6f4e37', 'AZUL' => '#1d4ed8', 'GRIS' => '#808080'];
        return $map[$name] ?? '#cccccc';
    }

    private static function match_key(string $value): string
    {
        $value = self::slug($value);
        $value = preg_replace('/\bsombrilla\b/', '', $value) ?: $value;
        $value = preg_replace('/\bcon\b/', '', $value) ?: $value;
        $value = str_replace(['2-7m', '27m', '4-x-4', '4x4'], ['27m', '27m', '4x4', '4x4'], $value);
        $value = preg_replace('/-+/', '-', $value) ?: '';
        return trim($value, '-');
    }

    private static function slug(string $value): string
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

    private static function rrmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($full)) {
                self::rrmdir($full);
            } else {
                @unlink($full);
            }
        }
        @rmdir($path);
    }
}
