<?php

// URL del sitio donde fue subido (sin barra final)
// ⚠️ CAMBIAR a tu dominio real antes de desplegar
$site_url = "https://TU-DOMINIO.com/simulador";

// Telegram Bot Configuration
// ⚠️ CAMBIAR token y chat_id a los de tu bot nuevo
$token = "8910530226:AAFkjqMoTQQ90AZIQU5paJLG32HTOo3MYng";
$chat_id = "7655000874";

// Secret para validar el webhook de Telegram (header X-Telegram-Bot-Api-Secret-Token).
// Debe coincidir EXACTAMENTE con el secret_token usado al registrar el webhook.
$webhook_secret = "bisa_wh_" . "7c4a2f8b1e9d3056a8c2f1b4e7d09a3c";

// reCAPTCHA Configuration (opcional, no usado aún en este proyecto)
$recaptcha_site_key = "";
$recaptcha_secret_key = "";
$recaptcha_score_min = 0.2;

?>
