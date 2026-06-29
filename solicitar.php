<?php
// solicitar.php — formulario público para pedir el mercado
require_once 'includes/csrf.php';      // inicia sesión + CSRF
require_once 'includes/cabeceras.php';
require_once 'includes/config_sitio.php';
require_once 'config/conexion.php';

$errores = [];
$exito   = false;
$venta_ref = null;
$wa_msg   = '';   // mensaje de WhatsApp para confirmar el pedido

// Datos para repoblar el form si hay error
$old = [
    'nombre' => '', 'telefono' => '', 'email' => '', 'direccion' => '',
    'referencia' => '', 'distrito' => '', 'fecha_entrega' => '',
    'frecuencia' => 'unica', 'lista_libre' => '', 'notas' => '',
];

// Canastas disponibles
try {
    $canastas = $pdo->query(
        "SELECT id, nombre, descripcion, precio, etiqueta FROM canastas WHERE activo = 1 ORDER BY precio ASC"
    )->fetchAll();
} catch (\PDOException $e) {
    $canastas = [];
}

// Preselección desde la landing (?canasta=ID)
$preseleccion = isset($_GET['canasta']) && is_numeric($_GET['canasta']) ? (int)$_GET['canasta'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }
    $cantidades = $_POST['canasta_cant'] ?? []; // [canasta_id => cantidad]

    // Validación básica
    if ($old['nombre'] === '')    $errores[] = "Ingresa tu nombre.";
    if ($old['telefono'] === '')  $errores[] = "Ingresa un teléfono de contacto.";
    if ($old['direccion'] === '') $errores[] = "Ingresa la dirección de entrega.";
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no es válido.";
    }
    if (!in_array($old['frecuencia'], ['unica','semanal','quincenal','mensual'], true)) {
        $old['frecuencia'] = 'unica';
    }

    // Construir lista de canastas elegidas (cantidad > 0) con precio real de la BD
    $elegidas = [];
    $total = 0;
    if (!empty($cantidades) && is_array($cantidades)) {
        $mapa = [];
        foreach ($canastas as $c) { $mapa[$c['id']] = $c; }
        foreach ($cantidades as $cid => $cant) {
            $cid = (int)$cid; $cant = (int)$cant;
            if ($cant > 0 && isset($mapa[$cid])) {
                $precio = (float)$mapa[$cid]['precio'];
                $sub = $precio * $cant;
                $total += $sub;
                $elegidas[] = [
                    'id' => $cid, 'nombre' => $mapa[$cid]['nombre'],
                    'precio' => $precio, 'cantidad' => $cant, 'subtotal' => $sub,
                ];
            }
        }
    }

    // Debe pedir al menos una canasta o escribir una lista libre
    if (empty($elegidas) && $old['lista_libre'] === '') {
        $errores[] = "Elige al menos una canasta o escríbenos tu lista de compras.";
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $codigo = generar_codigo_seguimiento();

            $stmt = $pdo->prepare(
                "INSERT INTO solicitudes
                    (codigo, nombre, telefono, email, direccion, referencia, distrito, fecha_entrega, frecuencia, lista_libre, notas, total_estimado)
                 VALUES
                    (:codigo, :nombre, :telefono, :email, :direccion, :referencia, :distrito, :fecha_entrega, :frecuencia, :lista_libre, :notas, :total)"
            );
            $stmt->execute([
                ':codigo'        => $codigo,
                ':nombre'        => $old['nombre'],
                ':telefono'      => $old['telefono'],
                ':email'         => $old['email'] !== '' ? $old['email'] : null,
                ':direccion'     => $old['direccion'],
                ':referencia'    => $old['referencia'] !== '' ? $old['referencia'] : null,
                ':distrito'      => $old['distrito'] !== '' ? $old['distrito'] : null,
                ':fecha_entrega' => $old['fecha_entrega'] !== '' ? $old['fecha_entrega'] : null,
                ':frecuencia'    => $old['frecuencia'],
                ':lista_libre'   => $old['lista_libre'] !== '' ? $old['lista_libre'] : null,
                ':notas'         => $old['notas'] !== '' ? $old['notas'] : null,
                ':total'         => round($total, 2),
            ]);
            $solicitud_id = (int)$pdo->lastInsertId();

            if (!empty($elegidas)) {
                $insSC = $pdo->prepare(
                    "INSERT INTO solicitud_canastas (solicitud_id, canasta_id, nombre_canasta, precio_unitario, cantidad, subtotal)
                     VALUES (:sid, :cid, :nombre, :precio, :cant, :sub)"
                );
                foreach ($elegidas as $e) {
                    $insSC->execute([
                        ':sid' => $solicitud_id, ':cid' => $e['id'], ':nombre' => $e['nombre'],
                        ':precio' => $e['precio'], ':cant' => $e['cantidad'], ':sub' => $e['subtotal'],
                    ]);
                }
            }

            $pdo->commit();
            $exito = true;
            $venta_ref = $solicitud_id;

            // Armar el mensaje de WhatsApp: orden completa con indicaciones para alistar
            $frecLbl = ['unica'=>'Una vez','semanal'=>'Semanal','quincenal'=>'Quincenal','mensual'=>'Mensual'][$old['frecuencia']] ?? $old['frecuencia'];
            $wa_msg  = "🛒 *NUEVO PEDIDO #" . str_pad($solicitud_id, 4, '0', STR_PAD_LEFT) . "*\n";
            $wa_msg .= "(El mercadito de Julia)\n";
            $wa_msg .= "\n👤 *Cliente:* " . $old['nombre'];
            $wa_msg .= "\n📞 *Teléfono:* " . $old['telefono'];
            $wa_msg .= "\n📍 *Dirección:* " . $old['direccion'];
            if ($old['referencia'] !== '') $wa_msg .= "\n🔖 *Referencia:* " . $old['referencia'];
            if ($old['distrito'] !== '')   $wa_msg .= "\n🏙️ *Distrito:* " . $old['distrito'];
            if ($old['fecha_entrega'] !== '') $wa_msg .= "\n📅 *Entrega:* " . $old['fecha_entrega'];
            $wa_msg .= "\n🔁 *Frecuencia:* " . $frecLbl;

            if (!empty($elegidas)) {
                $wa_msg .= "\n\n🧺 *Canastas:*";
                foreach ($elegidas as $e) {
                    $wa_msg .= "\n• {$e['cantidad']}x {$e['nombre']} (S/. " . number_format($e['subtotal'], 2) . ")";
                }
                $wa_msg .= "\n*Total canastas:* S/. " . number_format($total, 2);
            }
            if ($old['lista_libre'] !== '') {
                $wa_msg .= "\n\n📋 *LISTA E INDICACIONES PARA ALISTAR:*\n" . $old['lista_libre'];
            }
            if ($old['notas'] !== '') {
                $wa_msg .= "\n\n📝 *Notas:* " . $old['notas'];
            }
            $wa_msg .= "\n\n📍 Seguimiento: " . url_seguimiento($codigo);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            log_error('solicitud.crear', $e);
            $errores[] = "No pudimos registrar tu pedido. Inténtalo de nuevo o escríbenos por WhatsApp.";
        }
    }
}

