<?php
// 1. Incluir la conexión a la base de datos
require_once '../../config/conexion.php';

$mensaje = "";
$tipo_alerta = "";

// 2. Procesar el formulario cuando se envía (Método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar y recibir los datos del formulario
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $precio_compra = trim($_POST['precio_compra']);
    $precio_venta = trim($_POST['precio_venta']);
    $stock = trim($_POST['stock']);
    $unidad_medida = trim($_POST['unidad_medida']);

    // Validar que los campos obligatorios no estén vacíos
    if (empty($nombre) || empty($categoria) || empty($precio_compra) || empty($precio_venta) || empty($unidad_medida)) {
        $mensaje = "Por favor, complete todos los campos obligatorios.";
        $tipo_alerta = "danger";
    } else {
        try {
            // Preparar la consulta SQL de inserción para evitar Inyección SQL
            $sql = "INSERT INTO productos (nombre, categoria, precio_compra, precio_venta, stock, unidad_medida) 
                    VALUES (:nombre, :categoria, :precio_compra, :precio_venta, :stock, :unidad_medida)";
            
            $stmt = $pdo->prepare($sql);
            
            // Ejecutar pasando los valores
            $resultado = $stmt->execute([
                ':nombre' => $nombre,
                ':categoria' => $categoria,
                ':precio_compra' => $precio_compra,
                ':precio_venta' => $precio_venta,
                ':stock' => !empty($stock) ? $stock : 0.000, // Si está vacío, por defecto 0
                ':unidad_medida' => $unidad_medida
            ]);

            if ($resultado) {
                // Redireccionar al inventario con un mensaje de éxito (opcional, pero recomendado)
                // Para este ejemplo, mostraremos el mensaje en la misma pantalla antes de irnos.
                $mensaje = "¡Producto registrado exitosamente!";
                $tipo_alerta = "success";
                
                // Limpiar campos para que no se queden en el formulario tras el éxito
                $nombre = $categoria = $precio_compra = $precio_venta = $stock = $unidad_medida = "";
            }
        } catch (\PDOException $e) {
            $mensaje = "Error al registrar el producto: " . $e->getMessage();
            $tipo_alerta = "danger";
        }
    }
}

// 3. Incluir el encabezado de la página
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
            <div class="card-header bg-success text-white py-3">
                <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Registrar Nueva Verdura / Fruta</h4>
            </div>
            <div class="card-body p-4">
                <form action="crear.php" method="POST">
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="nombre" class="form-label fw-semibold">Nombre / Variedad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Tomate Marzano, Papa Amarilla" value="<?= isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="categoria" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="" selected disabled>Seleccione...</option>
                                <option value="Verduras" <?= (isset($categoria) && $categoria == 'Verduras') ? 'selected' : ''; ?>>Verduras</option>
                                <option value="Frutas" <?= (isset($categoria) && $categoria == 'Frutas') ? 'selected' : ''; ?>>Frutas</option>
                                <option value="Tubérculos" <?= (isset($categoria) && $categoria == 'Tubérculos') ? 'selected' : ''; ?>>Tubérculos</option>
                                <option value="Hierbas" <?= (isset($categoria) && $categoria == 'Hierbas') ? 'selected' : ''; ?>>Hierbas / Aromáticas</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="unidad_medida" class="form-label fw-semibold">Unidad de Medida <span class="text-danger">*</span></label>
                            <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                                <option value="" selected disabled>Seleccione...</option>
                                <option value="kg" <?= (isset($unidad_medida) && $unidad_medida == 'kg') ? 'selected' : ''; ?>>Kilogramo (kg)</option>
                                <option value="unidad" <?= (isset($unidad_medida) && $unidad_medida == 'unidad') ? 'selected' : ''; ?>>Unidad</option>
                                <option value="atado" <?= (isset($unidad_medida) && $unidad_medida == 'atado') ? 'selected' : ''; ?>>Atado</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="precio_compra" class="form-label fw-semibold">Precio de Compra <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="precio_compra" name="precio_compra" placeholder="0.00" value="<?= isset($precio_compra) ? htmlspecialchars($precio_compra) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="precio_venta" class="form-label fw-semibold">Precio de Venta <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/.</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="precio_venta" name="precio_venta" placeholder="0.00" value="<?= isset($precio_venta) ? htmlspecialchars($precio_venta) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="stock" class="form-label fw-semibold">Stock Inicial Inicial</label>
                            <input type="number" step="0.001" min="0" class="form-control" id="stock" name="stock" placeholder="0.000" value="<?= isset($stock) ? htmlspecialchars($stock) : ''; ?>">
                            <div class="form-text">Puede usar hasta 3 decimales (ej: 10.500).</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-light border me-md-2">Limpiar Campos</button>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save me-2"></i>Guardar Producto
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php
// 4. Incluir el pie de página
require_once '../../includes/footer.php';
?>