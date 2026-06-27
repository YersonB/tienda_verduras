<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
requerir_rol('admin');   // Solo un administrador puede anular ventas

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: historial.php");
    exit;
}
csrf_verify();

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: historial.php");
    exit;
}
$venta_id = (int)$_POST['id'];

try {
    $pdo->beginTransaction();

    // Bloquear la venta y validar su estado
    $stmt = $pdo->prepare("SELECT id, estado FROM ventas WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $venta_id]);
    $venta = $stmt->fetch();

    if (!$venta) {
        throw new Exception("La venta no existe.");
    }
    if ($venta['estado'] === 'anulada') {
        $pdo->rollBack();
        header("Location: detalle_venta.php?id={$venta_id}&status=ya_anulada");
        exit;
    }

    // Devolver el stock de cada producto del detalle
    $detalles = $pdo->prepare("SELECT producto_id, cantidad FROM detalle_ventas WHERE venta_id = :id");
    $detalles->execute([':id' => $venta_id]);

    $devolver = $pdo->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id");
    foreach ($detalles->fetchAll() as $d) {
        $devolver->execute([':cantidad' => (float)$d['cantidad'], ':id' => (int)$d['producto_id']]);
    }

    // Marcar la venta como anulada (no se borra: se conserva el histórico)
    $anular = $pdo->prepare(
        "UPDATE ventas SET estado = 'anulada', anulada_por = :uid, anulada_fecha = NOW() WHERE id = :id"
    );
    $anular->execute([':uid' => (int)$_SESSION['usuario_id'], ':id' => $venta_id]);

    $pdo->commit();
    header("Location: detalle_venta.php?id={$venta_id}&status=anulada");
    exit;

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e instanceof \PDOException && $e->getCode() === '42S22') {
        // Columna estado no existe todavía
        die("Falta ejecutar sql/migraciones.sql (columna 'estado' en ventas). Hazlo en phpMyAdmin.");
    }
    morir_con_error('ventas.anular', $e);
}
