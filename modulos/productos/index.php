<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';

// ── Filtros ──────────────────────────────────────────────────────────────────
$busqueda  = trim($_GET['q'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$where = "WHERE 1=1";
$params = [];
if ($busqueda !== '') {
    $where .= " AND nombre LIKE :busqueda";
    $params[':busqueda'] = '%' . $busqueda . '%';
}
if ($categoria !== '') {
    $where .= " AND categoria = :categoria";
    $params[':categoria'] = $categoria;
}

// ── Paginación ────────────────────────────────────────────────────────────────
$por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM productos $where");
    $stmtCount->execute($params);
    $total_registros = (int)$stmtCount->fetchColumn();
    $total_paginas = max(1, (int)ceil($total_registros / $por_pagina));
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

    $sql = "SELECT * FROM productos $where ORDER BY nombre ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $productos = $stmt->fetchAll();
} catch (\PDOException $e) {
    morir_con_error('productos.listar', $e);
}

function url_pagina_prod(int $pagina): string {
    $q = $_GET;
    $q['pagina'] = $pagina;
    return '?' . http_build_query($q);
}

require_once '../../includes/header.php';

$status = $_GET['status'] ?? '';
?>

<?php if ($status === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Producto eliminado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif ($status === 'error_fk'): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>No se puede eliminar este producto porque tiene ventas registradas en el historial.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif ($status === 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i>Ocurrió un error al intentar eliminar el producto.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php panel_header('Control de Inventario', 'bi-box-seam', 'Gestiona tus productos y su stock',
    '<a href="precios_voz.php" class="btn btn-light fw-semibold"><i class="bi bi-mic-fill me-1"></i>Precios por voz</a>'
  . '<a href="../inventario/entrada.php" class="btn btn-light fw-semibold"><i class="bi bi-box-arrow-in-down me-1"></i>Ingresar Stock</a>'
  . '<a href="crear.php" class="btn fw-semibold" style="background:#fde047;color:#713f12;border:none;"><i class="bi bi-plus-circle me-1"></i>Nuevo Producto</a>'
); ?>

<!-- ── Filtros ──────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold mb-1 small">Buscar por nombre</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Ej. Tomate, Papa..." value="<?= htmlspecialchars($busqueda); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold mb-1 small">Categoría</label>
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach (['Verduras','Frutas','Carnes','Abarrotes','Tubérculos','Hierbas'] as $cat): ?>
                        <option value="<?= $cat; ?>" <?= $categoria === $cat ? 'selected' : ''; ?>><?= $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm px-3">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
            </div>
        </form>
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
                                        <form method="POST" action="eliminar.php" class="d-inline"
                                              data-confirm="¿Seguro que deseas eliminar «<?= htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>»?"
                                              data-confirm-btn="Sí, eliminar">
                                            <input type="hidden" name="id" value="<?= $producto['id']; ?>">
                                            <?= csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-emoji-frown fs-3 d-block mb-2"></i>
                                <?= ($busqueda !== '' || $categoria !== '')
                                    ? 'No hay productos que coincidan con el filtro.'
                                    : 'No se encontraron productos registrados.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_registros > 0): ?>
    <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
        <small class="text-muted">
            Mostrando <?= min($offset + 1, $total_registros); ?>–<?= min($offset + $por_pagina, $total_registros); ?>
            de <strong><?= $total_registros; ?></strong> producto(s)
        </small>
        <?php if ($total_paginas > 1): ?>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= url_pagina_prod($pagina_actual - 1); ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <?php if ($p === 1 || $p === $total_paginas || abs($p - $pagina_actual) <= 2): ?>
                        <li class="page-item <?= $p === $pagina_actual ? 'active' : ''; ?>">
                            <a class="page-link" href="<?= url_pagina_prod($p); ?>"><?= $p; ?></a>
                        </li>
                    <?php elseif (abs($p - $pagina_actual) === 3): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?= url_pagina_prod($pagina_actual + 1); ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>