<?php
// modulos/ventas/guardar_venta.php
header('Content-Type: application/json');

require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

csrf_verify_json();

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input || empty($input['productos']) || !is_array($input['productos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos de la venta incompletos o vacíos.']);
    exit;
}

// El usuario siempre viene de la sesión del servidor, nunca del cliente
$usuario_id = $_SESSION['usuario_id'];
$productos  = $input['productos'];

try {
    $pdo->beginTransaction();

    // Preparar consultas reutilizables antes del bucle
    $sqlCheckStock = "SELECT id, nombre, stock, precio_venta FROM productos WHERE id = :id FOR UPDATE";
    $stmtCheckStock = $pdo->prepare($sqlCheckStock);

    $sqlActualizarStock = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
    $stmtActualizarStock = $pdo->prepare($sqlActualizarStock);

    $sqlDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                   VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
    $stmtDetalle = $pdo->prepare($sqlDetalle);

    // Recorrer el carrito: validar stock y calcular totales con precios reales de la BD
    $total_venta = 0;
    $items_validados = [];

    foreach ($productos as $item) {
        $producto_id      = (int)$item['id'];
        $cantidad_vendida = (float)$item['cantidad'];

        if ($cantidad_vendida <= 0) {
            throw new Exception("La cantidad debe ser mayor a cero.");
        }

        // Leer precio y stock directamente de la BD (bloqueo FOR UPDATE para concurrencia)
        $stmtCheckStock->execute([':id' => $producto_id]);
        $prodBD = $stmtCheckStock->fetch();

        if (!$prodBD) {
            throw new Exception("El producto con ID {$producto_id} no existe en el inventario.");
        }

        $stock_actual = (float)$prodBD['stock'];
        if ($stock_actual < $cantidad_vendida) {
            throw new Exception(
                "Stock insuficiente para: {$prodBD['nombre']}. " .
                "Disponible: {$stock_actual}, solicitado: {$cantidad_vendida}."
            );
        }

        // El precio y subtotal se calculan desde la BD, ignorando lo que envió el cliente
        $precio_real = (float)$prodBD['precio_venta'];
        $subtotal_real = round($precio_real * $cantidad_vendida, 2);
        $total_venta += $subtotal_real;

        $items_validados[] = [
            'producto_id'     => $producto_id,
            'cantidad'        => $cantidad_vendida,
            'precio_unitario' => $precio_real,
            'subtotal'        => $subtotal_real,
        ];
    }

    // Insertar la cabecera de la venta con el total calculado en el servidor
    $sqlVenta = "INSERT INTO ventas (usuario_id, total) VALUES (:usuario_id, :total)";
    $stmtVenta = $pdo->prepare($sqlVenta);
    $stmtVenta->execute([':usuario_id' => $usuario_id, ':total' => round($total_venta, 2)]);
    $venta_id = $pdo->lastInsertId();

    // Insertar detalles y descontar stock
    foreach ($items_validados as $item) {
        $stmtDetalle->execute([
            ':venta_id'        => $venta_id,
            ':producto_id'     => $item['producto_id'],
            ':cantidad'        => $item['cantidad'],
            ':precio_unitario' => $item['precio_unitario'],
            ':subtotal'        => $item['subtotal'],
        ]);

        $stmtActualizarStock->execute([
            ':cantidad' => $item['cantidad'],
            ':id'       => $item['producto_id'],
        ]);
    }

    $pdo->commit();

    echo json_encode(['status' => 'success', 'venta_id' => $venta_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
