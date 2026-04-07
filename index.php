<?php
session_start();
require 'includes/db.php';
require 'includes/lang.php';

// ─── CARRITO: Añadir producto ────────────────────────────────────────────────
if (isset($_POST['add_to_cart'])) {
    $cantidad_pedida = max(1, (int)($_POST['cantidad'] ?? 1));
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $pid = (int)$_POST['id'];
        $res = $conn->query("SELECT id, cantidad FROM carritos WHERE usuario_id=$uid AND producto_id=$pid");
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $nueva = $r['cantidad'] + $cantidad_pedida;
            $conn->query("UPDATE carritos SET cantidad=$nueva, fecha_agregado=CURRENT_TIMESTAMP WHERE id={$r['id']}");
        } else {
            $conn->query("INSERT INTO carritos (usuario_id, producto_id, cantidad) VALUES ($uid,$pid,$cantidad_pedida)");
        }
    } else {
        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $_POST['id']) { $item['cantidad'] += $cantidad_pedida; $encontrado = true; break; }
        }
        if (!$encontrado) {
            $_SESSION['carrito'][] = ['id'=>$_POST['id'],'nombre'=>$_POST['nombre'],'precio'=>$_POST['precio'],'imagen'=>$_POST['imagen'],'cantidad'=>$cantidad_pedida];
        }
    }
    $contador_carrito = 0;
    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $r = $conn->query("SELECT SUM(cantidad) as t FROM carritos WHERE usuario_id=$uid")->fetch_assoc();
        $contador_carrito = (int)($r['t'] ?? 0);
    } else {
        foreach ($_SESSION['carrito'] ?? [] as $item) $contador_carrito += $item['cantidad'];
    }
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status'=>'success','cart_count'=>$contador_carrito]);
        exit();
    }
    header("Location: index.php?agregado=1"); exit();
}

// ─── CONTADOR CARRITO ────────────────────────────────────────────────────────
$contador_carrito = 0;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $r = $conn->query("SELECT SUM(cantidad) as t FROM carritos WHERE usuario_id=$uid")->fetch_assoc();
    $contador_carrito = (int)($r['t'] ?? 0);
} else {
    foreach ($_SESSION['carrito'] ?? [] as $item) $contador_carrito += $item['cantidad'];
}

// ─── FILTROS ─────────────────────────────────────────────────────────────────
$f_nombre     = trim($_GET['nombre'] ?? '');
$f_precio_min = (float)($_GET['precio_min'] ?? 0);
$f_precio_max = (float)($_GET['precio_max'] ?? 9999);
$f_orden      = $_GET['orden'] ?? 'trend_score';
$ordenes_ok   = ['trend_score'=>'trend_score DESC','precio_asc'=>'precio ASC','precio_desc'=>'precio DESC','nombre_asc'=>'nombre ASC'];
$orden_sql    = $ordenes_ok[$f_orden] ?? 'trend_score DESC';

$where = "activo = 1 AND precio BETWEEN $f_precio_min AND $f_precio_max";
if ($f_nombre !== '') $where .= " AND nombre LIKE '%" . $conn->real_escape_string($f_nombre) . "%'";

// ─── PAGINACIÓN ──────────────────────────────────────────────────────────────
$por_pagina   = 12;
$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$total        = (int)$conn->query("SELECT COUNT(*) as t FROM productos WHERE $where")->fetch_assoc()['t'];
$total_paginas = max(1, ceil($total / $por_pagina));
if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
$offset   = ($pagina_actual - 1) * $por_pagina;
$resultado = $conn->query("SELECT * FROM productos WHERE $where ORDER BY $orden_sql LIMIT $por_pagina OFFSET $offset");

$params = array_filter(['nombre'=>$f_nombre,'precio_min'=>$f_precio_min ?: null,'precio_max'=>$f_precio_max != 9999 ? $f_precio_max : null,'orden'=>$f_orden !== 'trend_score' ? $f_orden : null,'lang'=>$_GET['lang'] ?? null]);
$base_url = '?' . ($params ? http_build_query($params) . '&' : '');

