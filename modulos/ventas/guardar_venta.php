<?php
// modulos/ventas/guardar_venta.php

// 1. Configurar la cabecera para responder en formato JSON
header('Content-Type: application/json');

// 2. Incluir la conexión a la base de datos
require_once '../../includes/seguridad.php'; 
require_once '../../config/conexion.php';

// 3. Capturar el flujo de entrada JSON desde la petición Fetch
$inputJSON = file_get_contents('php://input');
$input = json_get_contents_array($inputJSON); // Decodificarlo como un array asociativo

// Función auxiliar para validar la decodificación de manera limpia
function json_get_contents_array($json) {
    return json_decode($json, true);
}

// 4. Validar que tengamos datos válidos
if (!$input || empty($input['productos']) || !isset($input['usuario_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Datos de la venta incompletos o vacíos.'
    ]);
    exit;
}

$usuario_id = $input['usuario_id'];
$productos  = $input['productos'];

try {
    // 5. INICIAR TRANSACCIÓN SQL
    // Esto garantiza que si un producto falla en el stock, toda la operación se cancele (Rollback)
    $pdo->beginTransaction();

    // 6. Calcular el total real en el backend por seguridad
    $total_venta = 0;
    foreach ($productos as $item) {
        $total_venta += (float)$item['subtotal'];
    }

    // 7. Insertar la cabecera de la venta
    $sqlVenta = "INSERT INTO ventas (usuario_id, total) VALUES (:usuario_id, :total)";
    $stmtVenta = $pdo->prepare($sqlVenta);
    $stmtVenta->execute([
        ':usuario_id' => $usuario_id,
        ':total'      => $total_venta
    ]);
    
    // Obtener el ID autogenerado de la venta recién creada
    $venta_id = $pdo->lastInsertId();

    // 8. Preparar las consultas para el bucle de detalles
    $sqlDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                   VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";
    $stmtDetalle = $pdo->prepare($sqlDetalle);

    $sqlCheckStock = "SELECT stock, nombre FROM productos WHERE id = :id FOR UPDATE";
    $stmtCheckStock = $pdo->prepare($sqlCheckStock);

    $sqlActualizarStock = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
    $stmtActualizarStock = $pdo->prepare($sqlActualizarStock);

    // 9. Recorrer el carrito para validar stock, guardar detalles y descontar inventario
    foreach ($productos as $item) {
        $producto_id     = $item['id'];
        $cantidad_vendida = (float)$item['cantidad'];
        $precio_unitario  = (float)$item['precio'];
        $subtotal         = (float)$item['subtotal'];

        // A. Verificar stock real en la base de datos (con bloqueo FOR UPDATE para concurrencia)
        $stmtCheckStock->execute([':id' => $producto_id]);
        $prodBD = $stmtCheckStock->fetch();

        if (!$prodBD) {
            throw new Exception("El producto con ID {$producto_id} no existe.");
        }

        $stock_actual = (float)$prodBD['stock'];

        if ($stock_actual < $cantidad_vendida) {
            throw new Exception("Stock insuficiente para: " . $prodBD['nombre'] . ". Disponible: " . $stock_actual);
        }

        // B. Insertar el registro en el detalle de la venta
        $stmtDetalle->execute([
            ':venta_id'        => $venta_id,
            ':producto_id'     => $producto_id,
            ':cantidad'        => $cantidad_vendida,
            ':precio_unitario' => $precio_unitario,
            ':subtotal'        => $subtotal
        ]);

        // C. Actualizar y restar el stock en la tabla productos
        $stmtActualizarStock->execute([
            ':cantidad' => $cantidad_vendida,
            ':id'       => $producto_id
        ]);
    }

    // 10. Si todo salió bien, confirmamos los cambios de forma permanente en la BD
    $pdo->commit();

    // Retornamos una respuesta exitosa a JavaScript
    echo json_encode([
        'status'   => 'success',
        'venta_id' => $venta_id
    ]);

} catch (Exception $e) {
    // Si algo falló en cualquier punto del bucle, cancelamos absolutamente todo
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Retornamos el error estructurado a la interfaz
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>