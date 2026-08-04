<?php

if (!defined('ABSPATH')) {
    exit;
}

class Milapro_Seed_Import_Admin
{
    private const OPTION_SEED_PATH = 'milapro_seed_import_path';
    private const NONCE_ACTION = 'milapro_seed_import';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_action('wp_ajax_milapro_seed_upload', [self::class, 'upload']);
        add_action('wp_ajax_milapro_seed_validate', [self::class, 'validate_ajax']);
        add_action('wp_ajax_milapro_seed_import_batch', [self::class, 'import_batch']);
    }

    public static function menu(): void
    {
        add_management_page('Importar catálogo MilaPro', 'Importar catálogo MilaPro', 'manage_options', 'milapro-seed-import', [self::class, 'render']);
    }

    public static function assets(string $hook): void
    {
        if ($hook !== 'tools_page_milapro-seed-import') {
            return;
        }

        wp_register_style('milapro-seed-import-admin', false, [], MILAPRO_HEADLESS_VERSION);
        wp_enqueue_style('milapro-seed-import-admin');
        wp_add_inline_style('milapro-seed-import-admin', '.milapro-seed-panel{max-width:980px}.milapro-seed-card{background:#fff;border:1px solid #dcdcde;padding:18px;margin:16px 0}.milapro-seed-log{background:#111;color:#f4f4f4;min-height:160px;max-height:360px;overflow:auto;padding:12px;white-space:pre-wrap}.milapro-seed-progress{height:16px;background:#dcdcde;margin:12px 0}.milapro-seed-progress span{display:block;height:100%;width:0;background:#2271b1}.milapro-seed-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.milapro-seed-status{font-weight:600;margin-top:10px}.milapro-seed-status.is-error{color:#b32d2e}.milapro-seed-status.is-ok{color:#008a20}');
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.'));
        }

        $path = self::seed_path();
        ?>
        <div class="wrap milapro-seed-panel">
            <h1>Importar catálogo MilaPro</h1>
            <p>Sube y valida <code>seed.json</code>, luego importa el contenido por lotes para evitar timeouts en hosting compartido.</p>

            <div class="milapro-seed-card">
                <h2>1. Seed</h2>
                <p>Archivo actual: <code id="milapro-seed-current"><?php echo esc_html($path ?: 'Ninguno'); ?></code></p>
                <input type="file" id="milapro-seed-file" accept="application/json,.json">
                <button type="button" class="button" id="milapro-seed-upload">Subir seed.json</button>
            </div>

            <div class="milapro-seed-card">
                <h2>2. Validación e importación</h2>
                <div class="milapro-seed-actions">
                    <button type="button" class="button" id="milapro-seed-validate">Validar seed</button>
                    <button type="button" class="button button-primary" id="milapro-seed-import">Importar seed</button>
                    <button type="button" class="button" id="milapro-seed-continue">Continuar importación</button>
                </div>
                <p>
                    <label><input type="checkbox" id="milapro-seed-skip-media" checked> Importar sin imágenes por ahora, recomendado para HostGator compartido</label>
                </p>
                <div class="milapro-seed-progress"><span id="milapro-seed-progress-bar"></span></div>
                <p class="milapro-seed-status" id="milapro-seed-status">Cargando herramienta...</p>
                <pre class="milapro-seed-log" id="milapro-seed-log">Listo.</pre>
            </div>
        </div>
        <script>
        <?php echo self::script(); ?>
        </script>
        <?php
    }

    public static function upload(): void
    {
        self::guard();

        if (empty($_FILES['seed']) || !isset($_FILES['seed']['tmp_name'])) {
            wp_send_json_error(['message' => 'No seed file received.'], 400);
        }

        $file = $_FILES['seed'];
        if (($file['size'] ?? 0) > Milapro_Seed_Validator::MAX_BYTES) {
            wp_send_json_error(['message' => 'Seed file is too large.'], 400);
        }

        $name = sanitize_file_name($file['name'] ?? 'seed.json');
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
            wp_send_json_error(['message' => 'Only JSON files are accepted.'], 400);
        }

        $uploads = wp_upload_dir();
        $dir = trailingslashit($uploads['basedir']) . 'milapro-seed';
        if (!wp_mkdir_p($dir)) {
            wp_send_json_error(['message' => 'Could not create upload directory.'], 500);
        }

        $target = trailingslashit($dir) . 'seed.json';
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            wp_send_json_error(['message' => 'Could not save seed file.'], 500);
        }

        $seed = Milapro_Seed_Validator::read_json_file($target);
        if (is_wp_error($seed)) {
            @unlink($target);
            wp_send_json_error(['message' => $seed->get_error_message()], 400);
        }

        update_option(self::OPTION_SEED_PATH, $target, false);
        delete_option(Milapro_Seed_Importer::OPTION_STATE);
        wp_send_json_success(['message' => 'Seed uploaded.', 'path' => $target]);
    }

    public static function validate_ajax(): void
    {
        self::guard();
        $seed = self::load_seed_or_error();
        wp_send_json_success(Milapro_Seed_Validator::validate($seed));
    }

    public static function import_batch(): void
    {
        self::guard();
        $seed = self::load_seed_or_error();
        $validation = Milapro_Seed_Validator::validate($seed);
        if (!$validation['valid']) {
            wp_send_json_error(['message' => 'Seed validation failed.', 'validation' => $validation], 400);
        }

        $steps = ['categories', 'products', 'reels', 'blogs'];
        $state = get_option(Milapro_Seed_Importer::OPTION_STATE, ['step_index' => 0, 'offset' => 0, 'report' => Milapro_Seed_Importer::empty_report()]);
        $reset = !empty($_POST['reset']);
        $skip_media = !empty($_POST['skip_media']);
        if ($reset) {
            $state = ['step_index' => 0, 'offset' => 0, 'report' => Milapro_Seed_Importer::empty_report(), 'skip_media' => $skip_media];
        }

        if (array_key_exists('skip_media', $state)) {
            $skip_media = (bool) $state['skip_media'];
        }

        $step_index = (int) ($state['step_index'] ?? 0);
        if ($step_index >= count($steps)) {
            flush_rewrite_rules(false);
            delete_option(Milapro_Seed_Importer::OPTION_STATE);
            wp_send_json_success(['done' => true, 'message' => 'Import completed.', 'report' => $state['report']]);
        }

        $step = $steps[$step_index];
        if (!defined('MILAPRO_IMPORTING_SEED')) {
            define('MILAPRO_IMPORTING_SEED', true);
        }

        $importer = new Milapro_Seed_Importer($seed, $state['report'] ?? [], ['skip_media' => $skip_media]);
        $result = $importer->import_batch($step, (int) ($state['offset'] ?? 0), 1);

        if ($result['done']) {
            $step_index++;
            $state['offset'] = 0;
        } else {
            $state['offset'] = $result['next_offset'];
        }
        $state['step_index'] = $step_index;
        $state['report'] = $result['report'];
        $state['skip_media'] = $skip_media;
        update_option(Milapro_Seed_Importer::OPTION_STATE, $state, false);

        $total_items = array_sum($validation['counts']);
        $done_items = 0;
        foreach ($steps as $i => $name) {
            if ($i < $step_index) $done_items += $validation['counts'][$name];
        }
        if ($step_index < count($steps)) $done_items += (int) $state['offset'];

        wp_send_json_success([
            'done' => $step_index >= count($steps),
            'step' => $step,
            'progress' => $total_items ? min(100, round(($done_items / $total_items) * 100)) : 100,
            'batch' => $result,
            'report' => $state['report'],
            'skip_media' => $skip_media,
        ]);
    }

    private static function guard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized.'], 403);
        }
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
    }

    private static function load_seed_or_error(): array
    {
        $path = self::seed_path();
        $seed = $path ? Milapro_Seed_Validator::read_json_file($path) : new WP_Error('milapro_no_seed', 'No seed file selected.');
        if (is_wp_error($seed)) {
            wp_send_json_error(['message' => $seed->get_error_message()], 400);
        }
        return $seed;
    }

    private static function seed_path(): string
    {
        $uploaded = (string) get_option(self::OPTION_SEED_PATH, '');
        if ($uploaded && is_readable($uploaded)) {
            return $uploaded;
        }

        $bundled = MILAPRO_HEADLESS_PLUGIN_DIR . 'seed/seed.json';
        if (is_readable($bundled)) {
            return $bundled;
        }

        $local = dirname(MILAPRO_HEADLESS_PLUGIN_DIR, 2) . '/migration/seed.json';
        if (wp_get_environment_type() === 'local' && is_readable($local)) {
            return $local;
        }

        return '';
    }

    private static function script(): string
    {
        $nonce = wp_create_nonce(self::NONCE_ACTION);
        $ajax = admin_url('admin-ajax.php');
        return <<<JS
(function() {
  const ajaxUrl = '{$ajax}';
  const nonce = '{$nonce}';
  const byId = (id) => document.getElementById(id);
  const status = (message, type) => {
    const el = byId('milapro-seed-status');
    if (!el) return;
    el.classList.remove('is-error', 'is-ok');
    if (type) el.classList.add('is-' + type);
    el.textContent = message;
  };
  const format = (message) => typeof message === 'string' ? message : JSON.stringify(message, null, 2);
  const log = (message) => { const el = byId('milapro-seed-log'); if (el) el.textContent = format(message); };
  const append = (message) => { const el = byId('milapro-seed-log'); if (el) el.textContent += String.fromCharCode(10) + format(message); };
  const progress = (value) => { const el = byId('milapro-seed-progress-bar'); if (el) el.style.width = value + '%'; };

  const post = (data) => fetch(ajaxUrl, {
    method: 'POST',
    credentials: 'same-origin',
    body: data instanceof FormData ? data : new URLSearchParams(data),
  }).then((response) => {
    const copy = response.clone();
    return copy.text().catch((error) => 'Could not read response body: ' + error.message).then((text) => {
    let body = text;
    try {
      body = JSON.parse(text);
    } catch (error) {}
    if (!response.ok || body.success === false) {
      throw { status: response.status, statusText: response.statusText, body, raw: text };
    }
    return body;
    });
  });

  const showError = (prefix, error) => {
    status(prefix, 'error');
    log({
      message: prefix,
      status: error && error.status ? error.status : '',
      statusText: error && error.statusText ? error.statusText : '',
      response: error && error.body ? error.body : error,
      raw: error && error.raw ? error.raw : '',
    });
  };

  status('Herramienta lista.', 'ok');

  byId('milapro-seed-upload').addEventListener('click', function() {
    const file = byId('milapro-seed-file').files[0];
    if (!file) return log('Selecciona seed.json primero.');
    const data = new FormData();
    data.append('action', 'milapro_seed_upload');
    data.append('nonce', nonce);
    data.append('seed', file);
    status('Subiendo seed...', '');
    log('Subiendo seed...');
    post(data)
      .then((response) => { byId('milapro-seed-current').textContent = response.data.path; status('Seed subido.', 'ok'); log(response.data); })
      .catch((error) => showError('Error al subir seed.', error));
  });

  byId('milapro-seed-validate').addEventListener('click', function() {
    status('Validando seed...', '');
    log('Validando seed...');
    post({ action: 'milapro_seed_validate', nonce })
      .then((response) => { status(response.data.valid ? 'Seed válido.' : 'Seed con errores.', response.data.valid ? 'ok' : 'error'); log(response.data); })
      .catch((error) => showError('Error al validar seed.', error));
  });

  byId('milapro-seed-import').addEventListener('click', function() {
    if (!confirm('Esto creará o actualizará categorías, productos, reels, posts e imágenes. ¿Continuar?')) return;
    progress(0);
    status('Importando seed...', '');
    log('Iniciando importación...');
    runBatch(true);
  });

  byId('milapro-seed-continue').addEventListener('click', function() {
    status('Continuando importación...', '');
    log('Continuando importación...');
    runBatch(false);
  });

  function runBatch(reset) {
    post({ action: 'milapro_seed_import_batch', nonce, reset: reset ? 1 : 0, skip_media: byId('milapro-seed-skip-media').checked ? 1 : 0 })
      .then((response) => {
        progress(response.data.progress || (response.data.done ? 100 : 0));
        append(response.data);
        if (!response.data.done) {
          setTimeout(() => runBatch(false), 1200);
        } else {
          progress(100);
          status('Importación completada.', 'ok');
        }
      })
      .catch((error) => showError('Error durante la importación.', error));
  }
})();
JS;
    }
}