// ─── SELECTOR IDIOMA ─────────────────────────────────────────────────────────
$url_sin_lang = preg_replace('/([&?])lang=[a-z]{2}(&|$)/', '$1', $_SERVER['REQUEST_URI']);
$url_sin_lang = rtrim($url_sin_lang, '?&');
$sep = str_contains($url_sin_lang, '?') ? '&' : '?';
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algorya | <?= t('catalogo_titulo') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand text-decoration-none d-flex align-items-center gap-2" href="index.php">
            <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 1L13 14H10.5L9.5 11H6.5L5.5 14H3L8 1ZM7.2 9H8.8L8 6.5Z" fill="white"/></svg>
            </div>
            <span class="fw-black text-primary" style="font-family:'Outfit',sans-serif;font-size:1.2rem;letter-spacing:-.04em;">Algorya</span><span class="premium-muted" style="font-size:.6rem;font-weight:500;margin-left:1px;">.store</span>
        </a>

        <!-- Móvil: iconos + hamburguesa -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <div id="darkModeToggleMobile" style="width:34px;height:34px;cursor:pointer;border:1px solid var(--border-color);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-moon-stars-fill" style="font-size:.85rem;pointer-events:none;color:var(--text-main);"></i>
            </div>
            <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
            <a href="carrito.php" class="btn btn-outline-primary btn-sm rounded-pill position-relative d-flex align-items-center justify-content-center" style="width:34px;height:34px;padding:0;">
                <i class="bi bi-cart3" style="font-size:.85rem;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.55rem;" <?= $contador_carrito > 0 ? '' : 'style="display:none;"' ?>><?= $contador_carrito ?></span>
            </a>
            <?php endif; ?>
            <button class="navbar-toggler border-0 p-0 d-flex align-items-center justify-content-center rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#navColapse" style="width:34px;height:34px;background:var(--hover-bg);">
                <i class="bi bi-list premium-text" style="font-size:1.2rem;"></i>
            </button>
        </div>

        <!-- Escritorio -->
        <div class="d-none d-lg-flex align-items-center gap-2 ms-auto">
            <div id="darkModeToggle" style="cursor:pointer;" title="<?= t('nav_modo_oscuro') ?>">
                <i class="bi bi-moon-stars-fill fs-6"></i>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?= LANG === 'es' ? '🇪🇸 ES' : '🇬🇧 EN' ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow mt-1" style="min-width:110px;">
                    <li><a class="dropdown-item premium-text <?= LANG==='es'?'fw-bold text-primary':'' ?>" href="<?= $url_sin_lang.$sep ?>lang=es">🇪🇸 <?= t('lang_es') ?></a></li>
                    <li><a class="dropdown-item premium-text <?= LANG==='en'?'fw-bold text-primary':'' ?>" href="<?= $url_sin_lang.$sep ?>lang=en">🇬🇧 <?= t('lang_en') ?></a></li>
                </ul>
            </div>
            <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
            <a href="carrito.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 position-relative d-flex align-items-center">
                <i class="bi bi-cart3 me-1"></i><?= t('nav_carrito') ?>
                <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm" <?= $contador_carrito > 0 ? '' : 'style="display:none;"' ?>><?= $contador_carrito ?></span>
            </a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="perfil.php" class="premium-text text-decoration-none fw-semibold d-flex align-items-center gap-1" style="font-size:.9rem;">
                    <i class="bi bi-person-circle text-primary"></i><?= htmlspecialchars($_SESSION['nombre']) ?>
                </a>
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear-fill me-1"></i><?= t('nav_gestion') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow-lg mt-2">
                        <li><a class="dropdown-item premium-text" href="admin_pedidos.php"><i class="bi bi-receipt me-2 text-primary"></i><?= t('nav_pedidos') ?></a></li>
                        <li><a class="dropdown-item premium-text" href="admin_usuarios.php"><i class="bi bi-people me-2 text-primary"></i><?= t('nav_clientes') ?></a></li>
                        <li><a class="dropdown-item premium-text" href="admin_estadisticas.php"><i class="bi bi-bar-chart me-2 text-primary"></i><?= t('nav_estadisticas') ?></a></li>
                        <li><a class="dropdown-item premium-text" href="admin_mailing.php"><i class="bi bi-envelope-at me-2 text-primary"></i><?= t('nav_enviar_email') ?></a></li>
                        <li><hr class="dropdown-divider" style="border-color:var(--border-color);"></li>
                        <li><a class="dropdown-item premium-text" href="add_product.php"><i class="bi bi-plus-circle me-2 text-success"></i><?= t('nav_anadir_producto') ?></a></li>
                    </ul>
                </div>
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
        </div>

        <!-- Menú colapsable móvil -->
        <div class="collapse navbar-collapse" id="navColapse">
            <div class="d-lg-none pt-3 pb-2 border-top mt-2" style="border-color:var(--border-color) !important;">
                <div class="d-flex gap-2 mb-3">
                    <a href="<?= $url_sin_lang.$sep ?>lang=es" class="btn btn-sm rounded-pill fw-bold flex-grow-1 <?= LANG==='es'?'btn-primary':'btn-outline-secondary' ?>">🇪🇸 Español</a>
                    <a href="<?= $url_sin_lang.$sep ?>lang=en" class="btn btn-sm rounded-pill fw-bold flex-grow-1 <?= LANG==='en'?'btn-primary':'btn-outline-secondary' ?>">🇬🇧 English</a>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="perfil.php" class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text fw-semibold mb-2" style="border:1px solid var(--border-color);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-black text-white flex-shrink-0" style="width:32px;height:32px;background:linear-gradient(135deg,#3b82f6,#6366f1);font-family:'Outfit',sans-serif;font-size:.85rem;">
                            <?= strtoupper(mb_substr($_SESSION['nombre'], 0, 1, 'UTF-8')) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
                        <i class="bi bi-chevron-right ms-auto premium-muted" style="font-size:.75rem;"></i>
                    </a>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <div class="d-flex flex-column gap-1 mb-2">
                        <a href="admin_pedidos.php" class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text" style="border:1px solid var(--border-color);"><i class="bi bi-receipt text-primary"></i><?= t('nav_pedidos') ?></a>
                        <a href="admin_estadisticas.php" class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text" style="border:1px solid var(--border-color);"><i class="bi bi-bar-chart text-primary"></i><?= t('nav_estadisticas') ?></a>
                    </div>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill fw-semibold"><i class="bi bi-box-arrow-right me-2"></i><?= t('nav_cerrar_sesion') ?></a>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-outline-primary w-100 rounded-pill fw-bold py-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-person me-2"></i><?= t('nav_entrar') ?>
                        </button>
                        <a href="registro.php" class="btn btn-primary w-100 rounded-pill fw-bold py-2">
                            <i class="bi bi-person-plus me-2"></i><?= t('nav_registrarse') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="hero-section mb-5">
    <div class="container text-center" data-aos="zoom-in" data-aos-duration="800">
        <h1 class="fw-bold mb-3 premium-text" style="font-size:clamp(2rem,6vw,3.5rem);letter-spacing:-2px;">
            <?= t('hero_titulo') ?>
        </h1>
        <p class="premium-muted fs-5 mx-auto" style="max-width:600px;">
            <?= t('hero_subtitulo') ?>
        </p>
    </div>
</div>

<!-- CATÁLOGO -->
<div class="container mb-5">

    <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-2" style="border-color:var(--border-color) !important;">
        <h3 class="fw-bold m-0 premium-text"><?= t('catalogo_titulo') ?></h3>
        <span class="text-success small fw-semibold"><i class="bi bi-cloud-check me-1"></i><?= t('catalogo_sincronizado') ?></span>
    </div>

    <!-- Filtros -->
    <form method="GET" action="index.php" class="mb-4">
        <?php if (isset($_GET['lang'])): ?><input type="hidden" name="lang" value="<?= htmlspecialchars($_GET['lang']) ?>"><?php endif; ?>
        <div class="premium-card admin-card rounded-4 p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;"><i class="bi bi-search me-1"></i>Buscar</label>
                    <input type="text" name="nombre" class="form-control premium-input shadow-none" placeholder="Nombre del producto..." value="<?= htmlspecialchars($f_nombre) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Precio mín.</label>
                    <input type="number" name="precio_min" class="form-control premium-input shadow-none" min="0" value="<?= $f_precio_min ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Precio máx.</label>
                    <input type="number" name="precio_max" class="form-control premium-input shadow-none" min="0" value="<?= $f_precio_max ?>">
                </div>
                <div class="col-8 col-md-3">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Ordenar por</label>
                    <select name="orden" class="form-select premium-input shadow-none">
                        <option value="trend_score" <?= $f_orden==='trend_score'?'selected':'' ?>>Tendencia primero</option>
                        <option value="precio_asc"  <?= $f_orden==='precio_asc' ?'selected':'' ?>>Precio: menor primero</option>
                        <option value="precio_desc" <?= $f_orden==='precio_desc'?'selected':'' ?>>Precio: mayor primero</option>
                        <option value="nombre_asc"  <?= $f_orden==='nombre_asc' ?'selected':'' ?>>Nombre A→Z</option>
                    </select>
                </div>
                <div class="col-4 col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold w-100"><i class="bi bi-funnel-fill"></i></button>
                </div>
            </div>
        </div>
    </form>

    <!-- Grid productos -->
    <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-5">
    <?php if ($resultado && $resultado->num_rows > 0):
        $delay = 0;
        while ($row = $resultado->fetch_assoc()): ?>
        <div class="col" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
            <div class="card premium-card h-100 rounded-4 overflow-hidden position-relative">
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <div class="position-absolute top-0 end-0 p-2 z-2 d-flex gap-1">
                    <a href="admin_edit_product.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm rounded-pill p-1 d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="bi bi-pencil-fill"></i></a>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill p-1 d-flex align-items-center justify-content-center btn-delete-product" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['nombre']) ?>" style="width:32px;height:32px;"><i class="bi bi-trash-fill"></i></button>
                </div>
                <?php endif; ?>
                <a href="producto.php?id=<?= $row['id'] ?>" class="premium-img-wrapper d-block text-center text-decoration-none">
                    <img src="img/<?= htmlspecialchars($row['imagen']) ?>" class="img-fluid"
                         alt="<?= htmlspecialchars($row['nombre']) ?>"
                         style="height:200px;width:100%;object-fit:contain;filter:drop-shadow(0 4px 6px rgba(0,0,0,0.05));"
                         onerror="this.src='https://dummyimage.com/200x200/dee2e6/6c757d.jpg&text=Sin+Imagen'">
                </a>
                <div class="card-body d-flex flex-column pt-3">
                    <a href="producto.php?id=<?= $row['id'] ?>" class="text-decoration-none mb-2">
                        <h5 class="card-title fw-bold text-truncate premium-text mb-0" title="<?= htmlspecialchars($row['nombre']) ?>" style="font-size:.9rem;"><?= htmlspecialchars($row['nombre']) ?></h5>
                    </a>
                    <div class="mt-auto pt-2 border-top" style="border-color:var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-black text-success" style="font-size:1.1rem;"><?= number_format($row['precio'],2) ?> €</span>
                            <span class="premium-muted" style="font-size:.75rem;">Stock: <?= $row['stock'] ?></span>
                        </div>
                        <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
                        <form action="index.php" method="POST" class="m-0 form-add-cart">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="id"       value="<?= $row['id'] ?>">
                            <input type="hidden" name="nombre"   value="<?= htmlspecialchars($row['nombre']) ?>">
                            <input type="hidden" name="precio"   value="<?= $row['precio'] ?>">
                            <input type="hidden" name="imagen"   value="<?= htmlspecialchars($row['imagen']) ?>">
                            <input type="hidden" name="cantidad" value="1">
                            <button type="submit" class="btn btn-premium-add w-100 rounded-pill fw-bold py-2 shadow-sm btn-submit-cart" style="font-size:.82rem;">
                                <i class="bi bi-cart-plus me-1"></i><?= t('btn_anadir_carro') ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php $delay = ($delay < 300) ? $delay + 50 : 0; endwhile;
    else: ?>
        <div class="col-12">
            <div class="alert premium-card text-center py-5 rounded-4">
                <i class="bi bi-inbox fs-1 premium-muted"></i>
                <h5 class="mt-3 fw-bold premium-text"><?= t('catalogo_vacio_titulo') ?></h5>
                <p class="premium-muted mb-0"><?= t('catalogo_vacio_texto') ?></p>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <nav aria-label="Paginación">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $pagina_actual<=1?'disabled':'' ?>">
                <a class="page-link premium-pagination rounded-start-pill px-4 py-2 fw-bold" href="<?= $base_url ?>pagina=<?= $pagina_actual-1 ?>"><i class="bi bi-chevron-left me-1"></i><?= t('paginacion_anterior') ?></a>
            </li>
            <?php for ($i=1; $i<=$total_paginas; $i++): ?>
            <li class="page-item <?= $pagina_actual==$i?'active':'' ?>">
                <a class="page-link premium-pagination py-2 px-3 fw-bold" href="<?= $base_url ?>pagina=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina_actual>=$total_paginas?'disabled':'' ?>">
                <a class="page-link premium-pagination rounded-end-pill px-4 py-2 fw-bold" href="<?= $base_url ?>pagina=<?= $pagina_actual+1 ?>"><?= t('paginacion_siguiente') ?> <i class="bi bi-chevron-right ms-1"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<!-- FOOTER -->
