<?php
// =============================================================================
// ALGORYA - producto.php
// Página de detalle de producto con reseñas dinámicas y elementos FOMO.
// =============================================================================
session_start();
require 'includes/lang.php';
require 'includes/db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }

$producto_id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

if ($resultado->num_rows === 0) { header("Location: index.php"); exit(); }
$producto = $resultado->fetch_assoc();

// Contador carrito
$contador_carrito = 0;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $r = $conn->query("SELECT SUM(cantidad) as t FROM carritos WHERE usuario_id=$uid")->fetch_assoc();
    $contador_carrito = (int)($r['t'] ?? 0);
} else {
    foreach ($_SESSION['carrito'] ?? [] as $i) $contador_carrito += $i['cantidad'];
}

// URL selector idioma
$url_sin_lang = preg_replace('/([&?])lang=[a-z]{2}(&|$)/', '$1', $_SERVER['REQUEST_URI']);
$url_sin_lang = rtrim($url_sin_lang, '?&');
$sep = str_contains($url_sin_lang, '?') ? '&' : '?';

// ─── RESEÑAS DINÁMICAS ───────────────────────────────────────────────────────
$nombres_es = ['Carlos M.','Laura G.','Javier R.','Ana S.','Miguel T.','Elena P.','David L.','Sara F.'];
$nombres_en = ['James H.','Emma W.','Oliver B.','Sophia K.','Noah R.','Ava M.','Liam T.','Isabella J.'];
$nombres    = LANG === 'es' ? $nombres_es : $nombres_en;

$comentarios_es = [
    'Superó mis expectativas. La calidad es excelente y llegó antes de lo previsto.',
    'Muy buen producto, exactamente como se describe. Lo recomiendo sin dudarlo.',
    'Relación calidad-precio inmejorable. Repito compra seguro.',
    'Me ha sorprendido gratamente. El acabado es muy cuidado.',
    'Perfecto para lo que necesitaba. Rápido y bien empaquetado.',
    'Gran compra. Funciona de maravilla y el precio es muy competitivo.',
    'Muy satisfecho con la compra. El producto es de buena calidad.',
    'Lo compré para regalar y ha gustado muchísimo. Totalmente recomendable.',
];
$comentarios_en = [
    'Exceeded my expectations. Excellent quality and arrived earlier than expected.',
    'Great product, exactly as described. I recommend it without hesitation.',
    'Unbeatable value for money. Will definitely buy again.',
    'I was pleasantly surprised. The finish is very well made.',
    'Perfect for what I needed. Fast and well packaged.',
    'Great purchase. Works wonderfully and the price is very competitive.',
    'Very happy with the purchase. The product is of good quality.',
    'I bought it as a gift and everyone loved it. Highly recommended.',
];
$comentarios = LANG === 'es' ? $comentarios_es : $comentarios_en;

$reseñas = [];
$ratings_base = [5, 5, 4, 5];
for ($i = 0; $i < 4; $i++) {
    $seed_n = ($producto_id * 7  + $i * 13) % count($nombres);
    $seed_c = ($producto_id * 11 + $i * 17) % count($comentarios);
    $dias   = (($producto_id + $i * 5) % 25) + 2;
    $reseñas[] = [
        'nombre'    => $nombres[$seed_n],
        'rating'    => $ratings_base[$i],
        'comentario'=> $comentarios[$seed_c],
        'dias'      => $dias,
        'verificado'=> ($i % 3 !== 2),
    ];
}

// FOMO
$viendo = (($producto_id * 3) % 15) + 3;

