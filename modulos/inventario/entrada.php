<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

$mensaje = "";
$tipo_alerta = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $producto_id = $_POST['producto_id'] ?? '';
    $cantidad    = $_POST['cantidad'] ?? '';
    $costo       = trim($_POST['costo_unitario'] ?? '');
    $nota        = trim($_POST['nota'] ?? '');

    if ($producto_id === '' || !is_numeric($producto_id)) {
        $mensaje = "Seleccione un producto válido."; $tipo_alerta = "danger";
    } elseif (!is_numeric($cantidad) || (float)$cantidad <= 0) {
        $mensaje = "La cantidad debe ser mayor a cero."; $tipo_alerta = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // Verificar que el producto exista (bloqueo para concurrencia)
            $stmt = $pdo->prepare("SELECT id, nombre FROM productos WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$producto_id]);
            $prod = $stmt->fetch();
            if (!$prod) {
                throw new Exception("El producto no existe.");
            }

            // Registrar la entrada (trazabilidad)
            $ins = $pdo->prepare(
                "INSERT INTO entradas_stock (producto_id, usuario_id, cantidad, costo_unitario, nota)
                 VALUES (:producto_id, :usuario_id, :cantidad, :costo, :nota)"
            );
            $ins->execute([
                ':producto_id' => (int)$producto_id,
                ':usuario_id'  => (int)$_SESSION['usuario_id'],
                ':cantidad'    => (float)$cantidad,
                ':costo'       => $costo !== '' ? (float)$costo : null,
                ':nota'        => $nota !== '' ? $nota : null,
            ]);

            // Aumentar el stock del producto
            $upd = $pdo->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id");
            $upd->execute([':cantidad' => (float)$cantidad, ':id' => (int)$producto_id]);

            // Si se indicó costo, actualizar también el precio de compra de referencia
            if ($costo !== '') {
                $updCosto = $pdo->prepare("UPDATE productos SET precio_compra = :costo WHERE id = :id");
                $updCosto->execute([':costo' => (float)$costo, ':id' => (int)$producto_id]);
            }

            $pdo->commit();
            $mensaje = "Entrada registrada: +{$cantidad} a «{$prod['nombre']}»."; $tipo_alerta = "success";
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // Si la tabla entradas_stock no existe aún, avisar de forma clara
            if ($e instanceof \PDOException && $e->getCode() === '42S02') {
                $mensaje = "Falta crear la tabla 'entradas_stock'. Ejecuta sql/migraciones.sql en phpMyAdmin.";
                $tipo_alerta = "warning";
            } else {
                log_error('inventario.entrada', $e);
                $mensaje = "No se pudo registrar la entrada: " . $e->getMessage();
                $tipo_alerta = "danger";
            }
        }
    }
}

// Productos para el selector
try {
    $productos = $pdo->query(
        "SELECT id, nombre, stock, unidad_medida FROM productos ORDER BY nombre ASC"
    )->fetchAll();
} catch (\PDOException $e) {
    morir_con_error('inventario.entrada.productos', $e);
}

// Últimas entradas registradas (si la tabla existe)
$ultimas = [];
try {
    $ultimas = $pdo->query(
        "SELECT e.cantidad, e.costo_unitario, e.nota, e.fecha,
                p.nombre AS producto, p.unidad_medida,
                COALESCE(u.nombre, '—') AS usuario
         FROM entradas_stock e
         JOIN productos p ON e.producto_id = p.id
         LEFT JOIN usuarios u ON e.usuario_id = u.id
         ORDER BY e.fecha DESC LIMIT 10"
    )->fetchAll();
} catch (\PDOException $e) {
    // La tabla puede no existir todavía; se ignora silenciosamente aquí.
}

require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <h2 class="text-secondary fw-bold mb-4">
            <i class="bi bi-box-arrow-in-down me-2 text-success"></i>Ingreso de Stock (Reposición)
        </h2>

        <div class="row g-4">
            <!-- Formulario -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Registrar entrada</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($mensaje !== ''): ?>
                            <div class="alert alert-<?= $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($mensaje); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="entrada.php">
                            <?= csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Producto <span class="text-danger">*</span></label>
                                <select name="producto_id" class="form-select" required>
                                    <option value="" selected disabled>Seleccione...</option>
                                    <?php foreach ($productos as $p): ?>
                                        <option value="<?= $p['id']; ?>">
                                            <?= htmlspecialchars($p['nombre']); ?>
                                            (stock: <?= number_format($p['stock'], 3); ?> <?= $p['unidad_medida']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cantidad que ingresa <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" min="0.001" name="cantidad" class="form-control" placeholder="0.000" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Costo de compra (opcional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="number" step="0.01" min="0" name="costo_unitario" class="form-control" placeholder="0.00">
                                </div>
                                <div class="form-text">Si lo indicas, se actualiza el precio de compra del producto.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nota (opcional)</label>
                                <input type="text" name="nota" class="form-control" placeholder="Ej. Proveedor mercado mayorista" maxlength="255">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-2"></i>Registrar Entrada
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Últimas entradas -->
            <div class="col-md-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Últimas entradas</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Producto</th>
                                        <th class="text-end">Cantidad</th>
                                        <th>Fecha</th>
                                        <th>Por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ultimas)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">Sin entradas registradas aún.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($ultimas as $e): ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold">
                                                    <?= htmlspecialchars($e['producto']); ?>
                                                    <?php if ($e['nota']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($e['nota']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end text-success fw-bold">
                                                    +<?= number_format($e['cantidad'], 3); ?> <?= $e['unidad_medida']; ?>
                                                </td>
                                                <td><small><?= date('d/m/Y H:i', strtotime($e['fecha'])); ?></small></td>
                                                <td><small class="text-muted"><?= htmlspecialchars($e['usuario']); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
