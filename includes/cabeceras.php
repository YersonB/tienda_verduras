<?php
// includes/cabeceras.php
// Cabeceras HTTP defensivas. Incluir al inicio de cada punto de entrada.

if (!headers_sent()) {
    // Evita que el navegador adivine el tipo MIME
    header('X-Content-Type-Options: nosniff');
    // Evita que el sitio sea embebido en iframes (clickjacking)
    header('X-Frame-Options: DENY');
    // Limita la información de referer enviada a terceros
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Desactiva APIs sensibles del navegador que no usamos
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // CSP: se permiten los CDNs que el proyecto usa (Bootstrap, Bootstrap Icons, Chart.js).
    // 'unsafe-inline' es necesario porque hay <script> y onclick embebidos en las vistas.
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
         . "font-src 'self' https://cdn.jsdelivr.net; "
         . "img-src 'self' data:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none'; "
         . "base-uri 'self'; "
         . "form-action 'self'";
    header("Content-Security-Policy: $csp");
}
