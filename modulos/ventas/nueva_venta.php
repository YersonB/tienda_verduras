<?php
// 1. Incluir la conexión a la base de datos
require_once '../../config/conexion.php';

// Para este ejemplo, asumiremos temporalmente que el ID del usuario/vendedor es 1 
// (el administrador que creamos por defecto). Más adelante se puede cambiar por la sesión activa.
$usuario_id = 1; 

// 2. Traer los productos con stock disponible para el selector
try {
    $sql = "SELECT id, nombre, precio_venta, stock, unidad_medida FROM productos WHERE stock > 0 ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error al cargar productos: " . $e->getMessage());
}

// 3. Incluir el encabezado de la página
require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-3">
        <h2 class="text-secondary fw-bold">
            <i class="bi bi-cart-plus me-2 text-success"></i>Nueva Venta
        </h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>Seleccionar Verdura / Fruta</h5>
            </div>
            <div class="card-body p-4">
                <form id="form-agregar-producto">
                    <div class="mb-3">
                        <label for="select-producto" class="form-label fw-semibold">Producto disponible</label>
                        <select class="form-select" id="select-producto" required>
                            <option value="" selected disabled>Elija un producto...</option>
                            <?php foreach ($productos as $prod): ?>
                                <option value="<?= $prod['id']; ?>" 
                                        data-precio="<?= $prod['precio_venta']; ?>" 
                                        data-stock="<?= $prod['stock']; ?>" 
                                        data-unidad="<?= $prod['unidad_medida']; ?>">
                                    <?= htmlspecialchars($prod['nombre']); ?> (S/. <?= number_format($prod['precio_venta'], 2); ?> x <?= $prod['unidad_medida']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Stock Actual</label>
                            <input type="text" class="form-control bg-light" id="info-stock" readonly value="0.000">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Precio Venta</label>
                            <input type="text" class="form-control bg-light" id="info-precio" readonly value="S/. 0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad-venta" class="form-label fw-semibold">Cantidad a Vender</label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0.001" class="form-control" id="cantidad-venta" placeholder="0.000" required disabled>
                            <span class="input-group-text" id="info-unidad">unid</span>
                        </div>
                        <div class="form-text">Ejemplo: Para medio kilo use 0.500.</div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold" id="btn-agregar" disabled>
                        <i class="bi bi-plus-lg me-2"></i>Agregar a la Lista
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Lista de Compra</h5>
                <span class="badge bg-light text-dark fw-bold" id="items-count">0 Productos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="min-height: 200px;">
                    <table class="table table-hover align-middle mb-0" id="tabla-carrito">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Producto</th>
                                <th class="text-end">Precio</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Quitar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="fila-vacia">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-cart shadow-sm p-3 rounded-circle bg-light fs-3 d-inline-block mb-3"></i>
                                    <p class="mb-0">El carrito está vacío. Agregue productos de la izquierda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-light border-top">
                    <div class="row justify-content-end align-items-center">
                        <div class="col-md-6 text-end">
                            <span class="fs-4 fw-normal me-3 text-muted">Total General:</span>
                            <span class="fs-3 fw-bold text-success" id="total-general">S/. 0.00</span>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-light border me-md-2" id="btn-cancelar" onclick="limpiarCarrito()">Cancelar Todo</button>
                        <button type="button" class="btn btn-primary px-5 fw-bold" id="btn-procesar-venta" disabled onclick="procesarVenta()">
                            <i class="bi bi-cash-coin me-2"></i>Procesar Venta
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
let carrito = [];
const selectProducto = document.getElementById('select-producto');
const infoStock = document.getElementById('info-stock');
const infoPrecio = document.getElementById('info-precio');
const infoUnidad = document.getElementById('info-unidad');
const cantidadVenta = document.getElementById('cantidad-venta');
const btnAgregar = document.getElementById('btn-agregar');

// 1. Detectar cambio de producto en el selector para mostrar datos informativos
selectProducto.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (this.value !== "") {
        infoStock.value = parseFloat(option.dataset.stock).toFixed(3);
        infoPrecio.value = "S/. " + parseFloat(option.dataset.precio).toFixed(2);
        infoUnidad.textContent = option.dataset.unidad;
        cantidadVenta.disabled = false;
        cantidadVenta.max = option.dataset.stock; // Evitar vender más de lo que hay
        cantidadVenta.value = "";
        btnAgregar.disabled = false;
    } else {
        resetFormIzquierdo();
    }
});

