<?php
// modulos/ventas/interpretar_lista.php — cotizador del personal (requiere login)
header('Content-Type: application/json');
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
require_once '../../includes/interpretar.php';
csrf_verify_json();

$in = json_decode(file_get_contents('php://input'), true);
$texto = trim($in['texto'] ?? '');

try {
    echo json_encode(['ok' => true, 'lineas' => cot_interpretar($pdo, $texto)]);
} catch (\PDOException $e) {
    morir_con_error_json('interpretar.staff', $e);
}
