<?php

if (!defined('ABSPATH')) {
    exit;
}

$seed_path = '/var/www/html/migration/seed.json';
$seed = Milapro_Seed_Validator::read_json_file($seed_path);
if (is_wp_error($seed)) {
    WP_CLI::error($seed->get_error_message() . ' Run npm run wp:seed:build first.');
}

$validation = Milapro_Seed_Validator::validate($seed);
if (!$validation['valid']) {
    WP_CLI::error(implode("\n", $validation['errors']));
}

$report = (new Milapro_Seed_Importer($seed))->import_all();
WP_CLI::success(wp_json_encode($report));
