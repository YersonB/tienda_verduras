<?php
require_once '../../includes/seguridad.php';
require_once '../../includes/config_sitio.php';
require_once '../../config/conexion.php';

// Prefill desde una solicitud (?solicitud=ID)
$texto_inicial = '';
$cliente_tel = '';
$cliente_nombre = '';
$solicitud_id = isset($_GET['solicitud']) && is_numeric($_GET['solicitud']) ? (int)$_GET['solicitud'] : 0;
if ($solicitud_id > 0) {
    try {
        $st = $pdo->prepare("SELECT nombre, telefono, lista_libre FROM solicitudes WHERE id = :id");
        $st->execute([':id' => (int)$_GET['solicitud']]);
        if ($s = $st->fetch()) {
            $texto_inicial  = (string)$s['lista_libre'];
            $cliente_tel    = preg_replace('/\D/', '', $s['telefono']);
            $cliente_nombre = $s['nombre'];
        }
    } catch (\PDOException $e) { /* ignora, formulario vacío */ }
}

require_once '../../includes/header.php';
?>

<?php panel_header('Cotizar lista del cliente', 'bi-magic', 'Pega la lista y se interpreta con tus precios',
    '<a href="../solicitudes/index.php" class="btn btn-light fw-semibold"><i class="bi bi-inboxes me-1"></i>Solicitudes</a>'
); ?>

<div class="row g-4">
    <!-- Entrada -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="section-title mb-0"><i class="bi bi-card-list me-2 text-success"></i>Lista del cliente</h5>
            </div>
            <div class="card-body">
                <textarea id="lista" class="form-control mb-3" rows="10"
                    placeholder="Pega aquí la lista, ej:&#10;2 kg de papa&#10;1 kg tomate&#10;6 huevos&#10;medio kilo de carne molida"><?= htmlspecialchars($texto_inicial); ?></textarea>
                <div class="d-grid">
                    <button class="btn btn-success btn-lg" id="btnInterpretar">
                        <i class="bi bi-magic me-2"></i>Interpretar y cotizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultado -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="section-title mb-0"><i class="bi bi-receipt me-2 text-success"></i>Cotización</h5>
                <span class="fs-5 fw-bold text-success">Total: <span id="total">S/. 0.00</span></span>
            </div>
            <div class="card-body">
                <div id="vacio" class="text-center text-muted py-5">
                    <div style="font-size:2.5rem;">🧮</div>
                    Pega una lista y toca <strong>Interpretar</strong> para ver los precios.
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 d-none" id="tabla">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center" style="width:90px;">Cant.</th>
                                <th class="text-end" style="width:110px;">P. Unit</th>
                                <th class="text-end" style="width:110px;">Subtotal</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="filas"></tbody>
                    </table>
                </div>

                <div id="acciones" class="d-none mt-4 d-flex flex-wrap gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" id="btnCopiar"><i class="bi bi-clipboard me-1"></i>Copiar</button>
                    <a class="btn btn-wa" id="btnWa" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Enviar cotización</a>
                    <button class="btn btn-success" id="btnRegistrar"><i class="bi bi-cash-coin me-1"></i>Registrar venta</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= csrf_token(); ?>';
const CLIENTE_TEL = '<?= htmlspecialchars($cliente_tel, ENT_QUOTES); ?>';
const SOLICITUD_ID = <?= $solicitud_id; ?>;
let filas = [];   // [{matches, sel, cantidad, precio, unidad}]

document.getElementById('btnInterpretar').addEventListener('click', interpretar);

