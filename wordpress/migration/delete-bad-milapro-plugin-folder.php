<?php
/**
 * Temporary cleanup script for a broken plugin folder created from a bad ZIP.
 *
 * Upload this file to the WordPress root, for example:
 * /cms/delete-bad-milapro-plugin-folder.php
 *
 * Then open:
 * https://your-domain.com/cms/delete-bad-milapro-plugin-folder.php?token=milapro-cleanup-2026&run=1
 *
 * Delete this file after it finishes.
 */

$token = 'milapro-cleanup-2026';

if (($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    exit('Forbidden');
}

$root = __DIR__;
$targets = [
    $root . '/wp-content/plugins/milapro-headless-cms-price-update',
];

function milapro_cleanup_remove(string $path, array &$report): void
{
    if (!file_exists($path) && !is_link($path)) {
        $report['missing'][] = $path;
        return;
    }

    if (is_file($path) || is_link($path)) {
        if (@unlink($path)) {
            $report['deleted_files'][] = $path;
        } else {
            $report['failed'][] = $path;
        }
        return;
    }

    $items = @scandir($path);
    if ($items === false) {
        $report['failed'][] = $path;
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        milapro_cleanup_remove($path . DIRECTORY_SEPARATOR . $item, $report);
    }

    if (@rmdir($path)) {
        $report['deleted_dirs'][] = $path;
    } else {
        $report['failed'][] = $path;
    }
}

$report = [
    'deleted_files' => [],
    'deleted_dirs' => [],
    'missing' => [],
    'failed' => [],
];

header('Content-Type: text/plain; charset=utf-8');

if (($_GET['run'] ?? '') !== '1') {
    echo "Dry run. Add &run=1 to delete:\n\n";
    echo implode("\n", $targets);
    exit;
}

foreach ($targets as $target) {
    milapro_cleanup_remove($target, $report);
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
