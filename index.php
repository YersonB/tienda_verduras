<?php
// index.php — Landing pública: "El mercadito de Julia"
require_once 'config/conexion.php';
require_once 'includes/config_sitio.php';

$canastas = [];
try {
    $canastas = $pdo->query(
        "SELECT id, nombre, descripcion, contenido, precio, etiqueta
         FROM canastas WHERE activo = 1 ORDER BY precio ASC"
    )->fetchAll();
} catch (\PDOException $e) {
    $canastas = [];
}

$titulo = NEGOCIO_NOMBRE . ' — ' . NEGOCIO_LEMA;
require_once 'includes/publico_header.php';
?>

<!-- ══════════════ HERO ══════════════ -->
<section class="hero pt-5">
    <span class="hero-blob" style="width:420px;height:420px;top:-120px;left:-100px;"></span>
    <span class="hero-blob" style="width:300px;height:300px;bottom:-120px;right:8%;"></span>
    <span class="float-emoji animar-flotar"        style="top:18%;left:6%;font-size:3rem;">🍅</span>
    <span class="float-emoji animar-flotar lento"  style="top:62%;left:12%;font-size:2.4rem;">🥕</span>
    <span class="float-emoji animar-flotar rapido" style="top:24%;right:10%;font-size:2.8rem;">🍓</span>
    <span class="float-emoji animar-flotar"        style="bottom:16%;right:16%;font-size:2.6rem;">🥦</span>

    <div class="container py-5" style="position:relative;z-index:2;">
        <div class="row align-items-center g-5 py-lg-4">
            <div class="col-lg-7 text-center text-lg-start">
                <span class="badge bg-warning text-dark fw-bold mb-3 px-3 py-2 rounded-pill animate__animated animate__fadeInDown">
                    <i class="bi bi-stars me-1"></i>Tu mercado, sin moverte de casa
                </span>
                <h1 class="display-2 mb-3 animate__animated animate__fadeInUp">
                    Hola, soy <span style="color:var(--amarillo)">Julia</span><br>
                    y hago <span style="text-decoration:underline wavy var(--amarillo);text-underline-offset:8px;">tu mercado</span> por ti
                </h1>
                <p class="fs-5 text-white-50 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="max-width:560px;">
                    ¿Sin tiempo para ir al mercado? Yo elijo a mano tus <strong class="text-white">carnes, frutas,
                    verduras y abarrotes</strong> bien fresquitos y te los llevo a tu puerta. 🛵💨
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start animate__animated animate__fadeInUp animate__delay-1s">
                    <a href="#lista" class="btn btn-amarillo btn-lg">
                        <i class="bi bi-magic me-2"></i>Cotiza tu pedido
                    </a>
                    <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="btn btn-wa btn-lg">
                        <i class="bi bi-whatsapp me-2"></i>Pídeme directo
                    </a>
                </div>

                <div class="row g-3 mt-4 text-center text-lg-start animate__animated animate__fadeIn animate__delay-2s" style="max-width:560px;">
                    <div class="col-4"><div class="fs-3 fw-bold">🕒</div><small class="text-white-50">Te ahorro<br>el tiempo</small></div>
                    <div class="col-4"><div class="fs-3 fw-bold">✋</div><small class="text-white-50">Elijo<br>a mano</small></div>
                    <div class="col-4"><div class="fs-3 fw-bold">🛵</div><small class="text-white-50">Llevo a<br>tu puerta</small></div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div class="position-relative">
                    <div class="animar-flotar" style="font-size:15rem;line-height:1;filter:drop-shadow(0 20px 30px rgba(0,0,0,.3));">🧺</div>
                    <span class="badge bg-white text-success shadow position-absolute animate__animated animate__pulse animate__infinite"
                          style="top:10%;right:-10px;font-size:.85rem;">🥬 ¡Fresco hoy!</span>
                    <span class="badge bg-white text-danger shadow position-absolute"
                          style="bottom:14%;left:-20px;font-size:.85rem;">🛵 Entrega rápida</span>
                </div>
            </div>
        </div>
    </div>

    <div class="onda">
        <svg viewBox="0 0 1440 90" preserveAspectRatio="none" style="width:100%;height:70px;">
            <path fill="#ffffff" d="M0,64L80,58.7C160,53,320,43,480,48C640,53,800,75,960,74.7C1120,75,1280,53,1360,42.7L1440,32L1440,90L0,90Z"></path>
        </svg>
    </div>
</section>

<!-- ══════════════ MARQUEE ══════════════ -->
<div class="marquee bg-white border-bottom">
    <div class="marquee__track">
        <?php $loop = '🍅 Tomate · 🥕 Zanahoria · 🍎 Manzana · 🥩 Carnes · 🍌 Plátano · 🥬 Lechuga · 🥔 Papa · 🍓 Fresa · 🧅 Cebolla · 🥑 Palta · 🍗 Pollo · 🌽 Choclo · ';
        echo str_repeat('<span>'.$loop.'</span>', 2); ?>
    </div>
</div>