// Rating
$rating = max(3.5, min(5.0, (float)($producto['rating'] ?? 4.2)));
$estrellas_llenas = floor($rating);
$media_estrella   = ($rating - $estrellas_llenas) >= 0.5;
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre']) ?> | Algorya</title>
    <?php
    $seo_titulo      = htmlspecialchars($producto['nombre']);
    $seo_descripcion = htmlspecialchars(substr($producto['descripcion'] ?? 'Descubre este producto en Algorya al mejor precio.', 0, 155));
    $seo_imagen      = 'https://algorya.store/img/' . htmlspecialchars($producto['imagen']);
    $seo_tipo        = 'product';
    require 'includes/seo.php';
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- NAVBAR COMPLETO -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-decoration-none d-flex align-items-center gap-2" href="index.php">
            <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 1L13 14H10.5L9.5 11H6.5L5.5 14H3L8 1ZM7.2 9H8.8L8 6.5Z" fill="white"/></svg>
            </div>
            <div class="d-flex align-items-baseline gap-1">
                <span class="fw-black text-primary" style="font-family:'Outfit',sans-serif;font-size:1.2rem;letter-spacing:-.04em;">Algorya</span><span class="premium-muted fw-semibold" style="font-size:.75rem;">.store</span>
            </div>
        </a>

        <!-- Móvil -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <div id="darkModeToggleMobile" style="width:34px;height:34px;cursor:pointer;border:1px solid var(--border-color);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-moon-stars-fill" style="font-size:.85rem;pointer-events:none;color:var(--text-main);"></i>
            </div>
            <a href="carrito.php" class="btn btn-outline-primary btn-sm rounded-pill position-relative d-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                <i class="bi bi-cart3" style="font-size:.85rem;"></i>
                <span id="cart-badge-mobile" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.55rem;<?= $contador_carrito > 0 ? '' : 'display:none;' ?>"><?= $contador_carrito ?></span>
            </a>
            <button class="navbar-toggler border-0 p-0 d-flex align-items-center justify-content-center rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#navColapse" style="width:34px;height:34px;background:var(--hover-bg);">
                <i class="bi bi-list premium-text" style="font-size:1.2rem;"></i>
            </button>
        </div>

        <!-- Escritorio -->
        <div class="d-none d-lg-flex align-items-center gap-2 ms-auto">
            <div id="darkModeToggle" style="cursor:pointer;"><i class="bi bi-moon-stars-fill fs-6"></i></div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?= LANG === 'es' ? '🇪🇸 ES' : '🇬🇧 EN' ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow mt-1" style="min-width:110px;">
                    <li><a class="dropdown-item premium-text <?= LANG==='es'?'fw-bold text-primary':'' ?>" href="<?= $url_sin_lang.$sep ?>lang=es">🇪🇸 Español</a></li>
                    <li><a class="dropdown-item premium-text <?= LANG==='en'?'fw-bold text-primary':'' ?>" href="<?= $url_sin_lang.$sep ?>lang=en">🇬🇧 English</a></li>
                </ul>
            </div>
            <a href="carrito.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 position-relative d-flex align-items-center">
                <i class="bi bi-cart3 me-1"></i><?= t('nav_carrito') ?>
                <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm" <?= $contador_carrito > 0 ? '' : 'style="display:none;"' ?>><?= $contador_carrito ?></span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="perfil.php" class="premium-text text-decoration-none fw-semibold d-flex align-items-center gap-1" style="font-size:.9rem;">
                    <i class="bi bi-person-circle text-primary"></i><?= htmlspecialchars($_SESSION['nombre']) ?>
                </a>
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin_estadisticas.php" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-gear-fill me-1"></i>Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger btn-sm border-0 rounded-pill"><i class="bi bi-box-arrow-right"></i></a>
            <?php else: ?>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="bi bi-person me-1"></i><?= t('nav_entrar') ?>
                </button>
                <a href="registro.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-person-plus me-1"></i><?= t('nav_registrarse') ?>
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i><?= t('producto_volver') ?>
            </a>
        </div>

        <!-- Menú móvil -->
        <div class="collapse navbar-collapse" id="navColapse">
            <div class="d-lg-none pt-3 pb-2 border-top mt-2" style="border-color:var(--border-color) !important;">
                <div class="d-flex gap-2 mb-3">
                    <a href="<?= $url_sin_lang.$sep ?>lang=es" class="btn btn-sm rounded-pill fw-bold flex-grow-1 <?= LANG==='es'?'btn-primary':'btn-outline-secondary' ?>">🇪🇸 Español</a>
                    <a href="<?= $url_sin_lang.$sep ?>lang=en" class="btn btn-sm rounded-pill fw-bold flex-grow-1 <?= LANG==='en'?'btn-primary':'btn-outline-secondary' ?>">🇬🇧 English</a>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="perfil.php" class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text fw-semibold mb-2" style="border:1px solid var(--border-color);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-black text-white" style="width:32px;height:32px;background:linear-gradient(135deg,#3b82f6,#6366f1);font-size:.85rem;">
                            <?= strtoupper(mb_substr($_SESSION['nombre'], 0, 1, 'UTF-8')) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill fw-semibold mb-2"><i class="bi bi-box-arrow-right me-2"></i><?= t('nav_cerrar_sesion') ?></a>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary w-100 rounded-pill fw-bold py-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-person me-2"></i><?= t('nav_entrar') ?>
                        </button>
                        <a href="registro.php" class="btn btn-primary w-100 rounded-pill fw-bold py-2">
                            <i class="bi bi-person-plus me-2"></i><?= t('nav_registrarse') ?>
                        </a>
                    </div>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary w-100 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i><?= t('producto_volver') ?>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- CONTENIDO -->
<div class="container my-4 my-md-5 flex-grow-1">

    <!-- PRODUCTO -->
    <div class="premium-card rounded-4 overflow-hidden shadow-sm mb-4">
        <div class="row g-0">

            <!-- Imagen -->
            <div class="col-md-6 premium-img-wrapper d-flex align-items-center justify-content-center p-4 p-md-5" style="min-height:320px;">
                <img src="img/<?= htmlspecialchars($producto['imagen']) ?>"
                     class="img-fluid rounded-3"
                     alt="<?= htmlspecialchars($producto['nombre']) ?>"
                     style="max-height:420px;object-fit:contain;filter:drop-shadow(0 8px 24px rgba(0,0,0,0.1));"
                     onerror="this.src='https://dummyimage.com/400x400/dee2e6/6c757d.jpg&text=Sin+Imagen'">
            </div>

            <!-- Detalles -->
            <div class="col-md-6 p-4 p-md-5 d-flex flex-column justify-content-center">

                <!-- Badges -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(59,130,246,.12);color:#2563eb;font-size:.78rem;font-weight:600;">
                        <i class="bi bi-lightning-charge-fill me-1"></i><?= t('producto_en_stock') ?>
                    </span>
                    <?php if ($producto['destacado'] ?? 0): ?>
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(245,158,11,.12);color:#d97706;font-size:.78rem;font-weight:600;">
                        <i class="bi bi-fire me-1"></i><?= LANG === 'es' ? 'Tendencia' : 'Trending' ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($producto['stock'] <= 5): ?>
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(239,68,68,.12);color:#dc2626;font-size:.78rem;font-weight:600;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= LANG === 'es' ? '¡Últimas unidades!' : 'Last units!' ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Nombre -->
                <h1 class="fw-black premium-text mb-2" style="font-family:'Outfit',sans-serif;font-size:clamp(1.4rem,3vw,2rem);letter-spacing:-.03em;line-height:1.15;">
                    <?= htmlspecialchars($producto['nombre']) ?>
                </h1>

                <!-- Rating -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="d-flex gap-1">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <?php if ($s <= $estrellas_llenas): ?>
                            <i class="bi bi-star-fill" style="color:#f59e0b;font-size:.9rem;"></i>
                            <?php elseif ($s === $estrellas_llenas + 1 && $media_estrella): ?>
                            <i class="bi bi-star-half" style="color:#f59e0b;font-size:.9rem;"></i>
                            <?php else: ?>
                            <i class="bi bi-star" style="color:#f59e0b;font-size:.9rem;"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="fw-bold premium-text" style="font-size:.88rem;"><?= number_format($rating,1) ?></span>
                    <span class="premium-muted" style="font-size:.8rem;">(<?= $producto['reviews'] ?? rand(80,300) ?> <?= LANG === 'es' ? 'valoraciones' : 'reviews' ?>)</span>
                </div>

                <!-- Precio -->
                <div class="mb-3">
                    <span class="fw-black text-success" style="font-family:'Outfit',sans-serif;font-size:2.4rem;letter-spacing:-.04em;line-height:1;">
                        <?= number_format($producto['precio'],2) ?> €
                    </span>
                    <span class="premium-muted ms-2" style="font-size:.8rem;"><?= LANG === 'es' ? 'IVA incluido' : 'VAT included' ?></span>
                </div>

                <!-- FOMO -->
                <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded-3" style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);">
                    <span>👁️</span>
                    <span class="fw-semibold" style="font-size:.82rem;color:#dc2626;">
                        <?= $viendo ?> <?= LANG === 'es' ? 'personas están viendo esto ahora' : 'people are viewing this right now' ?>
                    </span>
                </div>

                <!-- Descripción -->
                <p class="premium-muted mb-3" style="font-size:.9rem;line-height:1.6;">
                    <?= htmlspecialchars($producto['descripcion'] ?: '—') ?>
                </p>

                <!-- Stock -->
                <p class="premium-muted mb-4" style="font-size:.85rem;">
                    <?= t('producto_stock') ?>
                    <strong class="<?= $producto['stock'] <= 5 ? 'text-danger' : 'text-success' ?>">
                        <?= (int)$producto['stock'] ?> uds
                    </strong>
                    <?php if ($producto['stock'] <= 10 && $producto['stock'] > 5): ?>
                    <span class="text-warning fw-semibold ms-1" style="font-size:.78rem;">— <?= LANG === 'es' ? '¡Quedan pocas!' : 'Almost sold out!' ?></span>
                    <?php endif; ?>
                </p>

                <!-- Formulario -->
                <form action="index.php" method="POST" class="form-add-cart mt-auto">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="hidden" name="id"       value="<?= (int)$producto['id'] ?>">
                    <input type="hidden" name="nombre"   value="<?= htmlspecialchars($producto['nombre']) ?>">
                    <input type="hidden" name="precio"   value="<?= $producto['precio'] ?>">
                    <input type="hidden" name="imagen"   value="<?= htmlspecialchars($producto['imagen']) ?>">
                    <div class="d-flex gap-2 mb-3">
                        <div class="input-group" style="width:120px;flex-shrink:0;">
                            <span class="input-group-text premium-input bg-transparent border-end-0 fw-semibold" style="font-size:.82rem;">Cant:</span>
                            <input type="number" name="cantidad" value="1" min="1" max="<?= (int)$producto['stock'] ?>"
                                   class="form-control premium-input border-start-0 ps-0 text-center fw-bold shadow-none" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold flex-grow-1 shadow-sm btn-submit-cart" style="font-family:'Outfit',sans-serif;">
                            <i class="bi bi-cart-plus me-2"></i><?= t('producto_btn_anadir') ?>
                        </button>
                    </div>
                    <div class="text-center premium-muted" style="font-size:.78rem;">
                        <i class="bi bi-shield-check text-success me-1"></i><?= t('checkout_seguro') ?>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- RESEÑAS -->
    <div class="premium-card rounded-4 p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold premium-text mb-0" style="font-family:'Outfit',sans-serif;">
                <i class="bi bi-chat-square-quote-fill text-primary me-2"></i>
                <?= LANG === 'es' ? 'Opiniones de clientes' : 'Customer reviews' ?>
            </h4>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex gap-1">
                    <?php for ($s=1;$s<=5;$s++): ?><i class="bi bi-star-fill" style="color:#f59e0b;font-size:.85rem;"></i><?php endfor; ?>
                </div>
                <span class="fw-black premium-text" style="font-family:'Outfit',sans-serif;"><?= number_format($rating,1) ?>/5</span>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($reseñas as $r): ?>
            <div class="col-md-6">
                <div class="p-3 rounded-3 h-100" style="border:1px solid var(--border-color);background:var(--hover-bg);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-black text-white flex-shrink-0"
                                 style="width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#6366f1);font-size:.85rem;font-family:'Outfit',sans-serif;">
                                <?= strtoupper($r['nombre'][0]) ?>
                            </div>
                            <div>
                                <div class="fw-semibold premium-text" style="font-size:.88rem;"><?= $r['nombre'] ?></div>
                                <?php if ($r['verificado']): ?>
                                <div style="font-size:.7rem;color:#16a34a;" class="fw-semibold">
                                    <i class="bi bi-patch-check-fill me-1"></i><?= LANG === 'es' ? 'Compra verificada' : 'Verified purchase' ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <?php for ($s=0;$s<$r['rating'];$s++): ?><i class="bi bi-star-fill" style="color:#f59e0b;font-size:.75rem;"></i><?php endfor; ?>
                        </div>
                    </div>
                    <p class="premium-muted mb-2" style="font-size:.85rem;line-height:1.5;">"<?= $r['comentario'] ?>"</p>
                    <div class="premium-muted" style="font-size:.72rem;">
                        <?= LANG === 'es' ? "Hace {$r['dias']} días" : "{$r['dias']} days ago" ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:1050;">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body fw-bold fs-6"><i class="bi bi-check-circle-fill me-2"></i>¡Añadido al carrito!</div>
            <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Modal Login -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal rounded-4 border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold premium-text"><i class="bi bi-box-seam-fill text-primary me-1"></i>Algorya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <h4 class="fw-bold mb-3 premium-text"><?= t('login_bienvenido') ?></h4>
                <a href="google_login.php" class="btn w-100 rounded-pill fw-semibold mb-3 d-flex align-items-center justify-content-center gap-2"
                   style="border:1.5px solid var(--border-color);background:var(--card-bg);color:var(--text-main);font-size:.9rem;padding:.6rem 1rem;">
                    <svg width="18" height="18" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.35-8.16 2.35-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Continuar con Google
                </a>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <hr style="flex:1;border-color:var(--border-color);opacity:1;">
                    <span class="premium-muted" style="font-size:.78rem;">o con email</span>
                    <hr style="flex:1;border-color:var(--border-color);opacity:1;">
                </div>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label premium-muted small fw-bold"><?= t('modal_login_email') ?></label>
                        <input type="email" name="email" class="form-control form-control-lg premium-input shadow-none" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label premium-muted small fw-bold"><?= t('modal_login_password') ?></label>
                        <input type="password" name="password" class="form-control form-control-lg premium-input shadow-none" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 btn-lg rounded-pill fw-bold" style="background:#3b82f6;border:none;"><?= t('modal_login_btn') ?></button>
                </form>
                <div class="text-center mt-3">
                    <a href="recuperar_password.php" class="premium-muted text-decoration-none" style="font-size:.82rem;">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-4 mt-auto" style="border-top:1px solid var(--border-color);">
    <p class="mb-0 premium-muted small fw-bold"><i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy; <?= date('Y') ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.form-add-cart').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn-submit-cart');
            const orig = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?= LANG === "es" ? "Añadiendo..." : "Adding..." ?>';
            btn.disabled = true;
            fetch('index.php', { method:'POST', body:new FormData(this), headers:{'X-Requested-With':'XMLHttpRequest'} })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        ['cart-badge','cart-badge-mobile'].forEach(id => {
                            const el = document.getElementById(id);
                            if (el) { el.textContent = data.cart_count; el.style.display = 'inline-block'; }
                        });
                        new bootstrap.Toast(document.getElementById('cartToast')).show();
                    }
                })
                .finally(() => { btn.innerHTML = orig; btn.disabled = false; });
        });
    });
});
</script>
</body>
</html>