<footer class="text-center py-5 mt-auto" style="border-top:1px solid var(--border-color);">
    <div class="container">
        <h5 class="fw-bold premium-text mb-3"><i class="bi bi-box-seam-fill text-primary"></i> Algorya</h5>
        <p class="mb-1 fw-medium premium-text opacity-75">© <?= date("Y") ?> Todos los derechos reservados.</p>
        <p class="mb-0 premium-muted small fw-bold">Proyecto Final de Grado de ASIR realizado por Hugo Labao González</p>
    </div>
</footer>

<!-- MODAL LOGIN -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal rounded-4 border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold premium-text"><i class="bi bi-box-seam-fill text-primary me-1"></i>Algorya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <h4 class="fw-bold mb-4 premium-text">Bienvenido de nuevo</h4>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label premium-muted small fw-bold"><?= t('modal_login_email') ?></label>
                        <input type="email" name="email" class="form-control form-control-lg premium-input shadow-none" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label premium-muted small fw-bold"><?= t('modal_login_password') ?></label>
                        <input type="password" name="password" class="form-control form-control-lg premium-input shadow-none" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 btn-lg rounded-pill fw-bold shadow-sm" style="background:#3b82f6;border:none;"><?= t('modal_login_btn') ?></button>
                </form>
                <div class="text-center mt-3">
                    <a href="recuperar_password.php" class="text-decoration-none premium-muted" style="font-size:.82rem;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
                <div class="text-center mt-3">
                    <span class="premium-muted small"><?= t('modal_login_sin_cuenta') ?> <a href="registro.php" class="text-primary text-decoration-none fw-bold"><?= t('modal_login_registro') ?></a></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast carrito -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:1050;">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body fw-bold fs-6"><i class="bi bi-check-circle-fill me-2"></i>¡Añadido al carrito con éxito!</div>
            <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 50 });

// Carrito AJAX
document.querySelectorAll('.form-add-cart').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('.btn-submit-cart');
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
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

<?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
document.querySelectorAll('.btn-delete-product').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        if (!confirm('¿Borrar "' + this.dataset.name + '"?')) return;
        const orig = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        this.disabled = true;
        fetch('includes/admin_delete_product_ajax.php', {
            method:'POST', body:new URLSearchParams({id:this.dataset.id,action:'delete'}), headers:{'X-Requested-With':'XMLHttpRequest'}
        }).then(r=>r.json()).then(data=>{
            if(data.status==='success') location.reload();
            else { alert('Error: '+data.message); this.innerHTML=orig; this.disabled=false; }
        }).catch(()=>{ this.innerHTML=orig; this.disabled=false; });
    });
});
<?php endif; ?>
</script>
</body>
</html>