<?php
require_once '../../includes/seguridad.php';
require_once '../../config/conexion.php';
require_once '../../includes/header.php';
?>

<?php panel_header('Actualizar precios por voz', 'bi-mic-fill', 'Dicta varios productos seguidos y guarda todo de una',
    '<a href="index.php" class="btn btn-light fw-semibold"><i class="bi bi-arrow-left me-1"></i>Inventario</a>'
); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <!-- Micrófono -->
        <div class="card shadow-sm border-0 mb-4 text-center">
            <div class="card-body p-4">
                <button id="btnMic" class="btn btn-success rounded-circle shadow"
                        style="width:120px;height:120px;font-size:3rem;">
                    <i class="bi bi-mic-fill"></i>
                </button>
                <p id="estado" class="mt-3 mb-1 fw-semibold text-muted">Toca para empezar a dictar</p>
                <p class="text-muted small mb-0">
                    Di uno tras otro: <em>"cebolla 5"</em> … <em>"tomate 3.50"</em> … <em>"papa cuatro"</em>.
                    Toca de nuevo para detener.
                </p>
                <div id="transcript" class="alert alert-light border mt-3 d-none small mb-0"></div>
            </div>
        </div>

        <!-- Modo escrito -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <label class="form-label fw-semibold small text-muted">
                    <i class="bi bi-keyboard me-1"></i>O escríbelo (Enter para agregar). Ej: "cebolla 5"
                </label>
                <div class="input-group">
                    <input type="text" id="inputManual" class="form-control" placeholder="producto y precio...">
                    <button class="btn btn-success px-3" type="button" id="btnManual"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </div>

        <!-- Lista de cambios pendientes -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="section-title mb-0"><i class="bi bi-card-checklist me-2 text-success"></i>Cambios pendientes
                    <span class="badge bg-secondary rounded-pill ms-1" id="contador">0</span>
                </h5>
                <button class="btn btn-success" id="btnGuardar" disabled>
                    <i class="bi bi-cloud-check me-1"></i>Guardar todo
                </button>
            </div>
            <div class="card-body">
                <div id="vacio" class="text-center text-muted py-4">
                    <div style="font-size:2.5rem;">🧺</div>
                    Aún no hay cambios. Dicta o escribe un producto y su precio.
                </div>
                <div id="lista"></div>
            </div>
        </div>

    </div>
</div>

<script>
const CSRF = '<?= csrf_token(); ?>';
const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;

const btnMic   = document.getElementById('btnMic');
const elEstado = document.getElementById('estado');
const elTrans  = document.getElementById('transcript');
const elLista  = document.getElementById('lista');
const elVacio  = document.getElementById('vacio');
const elCont   = document.getElementById('contador');
const btnGuardar = document.getElementById('btnGuardar');

let cambios = [];     // [{id, nombre, unidad, actual, precio}]
let escuchando = false;
let recog = null;