function interpretar() {
    const texto = document.getElementById('lista').value.trim();
    if (!texto) { mostrarToast('Pega primero la lista del cliente.', 'warning'); return; }
    const btn = document.getElementById('btnInterpretar');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Interpretando...';

    fetch('interpretar_lista.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ texto })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { mostrarToast('No se pudo interpretar.', 'danger'); return; }
        filas = d.lineas.map(l => ({
            linea: l.linea,
            matches: l.matches,
            sel: l.matches.length ? 0 : -1,
            cantidad: l.cantidad,
            precio: l.matches.length ? Number(l.matches[0].precio_venta) : 0,
            unidad: l.matches.length ? l.matches[0].unidad_medida : ''
        }));
        render();
    })
    .catch(() => mostrarToast('Error de conexión.', 'danger'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-magic me-2"></i>Interpretar y cotizar'; });
}

function render() {
    const tbody = document.getElementById('filas');
    document.getElementById('vacio').style.display = filas.length ? 'none' : 'block';
    document.getElementById('tabla').classList.toggle('d-none', !filas.length);
    document.getElementById('acciones').classList.toggle('d-none', !filas.length);
    tbody.innerHTML = '';

    filas.forEach((f, i) => {
        const tr = document.createElement('tr');
        let opciones = '';
        f.matches.forEach((m, idx) => {
            opciones += `<option value="${idx}" ${idx === f.sel ? 'selected' : ''}>${m.nombre} (S/. ${Number(m.precio_venta).toFixed(2)})</option>`;
        });
        opciones += `<option value="-1" ${f.sel === -1 ? 'selected' : ''}>— sin coincidencia —</option>`;

        const sinMatch = f.sel === -1;
        tr.innerHTML = `
            <td>
                <select class="form-select form-select-sm" onchange="cambiarProducto(${i}, this.value)">${opciones}</select>
                <small class="text-muted">“${f.linea.replace(/"/g,'')}”</small>
            </td>
            <td><input type="number" step="0.001" min="0" class="form-control form-control-sm text-center"
                       value="${f.cantidad}" onchange="cambiarCant(${i}, this.value)"></td>
            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end"
                       value="${f.precio.toFixed(2)}" ${sinMatch ? 'disabled' : ''} onchange="cambiarPrecio(${i}, this.value)"></td>
            <td class="text-end fw-bold ${sinMatch ? 'text-muted' : 'text-success'}">${sinMatch ? '—' : 'S/. ' + (f.cantidad * f.precio).toFixed(2)}</td>
            <td class="text-center"><button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="quitar(${i})"><i class="bi bi-x"></i></button></td>`;
        tbody.appendChild(tr);
    });
    actualizarTotal();
}

function cambiarProducto(i, val) {
    val = parseInt(val);
    filas[i].sel = val;
    if (val >= 0) { filas[i].precio = Number(filas[i].matches[val].precio_venta); filas[i].unidad = filas[i].matches[val].unidad_medida; }
    render();
}
function cambiarCant(i, v) { filas[i].cantidad = parseFloat(v) || 0; render(); }
function cambiarPrecio(i, v) { filas[i].precio = parseFloat(v) || 0; render(); }
function quitar(i) { filas.splice(i, 1); render(); }

function actualizarTotal() {
    const total = filas.reduce((s, f) => s + (f.sel >= 0 ? f.cantidad * f.precio : 0), 0);
    document.getElementById('total').textContent = 'S/. ' + total.toFixed(2);
    document.getElementById('btnWa').href = enlaceWhatsApp(total);
}

function textoCotizacion(total) {
    let t = 'Cotización de tu pedido 🧺\n\n';
    filas.forEach(f => {
        if (f.sel >= 0) {
            const nombre = f.matches[f.sel].nombre;
            t += `• ${f.cantidad} ${f.unidad} ${nombre} — S/. ${(f.cantidad * f.precio).toFixed(2)}\n`;
        }
    });
    t += `\n*Total: S/. ${total.toFixed(2)}*\n¿Confirmas tu pedido? 🙏`;
    return t;
}
function enlaceWhatsApp(total) {
    const base = CLIENTE_TEL ? 'https://wa.me/' + CLIENTE_TEL : 'https://wa.me/';
    return base + '?text=' + encodeURIComponent(textoCotizacion(total));
}

document.getElementById('btnCopiar').addEventListener('click', () => {
    const total = filas.reduce((s, f) => s + (f.sel >= 0 ? f.cantidad * f.precio : 0), 0);
    navigator.clipboard.writeText(textoCotizacion(total))
        .then(() => mostrarToast('Cotización copiada', 'success'))
        .catch(() => mostrarToast('No se pudo copiar.', 'warning'));
});

// Registrar la cotización como venta (descuenta stock y cierra la solicitud)
document.getElementById('btnRegistrar').addEventListener('click', () => {
    const items = filas.filter(f => f.sel >= 0).map(f => ({
        id: f.matches[f.sel].id, cantidad: f.cantidad, precio: f.precio
    }));
    if (!items.length) { mostrarToast('No hay productos válidos para registrar.', 'warning'); return; }

    confirmarModal('¿Registrar esta cotización como una venta? Se descontará el stock.', 'Sí, registrar').then(ok => {
        if (!ok) return;
        const btn = document.getElementById('btnRegistrar');
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Registrando...';
        fetch('registrar_venta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ items, solicitud_id: SOLICITUD_ID })
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                mostrarToast('✅ Venta #' + d.venta_id + ' registrada', 'success');
                setTimeout(() => { window.location.href = 'detalle_venta.php?id=' + d.venta_id + '&print=1'; }, 800);
            } else {
                mostrarToast(d.msg || 'No se pudo registrar la venta.', 'danger');
                btn.disabled = false; btn.innerHTML = '<i class="bi bi-cash-coin me-1"></i>Registrar venta';
            }
        })
        .catch(() => { mostrarToast('Error de conexión.', 'danger'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-cash-coin me-1"></i>Registrar venta'; });
    });
});

<?php if ($texto_inicial !== ''): ?>
// Si vino de una solicitud, interpreta automáticamente al cargar
window.addEventListener('load', interpretar);
<?php endif; ?>
</script>

<?php require_once '../../includes/footer.php'; ?>
