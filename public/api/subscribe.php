<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

$config = load_config();
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$source = trim((string) ($_POST['source'] ?? 'newsletter'));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Ingresa un correo valido.']);
    exit;
}

if (!rate_limit_allows($email)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'Intenta de nuevo en unos minutos.']);
    exit;
}

$subject = 'Tu oferta de hasta 50% en Milapro Home';
$textBody = "Hola,\n\nGracias por suscribirte a Milapro Home.\n\nRecibimos tu correo para enviarte novedades, promociones y beneficios especiales. Nuestra oferta actual incluye descuentos de hasta 50% en referencias seleccionadas.\n\nSi quieres recibir asesoria personalizada, responde este correo y nuestro equipo comercial te ayudara.\n\nMilapro Home\nMuebles para exterior y proyectos a la medida";
$htmlBody = '<!doctype html><html lang="es"><body style="margin:0;background:#f6f3ef;font-family:Arial,sans-serif;color:#231f1c;">'
    . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f3ef;padding:28px 12px;"><tr><td align="center">'
    . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;">'
    . '<tr><td style="padding:30px 28px 12px;"><p style="margin:0 0 10px;color:#8b6b4a;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Milapro Home</p>'
    . '<h1 style="margin:0;font-size:32px;line-height:1.05;color:#231f1c;">Gracias por suscribirte</h1></td></tr>'
    . '<tr><td style="padding:6px 28px 30px;font-size:16px;line-height:1.55;color:#4e4740;">'
    . '<p>Recibimos tu correo para enviarte novedades, promociones y beneficios especiales.</p>'
    . '<p style="font-size:24px;line-height:1.2;font-weight:800;color:#231f1c;">Oferta actual: hasta 50% en referencias seleccionadas.</p>'
    . '<p>Si quieres recibir asesoria personalizada, responde este correo y nuestro equipo comercial te ayudara.</p>'
    . '<p style="margin-top:24px;">Milapro Home<br>Muebles para exterior y proyectos a la medida</p>'
    . '</td></tr></table></td></tr></table></body></html>';

try {
    smtp_send($config, $email, $subject, $textBody, $htmlBody, $source);
    newsletter_log($config, 'sent', ['to' => $email, 'source' => $source]);
    echo json_encode(['ok' => true, 'message' => 'Gracias por suscribirte. Revisa tu correo.']);
} catch (Throwable $error) {
    error_log('Newsletter SMTP error: ' . $error->getMessage());
    newsletter_log($config, 'error', ['to' => $email, 'source' => $source, 'error' => $error->getMessage()]);
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No pudimos enviar el correo. Intenta de nuevo mas tarde.']);
}

function load_config(): array
{
    $configPath = __DIR__ . '/subscribe.config.php';
    $fileConfig = file_exists($configPath) ? require $configPath : [];

    $config = array_merge([
        'smtp_host' => getenv('NEWSLETTER_SMTP_HOST') ?: '',
        'smtp_port' => (int) (getenv('NEWSLETTER_SMTP_PORT') ?: 465),
        'smtp_secure' => getenv('NEWSLETTER_SMTP_SECURE') ?: 'ssl',
        'smtp_user' => getenv('NEWSLETTER_SMTP_USER') ?: '',
        'smtp_pass' => getenv('NEWSLETTER_SMTP_PASS') ?: '',
        'from_email' => getenv('NEWSLETTER_FROM_EMAIL') ?: '',
        'from_name' => getenv('NEWSLETTER_FROM_NAME') ?: 'Milapro Home',
        'bcc_email' => getenv('NEWSLETTER_BCC_EMAIL') ?: '',
        'debug_log' => getenv('NEWSLETTER_DEBUG_LOG') === 'true',
    ], is_array($fileConfig) ? $fileConfig : []);

    foreach (['smtp_host', 'smtp_user', 'smtp_pass', 'from_email'] as $key) {
        if (trim((string) ($config[$key] ?? '')) === '') {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'El envio de correos no esta configurado.']);
            exit;
        }
    }

    return $config;
}

function rate_limit_allows(string $email): bool
{
    $key = preg_replace('/[^a-z0-9_.-]/i', '_', hash('sha256', $email));
    $path = sys_get_temp_dir() . '/milapro_newsletter_' . $key;
    $now = time();

    if (file_exists($path) && $now - (int) file_get_contents($path) < 60) {
        return false;
    }

    file_put_contents($path, (string) $now, LOCK_EX);
    return true;
}

function smtp_send(array $config, string $to, string $subject, string $textBody, string $htmlBody, string $source): void
{
    $host = (string) $config['smtp_host'];
    $port = (int) $config['smtp_port'];
    $secure = strtolower((string) $config['smtp_secure']);
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

    $socket = stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        throw new RuntimeException("SMTP connection failed: $errstr ($errno)");
    }

    stream_set_timeout($socket, 20);
    smtp_expect($socket, 220);
    smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'milaprohome.com'), 250);

    if ($secure === 'tls') {
        smtp_command($socket, 'STARTTLS', 220);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Could not enable TLS.');
        }
        smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'milaprohome.com'), 250);
    }

    smtp_command($socket, 'AUTH LOGIN', 334);
    smtp_command($socket, base64_encode((string) $config['smtp_user']), 334);
    smtp_command($socket, base64_encode((string) $config['smtp_pass']), 235);
    smtp_command($socket, 'MAIL FROM:<' . (string) $config['from_email'] . '>', 250);
    smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);

    $bcc = trim((string) ($config['bcc_email'] ?? ''));
    if ($bcc !== '' && filter_var($bcc, FILTER_VALIDATE_EMAIL) && strtolower($bcc) !== strtolower($to)) {
        smtp_command($socket, 'RCPT TO:<' . $bcc . '>', [250, 251]);
    }
    smtp_command($socket, 'DATA', 354);

    $boundary = 'milapro_' . bin2hex(random_bytes(12));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . encode_header((string) $config['from_name']) . ' <' . (string) $config['from_email'] . '>',
        'Reply-To: ' . (string) $config['from_email'],
        'To: <' . $to . '>',
        'Subject: ' . $encodedSubject,
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@milaprohome.com>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'X-Milapro-Source: ' . preg_replace('/[^a-z0-9_-]/i', '', $source),
    ];

    $message = implode("\r\n", $headers)
        . "\r\n\r\n--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
        . normalize_smtp_body($textBody)
        . "\r\n\r\n--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
        . normalize_smtp_body($htmlBody)
        . "\r\n\r\n--$boundary--\r\n.";

    smtp_command($socket, $message, 250);
    smtp_command($socket, 'QUIT', 221);
    fclose($socket);
}

function smtp_command($socket, string $command, $expected): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $expected);
}

function smtp_expect($socket, $expected): string
{
    $expectedCodes = is_array($expected) ? $expected : [$expected];
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (preg_match('/^\\d{3} /', $line)) {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
    }

    return $response;
}

function encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function normalize_smtp_body(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = preg_replace('/^\./m', '..', $body);
    return str_replace("\n", "\r\n", $body);
}

function newsletter_log(array $config, string $event, array $context): void
{
    if (empty($config['debug_log'])) {
        return;
    }

    $entry = [
        'time' => date(DATE_ATOM),
        'event' => $event,
        'context' => $context,
    ];

    file_put_contents(__DIR__ . '/subscribe-debug.log', json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}
