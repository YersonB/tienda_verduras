<?php
// includes/errores.php
// Manejo centralizado de errores: registra en archivo y muestra mensaje genérico.

define('LOG_DIR', __DIR__ . '/../logs');

/**
 * Registra un error en logs/error.log con fecha, IP y mensaje.
 */
function log_error(string $contexto, \Throwable $e): void
{
    if (!is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0775, true);
    }
    $ip    = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $linea = sprintf(
        "[%s] [%s] %s | %s en %s:%d%s",
        date('Y-m-d H:i:s'),
        $ip,
        $contexto,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        PHP_EOL
    );
    @error_log($linea, 3, LOG_DIR . '/error.log');
}

/**
 * Loggea el error y termina mostrando un mensaje genérico (sin filtrar detalles internos).
 */
function morir_con_error(string $contexto, \Throwable $e, string $mensajeUsuario = "Ocurrió un error inesperado. Intenta nuevamente más tarde."): void
{
    log_error($contexto, $e);
    http_response_code(500);
    die(htmlspecialchars($mensajeUsuario));
}

/**
 * Versión JSON para endpoints Fetch.
 */
function morir_con_error_json(string $contexto, \Throwable $e, string $mensajeUsuario = "Ocurrió un error al procesar la solicitud."): void
{
    log_error($contexto, $e);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $mensajeUsuario]);
    exit;
}
