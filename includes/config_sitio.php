<?php
// includes/config_sitio.php — datos centrales del negocio
require_once __DIR__ . '/../config/entorno.php';

const NEGOCIO_NOMBRE   = 'El mercadito de Julia';
const NEGOCIO_LEMA     = 'Nosotros hacemos tu mercado';

// WhatsApp con código de país (Perú = 51). Número: 951 866 748
const WHATSAPP_NUMERO  = '51951866748';
const WHATSAPP_VISIBLE = '+51 951 866 748';

/**
 * Construye un enlace wa.me con un mensaje opcional pre-llenado.
 */
function whatsapp_link(string $texto = ''): string
{
    $base = 'https://wa.me/' . WHATSAPP_NUMERO;
    return $texto !== '' ? $base . '?text=' . rawurlencode($texto) : $base;
}

// Mensaje de saludo por defecto (cliente que entra directo sin lista)
function whatsapp_saludo(): string
{
    return whatsapp_link("¡Hola Julia! 🛒 Quisiera información para que hagan mi mercado. ¿Me ayudas?");
}

// Genera un código público de seguimiento, ej. "MJ7KP9Q"
function generar_codigo_seguimiento(int $largo = 6): string
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sin caracteres confusos (O,0,I,1)
    $cod = '';
    for ($i = 0; $i < $largo; $i++) {
        $cod .= $abc[random_int(0, strlen($abc) - 1)];
    }
    return 'MJ' . $cod;
}

// URL absoluta para el seguimiento (clickeable en WhatsApp)
function url_seguimiento(string $codigo): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE_URL . '/seguimiento.php?codigo=' . rawurlencode($codigo);
}
