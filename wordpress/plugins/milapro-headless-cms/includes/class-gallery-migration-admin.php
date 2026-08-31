<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Temporary wp-admin tool to run the product gallery migration in production
 * without WP-CLI/SSH access. Remove this file and its require line from
 * milapro-headless-cms.php once the migration has been verified.
 */
class Milapro_Gallery_Migration_Admin
{
    private const NONCE_ACTION = 'milapro_gallery_migration';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('wp_ajax_milapro_gallery_status', [self::class, 'status']);
        add_action('wp_ajax_milapro_gallery_extract', [self::class, 'extract']);
        add_action('wp_ajax_milapro_gallery_dryrun', [self::class, 'dryrun']);
        add_action('wp_ajax_milapro_gallery_migrate', [self::class, 'migrate']);
        add_action('wp_ajax_milapro_gallery_cleanup', [self::class, 'cleanup']);
    }

    public static function menu(): void
    {
        add_management_page('Migración Galerías MilaPro', 'Migración Galerías MilaPro', 'manage_options', 'milapro-gallery-migration', [self::class, 'render']);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.'));
        }

        $nonce = wp_create_nonce(self::NONCE_ACTION);
        ?>
        <div class="wrap" style="max-width:980px">
            <h1>Migración Galerías MilaPro</h1>
            <p>Herramienta temporal de un solo uso. Reemplaza <code>_milapro_gallery_images</code> de los productos canónicos usando las imágenes subidas en <code>wp-content/uploads/milapro-galerias/gallery-source.zip</code>. Nunca modifica <code>_milapro_main_image</code> ni <code>featured_media</code>.</p>
            <div style="background:#fff;border:1px solid #dcdcde;padding:18px;margin:16px 0;display:flex;gap:10px;flex-wrap:wrap">
                <button type="button" class="button" id="mgm-status">1. Ver estado</button>
                <button type="button" class="button" id="mgm-extract">2. Extraer ZIP</button>
                <button type="button" class="button" id="mgm-dryrun">3. Dry-run</button>
                <button type="button" class="button button-primary" id="mgm-migrate">4. Ejecutar migración real</button>
                <button type="button" class="button" id="mgm-cleanup">5. Limpiar archivos temporales</button>
            </div>
            <pre id="mgm-log" style="background:#111;color:#f4f4f4;min-height:200px;max-height:520px;overflow:auto;padding:12px;white-space:pre-wrap">Listo.</pre>
        </div>
        <script>
        (function () {
            var nonce = <?php echo wp_json_encode($nonce); ?>;
            var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
            var log = document.getElementById('mgm-log');

            function call(action, extra) {
                var body = new FormData();
                body.append('action', action);
                body.append('nonce', nonce);
                if (extra) {
                    Object.keys(extra).forEach(function (k) { body.append(k, extra[k]); });
                }
                return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
                    .then(function (r) { return r.text(); })
                    .then(function (text) {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Respuesta no-JSON (probable error 500/timeout): ' + text.slice(0, 300));
                        }
                    });
            }

            function run(action) {
                log.textContent = 'Ejecutando ' + action + '...';
                call(action)
                    .then(function (data) { log.textContent = JSON.stringify(data, null, 2); })
                    .catch(function (err) { log.textContent = 'Error: ' + err.message; });
            }

            function runMigrateBatches() {
                var offset = 0;
                var combined = { products_updated: 0, images_imported: 0, images_reused: 0, images_failed: 0, main_image_unchanged: 0, errors: [], main_image_changed: [], batches: [] };

                function step() {
                    log.textContent = combined.batches.length + ' lotes procesados. Ejecutando lote en offset ' + offset + '...';
                    return call('milapro_gallery_migrate', { offset: offset }).then(function (data) {
                        var report = data.data && data.data.report ? data.data.report : {};
                        combined.batches.push(report);
                        combined.products_updated += report.products_updated || 0;
                        combined.images_imported += report.images_imported || 0;
                        combined.images_reused += report.images_reused || 0;
                        combined.images_failed += report.images_failed || 0;
                        combined.main_image_unchanged += report.main_image_unchanged || 0;
                        if (report.errors && report.errors.length) combined.errors = combined.errors.concat(report.errors);
                        if (report.main_image_changed && report.main_image_changed.length) combined.main_image_changed = combined.main_image_changed.concat(report.main_image_changed);

                        log.textContent = JSON.stringify(combined, null, 2);

                        if (report.aborted) {
                            log.textContent = 'MIGRACION ABORTADA (posible cambio de imagen principal). Revisar arriba.\n\n' + log.textContent;
                            return;
                        }
                        if (report.next_offset !== null && report.next_offset !== undefined) {
                            offset = report.next_offset;
                            return step();
                        }
                        log.textContent = 'MIGRACION COMPLETA.\n\n' + log.textContent;
                    });
                }

                return step().catch(function (err) {
                    log.textContent = 'Error durante la migracion (offset ' + offset + '): ' + err.message + '\n\nProgreso previo:\n' + JSON.stringify(combined, null, 2);
                });
            }

            document.getElementById('mgm-status').addEventListener('click', function () { run('milapro_gallery_status'); });
            document.getElementById('mgm-extract').addEventListener('click', function () { run('milapro_gallery_extract'); });
            document.getElementById('mgm-dryrun').addEventListener('click', function () { run('milapro_gallery_dryrun'); });
            document.getElementById('mgm-migrate').addEventListener('click', function () {
                if (window.confirm('Esto va a reemplazar la galeria de los productos canonicos en produccion, en lotes pequenos. Continuar?')) {
                    log.textContent = 'Iniciando migracion por lotes...';
                    runMigrateBatches();
                }
            });
            document.getElementById('mgm-cleanup').addEventListener('click', function () { run('milapro_gallery_cleanup'); });
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
        return trailingslashit($uploads['basedir']) . 'milapro-galerias';
    }

    public static function status(): void
    {
        self::guard();
        $dir = self::base_dir();
        wp_send_json_success([
            'dir' => $dir,
            'zip_exists' => file_exists($dir . '/gallery-source.zip'),
            'extracted_exists' => is_dir($dir . '/1000x1000'),
        ]);
    }

    public static function extract(): void
    {
        self::guard();
        $dir = self::base_dir();
        $zip_path = $dir . '/gallery-source.zip';
        if (!file_exists($zip_path)) {
            wp_send_json_error(['message' => 'zip_not_found', 'path' => $zip_path], 400);
        }

        $target = $dir . '/1000x1000';
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

    private const MAX_IMAGES_PER_BATCH = 12;

    public static function dryrun(): void
    {
        self::guard();
        wp_send_json_success(['report' => self::run_migration(true, 0, 0)]);
    }

    public static function migrate(): void
    {
        self::guard();
        $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
        wp_send_json_success(['report' => self::run_migration(false, $offset, self::MAX_IMAGES_PER_BATCH)]);
    }

    public static function cleanup(): void
    {
        self::guard();
        $dir = self::base_dir();
        $removed = [];

        if (is_dir($dir . '/1000x1000')) {
            self::rrmdir($dir . '/1000x1000');
            $removed[] = '1000x1000/';
        }
        if (file_exists($dir . '/gallery-source.zip')) {
            unlink($dir . '/gallery-source.zip');
            $removed[] = 'gallery-source.zip';
        }

        wp_send_json_success(['removed' => $removed]);
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

    private static function run_migration(bool $dry_run, int $offset = 0, int $limit = 0): array
    {
        $base_path = self::base_dir() . '/1000x1000';

        $report = [
            'base_path' => $base_path,
            'dry_run' => $dry_run,
            'batch_offset' => $offset,
            'batch_limit' => $limit,
            'total_products' => 0,
            'next_offset' => null,
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
            'aborted' => false,
        ];

        if (!is_dir($base_path) || !is_readable($base_path)) {
            $report['errors'][] = 'Gallery base path is not readable: ' . $base_path;
            return $report;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $wp_products = self::wp_products();
        $all_local_products = self::local_products($base_path);
        $report['total_products'] = count($all_local_products);

        if ($limit > 0) {
            // $limit is a max-images-per-batch budget, not a product count: gallery
            // sizes vary a lot (1 to 10 images), and HostGator kills long-running
            // requests, so batches are sized by image volume, not product count.
            $local_products = [];
            $running_images = 0;
            $idx = $offset;
            $total = count($all_local_products);
            while ($idx < $total) {
                $candidate = $all_local_products[$idx];
                $candidate_image_count = count($candidate['images']);
                if (!empty($local_products) && ($running_images + $candidate_image_count) > $limit) {
                    break;
                }
                $local_products[] = $candidate;
                $running_images += $candidate_image_count;
                $idx++;
            }
            $report['next_offset'] = $idx < $total ? $idx : null;
        } else {
            $local_products = array_slice($all_local_products, $offset);
        }

        foreach ($local_products as $local_product) {
            $report['products_scanned']++;

            if (self::is_noncanonical($local_product)) {
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

            $match = self::match_product($local_product, $wp_products);
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
                $attachment_id = self::import_image($image, $local_product, $match['slug'], $index, $dry_run, $report);
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
                $report['errors'][] = 'Main image changed unexpectedly for ' . $match['slug'] . ' - migration aborted';
                $report['aborted'] = true;
                return $report;
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

        return $report;
    }

    private static function wp_products(): array
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
                'normalized_title' => self::normalize($post->post_title),
                'title_key' => self::key($post->post_title),
                'normalized_terms' => array_map([self::class, 'category_key'], $term_names),
            ];
        }, $posts);
    }

    private static function local_products(string $base_path): array
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
                    'images' => self::product_images($product_path),
                ];
            }
        }

        return $products;
    }

    private static function product_images(string $product_path): array
    {
        $images = [];
        foreach (self::slots() as $slot => $label) {
            $slot_path = self::find_dir($product_path, $label);
            if (!$slot_path) {
                continue;
            }

            foreach (self::images_recursive($slot_path) as $slot_index => $image_path) {
                $images[] = [
                    'path' => $image_path,
                    'slot' => $slot,
                    'label' => $label,
                    'slot_index' => $slot_index + 1,
                    'relative_path' => self::relative_path($product_path, $image_path),
                ];
            }
        }

        return $images;
    }

    private static function slots(): array
    {
        return [
            '2-fondo-blanco' => '2 fondo blanco',
            '3-medidas' => '3 medidas',
            '4-variantes' => '4 variantes',
        ];
    }

    private static function find_dir(string $product_path, string $expected): string
    {
        $children = glob($product_path . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($children as $child) {
            if (self::normalize(basename($child)) === self::normalize($expected)) {
                return $child;
            }
        }

        return '';
    }

    private static function images_recursive(string $path): array
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

    private static function is_noncanonical(array $local_product): bool
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

        return in_array(self::category_key($local_product['category']) . '|' . self::normalize($local_product['product']), $removed, true);
    }

    private static function match_product(array $local_product, array $wp_products): array
    {
        $local_category = self::category_key($local_product['category']);
        $local_title = self::normalize($local_product['product']);
        $local_key = self::key($local_product['product']);
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
        $local_tokens = self::tokens($local_title);
        foreach ($same_category as $wp_product) {
            $wp_title = $wp_product['normalized_title'];
            $wp_tokens = self::tokens($wp_title);

            if ($local_title === $wp_title || str_contains($local_title, $wp_title) || str_contains($wp_title, $local_title) || self::tokens_contained($local_tokens, $wp_tokens) || self::tokens_contained($wp_tokens, $local_tokens)) {
                $candidates[] = $wp_product;
            }
        }

        if (count($candidates) === 1) {
            return ['post_id' => (int) $candidates[0]['post_id'], 'slug' => $candidates[0]['slug'], 'reason' => 'matched_partial', 'candidates' => [$candidates[0]]];
        }

        return ['post_id' => 0, 'slug' => '', 'reason' => empty($candidates) ? 'no_match' : 'ambiguous_match', 'candidates' => $candidates];
    }

    private static function import_image(array $image, array $local_product, string $product_slug, int $index, bool $dry_run, array &$report): int
    {
        $source_key = $local_product['category'] . '/' . $local_product['product'] . '/' . $image['relative_path'];
        $extension = strtolower(pathinfo($image['path'], PATHINFO_EXTENSION));
        $filename = self::seo_filename($local_product['category'], $local_product['product'], $image['slot'], $extension, (int) $image['slot_index']);
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
                self::ensure_attachment_filename((int) $existing[0], $filename, $report);
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

    private static function relative_path(string $base, string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $base), '/') . '/';
        $path = str_replace('\\', '/', $path);
        return str_starts_with($path, $base) ? substr($path, strlen($base)) : basename($path);
    }

    private static function seo_filename(string $category, string $product, string $slot, string $extension, int $slot_index): string
    {
        $slot_label = preg_replace('/^(\d)-/', '$1-', $slot) ?: $slot;
        $slot_label = preg_replace('/^(\d)-/', '0$1-', $slot_label) ?: $slot_label;
        $prefix = self::slug($category . '-' . $product . '-galeria-' . $slot_label);
        $suffix = $slot_index === 1 ? '' : '-' . str_pad((string) $slot_index, 2, '0', STR_PAD_LEFT);
        return $prefix . $suffix . '.' . $extension;
    }

    private static function ensure_attachment_filename(int $attachment_id, string $filename, array &$report): void
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

    private static function category_key(string $value): string
    {
        $key = self::key($value);
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

    private static function key(string $value): string
    {
        return str_replace(' ', '', self::normalize($value));
    }

    private static function normalize(string $value): string
    {
        return str_replace('-', ' ', self::slug($value));
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

    private static function tokens(string $value): array
    {
        return array_values(array_filter(explode(' ', $value), function (string $token): bool {
            return $token !== '';
        }));
    }

    private static function tokens_contained(array $needles, array $haystack): bool
    {
        if (empty($needles) || empty($haystack)) {
            return false;
        }

        return count(array_diff($needles, $haystack)) === 0;
    }
}
