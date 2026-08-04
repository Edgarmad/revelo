<?php

if (!defined('ABSPATH')) {
    exit;
}

class Milapro_Seed_Media_Importer
{
    private $report;
    private $skip_media;

    public function __construct(array &$report, bool $skip_media = false)
    {
        $this->report = &$report;
        $this->skip_media = $skip_media;
    }

    public function attachment(?string $source, string $title = ''): int
    {
        if ($this->skip_media) {
            $this->report['images_skipped']++;
            return 0;
        }

        $source = trim((string) $source);
        if ($source === '') {
            $this->report['images_skipped']++;
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
            $this->report['images_skipped']++;
            return (int) $existing[0];
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp = $this->temporary_file($source);
        if (is_wp_error($tmp)) {
            $this->report['images_failed']++;
            $this->report['errors'][] = $tmp->get_error_message();
            error_log('[Milapro seed] ' . $tmp->get_error_message());
            return 0;
        }

        $filename = basename(parse_url($source, PHP_URL_PATH) ?: $source);
        $file = [
            'name' => sanitize_file_name($filename ?: uniqid('milapro-image-', true) . '.jpg'),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file, 0, $title);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            $this->report['images_failed']++;
            $this->report['errors'][] = $attachment_id->get_error_message();
            error_log('[Milapro seed] ' . $attachment_id->get_error_message());
            return 0;
        }

        update_post_meta((int) $attachment_id, '_milapro_source_path', $source);
        $this->report['images_imported']++;

        return (int) $attachment_id;
    }

    private function temporary_file(string $source)
    {
        if (preg_match('#^https?://#i', $source)) {
            $tmp = download_url($source, 20);
            return is_wp_error($tmp) ? new WP_Error('milapro_image_download_failed', 'Image download failed: ' . $source) : $tmp;
        }

        $path = $this->local_path($source);
        if (!$path || !is_readable($path)) {
            $url = $this->fallback_url($source);
            if ($url) {
                $tmp = download_url($url, 20);
                return is_wp_error($tmp) ? new WP_Error('milapro_image_missing', 'Missing media file: ' . $source) : $tmp;
            }
            return new WP_Error('milapro_image_missing', 'Missing media file: ' . $source);
        }

        $tmp = wp_tempnam(basename($path));
        if (!$tmp || !copy($path, $tmp)) {
            return new WP_Error('milapro_image_copy_failed', 'Could not copy media file: ' . $source);
        }

        return $tmp;
    }

    private function local_path(string $source): ?string
    {
        $relative = ltrim($source, '/');
        if (str_starts_with($source, 'style:')) {
            $relative = 'style/' . substr($source, 6);
        }

        $candidates = [
            MILAPRO_HEADLESS_PLUGIN_DIR . 'seed-assets/' . $relative,
            MILAPRO_HEADLESS_PLUGIN_DIR . 'migration-public/' . ltrim($source, '/'),
            MILAPRO_HEADLESS_PLUGIN_DIR . 'migration-style-images/' . (str_starts_with($source, 'style:') ? substr($source, 6) : $relative),
            ABSPATH . 'migration-public/' . ltrim($source, '/'),
            ABSPATH . 'migration-style-images/' . (str_starts_with($source, 'style:') ? substr($source, 6) : $relative),
        ];

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function fallback_url(string $source): string
    {
        if (!str_starts_with($source, '/') || str_starts_with($source, '//')) {
            return '';
        }

        if (defined('MILAPRO_SEED_PUBLIC_BASE_URL')) {
            return rtrim((string) MILAPRO_SEED_PUBLIC_BASE_URL, '/') . $source;
        }

        $home = home_url();
        $host = wp_parse_url($home, PHP_URL_HOST);
        if (is_string($host) && str_starts_with($host, 'cms.')) {
            return str_replace('//' . $host, '//' . substr($host, 4), rtrim($home, '/')) . $source;
        }

        return rtrim($home, '/') . $source;
    }
}
