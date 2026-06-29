<?php
// cotizar.php — cotizador público: el cliente arma su lista y ve cuánto sale
require_once 'includes/cabeceras.php';
require_once 'includes/csrf.php';
require_once 'includes/config_sitio.php';

$titulo = 'Cotiza tu pedido — ' . NEGOCIO_NOMBRE;
require_once 'includes/publico_header.php';
?>

<section class="py-5 bg-crema" style="min-height:70vh;">
    <div class="container">
        <div class="text-center mb-4">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-2">🧮 Al instante</span>
            <h1 class="display-6 fw-black">Cotiza tu pedido</h1>
            <p class="lead-soft fs-5">Pega tu lista y mira cuánto te saldría. Si te conviene, ¡seguimos con tu pedido!</p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Lista -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <label class="form-label fw-bold">Tu lista de compras</label>
                        <textarea id="lista" class="form-control mb-3" rows="9"
                            placeholder="Ej:&#10;2 kg de papa&#10;1 kg tomate&#10;6 huevos&#10;medio kilo de carne molida&#10;3 lechugas"></textarea>
                        <div class="d-grid">
                            <button class="btn btn-success btn-lg" id="btnCotizar">
                                <i class="bi bi-magic me-2"></i>Ver mi cotización
                            </button>
                        </div>
                        <p class="text-center lead-soft small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>Los precios son referenciales. Te confirmamos el total final por WhatsApp.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resultado -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Tu cotización</h5>
                        <span class="fs-5 fw-black texto-degradado">Total: <span id="total">S/. 0.00</span></span>
                    </div>
                    <div class="card-body">
                        <div id="vacio" class="text-center lead-soft py-5">
                            <div style="font-size:2.5rem;">🧺</div>
                            Pega tu lista y toca <strong>Ver mi cotización</strong>.
                        </div>

                        <div id="resultado" class="d-none">
                            <div id="items"></div>
                            <div id="sinprecio" class="mt-3"></div>

                            <div class="d-grid gap-2 mt-4">
                                <button class="btn btn-success btn-lg" id="btnContinuar">
                                    <i class="bi bi-bag-check me-2"></i>Me conviene, continuar mi pedido
                                </button>
                                <a class="btn btn-wa" id="btnWa" target="_blank" rel="noopener">
                                    <i class="bi bi-whatsapp me-2"></i>Pedir por WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const CSRF = '<?= csrf_token(); ?>';
const WA_URL = 'https://wa.me/<?= WHATSAPP_NUMERO; ?>';
let items = [];      // coincidencias con precio
let sinPrecio = [];  // líneas sin coincidencia (las conseguimos igual)

document.getElementById('btnCotizar').addEventListener('click', cotizar);

function cotizar() {
    const texto = document.getElementById('lista').value.trim();
    if (!texto) { mostrarToast('Escribe tu lista primero 🙂', 'warning'); return; }
    const btn = document.getElementById('btnCotizar');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Calculando...';

    fetch('cotizar_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ texto })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { mostrarToast(d.msg || 'No se pudo cotizar.', 'danger'); return; }
        items = []; sinPrecio = [];
        d.lineas.forEach(l => {
            if (l.matches.length) {
                items.push({ nombre: l.matches[0].nombre, cantidad: l.cantidad,
                             precio: Number(l.matches[0].precio_venta), unidad: l.matches[0].unidad_medida });
            } else {
                sinPrecio.push(l.linea);
            }
        });
        render();
    })
    .catch(() => mostrarToast('Error de conexión.', 'danger'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-magic me-2"></i>Ver mi cotización'; });
}

function render() {
    document.getElementById('vacio').style.display = (items.length || sinPrecio.length) ? 'none' : 'block';
    document.getElementById('resultado').classList.toggle('d-none', !(items.length || sinPrecio.length));

    const cont = document.getElementById('items');
    cont.innerHTML = items.map((it, i) => `
        <div class="d-flex align-items-center gap-2 border-bottom py-2">
            <div class="flex-grow-1">
                <span class="fw-semibold">${it.nombre}</span>
                <small class="text-muted d-block">S/. ${it.precio.toFixed(2)} / ${it.unidad}</small>
            </div>
            <input type="number" step="0.001" min="0" class="form-control form-control-sm text-center" style="width:80px;"
                   value="${it.cantidad}" onchange="setCant(${i}, this.value)">
            <div class="text-end fw-bold text-success" style="min-width:90px;">S/. ${(it.cantidad*it.precio).toFixed(2)}</div>
        </div>`).join('');

    const sp = document.getElementById('sinprecio');
    if (sinPrecio.length) {
        sp.innerHTML = '<div class="alert alert-light border small mb-0"><strong>Estos te los conseguimos</strong> ' +
            '(precio a confirmar): ' + sinPrecio.map(s => s.replace(/</g,'&lt;')).join(', ') + '</div>';
    } else { sp.innerHTML = ''; }

    actualizarTotal();
}

function setCant(i, v) { items[i].cantidad = parseFloat(v) || 0; render(); }

function actualizarTotal() {
    const total = items.reduce((s, it) => s + it.cantidad * it.precio, 0);
    document.getElementById('total').textContent = 'S/. ' + total.toFixed(2);
    document.getElementById('btnWa').href = WA_URL + '?text=' + encodeURIComponent(mensajeWA(total));
}

function mensajeWA(total) {
    let t = '¡Hola Julia! 🛒 Quiero hacer este pedido:\n\n';
    items.forEach(it => { t += `• ${it.cantidad} ${it.unidad} ${it.nombre} — S/. ${(it.cantidad*it.precio).toFixed(2)}\n`; });
    sinPrecio.forEach(s => { t += `• ${s} (a confirmar)\n`; });
    t += `\n*Total aprox.: S/. ${total.toFixed(2)}*\n¿Me confirmas y coordinamos la entrega? 🙏`;
    return t;
}

// Continuar: guarda la lista y va al formulario de pedido (queda registrado)
document.getElementById('btnContinuar').addEventListener('click', () => {
    try { sessionStorage.setItem('mercadito_lista', document.getElementById('lista').value.trim()); } catch (e) {}
    window.location.href = 'solicitar.php';
});

// Si el cliente escribió su lista en la landing, traerla y cotizar al instante
(function () {
    try {
        const previa = sessionStorage.getItem('mercadito_cotizar');
        if (previa) {
            document.getElementById('lista').value = previa;
            sessionStorage.removeItem('mercadito_cotizar');
            cotizar();
        }
    } catch (e) {}
})();
</script>

<?php require_once 'includes/publico_footer.php'; ?>