function resetFormIzquierdo() {
    infoStock.value = "0.000";
    infoPrecio.value = "S/. 0.00";
    infoUnidad.textContent = "unid";
    cantidadVenta.disabled = true;
    cantidadVenta.value = "";
    btnAgregar.disabled = true;
    selectProducto.value = "";
}

// 2. Agregar producto al carrito local
document.getElementById('form-agregar-producto').addEventListener('submit', function(e) {
    e.preventDefault();
    const option = selectProducto.options[selectProducto.selectedIndex];
    const id = selectProducto.value;
    const nombre = option.text.split('(')[0].trim();
    const precio = parseFloat(option.dataset.precio);
    const cantidad = parseFloat(cantidadVenta.value);
    const stockMax = parseFloat(option.dataset.stock);
    const unidad = option.dataset.unidad;

    if (cantidad <= 0 || isNaN(cantidad)) {
        alert("Ingrese una cantidad válida mayor a cero.");
        return;
    }

    if (cantidad > stockMax) {
        alert("No hay suficiente stock. El stock máximo disponible es: " + stockMax + " " + unidad);
        return;
    }

    // Verificar si ya existe en el carrito para sumar cantidades
    const existe = carrito.find(item => item.id === id);
    if (existe) {
        if ((existe.cantidad + cantidad) > stockMax) {
            alert("La suma de las cantidades supera el stock disponible en tienda.");
            return;
        }
        existe.cantidad += cantidad;
        existe.subtotal = existe.cantidad * existe.precio;
    } else {
        carrito.push({
            id, nombre, precio, cantidad, unidad, subtotal: cantidad * precio
        });
    }

    resetFormIzquierdo();
    actualizarTabla();
});

// 3. Renderizar y actualizar el carrito en pantalla
function actualizarTabla() {
    const tbody = document.querySelector('#tabla-carrito tbody');
    tbody.innerHTML = "";

    if (carrito.length === 0) {
        tbody.innerHTML = `
            <tr id="fila-vacia">
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-cart shadow-sm p-3 rounded-circle bg-light fs-3 d-inline-block mb-3"></i>
                    <p class="mb-0">El carrito está vacío. Agregue productos de la izquierda.</p>
                </td>
            </tr>`;
        document.getElementById('total-general').textContent = "S/. 0.00";
        document.getElementById('btn-procesar-venta').disabled = true;
        document.getElementById('items-count').textContent = "0 Productos";
        return;
    }

    let total = 0;
    carrito.forEach((item, index) => {
        total += item.subtotal;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td class="ps-3 fw-bold text-dark">${item.nombre}</td>
            <td class="text-end text-muted">S/. ${item.precio.toFixed(2)}</td>
            <td class="text-center bg-light fw-semibold">${item.cantidad.toFixed(3)} <small class="text-muted">${item.unidad}</small></td>
            <td class="text-end fw-bold text-success">S/. ${item.subtotal.toFixed(2)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="eliminarItem(${index})">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });

    document.getElementById('total-general').textContent = "S/. " + total.toFixed(2);
    document.getElementById('btn-procesar-venta').disabled = false;
    document.getElementById('items-count').textContent = carrito.length + " Producto(s)";
}

function eliminarItem(index) {
    carrito.splice(index, 1);
    actualizarTabla();
}

function limpiarCarrito() {
    if(confirm("¿Seguro que desea limpiar toda la lista actual?")) {
        carrito = [];
        actualizarTabla();
    }
}

// 4. Enviar los datos del carrito al servidor PHP para procesarlos
function procesarVenta() {
    if (carrito.length === 0) return;

    if (!confirm("¿Confirmar y procesar la venta actual?")) return;

    // Usaremos la API Fetch para enviar el array en formato JSON de forma asíncrona
    fetch('guardar_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            usuario_id: <?= $usuario_id; ?>,
            productos: carrito
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert("¡Venta registrada exitosamente! Código de Boleta: #" + data.venta_id);
            // Recargar la página para limpiar todo y actualizar el stock visual del selector
            window.location.reload();
        } else {
            alert("Error al procesar la venta: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Ocurrió un error en la comunicación de datos.");
    });
}
</script>

<?php
// 4. Incluir el pie de página
require_once '../../includes/footer.php';
?>