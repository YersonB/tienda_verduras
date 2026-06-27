<?php
// includes/login_throttle.php
// Limita intentos de login fallidos por IP usando un archivo JSON (sin dependencia de BD).

define('THROTTLE_MAX_INTENTOS', 5);          // intentos permitidos
define('THROTTLE_VENTANA', 15 * 60);         // ventana de 15 minutos
define('THROTTLE_FILE', __DIR__ . '/../logs/login_intentos.json');

function throttle_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
}

function throttle_leer(): array
{
    if (!is_file(THROTTLE_FILE)) {
        return [];
    }
    $data = json_decode(@file_get_contents(THROTTLE_FILE), true);
    return is_array($data) ? $data : [];
}

function throttle_guardar(array $data): void
{
    @file_put_contents(THROTTLE_FILE, json_encode($data), LOCK_EX);
}

/**
 * Devuelve los segundos restantes de bloqueo, o 0 si la IP puede intentar.
 */
function throttle_bloqueo_restante(): int
{
    $data  = throttle_leer();
    $ip    = throttle_ip();
    $ahora = time();

    if (empty($data[$ip])) {
        return 0;
    }

    // Conservar solo los intentos dentro de la ventana de tiempo
    $intentos = array_filter($data[$ip], fn($t) => ($ahora - $t) < THROTTLE_VENTANA);

    if (count($intentos) >= THROTTLE_MAX_INTENTOS) {
        $mas_antiguo = min($intentos);
        return (int)max(0, THROTTLE_VENTANA - ($ahora - $mas_antiguo));
    }
    return 0;
}

/**
 * Registra un intento fallido para la IP actual.
 */
function throttle_registrar_fallo(): void
{
    $data  = throttle_leer();
    $ip    = throttle_ip();
    $ahora = time();

    $data[$ip]   = $data[$ip] ?? [];
    $data[$ip][] = $ahora;
    // Limpiar intentos antiguos para que el archivo no crezca indefinidamente
    $data[$ip] = array_values(array_filter($data[$ip], fn($t) => ($ahora - $t) < THROTTLE_VENTANA));

    throttle_guardar($data);
}

/**
 * Limpia los intentos de la IP tras un login exitoso.
 */
function throttle_limpiar(): void
{
    $data = throttle_leer();
    unset($data[throttle_ip()]);
    throttle_guardar($data);
}
