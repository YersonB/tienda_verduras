<?php require_once __DIR__ . '/config_sitio.php'; ?>
</main>

<footer class="text-light pt-5 pb-4 mt-0" style="background:linear-gradient(135deg,#14532d,#166534);">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <h5 class="fw-bold mb-3">🧺 <span style="color:var(--amarillo)"><?= htmlspecialchars(NEGOCIO_NOMBRE); ?></span></h5>
                <p class="text-white-50 mb-3"><?= htmlspecialchars(NEGOCIO_LEMA); ?>. Carnes, frutas, verduras y
                   abarrotes frescos, elegidos a mano y llevados a tu puerta. 💚</p>
                <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                    <i class="bi bi-whatsapp me-1"></i>Escríbeme
                </a>
            </div>
            <div class="col-6 col-md-3">
                <h6 class="fw-bold mb-3">Explora</h6>
                <ul class="list-unstyled text-white-50 lh-lg">
                    <li><a href="<?= BASE_URL ?>/index.php#como-funciona" class="link-light text-decoration-none">Cómo funciona</a></li>
                    <li><a href="<?= BASE_URL ?>/cotizar.php" class="link-light text-decoration-none">Cotiza tu pedido</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#canastas" class="link-light text-decoration-none">Canastas</a></li>
                    <li><a href="<?= BASE_URL ?>/seguimiento.php" class="link-light text-decoration-none">Seguir mi pedido</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h6 class="fw-bold mb-3">Contáctame</h6>
                <ul class="list-unstyled text-white-50 lh-lg">
                    <li>
                        <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="link-light text-decoration-none">
                            <i class="bi bi-whatsapp me-2 text-success"></i><?= htmlspecialchars(WHATSAPP_VISIBLE); ?>
                        </a>
                    </li>
                    <li><i class="bi bi-clock me-2 text-success"></i>Lun a Sáb · 8:00 - 18:00</li>
                    <li><i class="bi bi-truck me-2 text-success"></i>Entrega a domicilio</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="text-center text-white-50 small">
            &copy; <?= date('Y'); ?> <?= htmlspecialchars(NEGOCIO_NOMBRE); ?>. Hecho con 💚 para ti.
        </div>
    </div>
</footer>

<!-- Saludo de bienvenida de Julia (primera visita) -->
<style>
    .welcome-julia { position:fixed; left:20px; bottom:24px; z-index:1085; max-width:330px;
        background:#fff; border-radius:20px; box-shadow:0 1rem 2.4rem rgba(0,0,0,.22);
        padding:16px 18px; border:1px solid #eef2f7;
        transform:translateY(160%); opacity:0; transition:all .55s cubic-bezier(.2,.8,.2,1); }
    .welcome-julia.show { transform:none; opacity:1; }
    .welcome-avatar { width:48px; height:48px; border-radius:50%; background:rgba(22,163,74,.12);
        display:flex; align-items:center; justify-content:center; font-size:1.7rem; flex:0 0 auto; }
    .welcome-close { position:absolute; top:8px; right:12px; border:none; background:transparent;
        font-size:1.4rem; line-height:1; color:#9ca3af; cursor:pointer; }
    .welcome-close:hover { color:#374151; }
    @media (max-width:576px){ .welcome-julia{ left:12px; right:88px; max-width:none; bottom:18px; } }
</style>
<div id="welcomeJulia" class="welcome-julia" role="dialog" aria-live="polite">
    <button class="welcome-close" onclick="cerrarWelcome()" aria-label="Cerrar">&times;</button>
    <div class="d-flex align-items-start gap-2">
        <div class="welcome-avatar">🧺</div>
        <div>
            <div class="fw-bold" style="font-family:'Fraunces',serif;">¡Hola! Soy Julia 👋</div>
            <div class="small text-muted mb-2">¿Armamos tu mercado? Yo lo compro y te lo llevo fresquito. 💚</div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/index.php#lista" class="btn btn-success btn-sm" onclick="cerrarWelcome()">¡Sí, vamos!</a>
                <a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="btn btn-wa btn-sm" onclick="cerrarWelcome()">
                    <i class="bi bi-whatsapp"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    function cerrarWelcome() {
        const w = document.getElementById('welcomeJulia');
        if (w) w.classList.remove('show');
        try { localStorage.setItem('juliaWelcome', '1'); } catch (e) {}
    }
    (function () {
        const w = document.getElementById('welcomeJulia');
        if (!w) return;
        let visto = false;
        try { visto = localStorage.getItem('juliaWelcome') === '1'; } catch (e) {}
        if (!visto) setTimeout(() => w.classList.add('show'), 1600);
    })();
</script>

<!-- Botón flotante de WhatsApp -->
<span class="wa-tooltip animate__animated animate__fadeInRight">¡Escríbeme! 👋</span>
<a href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener" class="wa-flotante" aria-label="Escríbeme por WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar: sombra al hacer scroll
(function(){
    const nav = document.getElementById('navPrincipal');
    if (nav) {
        const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 30);
        window.addEventListener('scroll', onScroll, { passive:true });
        onScroll();
    }
    // Scroll reveal con IntersectionObserver
    const reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && reveals.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        reveals.forEach(el => io.observe(el));
    } else {
        reveals.forEach(el => el.classList.add('visible'));
    }

    // "¡No te vayas!" al cambiar de pestaña
    const tituloOriginal = document.title;
    const mensajesVuelve = ['🥺 ¡No te vayas!', '🧺 Tu mercado te espera...', '💚 ¡Vuelve pronto!'];
    let vueltaTimer = null, idx = 0;
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            idx = 0;
            document.title = mensajesVuelve[0];
            vueltaTimer = setInterval(() => {
                idx = (idx + 1) % mensajesVuelve.length;
                document.title = mensajesVuelve[idx];
            }, 1500);
        } else {
            clearInterval(vueltaTimer);
            document.title = tituloOriginal;
        }
    });
})();
</script>
</body>
</html>
