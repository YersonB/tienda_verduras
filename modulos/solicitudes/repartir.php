<?php
require_once '../../includes/seguridad.php';
require_once '../../includes/config_sitio.php';
require_once '../../config/conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

try {
    $st = $pdo->prepare("SELECT id, codigo, nombre, telefono, direccion, referencia, distrito FROM solicitudes WHERE id = :id");
    $st->execute([':id' => $id]);
    $s = $st->fetch();
    if (!$s) { header("Location: index.php"); exit; }
} catch (\PDOException $e) {
    morir_con_error('repartir.cargar', $e);
}

require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>

<?php panel_header('Reparto en vivo', 'bi-truck', 'Comparte tu ubicación con el cliente',
    '<a href="detalle.php?id=' . $id . '" class="btn btn-light fw-semibold"><i class="bi bi-arrow-left me-1"></i>Volver</a>'
); ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="section-title mb-3"><i class="bi bi-person me-2 text-success"></i><?= htmlspecialchars($s['nombre']); ?></h5>
                <p class="mb-1"><i class="bi bi-geo-alt text-danger me-2"></i><?= htmlspecialchars($s['direccion']); ?></p>
                <?php if ($s['referencia']): ?><p class="text-muted small mb-1">Ref: <?= htmlspecialchars($s['referencia']); ?></p><?php endif; ?>
                <?php if ($s['distrito']): ?><p class="text-muted small mb-2"><?= htmlspecialchars($s['distrito']); ?></p><?php endif; ?>
                <?php $wa = preg_replace('/\D/', '', $s['telefono']); ?>
                <a href="https://wa.me/<?= $wa; ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($s['telefono']); ?>
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted small mb-2">Código del cliente para seguir el pedido:</p>
                <div class="fw-black fs-4 texto-degradado mb-3"><?= htmlspecialchars($s['codigo']); ?></div>
                <button id="btnCompartir" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-broadcast me-2"></i>Compartir mi ubicación
                </button>
                <button id="btnDetener" class="btn btn-outline-danger w-100 mt-2 d-none">
                    <i class="bi bi-stop-circle me-2"></i>Detener
                </button>
                <p id="estadoGps" class="small text-muted mt-3 mb-0">Toca para empezar a transmitir tu ubicación.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div id="mapa" style="height: 460px; border-radius: 18px;"></div>
        </div>
        <p class="text-muted small mt-2">
            <i class="bi bi-info-circle me-1"></i>Mantén esta página abierta mientras manejas. El cliente verá tu punto moverse en su mapa.
        </p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
const CSRF = '<?= csrf_token(); ?>';
const SOLICITUD_ID = <?= $id; ?>;

let map, marcador, watchId = null, ultimoEnvio = 0;

// Inicializa el mapa (centro temporal en Lima hasta tener GPS)
map = L.map('mapa').setView([-12.0464, -77.0428], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
}).addTo(map);

function setEstado(txt, color) {
    const el = document.getElementById('estadoGps');
    el.textContent = txt;
    el.className = 'small mt-3 mb-0 text-' + (color || 'muted');
}

function enviarUbicacion(lat, lng) {
    fetch('ubicacion_set.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({ id: SOLICITUD_ID, lat, lng })
    }).catch(() => {});
}

function onPos(pos) {
    const { latitude: lat, longitude: lng } = pos.coords;
    const punto = [lat, lng];
    if (!marcador) {
        marcador = L.marker(punto).addTo(map).bindPopup('🛵 Tú estás aquí');
        map.setView(punto, 16);
    } else {
        marcador.setLatLng(punto);
        map.panTo(punto);
    }
    // Enviar al servidor como máximo cada 4 segundos
    const ahora = Date.now();
    if (ahora - ultimoEnvio > 4000) {
        ultimoEnvio = ahora;
        enviarUbicacion(lat, lng);
        setEstado('🟢 Transmitiendo tu ubicación...', 'success');
    }
}

function onErr(err) {
    if (err.code === 1) setEstado('Permiso de ubicación denegado. Actívalo en el candado 🔒.', 'danger');
    else setEstado('No se pudo obtener tu ubicación. Revisa el GPS.', 'danger');
}

document.getElementById('btnCompartir').addEventListener('click', () => {
    if (!('geolocation' in navigator)) { mostrarToast('Tu dispositivo no soporta GPS.', 'warning'); return; }
    setEstado('Obteniendo tu ubicación...');
    watchId = navigator.geolocation.watchPosition(onPos, onErr, {
        enableHighAccuracy: true, maximumAge: 2000, timeout: 15000
    });
    document.getElementById('btnCompartir').classList.add('d-none');
    document.getElementById('btnDetener').classList.remove('d-none');
});

document.getElementById('btnDetener').addEventListener('click', () => {
    if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    setEstado('Transmisión detenida.', 'muted');
    document.getElementById('btnDetener').classList.add('d-none');
    document.getElementById('btnCompartir').classList.remove('d-none');
});

// Ajusta el tamaño del mapa al render
setTimeout(() => map.invalidateSize(), 300);
</script>

<?php require_once '../../includes/footer.php'; ?>
