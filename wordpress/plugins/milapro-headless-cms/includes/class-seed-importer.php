<?php

if (!defined('ABSPATH')) {
    exit;
}

class Milapro_Seed_Importer
{
    public const OPTION_STATE = 'milapro_seed_import_state';
    private $seed;
    private $report;
    private $media;
    private $options;

    public function __construct(array $seed, array $report = [], array $options = [])
    {
        $this->seed = $seed;
        $this->report = wp_parse_args($report, self::empty_report());
        $this->options = wp_parse_args($options, ['skip_media' => false]);
        $this->media = new Milapro_Seed_Media_Importer($this->report, (bool) $this->options['skip_media']);
    }

    public static function empty_report(): array
    {
        return [
            'created' => ['categories' => 0, 'products' => 0, 'reels' => 0, 'blogs' => 0],
            'updated' => ['categories' => 0, 'products' => 0, 'reels' => 0, 'blogs' => 0],
            'skipped' => ['categories' => 0, 'products' => 0, 'reels' => 0, 'blogs' => 0],
            'errors' => [],
            'images_imported' => 0,
            'images_failed' => 0,
            'images_skipped' => 0,
        ];
    }

    public static function bool($value): int
    {
        return $value ? 1 : 0;
    }

    public function import_all(): array
    {
        foreach (['categories', 'products', 'reels', 'blogs'] as $step) {
            $offset = 0;
            do {
                $result = $this->import_batch($step, $offset, 20);
                $offset = $result['next_offset'];
            } while (!$result['done']);
        }
        flush_rewrite_rules(false);
        return $this->report;
    }

    public function import_batch(string $step, int $offset, int $limit = 5): array
    {
        $items = $this->seed[$step] ?? [];
        $batch = array_slice($items, $offset, $limit);

        foreach ($batch as $item) {
            try {
                if ($step === 'categories') $this->category($item);
                if ($step === 'products') $this->product($item);
                if ($step === 'reels') $this->reel($item);
                if ($step === 'blogs') $this->blog($item);
            } catch (Throwable $error) {
                $this->report['errors'][] = $error->getMessage();
                error_log('[Milapro seed] ' . $error->getMessage());
            }
        }

        $next = $offset + count($batch);
        return [
            'step' => $step,
            'next_offset' => $next,
            'total' => count($items),
            'done' => $next >= count($items),
            'report' => $this->report,
        ];
    }

    private function category(array $category): void
    {
        $slug = sanitize_title($category['slug'] ?? '');
        if (!$slug) {
            $this->report['skipped']['categories']++;
            return;
        }

        $term = term_exists($slug, 'product_category');
        $data = [
            'slug' => $slug,
            'description' => wp_kses_post($category['description'] ?? ''),
        ];

        if (!$term) {
            $term = wp_insert_term(sanitize_text_field($category['name'] ?? $slug), 'product_category', $data);
            $created = true;
        } else {
            $term = wp_update_term((int) $term['term_id'], 'product_category', ['name' => sanitize_text_field($category['name'] ?? $slug)] + $data);
            $created = false;
        }

        if (is_wp_error($term)) {
            throw new RuntimeException($term->get_error_message());
        }

        $term_id = (int) $term['term_id'];
        $image_id = $this->media->attachment($category['sourceImage'] ?? null, $category['name'] ?? '');
        update_term_meta($term_id, '_milapro_source_slug', $slug);
        update_term_meta($term_id, '_milapro_category_image', $image_id);
        update_term_meta($term_id, '_milapro_eyebrow', sanitize_text_field($category['eyebrow'] ?? ''));
        update_term_meta($term_id, '_milapro_display_order', (int) ($category['displayOrder'] ?? 999));
        update_term_meta($term_id, '_milapro_featured', self::bool($category['featured'] ?? true));
        $this->report[$created ? 'created' : 'updated']['categories']++;
    }

