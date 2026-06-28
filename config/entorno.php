<?php
// config/entorno.php — configuración según el entorno (local o nube)
// Se carga al inicio de la app. Lee variables de entorno o un archivo .env.

// ── Cargar archivo .env si existe (formato CLAVE=valor por línea) ────────────
$__envFile = __DIR__ . '/../.env';
if (is_file($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        $k = trim($k);
        $v = trim(trim($v), "\"'");
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

function env(string $clave, $default = null) {
    // Prioriza el valor cargado desde .env (no depende de putenv/getenv,
    // que algunos hostings gratuitos restringen).
    if (array_key_exists($clave, $_ENV)) {
        return $_ENV[$clave];
    }
    $v = getenv($clave);
    return $v === false ? $default : $v;
}

// ── Entorno general ─────────────────────────────────────────────────────────
define('APP_ENV', env('APP_ENV', 'local'));                 // 'local' o 'production'
define('APP_DEBUG', filter_var(env('APP_DEBUG', APP_ENV === 'local' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN));

// Prefijo de rutas. En XAMPP local: /tienda_verduras. En la nube (raíz del dominio): vacío.
define('BASE_URL', rtrim((string) env('BASE_URL', '/tienda_verduras'), '/'));

// ── Base de datos ───────────────────────────────────────────────────────────
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'tienda_verduras'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// ── Zona horaria (Perú por defecto) ─────────────────────────────────────────
date_default_timezone_set(env('APP_TZ', 'America/Lima'));

// ── Fechas en español (reemplazo de strftime, deprecado) ────────────────────
function fecha_larga(?int $ts = null): string
{
    $ts = $ts ?? time();
    $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return $dias[(int)date('w', $ts)] . ' ' . date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

function mes_anio(?int $ts = null): string
{
    $ts = $ts ?? time();
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

// ── Manejo de errores según entorno ─────────────────────────────────────────
error_reporting(E_ALL);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ── Endurecer las cookies de sesión (antes de iniciarla) ────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
}
