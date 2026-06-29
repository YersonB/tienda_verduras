<?php
// includes/cabeceras.php
// Cabeceras HTTP defensivas. Incluir al inicio de cada punto de entrada.
require_once __DIR__ . '/../config/entorno.php';
require_once __DIR__ . '/errores.php';   // log_error() disponible en todas las páginas

if (!headers_sent()) {
    // Evita que el navegador adivine el tipo MIME
    header('X-Content-Type-Options: nosniff');
    // Evita que el sitio sea embebido en iframes (clickjacking)
    header('X-Frame-Options: DENY');
    // Limita la información de referer enviada a terceros
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Permite micrófono (precios por voz) y geolocalización (mapa de delivery)
    // solo en nuestro propio sitio; cámara desactivada.
    header('Permissions-Policy: geolocation=(self), microphone=(self), camera=()');

    // CSP: se permiten los CDNs que el proyecto usa (Bootstrap, Bootstrap Icons, Chart.js).
    // 'unsafe-inline' es necesario porque hay <script> y onclick embebidos en las vistas.
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
         . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; "
         . "img-src 'self' data: https:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none'; "
         . "base-uri 'self'; "
         . "form-action 'self'";
    header("Content-Security-Policy: $csp");
}
