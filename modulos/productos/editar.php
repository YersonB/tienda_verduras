<?php
// 1. Incluir la conexión a la base de datos
require_once '../../includes/seguridad.php'; 
require_once '../../config/conexion.php';

$mensaje = "";
$tipo_alerta = "";

// 2. Verificar si viene un ID válido por la URL (Método GET)
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("Location: index.php");
    exit;
}

$id = trim($_GET['id']);

// 3. Procesar la actualización cuando se envía el formulario (Método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $precio_compra = trim($_POST['precio_compra']);
    $precio_venta = trim($_POST['precio_venta']);
    $stock = trim($_POST['stock']);
    $unidad_medida = trim($_POST['unidad_medida']);

    if (empty($nombre) || empty($categoria) || empty($precio_compra) || empty($precio_venta) || empty($unidad_medida)) {
        $mensaje = "Por favor, complete todos los campos obligatorios.";
        $tipo_alerta = "danger";
    } else {
        try {
            $sql = "UPDATE productos SET 
                        nombre = :nombre, 
                        categoria = :categoria, 
                        precio_compra = :precio_compra, 
                        precio_venta = :precio_venta, 
                        stock = :stock, 
                        unidad_medida = :unidad_medida 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                ':nombre'        => $nombre,
                ':categoria'     => $categoria,
                ':precio_compra' => $precio_compra,
                ':precio_venta'  => $precio_venta,
                ':stock'         => !empty($stock) ? $stock : 0.000,
                ':unidad_medida' => $unidad_medida,
                ':id'            => $id
            ]);

            if ($resultado) {
                $mensaje = "¡Producto actualizado exitosamente!";
                $tipo_alerta = "success";
            }
        } catch (\PDOException $e) {
            $mensaje = "Error al actualizar el producto: " . $e->getMessage();
            $tipo_alerta = "danger";
        }
    }
}

// 4. Obtener los datos actuales del producto para pintarlos en el formulario
try {
    $sql = "SELECT * FROM productos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $producto = $stmt->fetch();

    // Si el producto no existe en la BD, redirigir al inventario
    if (!$producto) {
        header("Location: index.php");
        exit;
    }
} catch (\PDOException $e) {
    die("Error al consultar el producto: " . $e->getMessage());
}

// 5. Incluir el encabezado de la página
require_once '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <div class="mb-3">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al Inventario
            </a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                <?= $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark py-3">
                <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Modificar Producto: <?= htmlspecialchars($producto['nombre']); ?></h4>
            </div>
            <div class="card-body p-4">
                <form action="editar.php?id=<?= $id; ?>" method="POST">
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="nombre" class="form-label fw-semibold">Nombre / Variedad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($producto['nombre']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="categoria" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="Verduras" <?= ($producto['categoria'] == 'Verduras') ? 'selected' : ''; ?>>Verduras</option>
                                <option value="Frutas" <?= ($producto['categoria'] == 'Frutas') ? 'selected' : ''; ?>>Frutas</option>
                                <option value="Tubérculos" <?= ($producto['categoria'] == 'Tubérculos') ? 'selected' : ''; ?>>Tubérculos</option>
                                <option value="Hierbas" <?= ($producto['categoria'] == 'Hierbas') ? 'selected' : ''; ?>>Hierbas / Aromáticas</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="unidad_medida" class="form-label fw-semibold">Unidad de Medida <span class="text-danger">*</span></label>
                            <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                                <option value="kg" <?= ($producto['unidad_medida'] == 'kg') ? 'selected' : ''; ?>>Kilogramo (kg)</option>
                                <option value="unidad" <?= ($producto['unidad_medida'] == 'unidad') ? 'selected' : ''; ?>>Unidad</option>
                                <option value="atado" <?= ($producto['unidad_medida'] == 'atado') ? 'selected' : ''; ?>>Atado</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="precio_compra" class="form-label fw-semibold">Precio de Compra <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="precio_compra" name="precio_compra" value="<?= htmlspecialchars($producto['precio_compra']); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="precio_venta" class="form-label fw-semibold">Precio de Venta <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="precio_venta" name="precio_venta" value="<?= htmlspecialchars($producto['precio_venta']); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="stock" class="form-label fw-semibold">Stock Disponible</label>
                            <input type="number" step="0.001" min="0" class="form-control" id="stock" name="stock" value="<?= htmlspecialchars($producto['stock']); ?>">
                            <div class="form-text">Útil para actualizar cuando llegue nueva mercadería.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-light border me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-warning px-4 text-dark fw-bold">
                            <i class="bi bi-arrow-clockwise me-2"></i>Actualizar Producto
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php
// 6. Incluir el pie de página
require_once '../../includes/footer.php';
?>