    private function product(array $product): void
    {
        $post_id = $this->upsert_post('products', $product['slug'] ?? '', [
            'post_title' => sanitize_text_field($product['name'] ?? ''),
            'post_excerpt' => wp_kses_post($product['shortDescription'] ?? ''),
            'post_content' => wp_kses_post($product['description'] ?? ''),
        ], 'products');

        if (!$post_id) return;

        $main_image_id = $this->media->attachment($product['sourceImage'] ?? null, $product['name'] ?? '');
        if ($main_image_id) set_post_thumbnail($post_id, $main_image_id);

        $gallery_ids = [];
        foreach ($product['gallery'] ?? [] as $image) {
            $image_id = $this->media->attachment((string) $image, $product['name'] ?? '');
            if ($image_id) $gallery_ids[] = $image_id;
        }

        $term_ids = [];
        foreach ($product['categories'] ?? [] as $slug) {
            $term = term_exists(sanitize_title($slug), 'product_category');
            if ($term && !is_wp_error($term)) $term_ids[] = (int) $term['term_id'];
        }
        wp_set_object_terms($post_id, $term_ids, 'product_category');

        $colors = [];
        foreach ($product['colors'] ?? [] as $color) {
            if (!is_array($color)) continue;
            $color_image_id = $this->media->attachment($color['image'] ?? null, ($product['name'] ?? '') . ' ' . ($color['name'] ?? ''));
            $color['image'] = $color_image_id ?: ($color['image'] ?? '');
            $colors[] = $color;
        }

        $this->post_meta($post_id, [
            'source_slug' => sanitize_title($product['slug'] ?? ''),
            'price' => $product['price'] ?? '',
            'compare_at_price' => $product['compareAtPrice'] ?? '',
            'sku' => sanitize_text_field($product['sku'] ?? ''),
            'available' => self::bool($product['available'] ?? true),
            'featured' => self::bool($product['featured'] ?? false),
            'sale' => self::bool($product['sale'] ?? false),
            'display_order' => (int) ($product['displayOrder'] ?? 999),
            'collection' => sanitize_text_field($product['collection'] ?? ''),
            'brand' => sanitize_text_field($product['brand'] ?? 'Milapro Home'),
            'dimensions' => sanitize_text_field($product['dimensions'] ?? ''),
            'main_image' => $main_image_id,
            'gallery_images' => array_map(function ($id) {
                return ['image' => $id];
            }, $gallery_ids),
            'colors' => $colors,
            'variants' => $product['variants'] ?? [],
            'specifications' => $product['specifications'] ?? [],
        ]);
    }

    private function reel(array $reel): void
    {
        $post_id = $this->upsert_post('reels', $reel['slug'] ?? '', [
            'post_title' => sanitize_text_field($reel['title'] ?? ''),
            'post_content' => '',
        ], 'reels');
        if (!$post_id) return;

        $cover_id = $this->media->attachment($reel['sourceImage'] ?? null, $reel['title'] ?? '');
        if ($cover_id) set_post_thumbnail($post_id, $cover_id);
        $this->post_meta($post_id, [
            'source_slug' => sanitize_title($reel['slug'] ?? ''),
            'video_url' => esc_url_raw($reel['videoUrl'] ?? ''),
            'cover_image' => $cover_id,
            'platform' => sanitize_text_field($reel['platform'] ?? 'instagram'),
            'display_order' => (int) ($reel['displayOrder'] ?? 999),
            'is_visible' => self::bool($reel['visible'] ?? true),
        ]);
    }

    private function blog(array $blog): void
    {
        $post_id = $this->upsert_post('blogs', $blog['slug'] ?? '', [
            'post_title' => sanitize_text_field($blog['title'] ?? ''),
            'post_excerpt' => wp_kses_post($blog['excerpt'] ?? ''),
        ], 'blogs');
        if (!$post_id) return;

        $image_id = $this->media->attachment($blog['sourceImage'] ?? null, $blog['title'] ?? '');
        if ($image_id) set_post_thumbnail($post_id, $image_id);
        $body_text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", (string) ($blog['content'] ?? ''));
        $this->post_meta($post_id, [
            'source_slug' => sanitize_title($blog['slug'] ?? ''),
            'blog_eyebrow' => sanitize_text_field($blog['category'] ?? 'Milapro Home'),
            'blog_intro_text' => sanitize_textarea_field($blog['excerpt'] ?? ''),
            'blog_body_text' => sanitize_textarea_field(wp_strip_all_tags($body_text)),
            'blog_image' => $image_id,
            'blog_display_order' => (int) ($blog['displayOrder'] ?? 999),
            'blog_is_visible' => self::bool($blog['visible'] ?? true),
        ]);
    }

    private function upsert_post(string $post_type, string $slug, array $post_data, string $report_key): int
    {
        $slug = sanitize_title($slug);
        if (!$slug) {
            $this->report['skipped'][$report_key]++;
            return 0;
        }

        $existing = get_page_by_path($slug, OBJECT, $post_type);
        if (!$existing) {
            $by_meta = get_posts(['post_type' => $post_type, 'meta_key' => '_milapro_source_slug', 'meta_value' => $slug, 'fields' => 'ids', 'posts_per_page' => 1]);
            $existing = $by_meta ? get_post((int) $by_meta[0]) : null;
        }

        $post_data['post_type'] = $post_type;
        $post_data['post_name'] = $slug;
        $post_data['post_status'] = 'publish';
        $result = $existing ? wp_update_post(['ID' => $existing->ID] + $post_data, true) : wp_insert_post($post_data, true);
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
        $this->report[$existing ? 'updated' : 'created'][$report_key]++;
        update_post_meta((int) $result, '_milapro_source_slug', $slug);
        return (int) $result;
    }

    private function post_meta(int $post_id, array $values): void
    {
        foreach ($values as $key => $value) {
            update_post_meta($post_id, '_milapro_' . $key, $value);
        }
    }
}