// ── Voz (dictado continuo) ──────────────────────────────────────────────────
function toggleMic() {
    if (!Recognition) { mostrarToast('Tu navegador no soporta voz. Usa Chrome en el celular o escribe abajo.', 'warning'); return; }
    escuchando ? detener() : iniciar();
}
function iniciar() {
    recog = new Recognition();
    recog.lang = 'es-PE';
    recog.interimResults = true;   // mostrar texto en vivo mientras hablas
    recog.continuous = true;
    escuchando = true;
    btnMic.classList.replace('btn-success', 'btn-danger');
    elEstado.textContent = '🎙️ Escuchando... dicta tus productos';

    recog.onresult = (e) => {
        let interim = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
            const res = e.results[i];
            if (res.isFinal) {
                const txt = res[0].transcript.trim();
                elTrans.classList.remove('d-none');
                elTrans.innerHTML = 'Escuché: <strong>"' + txt.replace(/</g, '&lt;') + '"</strong>';
                procesar(txt);
            } else {
                interim += res[0].transcript;
            }
        }
        if (interim) {
            elTrans.classList.remove('d-none');
            elTrans.innerHTML = '<span class="text-muted">… ' + interim.replace(/</g, '&lt;') + '</span>';
        }
    };

    recog.onerror = (e) => {
        if (e.error === 'not-allowed') {
            mostrarToast('Micrófono bloqueado. Candado 🔒 → Micrófono → Permitir, y recarga.', 'danger');
            detener();
        } else if (e.error === 'service-not-allowed') {
            mostrarToast('Tu navegador bloqueó el servicio de voz. Prueba en Google Chrome, o desactiva la "Prevención de seguimiento" para este sitio.', 'danger');
            detener();
        } else if (e.error === 'audio-capture') {
            mostrarToast('No detecto ningún micrófono conectado.', 'danger');
            detener();
        } else if (e.error === 'network') {
            mostrarToast('El servicio de voz necesita HTTPS/internet. Funcionará en tu sitio publicado (https).', 'warning');
            detener();
        } else if (e.error !== 'no-speech' && e.error !== 'aborted') {
            mostrarToast('Error de voz: ' + e.error, 'warning');
        }
        // 'no-speech' / 'aborted' se ignoran: el dictado sigue escuchando
    };

    recog.onend = () => { if (escuchando) { try { recog.start(); } catch (e) {} } }; // reinicia tras silencio

    try { recog.start(); }
    catch (e) { mostrarToast('No se pudo iniciar el micrófono. Intenta de nuevo.', 'danger'); detener(); }
}
function detener() {
    escuchando = false;
    if (recog) recog.stop();
    btnMic.classList.replace('btn-danger', 'btn-success');
    elEstado.textContent = 'Toca para empezar a dictar';
}

// ── Procesar una frase → agregar a la lista ─────────────────────────────────
function procesar(texto) {
    const { keyword, precio } = parsear(texto);
    if (!keyword) { mostrarToast('No entendí el producto. Repite, ej: "cebolla 5".', 'warning'); return; }
    fetch('actualizar_precio.php?accion=buscar&q=' + encodeURIComponent(keyword))
        .then(r => r.json())
        .then(d => {
            if (!d.ok || !d.productos.length) { mostrarToast('No encontré "' + keyword + '".', 'warning'); return; }
            const p = d.productos[0]; // mejor coincidencia
            agregarCambio(p, precio);
        })
        .catch(() => mostrarToast('Error de conexión.', 'danger'));
}

function agregarCambio(p, precioSug) {
    const precio = (precioSug !== null && precioSug !== undefined) ? precioSug : Number(p.precio_venta);
    const existente = cambios.find(c => c.id === p.id);
    if (existente) {
        existente.precio = precio;            // si repite el producto, actualiza
    } else {
        cambios.push({ id: p.id, nombre: p.nombre, unidad: p.unidad_medida, actual: Number(p.precio_venta), precio });
    }
    render();
    mostrarToast(p.nombre + ' → S/. ' + Number(precio).toFixed(2) + ' (pendiente)', 'info');
}

function quitar(id) { cambios = cambios.filter(c => c.id !== id); render(); }
function setPrecio(id, val) { const c = cambios.find(x => x.id === id); if (c) c.precio = parseFloat(val); }

function render() {
    elCont.textContent = cambios.length;
    elVacio.style.display = cambios.length ? 'none' : 'block';
    btnGuardar.disabled = cambios.length === 0;
    elLista.innerHTML = cambios.map(c => `
        <div class="d-flex flex-wrap align-items-center gap-2 border-bottom py-2">
            <div class="flex-grow-1">
                <span class="fw-semibold">${c.nombre}</span>
                <small class="text-muted">· actual S/. ${c.actual.toFixed(2)} /${c.unidad}</small>
            </div>
            <div class="input-group" style="max-width:150px;">
                <span class="input-group-text">S/.</span>
                <input type="number" step="0.01" min="0" class="form-control fw-bold text-end"
                       value="${Number(c.precio).toFixed(2)}" onchange="setPrecio(${c.id}, this.value)">
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="quitar(${c.id})"><i class="bi bi-x-lg"></i></button>
        </div>`).join('');
}

