<?php
// modulos/solicitudes/contar_nuevas.php — devuelve cuántas solicitudes están "nuevas"
header('Content-Type: application/json');
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

try {
    $n = (int)$pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado = 'nueva'")->fetchColumn();
    echo json_encode(['ok' => true, 'nuevas' => $n]);
} catch (\PDOException $e) {
    echo json_encode(['ok' => false, 'nuevas' => 0]);
}
