<?php
// 1. Incluir la conexión a la base de datos de manera relativa
require_once '../../includes/seguridad.php'; // <--- NUEVO: Bloquea accesos no autorizados
require_once '../../config/conexion.php';
// 2. Hacer la consulta SQL para traer todos los productos
try {
    $sql = "SELECT * FROM productos ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error al consultar productos: " . $e->getMessage());
}

// 3. Incluir el encabezado de la página
require_once '../../includes/header.php';
?>

<div class="row mb-3 align-items-center">
    <div class="col-md-6">
        <h2 class="text-secondary fw-bold">
            <i class="bi bi-box-seam me-2 text-success"></i>Control de Inventario
        </h2>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="crear.php" class="btn btn-success shadow-sm">
            <i class="bi bi-plus-circle me-2"></i>Agregar Nueva Verdura / Fruta
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Nombre / Variedad</th>
                        <th>Categoría</th>
                        <th class="text-end">P. Compra</th>
                        <th class="text-end">P. Venta</th>
                        <th class="text-center">Stock Disponible</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($productos) > 0): ?>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td class="fw-bold ps-3 text-muted"><?= $producto['id']; ?></td>
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($producto['nombre']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-uppercase" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($producto['categoria']); ?>
                                    </span>
                                </td>
                                <td class="text-end text-muted">S/. <?= number_format($producto['precio_compra'], 2); ?></td>
                                <td class="text-end fw-bold text-success">S/. <?= number_format($producto['precio_venta'], 2); ?></td>
                                <td class="text-center">
                                    <?php if ($producto['stock'] <= 15): ?>
                                        <span class="badge bg-danger">
                                            <?= number_format($producto['stock'], 3); ?> <?= $producto['unidad_medida']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">
                                            <?= number_format($producto['stock'], 3); ?> <?= $producto['unidad_medida']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="editar.php?id=<?= $producto['id']; ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="eliminar.php?id=<?= $producto['id']; ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-emoji-frown fs-3 d-block mb-2"></i>
                                No se encontraron productos registrados en la base de datos.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 4. Incluir el pie de página
require_once '../../includes/footer.php';
?>