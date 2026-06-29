<?php
// modulos/solicitudes/ubicacion_set.php — el repartidor envía su ubicación GPS
header('Content-Type: application/json');
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
csrf_verify_json();

$in  = json_decode(file_get_contents('php://input'), true);
$id  = (int)($in['id'] ?? 0);
$lat = isset($in['lat']) ? (float)$in['lat'] : null;
$lng = isset($in['lng']) ? (float)$in['lng'] : null;

// Validar id y rango de coordenadas
if ($id <= 0 || $lat === null || $lng === null
    || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['ok' => false, 'msg' => 'Datos de ubicación inválidos.']);
    exit;
}

try {
    // Guarda la posición y marca "en camino" (salvo que ya esté entregada/cancelada)
    $upd = $pdo->prepare(
        "UPDATE solicitudes
            SET lat = :lat, lng = :lng, ubicacion_actualizada = NOW(),
                estado = IF(estado IN ('entregada','cancelada'), estado, 'en_camino')
          WHERE id = :id"
    );
    $upd->execute([':lat' => $lat, ':lng' => $lng, ':id' => $id]);
    echo json_encode(['ok' => true]);
} catch (\PDOException $e) {
    morir_con_error_json('ubicacion.set', $e);
}
