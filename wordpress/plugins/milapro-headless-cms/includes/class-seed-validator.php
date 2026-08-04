<?php

if (!defined('ABSPATH')) {
    exit;
}

class Milapro_Seed_Validator
{
    public const MAX_BYTES = 15728640;

    public static function read_json_file(string $path)
    {
        if (!is_readable($path)) {
            return new WP_Error('milapro_seed_missing', 'Seed file not found or not readable.');
        }

        if (filesize($path) > self::MAX_BYTES) {
            return new WP_Error('milapro_seed_too_large', 'Seed file is too large.');
        }

        $contents = file_get_contents($path);
        $seed = json_decode((string) $contents, true);
        if (!is_array($seed) || json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('milapro_seed_invalid_json', 'Seed file is not valid JSON.');
        }

        return $seed;
    }

    public static function validate(array $seed): array
    {
        $errors = [];
        $warnings = [];
        $sections = ['categories', 'products', 'reels', 'blogs'];

        foreach ($sections as $section) {
            if (!isset($seed[$section])) {
                $warnings[] = sprintf('Missing section: %s.', $section);
                continue;
            }
            if (!is_array($seed[$section])) {
                $errors[] = sprintf('Section %s must be an array.', $section);
            }
        }

        self::require_fields($seed['categories'] ?? [], 'categories', ['slug', 'name'], $errors);
        self::require_fields($seed['products'] ?? [], 'products', ['slug', 'name', 'shortDescription', 'description', 'sku', 'price'], $errors);
        self::require_fields($seed['reels'] ?? [], 'reels', ['slug', 'title'], $errors);
        self::require_fields($seed['blogs'] ?? [], 'blogs', ['slug', 'title', 'excerpt', 'content'], $errors);

        foreach (['categories', 'products', 'reels', 'blogs'] as $section) {
            self::detect_duplicate_slugs($seed[$section] ?? [], $section, $errors);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'counts' => [
                'categories' => count($seed['categories'] ?? []),
                'products' => count($seed['products'] ?? []),
                'reels' => count($seed['reels'] ?? []),
                'blogs' => count($seed['blogs'] ?? []),
            ],
        ];
    }

    private static function require_fields(array $items, string $section, array $fields, array &$errors): void
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $errors[] = sprintf('%s[%d] must be an object.', $section, $index);
                continue;
            }
            foreach ($fields as $field) {
                if (!array_key_exists($field, $item) || $item[$field] === '') {
                    $errors[] = sprintf('%s[%d] missing required field: %s.', $section, $index, $field);
                }
            }
        }
    }

    private static function detect_duplicate_slugs(array $items, string $section, array &$errors): void
    {
        $seen = [];
        foreach ($items as $index => $item) {
            $slug = is_array($item) ? sanitize_title((string) ($item['slug'] ?? '')) : '';
            if (!$slug) {
                continue;
            }
            if (isset($seen[$slug])) {
                $errors[] = sprintf('%s[%d] has duplicate slug: %s.', $section, $index, $slug);
            }
            $seen[$slug] = true;
        }
    }
}
