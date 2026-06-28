<?php
// config/conexion.php
require_once __DIR__ . '/entorno.php';

$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones ante errores SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Resultados como arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Prepares reales (más seguro)
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    error_log('Error de conexión a la BD: ' . $e->getMessage());
    if (APP_DEBUG) {
        die('Error crítico de conexión a la base de datos: ' . $e->getMessage());
    }
    http_response_code(503);
    die('El servicio no está disponible en este momento. Intenta nuevamente más tarde.');
}
