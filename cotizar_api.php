<?php
// cotizar_api.php — cotizador público para el cliente (sin login)
header('Content-Type: application/json');
require_once 'includes/cabeceras.php';   // entorno + errores
require_once 'includes/csrf.php';
require_once 'config/conexion.php';
require_once 'includes/interpretar.php';

csrf_verify_json();

$in = json_decode(file_get_contents('php://input'), true);
$texto = trim($in['texto'] ?? '');

if (mb_strlen($texto) > 3000) {
    echo json_encode(['ok' => false, 'msg' => 'La lista es demasiado larga.']);
    exit;
}

try {
    echo json_encode(['ok' => true, 'lineas' => cot_interpretar($pdo, $texto)]);
} catch (\PDOException $e) {
    morir_con_error_json('interpretar.publico', $e);
}