<!-- ══════════════ CÓMO FUNCIONA ══════════════ -->
<section id="como-funciona" class="seccion py-5 bg-crema2">
    <div class="container py-4">
        <div class="text-center mb-5 reveal">
            <span class="badge badge-soft px-3 py-2 rounded-pill mb-2">Súper fácil</span>
            <h2 class="display-5">¿Cómo funciona?</h2>
            <p class="fs-5 lead-soft">Tres pasitos y listo. 😉</p>
        </div>
        <div class="row g-4">
            <?php
            $pasos = [
                ['linear-gradient(135deg,#16a34a,#15803d)','bi-pencil-square','Mándame tu lista','Escribe lo que necesitas o elige una canasta. ¡Por WhatsApp en segundos!'],
                ['linear-gradient(135deg,#fb923c,#f97316)','bi-bag-heart','Yo lo compro','Voy al mercado y escojo a mano cada producto, fresco y al mejor precio.'],
                ['linear-gradient(135deg,#fb7185,#ef4444)','bi-house-heart','Te lo llevo','Recibes todo en tu puerta el día y la hora que prefieras. 🛵'],
            ];
            foreach ($pasos as $i => $p): ?>
                <div class="col-md-4 reveal d<?= $i+1; ?>">
                    <div class="card border-0 shadow-sm h-100 text-center card-hover position-relative">
                        <div class="card-body py-5 px-4">
                            <span class="paso-num position-absolute top-0 start-50 translate-middle"><?= $i+1; ?></span>
                            <span class="icono-circulo mt-2 mb-3" style="background:<?= $p[0]; ?>"><i class="bi <?= $p[1]; ?>"></i></span>
                            <h4 class="fw-bold h5"><?= $p[2]; ?></h4>
                            <p class="lead-soft mb-0"><?= $p[3]; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════ COTIZA TU PEDIDO ══════════════ -->
<section id="lista" class="seccion py-5" style="background:linear-gradient(135deg,var(--verde) 0%,var(--verde-osc) 60%,var(--verde-prof) 100%);color:#fff;">
    <div class="container py-4">
        <div class="text-center mb-4 reveal">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-2">🧮 Al instante</span>
            <h2 class="display-5 text-white">Cotiza tu pedido en segundos</h2>
            <p class="fs-5 text-white-50">Escribe tu lista y te decimos cuánto sale al toque. Si te conviene, seguimos con tu pedido. 👇</p>
        </div>

        <div class="row justify-content-center reveal">
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <label class="form-label fw-bold text-dark">Tu lista de compras</label>
                        <textarea id="listaLanding" class="form-control form-control-lg mb-3" rows="6"
                            placeholder="Ej:&#10;2 kg de papa&#10;1 kg tomate&#10;6 huevos&#10;medio kilo de carne molida&#10;3 lechugas"></textarea>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success btn-lg fw-bold" id="btnCotizarLanding">
                                <i class="bi bi-magic me-2"></i>Ver cuánto sale mi pedido
                            </button>
                            <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="btn btn-outline-success">
                                <i class="bi bi-chat-dots me-2"></i>Prefiero escribir directo a Julia
                            </a>
                        </div>
                        <p class="text-center lead-soft small mt-3 mb-0">
                            <i class="bi bi-shield-check me-1"></i>Sin compromiso. Te confirmo el precio antes de comprar.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════ QUÉ COMPRO ══════════════ -->
<section id="categorias" class="seccion py-5 bg-crema">
    <div class="container py-4">
        <div class="text-center mb-5 reveal">
            <span class="badge badge-soft px-3 py-2 rounded-pill mb-2">Variedad</span>
            <h2 class="display-5">Te compro de todo 🛒</h2>
            <p class="fs-5 lead-soft">Todo lo de tu mercado, en un solo pedido.</p>
        </div>
        <div class="row g-4">
            <?php
            $cats = [
                ['🥩','Carnes','Res, pollo, cerdo y embutidos','#fee2e2'],
                ['🍎','Frutas','De estación, dulces y en su punto','#fef3c7'],
                ['🥬','Verduras','Verdes y bien fresquitas','#dcfce7'],
                ['🛒','Abarrotes','Arroz, aceite, menestras y más','#ffedd5'],
            ];
            foreach ($cats as $i => $c): ?>
                <div class="col-6 col-md-3 reveal d<?= $i+1; ?>">
                    <div class="card border-0 shadow-sm h-100 text-center card-hover">
                        <div class="card-body py-5">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:84px;height:84px;background:<?= $c[3]; ?>;font-size:2.6rem;"><?= $c[0]; ?></div>
                            <h4 class="fw-bold h5 mb-1"><?= $c[1]; ?></h4>
                            <p class="lead-soft small mb-0"><?= $c[2]; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════ CANASTAS ══════════════ -->