$titulo = 'Pedir mi mercado — ' . NEGOCIO_NOMBRE;
require_once 'includes/publico_header.php';
?>

<section class="py-5 bg-light">
    <div class="container">

        <?php if ($exito): ?>
            <!-- ── Confirmación ──────────────────────────────────────────── -->
            <div class="row justify-content-center">
                <div class="col-lg-7 text-center">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">
                            <div class="animate__animated animate__bounceIn" style="font-size: 4.5rem;">🎉</div>
                            <h2 class="fw-black mt-2">¡Pedido recibido, <?= htmlspecialchars($old['nombre']); ?>!</h2>
                            <p class="text-muted fs-5">
                                Tu pedido quedó registrado. <strong>Falta un paso:</strong> toca el botón verde
                                y dale <strong>ENVIAR</strong> en WhatsApp para que Julia reciba tu pedido. 👇
                            </p>

                            <div class="bg-light rounded-4 p-3 mx-auto mb-3" style="max-width:420px;">
                                <div class="text-muted small">Tu código de seguimiento</div>
                                <div class="fw-black fs-3 texto-degradado"><?= htmlspecialchars($codigo); ?></div>
                                <a href="seguimiento.php?codigo=<?= urlencode($codigo); ?>" class="small">
                                    <i class="bi bi-geo-alt me-1"></i>Seguir el estado de mi pedido
                                </a>
                            </div>

                            <div class="d-grid gap-2 col-md-9 mx-auto mt-2">
                                <a href="<?= whatsapp_link($wa_msg); ?>" target="_blank" rel="noopener"
                                   class="btn btn-wa btn-lg fw-bold animate__animated animate__pulse animate__infinite">
                                    <i class="bi bi-whatsapp me-2"></i>Enviar mi pedido a Julia
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-house me-1"></i>Volver al inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ── Formulario ────────────────────────────────────────────── -->
            <div class="text-center mb-4">
                <h1 class="fw-bold">Pide tu mercado</h1>
                <p class="text-muted">Elige tus canastas, escribe lo que necesites y nosotros nos encargamos.</p>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?><li><?= htmlspecialchars($err); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="solicitar.php<?= $preseleccion ? '?canasta=' . $preseleccion : ''; ?>">
                <?= csrf_field(); ?>
                <div class="row g-4">

                    <!-- Canastas + lista libre -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-basket2 me-2 text-success"></i>1. Elige tus canastas</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($canastas)): ?>
                                    <p class="text-muted mb-0">Aún no hay canastas publicadas. Usa la lista libre de abajo.</p>
                                <?php else: ?>
                                    <?php foreach ($canastas as $c):
                                        $valor = ($preseleccion === (int)$c['id']) ? 1 : (int)($_POST['canasta_cant'][$c['id']] ?? 0);
                                    ?>
                                        <div class="d-flex align-items-center justify-content-between border-bottom py-3">
                                            <div class="pe-3">
                                                <div class="fw-semibold"><?= htmlspecialchars($c['nombre']); ?>
                                                    <span class="text-success fw-bold">· S/. <?= number_format($c['precio'], 2); ?></span>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($c['descripcion']); ?></small>
                                            </div>
                                            <input type="number" min="0" max="20"
                                                   class="form-control form-control-sm text-center"
                                                   style="width: 80px;"
                                                   name="canasta_cant[<?= $c['id']; ?>]"
                                                   value="<?= $valor; ?>">
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-success"></i>2. ¿Algo más? Escríbelo aquí</h5>
                            </div>
                            <div class="card-body">
                                <label class="form-label text-muted small">
                                    Lista libre: anota cualquier producto que quieras que te compremos
                                    (cantidades, marcas, preferencias…).
                                </label>
                                <textarea name="lista_libre" class="form-control" rows="5"
                                          placeholder="Ej:&#10;- 2 kg de pechuga de pollo&#10;- 1 sandía mediana&#10;- Pan integral marca X&#10;- 6 yogures de fresa"><?= htmlspecialchars($old['lista_libre']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de contacto y entrega -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-truck me-2 text-success"></i>3. Tus datos y entrega</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($old['nombre']); ?>" required>
                                </div>
                                <div class="row g-2">
                                    <div class="col-7 mb-3">
                                        <label class="form-label fw-semibold">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                                        <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($old['telefono']); ?>" required>
                                    </div>
                                    <div class="col-5 mb-3">
                                        <label class="form-label fw-semibold">Distrito</label>
                                        <input type="text" name="distrito" class="form-control" value="<?= htmlspecialchars($old['distrito']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Correo (opcional)</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Dirección de entrega <span class="text-danger">*</span></label>
                                    <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($old['direccion']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Referencia</label>
                                    <input type="text" name="referencia" class="form-control" placeholder="Ej. Casa azul, frente al parque" value="<?= htmlspecialchars($old['referencia']); ?>">
                                </div>
                                <div class="row g-2">
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-semibold">Fecha deseada</label>
                                        <input type="date" name="fecha_entrega" class="form-control" value="<?= htmlspecialchars($old['fecha_entrega']); ?>">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label fw-semibold">Frecuencia</label>
                                        <select name="frecuencia" class="form-select">
                                            <?php foreach (['unica'=>'Una vez','semanal'=>'Semanal','quincenal'=>'Quincenal','mensual'=>'Mensual'] as $val=>$lbl): ?>
                                                <option value="<?= $val; ?>" <?= $old['frecuencia']===$val?'selected':''; ?>><?= $lbl; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Notas para nosotros</label>
                                    <textarea name="notas" class="form-control" rows="2" placeholder="Horario preferido, indicaciones, etc."><?= htmlspecialchars($old['notas']); ?></textarea>
                                </div>

                                <div class="alert alert-light border small text-muted mb-3">
                                    <i class="bi bi-info-circle me-1"></i>El total de las canastas es referencial.
                                    Te confirmaremos el monto final (incluida tu lista libre) antes de comprar.
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg fw-bold">
                                        <i class="bi bi-send-check me-2"></i>Enviar mi pedido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if (!$exito): ?>
<script>
// Si el cliente viene del cotizador, precargar su lista
(function () {
    try {
        const lista = sessionStorage.getItem('mercadito_lista');
        const campo = document.querySelector('textarea[name="lista_libre"]');
        if (lista && campo && !campo.value.trim()) {
            campo.value = lista;
            sessionStorage.removeItem('mercadito_lista');
            campo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } catch (e) {}
})();
</script>
<?php endif; ?>

<?php require_once 'includes/publico_footer.php'; ?>
