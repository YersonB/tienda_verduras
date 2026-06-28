<?php
// includes/publico_header.php — cabecera para las páginas públicas (cliente)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/cabeceras.php';
require_once __DIR__ . '/config_sitio.php';

$pagina_actual = $_SERVER['REQUEST_URI'] ?? '';
function publico_active(string $seg): string {
    global $pagina_actual;
    return str_contains($pagina_actual, $seg) ? 'active' : '';
}
$titulo = $titulo ?? (NEGOCIO_NOMBRE . ' — ' . NEGOCIO_LEMA);
$BASE   = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo); ?></title>
    <meta name="description" content="<?= htmlspecialchars(NEGOCIO_NOMBRE); ?>: hacemos tu mercado por ti. Carnes, frutas, verduras y abarrotes frescos llevados a tu puerta. Pide por WhatsApp en segundos.">
    <meta name="theme-color" content="#16a34a">
    <link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20100%20100'%3E%3Crect%20width='100'%20height='100'%20rx='24'%20fill='%2316a34a'/%3E%3Ctext%20x='50'%20y='56'%20font-size='60'%20text-anchor='middle'%20dominant-baseline='central'%3E%F0%9F%A7%BA%3C/text%3E%3C/svg%3E">
    <script>document.documentElement.className = 'js';</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root {
            --verde:#16a34a; --verde-osc:#15803d; --verde-prof:#14532d;
            --naranja:#fb923c; --naranja-osc:#f97316;
            --rosa:#fb7185; --rojo:#ef4444; --amarillo:#fde047;
            --crema:#fffaf0; --crema2:#fff3e0;
            --ink:#1f2937; --ink-soft:#6b7280;
            --sombra: 0 18px 40px -18px rgba(20,83,45,.35);
            --radio: 22px;
        }
        * { scroll-behavior: smooth; }
        body { font-family:'Plus Jakarta Sans',system-ui,sans-serif; color:var(--ink); overflow-x:hidden; background:#fff; }
        h1,h2,h3,.display-font { font-family:'Fraunces',Georgia,serif; font-weight:900; letter-spacing:-.5px; line-height:1.05; }
        .lead-soft { color:var(--ink-soft); }

        .texto-degradado { background:linear-gradient(100deg,var(--verde),var(--naranja-osc)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
        .bg-crema { background:var(--crema); }
        .bg-crema2 { background:linear-gradient(180deg,var(--crema) 0%, var(--crema2) 100%); }

        /* NAVBAR */
        .navbar { transition: all .3s ease; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.05); padding-top:.85rem; padding-bottom:.85rem; }
        .navbar.scrolled { box-shadow:0 8px 26px -14px rgba(0,0,0,.22); padding-top:.45rem; padding-bottom:.45rem; }
        .navbar-brand { font-family:'Fraunces',serif; font-weight:900; font-size:1.4rem; }
        .navbar .nav-link { font-weight:600; color:var(--ink) !important; position:relative; }
        .navbar .nav-link::after { content:''; position:absolute; left:.8rem; right:.8rem; bottom:.35rem; height:2px; background:var(--verde); transform:scaleX(0); transform-origin:left; transition:transform .25s; }
        .navbar .nav-link:hover::after { transform:scaleX(1); }

        /* BOTONES */
        .btn { border-radius:999px; font-weight:700; transition:transform .15s ease, box-shadow .2s ease; }
        .btn-lg { padding:.8rem 1.6rem; }
        .btn-naranja { background:linear-gradient(135deg,var(--naranja),var(--naranja-osc)); border:none; color:#fff; }
        .btn-naranja:hover { color:#fff; transform:translateY(-3px); box-shadow:0 12px 24px -10px rgba(249,115,22,.7); }
        .btn-wa { background:#25D366; border:none; color:#fff; }
        .btn-wa:hover { background:#1da851; color:#fff; transform:translateY(-3px); box-shadow:0 12px 24px -10px rgba(37,211,102,.7); }
        .btn-amarillo { background:var(--amarillo); border:none; color:#713f12; }
        .btn-amarillo:hover { color:#713f12; transform:translateY(-3px); box-shadow:0 12px 24px -10px rgba(250,204,21,.8); }
        .btn-blanco { background:#fff; border:none; color:var(--verde-osc); }
        .btn-blanco:hover { color:var(--verde-osc); transform:translateY(-3px); box-shadow:0 12px 24px -10px rgba(0,0,0,.3); }

        /* HERO */
        .hero { position:relative; color:#fff; isolation:isolate;
            background:
              radial-gradient(1100px 500px at 80% -10%, rgba(253,224,71,.28), transparent 60%),
              radial-gradient(900px 500px at -10% 110%, rgba(251,113,133,.30), transparent 55%),
              linear-gradient(135deg,var(--verde) 0%, var(--verde-osc) 55%, var(--verde-prof) 100%);
        }
        .hero-blob { position:absolute; border-radius:46% 54% 60% 40%/45% 45% 55% 55%; filter:blur(1px); opacity:.16; background:#fff; z-index:-1; }
        .float-emoji { position:absolute; opacity:.9; filter:drop-shadow(0 8px 14px rgba(0,0,0,.18)); z-index:0; }
        @keyframes flotar { 0%,100%{ transform:translateY(0) rotate(0); } 50%{ transform:translateY(-16px) rotate(6deg); } }
        .animar-flotar { animation:flotar 4s ease-in-out infinite; }
        .animar-flotar.lento { animation-duration:6s; }
        .animar-flotar.rapido { animation-duration:3s; }

        /* MARQUEE */
        .marquee { overflow:hidden; white-space:nowrap; }
        .marquee__track { display:inline-flex; gap:3rem; padding:1rem 0; animation:scroll-x 28s linear infinite; font-weight:700; color:var(--verde-osc); }
        .marquee:hover .marquee__track { animation-play-state:paused; }
        @keyframes scroll-x { from{ transform:translateX(0); } to{ transform:translateX(-50%); } }

        /* TARJETAS */
        .card { border-radius:var(--radio); }
        .card-hover { transition:transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s; }
        .card-hover:hover { transform:translateY(-8px); box-shadow:var(--sombra) !important; }
        .icono-circulo { width:78px; height:78px; border-radius:24px; display:inline-flex; align-items:center; justify-content:center; font-size:2.1rem; color:#fff; box-shadow:0 12px 22px -10px rgba(0,0,0,.3); }

        /* PASOS conectados */
        .paso-num { width:38px; height:38px; border-radius:50%; background:#fff; color:var(--verde-osc); font-weight:800; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 14px -6px rgba(0,0,0,.3); }

        /* CHIPS */
        .chip { cursor:pointer; user-select:none; transition:all .15s ease; border:1.6px solid #e5e7eb; color:var(--ink); background:#fff; border-radius:999px; padding:.4rem .85rem; font-size:.85rem; font-weight:600; }
        .chip:hover { border-color:var(--verde); background:var(--verde); color:#fff; transform:translateY(-2px); }
        .chip:active { transform:scale(.96); }

        /* LISTA INTERACTIVA */
        .nota { background:#fffdf5; border-radius:var(--radio); box-shadow:var(--sombra); border:1px solid #f1e9d2; }
        .nota__head { background:repeating-linear-gradient(45deg,#16a34a,#16a34a 12px,#15803d 12px,#15803d 24px); }
        .item-lista { display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.6rem .9rem; border-radius:14px; background:#fff; border:1px solid #eef2f7; margin-bottom:.5rem; animation:popIn .2s ease; }
        @keyframes popIn { from{ opacity:0; transform:translateY(-6px) scale(.98);} to{ opacity:1; transform:none;} }
        .item-lista .quitar { border:none; background:#fee2e2; color:#dc2626; width:28px; height:28px; border-radius:50%; line-height:1; flex:0 0 auto; }
        .item-lista .quitar:hover { background:#dc2626; color:#fff; }

        /* SCROLL REVEAL */
        .js .reveal { opacity:0; transform:translateY(34px); transition:opacity .7s cubic-bezier(.2,.8,.2,1), transform .7s cubic-bezier(.2,.8,.2,1); }
        .js .reveal.visible { opacity:1; transform:none; }
        .reveal.d1{ transition-delay:.08s;} .reveal.d2{ transition-delay:.16s;} .reveal.d3{ transition-delay:.24s;} .reveal.d4{ transition-delay:.32s;}

        /* SEPARADOR ONDA */
        .onda { display:block; line-height:0; }

        /* WHATSAPP FLOTANTE */
        @keyframes latido { 0%,100%{ transform:scale(1);} 50%{ transform:scale(1.09);} }
        .wa-flotante { position:fixed; right:20px; bottom:22px; z-index:1080; width:62px; height:62px; border-radius:50%; background:#25D366; color:#fff; display:flex; align-items:center; justify-content:center; font-size:2rem; box-shadow:0 .6rem 1.4rem rgba(37,211,102,.55); animation:latido 1.8s ease-in-out infinite; text-decoration:none; }
        .wa-flotante::before { content:''; position:absolute; inset:0; border-radius:50%; border:2px solid #25D366; animation:ripple 2s ease-out infinite; }
        @keyframes ripple { 0%{ transform:scale(1); opacity:.6;} 100%{ transform:scale(1.8); opacity:0;} }
        .wa-flotante:hover { color:#fff; background:#1da851; }
        .wa-tooltip { position:fixed; right:92px; bottom:36px; z-index:1080; background:#fff; color:#15803d; font-weight:700; padding:.45rem .95rem; border-radius:999px; box-shadow:0 .4rem 1rem rgba(0,0,0,.15); font-size:.85rem; }
        @media (max-width:576px){ .wa-tooltip{ display:none; } }

        .seccion { scroll-margin-top:90px; }
        .badge-soft { background:rgba(22,163,74,.12); color:var(--verde-osc); font-weight:700; }
        @media (prefers-reduced-motion: reduce){ *{ animation:none !important; transition:none !important; } .js .reveal{ opacity:1; transform:none; } }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-light navbar-expand-lg sticky-top" id="navPrincipal">
    <div class="container">
        <a class="navbar-brand" href="<?= $BASE; ?>/index.php">
            🧺 <span class="texto-degradado"><?= htmlspecialchars(NEGOCIO_NOMBRE); ?></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navPublico">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navPublico">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= $BASE; ?>/index.php#como-funciona">Cómo funciona</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $BASE; ?>/index.php#lista">Arma tu lista</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $BASE; ?>/index.php#canastas">Canastas</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $BASE; ?>/seguimiento.php">Seguir mi pedido</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-wa px-3" href="<?= whatsapp_saludo(); ?>" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp me-1"></i>Pedir ahora
                    </a>
                </li>
                <li class="nav-item ms-lg-1">
                    <a class="nav-link text-muted small px-2" href="<?= $BASE; ?>/login.php" title="Acceso del personal">
                        <i class="bi bi-person-badge"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