<section id="canastas" class="seccion py-5">
    <div class="container py-4">
        <div class="text-center mb-5 reveal">
            <span class="badge badge-soft px-3 py-2 rounded-pill mb-2">Listas para pedir</span>
            <h2 class="display-5">Mis canastas 🧺</h2>
            <p class="fs-5 lead-soft">Combos pensados para ti. O arma la tuya a medida.</p>
        </div>

        <?php if (empty($canastas)): ?>
            <div class="alert alert-warning text-center reveal">
                Pronto publicaré mis canastas. ¡Mientras tanto, mándame tu lista por WhatsApp! 💚
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($canastas as $i => $c):
                    $msg = "¡Hola Julia! 🛒 Quiero pedir la *{$c['nombre']}* (S/. " . number_format($c['precio'], 2) . "). ¿Me ayudas?";
                    $cols = ['#16a34a','#fb923c','#fb7185','#f59e0b'];
                    $bar = $cols[$i % count($cols)];
                ?>
                    <div class="col-md-6 col-lg-3 reveal d<?= ($i%4)+1; ?>">
                        <div class="card border-0 shadow-sm h-100 card-hover overflow-hidden">
                            <div style="height:6px;background:<?= $bar; ?>;"></div>
                            <div class="card-body d-flex flex-column">
                                <?php if (!empty($c['etiqueta'])): ?>
                                    <span class="badge bg-warning text-dark align-self-start mb-2"><?= htmlspecialchars($c['etiqueta']); ?></span>
                                <?php endif; ?>
                                <h4 class="fw-bold h5"><?= htmlspecialchars($c['nombre']); ?></h4>
                                <p class="lead-soft small flex-grow-1"><?= htmlspecialchars($c['contenido'] ?: $c['descripcion']); ?></p>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="fs-4 fw-bold texto-degradado">S/. <?= number_format($c['precio'], 2); ?></span>
                                    <a href="<?= whatsapp_link($msg); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-wa">
                                        <i class="bi bi-whatsapp me-1"></i>Pedir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4 reveal">
                <a href="solicitar.php" class="btn btn-outline-success btn-lg">
                    <i class="bi bi-list-check me-1"></i>Pedido detallado con varias canastas
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══════════════ FAQ ══════════════ -->
<section id="faq" class="seccion py-5 bg-crema2">
    <div class="container py-4">
        <div class="text-center mb-5 reveal">
            <span class="badge badge-soft px-3 py-2 rounded-pill mb-2">Dudas frecuentes</span>
            <h2 class="display-5">Preguntas frecuentes 🤔</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8 reveal">
                <div class="accordion accordion-flush shadow-sm rounded-4 overflow-hidden" id="faqAcc">
                    <?php
                    $faqs = [
                        ['¿Cómo hago mi pedido?', 'Súper fácil: escribe tu lista en "Cotiza tu pedido" y verás al instante cuánto sale; si te conviene, continúas con tu pedido. También puedes escribirme directo por WhatsApp o elegir una de mis canastas.'],
                        ['¿Cómo sé cuánto voy a pagar?', 'Antes de comprar te confirmo el precio total por WhatsApp. No hay sorpresas: tú apruebas y recién voy al mercado.'],
                        ['¿Cómo pago?', 'Puedes pagar al recibir tu pedido (efectivo o Yape/Plin). Lo coordinamos por WhatsApp.'],
                        ['¿Qué días entregan?', 'De lunes a sábado. Tú eliges el día y la hora que más te convenga al hacer tu pedido.'],
                        ['¿Y si un producto no estaba fresco?', 'Solo llevo lo que yo misma compraría. Si algo no te convence, conversamos y lo solucionamos. 💚'],
                    ];
                    foreach ($faqs as $i => $f): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i? 'collapsed':''; ?> fw-bold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq<?= $i; ?>">
                                    <?= htmlspecialchars($f[0]); ?>
                                </button>
                            </h2>
                            <div id="faq<?= $i; ?>" class="accordion-collapse collapse <?= $i===0?'show':''; ?>" data-bs-parent="#faqAcc">
                                <div class="accordion-body lead-soft"><?= htmlspecialchars($f[1]); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════ CTA FINAL ══════════════ -->
<section class="py-5" style="background:linear-gradient(135deg,var(--naranja),var(--rosa));color:#fff;">
    <div class="container text-center py-4 reveal">
        <div class="display-6 mb-2">🛒💨</div>
        <h2 class="display-5 mb-3">Tu tiempo vale oro</h2>
        <p class="fs-5 mb-4">Deja que Julia haga tu mercado. ¡Escríbeme ahora mismo!</p>
        <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="btn btn-blanco btn-lg px-5">
            <i class="bi bi-whatsapp me-2"></i>Pedir por WhatsApp
        </a>
    </div>
</section>

<script>
// La lista de la landing continúa en el cotizador (que la interpreta con precios)
document.getElementById('btnCotizarLanding').addEventListener('click', () => {
    const texto = document.getElementById('listaLanding').value.trim();
    try { if (texto) sessionStorage.setItem('mercadito_cotizar', texto); } catch (e) {}
    window.location.href = 'cotizar.php';
});
</script>

<?php require_once 'includes/publico_footer.php'; ?>