// ── Guardar todo ────────────────────────────────────────────────────────────
btnGuardar.addEventListener('click', () => {
    if (!cambios.length) return;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
    fetch('actualizar_precio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ cambios: cambios.map(c => ({ id: c.id, precio: c.precio })) })
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            mostrarToast('✅ ' + d.actualizados + ' precio(s) actualizado(s)', 'success');
            cambios = []; render();
        } else {
            mostrarToast(d.msg || 'No se pudo guardar.', 'danger');
        }
    })
    .catch(() => mostrarToast('Error de conexión.', 'danger'))
    .finally(() => { btnGuardar.innerHTML = '<i class="bi bi-cloud-check me-1"></i>Guardar todo'; btnGuardar.disabled = cambios.length === 0; });
});

// ── Parseo de texto ─────────────────────────────────────────────────────────
function parsear(texto) {
    let t = ' ' + texto.toLowerCase().trim() + ' ';
    t = t.replace(/,/g, '.');
    const precio = extraerPrecio(t);
    const stop = new Set(['el','la','los','las','de','del','un','una','unos','unas','es','son','a','al',
        'precio','precios','sol','soles','nuevo','nueva','pon','poner','ponle','ponme','cambia','cambiar',
        'cambiale','actualiza','actualizar','vale','cuesta','ahora','por','kilo','kilos','esta','está',
        'queda','sube','baja','y','con','punto','coma','medio','su','que','este','para']);
    const palabras = t.replace(/[0-9.]+/g, ' ').split(/\s+/).filter(w => w && !stop.has(w));
    return { keyword: palabras.join(' ').trim(), precio };
}
function extraerPrecio(t) {
    const mapa = {cero:0,un:1,uno:1,una:1,dos:2,tres:3,cuatro:4,cinco:5,seis:6,siete:7,ocho:8,nueve:9,
        diez:10,once:11,doce:12,trece:13,catorce:14,quince:15,dieciseis:16,'dieciséis':16,diecisiete:17,
        dieciocho:18,diecinueve:19,veinte:20,veinticinco:25,treinta:30,cuarenta:40,cincuenta:50,
        sesenta:60,setenta:70,ochenta:80,noventa:90,cien:100};
    let m = t.match(/(\d+)\s*(?:con|punto|\.)\s*(\d+)/);
    if (m) return parseFloat(m[1] + '.' + m[2]);
    m = t.match(/(\d+\.\d{1,2})/); if (m) return parseFloat(m[1]);
    m = t.match(/(\d+)/); let entero = m ? parseInt(m[1], 10) : null;
    if (entero === null) {
        const w = t.split(/\s+/);
        for (let i = 0; i < w.length; i++) {
            if (mapa[w[i]] !== undefined) {
                let v = mapa[w[i]];
                if (v % 10 === 0 && w[i+1] === 'y' && mapa[w[i+2]] !== undefined) v += mapa[w[i+2]];
                entero = v; break;
            }
        }
    }
    let dec = /\bmedio\b/.test(t) ? 0.5 : 0;
    if (entero === null) return dec ? dec : null;
    return entero + dec;
}

// ── Eventos ─────────────────────────────────────────────────────────────────
btnMic.addEventListener('click', toggleMic);
document.getElementById('btnManual').addEventListener('click', () => {
    const v = document.getElementById('inputManual').value.trim();
    if (v) { procesar(v); document.getElementById('inputManual').value = ''; }
});
document.getElementById('inputManual').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnManual').click(); }
});
if (!Recognition) {
    elEstado.innerHTML = '<span class="text-warning">Tu navegador no soporta voz. Usa <strong>Chrome</strong> o <strong>Edge</strong>, o el modo escrito 👇</span>';
    btnMic.disabled = true;
    btnMic.classList.replace('btn-success', 'btn-secondary');
} else if (!window.isSecureContext) {
    elEstado.innerHTML = '<span class="text-warning">La voz necesita HTTPS (o localhost). En tu sitio publicado funcionará.</span>';
}
</script>

<?php require_once '../../includes/footer.php'; ?>
