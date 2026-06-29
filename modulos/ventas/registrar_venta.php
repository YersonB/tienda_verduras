<?php
// modulos/ventas/registrar_venta.php
// Crea una venta a partir de una cotización (items producto+cantidad+precio),
// descuenta stock y, si viene de una solicitud, la marca como entregada.
header('Content-Type: application/json');
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
csrf_verify_json();

$in = json_decode(file_get_contents('php://input'), true);
$items = $in['items'] ?? [];
$solicitud_id = (int)($in['solicitud_id'] ?? 0);

if (empty($items) || !is_array($items)) {
    echo json_encode(['ok' => false, 'msg' => 'No hay productos para registrar.']);
    exit;
}

// Normalizar y validar los items
$limpios = [];
$total = 0;
foreach ($items as $it) {
    $id  = (int)($it['id'] ?? 0);
    $cant = (float)($it['cantidad'] ?? 0);
    $precio = (float)($it['precio'] ?? 0);
    if ($id <= 0 || $cant <= 0 || $precio < 0) continue;
    $sub = round($precio * $cant, 2);
    $total += $sub;
    $limpios[] = ['id' => $id, 'cantidad' => $cant, 'precio' => $precio, 'subtotal' => $sub];
}

if (empty($limpios)) {
    echo json_encode(['ok' => false, 'msg' => 'Los productos no son válidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Cabecera de la venta
    $stmtV = $pdo->prepare("INSERT INTO ventas (usuario_id, total) VALUES (:uid, :total)");
    $stmtV->execute([':uid' => (int)$_SESSION['usuario_id'], ':total' => round($total, 2)]);
    $venta_id = (int)$pdo->lastInsertId();

    $stmtD = $pdo->prepare(
        "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
         VALUES (:vid, :pid, :cant, :precio, :sub)"
    );
    $stmtS = $pdo->prepare("UPDATE productos SET stock = stock - :cant WHERE id = :id");

    foreach ($limpios as $it) {
        $stmtD->execute([
            ':vid' => $venta_id, ':pid' => $it['id'], ':cant' => $it['cantidad'],
            ':precio' => $it['precio'], ':sub' => $it['subtotal'],
        ]);
        $stmtS->execute([':cant' => $it['cantidad'], ':id' => $it['id']]);
    }

    // Si vino de una solicitud, enlazarla y marcarla entregada
    if ($solicitud_id > 0) {
        $stmtSol = $pdo->prepare(
            "UPDATE solicitudes SET venta_id = :vid, estado = 'entregada' WHERE id = :sid"
        );
        $stmtSol->execute([':vid' => $venta_id, ':sid' => $solicitud_id]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'venta_id' => $venta_id]);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    morir_con_error_json('registrar_venta', $e);
}
