<?php
// ubicacion_get.php — el cliente consulta la posición del repartidor por su código
header('Content-Type: application/json');
require_once 'includes/cabeceras.php';
require_once 'config/conexion.php';

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $st = $pdo->prepare(
        "SELECT estado, lat, lng,
                TIMESTAMPDIFF(SECOND, ubicacion_actualizada, NOW()) AS hace_seg
         FROM solicitudes WHERE codigo = :c LIMIT 1"
    );
    $st->execute([':c' => $codigo]);
    $r = $st->fetch();

    if (!$r) { echo json_encode(['ok' => false]); exit; }

    echo json_encode([
        'ok'       => true,
        'estado'   => $r['estado'],
        'lat'      => $r['lat'] !== null ? (float)$r['lat'] : null,
        'lng'      => $r['lng'] !== null ? (float)$r['lng'] : null,
        'hace_seg' => $r['hace_seg'] !== null ? (int)$r['hace_seg'] : null,
    ]);
} catch (\PDOException $e) {
    morir_con_error_json('ubicacion.get', $e);
}
