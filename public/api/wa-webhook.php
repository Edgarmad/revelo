<?php

declare(strict_types=1);

/**
 * Puente entre los webhooks de WhatsApp (Meta) y Make.
 *
 * Meta manda la verificacion como GET con el parametro hub.challenge, que Make
 * no puede leer desde un webhook personalizado. Este archivo responde esa
 * verificacion y reenvia los eventos reales (POST) al escenario de Make.
 *
 * La configuracion vive en wa-webhook.config.php, fuera del repositorio.
 */

$config = load_config();

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    handle_verification($config);
}

if ($method === 'POST') {
    handle_event($config);
}

http_response_code(405);
header('Allow: GET, POST');
exit;

/**
 * Responde el saludo de verificacion de Meta devolviendo hub.challenge en texto
 * plano, pero solo si el token coincide con el configurado.
 */
function handle_verification(array $config): void
{
    $mode = (string) ($_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '');
    $token = (string) ($_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '');
    $challenge = (string) ($_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '');

    if ($mode !== 'subscribe' || !hash_equals($config['verify_token'], $token)) {
        http_response_code(403);
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $challenge;
    exit;
}

/**
 * Valida la firma de Meta y reenvia el cuerpo intacto al webhook de Make.
 *
 * Siempre responde 200: si Make fallara, no queremos que Meta marque el
 * endpoint como caido y deje de enviar eventos.
 */
function handle_event(array $config): void
{
    $payload = (string) file_get_contents('php://input');

    if (!signature_is_valid($config, $payload)) {
        http_response_code(403);
        exit;
    }

    $ch = curl_init($config['make_webhook_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);

    http_response_code(200);
    echo 'EVENT_RECEIVED';
    exit;
}

/**
 * Comprueba la cabecera X-Hub-Signature-256 contra el secreto de la app.
 * Sin esto, cualquiera que conozca la URL podria inyectar ecos falsos y
 * silenciar el bot para numeros arbitrarios.
 */
function signature_is_valid(array $config, string $payload): bool
{
    $secret = trim((string) ($config['app_secret'] ?? ''));

    if ($secret === '') {
        return false;
    }

    $header = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');

    if (strpos($header, 'sha256=') !== 0) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

    return hash_equals($expected, $header);
}

function load_config(): array
{
    $configPath = __DIR__ . '/wa-webhook.config.php';
    $fileConfig = file_exists($configPath) ? require $configPath : [];

    $config = array_merge([
        'make_webhook_url' => getenv('WA_MAKE_WEBHOOK_URL') ?: '',
        'verify_token' => getenv('WA_VERIFY_TOKEN') ?: '',
        'app_secret' => getenv('WA_APP_SECRET') ?: '',
    ], is_array($fileConfig) ? $fileConfig : []);

    foreach (['make_webhook_url', 'verify_token', 'app_secret'] as $key) {
        if (trim((string) ($config[$key] ?? '')) === '') {
            http_response_code(500);
            exit;
        }
    }

    return $config;
}
