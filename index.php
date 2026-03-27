<?php
// =============================================================================
// ALGORYA - index.php
// Catálogo principal con sistema i18n (ES/EN) integrado.
// =============================================================================

session_start();
require 'includes/db.php';
require 'includes/lang.php'; // ← Carga el motor i18n + función t()

// =============================================================================
// ACCIÓN AJAX — Añadir producto al carrito
// =============================================================================

if (isset($_POST['add_to_cart'])) {

    $cantidad_pedida = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
    if ($cantidad_pedida < 1) $cantidad_pedida = 1;

    if (isset($_SESSION['user_id'])) {
        $usuario_id  = (int) $_SESSION['user_id'];
        $producto_id = (int) $_POST['id'];

        $stmt = $conn->prepare("SELECT id, cantidad FROM carritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $nueva_cantidad = $row['cantidad'] + $cantidad_pedida;
            $stmt2 = $conn->prepare("UPDATE carritos SET cantidad = ?, fecha_agregado = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt2->bind_param("ii", $nueva_cantidad, $row['id']);
            $stmt2->execute();
            $stmt2->close();
        } else {
            $stmt2 = $conn->prepare("INSERT INTO carritos (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
            $stmt2->bind_param("iii", $usuario_id, $producto_id, $cantidad_pedida);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();

    } else {
        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $_POST['id']) {
                $item['cantidad'] += $cantidad_pedida;
                $encontrado = true;
                break;
            }
        }
        unset($item);
        if (!$encontrado) {
            $_SESSION['carrito'][] = [
                'id'       => $_POST['id'],
                'nombre'   => $_POST['nombre'],
                'precio'   => $_POST['precio'],
                'imagen'   => $_POST['imagen'],
                'cantidad' => $cantidad_pedida
            ];
        }
    }

    $contador_carrito = 0;
    if (isset($_SESSION['user_id'])) {
        $uid = (int) $_SESSION['user_id'];
        $res = $conn->query("SELECT SUM(cantidad) as total FROM carritos WHERE usuario_id = $uid");
        if ($res && $row = $res->fetch_assoc()) $contador_carrito = (int)($row['total'] ?? 0);
    } else {
        if (isset($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) $contador_carrito += $item['cantidad'];
        }
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'cart_count' => $contador_carrito]);
        exit();
    }

    header("Location: index.php?agregado=1");
    exit();
}

// =============================================================================
// CONTADOR INICIAL DEL CARRITO
// =============================================================================

$contador_carrito = 0;
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $res_contador = $conn->query("SELECT SUM(cantidad) as total FROM carritos WHERE usuario_id = $uid");
    if ($res_contador && $row_contador = $res_contador->fetch_assoc()) {
        $contador_carrito = (int)($row_contador['total'] ?? 0);
    }
} else {
    if (isset($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) $contador_carrito += $item['cantidad'];
    }
}

// =============================================================================
// FILTROS DE BÚSQUEDA + PAGINACIÓN
// Parámetros GET: buscar, precio_min, precio_max, orden, pagina
// =============================================================================

$productos_por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Recoger filtros de forma segura
$buscar     = trim($_GET['buscar']     ?? '');
$precio_min = isset($_GET['precio_min']) && $_GET['precio_min'] !== '' ? (float)$_GET['precio_min'] : null;
$precio_max = isset($_GET['precio_max']) && $_GET['precio_max'] !== '' ? (float)$_GET['precio_max'] : null;
$orden      = in_array($_GET['orden'] ?? '', ['precio_asc','precio_desc','nombre','reciente'])
              ? $_GET['orden'] : 'reciente';

// Construir WHERE dinámico con prepared statements
// activo = 1 es SIEMPRE obligatorio — productos desactivados nunca se muestran
$where_parts = ['activo = 1'];
$params      = [];
$tipos       = '';

if ($buscar !== '') {
    $where_parts[] = "nombre LIKE ?";
    $params[]      = '%' . $buscar . '%';
    $tipos        .= 's';
}
if ($precio_min !== null) {
    $where_parts[] = "precio >= ?";
    $params[]      = $precio_min;
    $tipos        .= 'd';
}
if ($precio_max !== null) {
    $where_parts[] = "precio <= ?";
    $params[]      = $precio_max;
    $tipos        .= 'd';
}

$where_sql = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$order_sql = match($orden) {
    'precio_asc'  => 'ORDER BY precio ASC',
    'precio_desc' => 'ORDER BY precio DESC',
    'nombre'      => 'ORDER BY nombre ASC',
    default       => 'ORDER BY destacado DESC, id DESC',
};

// Contar total con filtros
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM productos $where_sql");
if (!empty($params)) $stmt_count->bind_param($tipos, ...$params);
$stmt_count->execute();
$total_productos = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_paginas = max(1, ceil($total_productos / $productos_por_pagina));
if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Consulta principal
$stmt_prod = $conn->prepare("SELECT * FROM productos $where_sql $order_sql LIMIT $productos_por_pagina OFFSET $offset");
if (!empty($params)) $stmt_prod->bind_param($tipos, ...$params);
$stmt_prod->execute();
$resultado = $stmt_prod->get_result();
$stmt_prod->close();

// URL base para paginación conservando filtros activos
$filtros_activos = array_filter([
    'buscar'     => $buscar,
    'precio_min' => $precio_min !== null ? $precio_min : '',
    'precio_max' => $precio_max !== null ? $precio_max : '',
    'orden'      => $orden !== 'reciente' ? $orden : '',
]);
$filtros_url = http_build_query($filtros_activos);
$base_url    = '?' . ($filtros_url ? $filtros_url . '&' : '');

// =============================================================================
// URL base para el selector de idioma (quita ?lang=xx de la URL actual)
// Así el selector no acumula parámetros ?lang=es&lang=en&lang=es...
// =============================================================================
$url_sin_lang = preg_replace('/([&?])lang=[a-z]{2}(&|$)/', '$1', $_SERVER['REQUEST_URI']);
$url_sin_lang = rtrim($url_sin_lang, '?&');
$separador    = str_contains($url_sin_lang, '?') ? '&' : '?';

?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('marca_nombre') ?> | <?= t('catalogo_titulo') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<!-- =========================================================================
     NAVBAR
     ========================================================================= -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing:-1px;">
            <i class="bi bi-box-seam-fill text-primary me-1"></i>
            <span class="text-primary"><?= t('marca_nombre') ?></span><span class="premium-text" style="font-size:0.55em;margin-left:1px;"><?= t('marca_tld') ?></span>
        </a>

        <div class="d-flex align-items-center gap-2 gap-md-3">

            <!-- Modo oscuro -->
            <div id="darkModeToggle" title="<?= t('nav_modo_oscuro') ?>">
                <i class="bi bi-moon-stars-fill fs-6"></i>
            </div>

            <!-- ── SELECTOR DE IDIOMA ────────────────────────────────────── -->
            <!-- Dropdown con banderas. Al pulsar cambia el idioma vía ?lang=xx
                 y lo guarda en cookie para todas las páginas. -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 dropdown-toggle"
                        type="button" data-bs-toggle="dropdown"
                        title="<?= t('lang_selector_titulo') ?>">
                    <?php if (LANG === 'es'): ?>
                        🇪🇸 <span class="d-none d-md-inline">ES</span>
                    <?php else: ?>
                        🇬🇧 <span class="d-none d-md-inline">EN</span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow mt-1" style="min-width:120px;">
                    <li>
                        <a class="dropdown-item premium-text <?= LANG === 'es' ? 'fw-bold text-primary' : '' ?>"
                           href="<?= $url_sin_lang . $separador ?>lang=es">
                            🇪🇸 <?= t('lang_es') ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item premium-text <?= LANG === 'en' ? 'fw-bold text-primary' : '' ?>"
                           href="<?= $url_sin_lang . $separador ?>lang=en">
                            🇬🇧 <?= t('lang_en') ?>
                        </a>
                    </li>
                </ul>
            </div>
            <!-- ── FIN SELECTOR DE IDIOMA ─────────────────────────────────── -->

            <!-- Carrito (oculto para admin) -->
            <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
            <a href="carrito.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 position-relative d-flex align-items-center">
                <i class="bi bi-cart3 me-1"></i>
                <span class="d-none d-md-inline"><?= t('nav_carrito') ?></span>
                <span id="cart-badge"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm"
                      <?= ($contador_carrito > 0) ? '' : 'style="display:none;"' ?>>
                    <?= $contador_carrito ?>
                </span>
            </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Usuario logueado -->
                <a href="perfil.php" class="premium-text d-none d-md-inline text-decoration-none fw-semibold">
                    <i class="bi bi-person-circle text-primary"></i>
                    <?= htmlspecialchars($_SESSION['nombre']) ?>
                </a>

                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3"
                            type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear-fill me-1"></i> <?= t('nav_gestion') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow-lg mt-2">
                        <li><a class="dropdown-item premium-text" href="admin_pedidos.php"><i class="bi bi-cart-check me-2"></i><?= t('nav_pedidos') ?></a></li>
                        <li><a class="dropdown-item premium-text" href="admin_usuarios.php"><i class="bi bi-people me-2"></i><?= t('nav_clientes') ?></a></li>
                        <li><a class="dropdown-item premium-text" href="admin_estadisticas.php"><i class="bi bi-bar-chart me-2"></i><?= t('nav_estadisticas') ?></a></li>
                        <li><a class="dropdown-item premium-text" href="admin_mailing.php"><i class="bi bi-envelope-at me-2"></i><?= t('nav_enviar_email') ?></a></li>
                        <li><hr class="dropdown-divider" style="border-color:var(--border-color);"></li>
                        <li><a class="dropdown-item premium-text" href="add_product.php"><i class="bi bi-plus-circle me-2"></i><?= t('nav_anadir_producto') ?></a></li>
                    </ul>
                </div>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-danger btn-sm border-0 rounded-pill ms-1"
                   title="<?= t('nav_cerrar_sesion') ?>">
                    <i class="bi bi-box-arrow-right"></i>
                </a>

            <?php else: ?>
                <!-- Visitante: abre modal de login o va a registro -->
                <button type="button"
                        class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold"
                        data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="bi bi-person me-1"></i> <?= t('nav_entrar') ?>
                </button>
                <a href="registro.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-person-plus me-1"></i>
                    <span class="d-none d-md-inline"><?= t('nav_registrarse') ?></span>
                </a>
            <?php endif; ?>

        </div>
    </div>
</nav>


<!-- =========================================================================
     HERO
     ========================================================================= -->
<div class="hero-section mb-5">
    <div class="container text-center" data-aos="zoom-in" data-aos-duration="800">
        <h1 class="fw-bold mb-3 premium-text" style="font-size:3.5rem;letter-spacing:-2px;">
            <?= t('hero_titulo') ?>
        </h1>
        <p class="premium-muted fs-5 mx-auto" style="max-width:600px;">
            <?= t('hero_subtitulo') ?>
        </p>
    </div>
</div>


<!-- =========================================================================
     CATÁLOGO
     ========================================================================= -->
<div class="container mb-5">

    <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-2"
         style="border-color:var(--border-color) !important;">
        <h3 class="fw-bold m-0 premium-text"><?= t('catalogo_titulo') ?></h3>
        <span class="text-success small fw-semibold">
            <i class="bi bi-cloud-check me-1"></i><?= t('catalogo_sincronizado') ?>
        </span>
    </div>

    <!-- BARRA DE FILTROS -->
    <form method="GET" action="index.php" class="mb-4">
        <?php if (isset($_GET['lang'])): ?>
        <input type="hidden" name="lang" value="<?= htmlspecialchars($_GET['lang']) ?>">
        <?php endif; ?>
        <div class="card premium-card border-0 rounded-4 shadow-sm p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.4px;">
                        <i class="bi bi-search me-1"></i><?= LANG === 'en' ? 'Search' : 'Buscar' ?>
                    </label>
                    <input type="text" name="buscar"
                           class="form-control premium-input shadow-none"
                           placeholder="<?= LANG === 'en' ? 'Product name...' : 'Nombre del producto...' ?>"
                           value="<?= htmlspecialchars($buscar) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.4px;">
                        <?= LANG === 'en' ? 'Min €' : 'Precio mín.' ?>
                    </label>
                    <input type="number" name="precio_min" min="0" step="0.01"
                           class="form-control premium-input shadow-none" placeholder="0"
                           value="<?= $precio_min !== null ? $precio_min : '' ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.4px;">
                        <?= LANG === 'en' ? 'Max €' : 'Precio máx.' ?>
                    </label>
                    <input type="number" name="precio_max" min="0" step="0.01"
                           class="form-control premium-input shadow-none" placeholder="9999"
                           value="<?= $precio_max !== null ? $precio_max : '' ?>">
                </div>
                <div class="col-8 col-md-3">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.4px;">
                        <i class="bi bi-sort-down me-1"></i><?= LANG === 'en' ? 'Sort by' : 'Ordenar por' ?>
                    </label>
                    <select name="orden" class="form-select premium-input shadow-none">
                        <option value="reciente"    <?= $orden === 'reciente'    ? 'selected' : '' ?>><?= LANG === 'en' ? 'Trending first' : 'Tendencia primero' ?></option>
                        <option value="precio_asc"  <?= $orden === 'precio_asc'  ? 'selected' : '' ?>><?= LANG === 'en' ? 'Price: low to high' : 'Precio: menor a mayor' ?></option>
                        <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>><?= LANG === 'en' ? 'Price: high to low' : 'Precio: mayor a menor' ?></option>
                        <option value="nombre"      <?= $orden === 'nombre'      ? 'selected' : '' ?>><?= LANG === 'en' ? 'Name A-Z' : 'Nombre A-Z' ?></option>
                    </select>
                </div>
                <div class="col-4 col-md-1 d-flex gap-1 align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold flex-grow-1"
                            title="<?= LANG === 'en' ? 'Apply filters' : 'Filtrar' ?>">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <?php if ($buscar || $precio_min !== null || $precio_max !== null || $orden !== 'reciente'): ?>
                    <a href="index.php<?= isset($_GET['lang']) ? '?lang=' . htmlspecialchars($_GET['lang']) : '' ?>"
                       class="btn btn-outline-secondary rounded-pill"
                       title="<?= LANG === 'en' ? 'Clear' : 'Limpiar' ?>">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($buscar || $precio_min !== null || $precio_max !== null): ?>
            <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2"
                 style="border-color:var(--border-color) !important;">
                <span class="premium-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= $total_productos ?> <?= LANG === 'en' ? 'results' : 'resultados' ?>
                </span>
                <?php if ($buscar): ?>
                <span class="badge rounded-pill px-2" style="background:rgba(59,130,246,0.12);color:var(--text-main);">
                    "<?= htmlspecialchars($buscar) ?>"
                </span>
                <?php endif; ?>
                <?php if ($precio_min !== null || $precio_max !== null): ?>
                <span class="badge rounded-pill px-2" style="background:rgba(59,130,246,0.12);color:var(--text-main);">
                    <?= ($precio_min ?? '0') ?>€ — <?= ($precio_max ?? '∞') ?>€
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
        <?php
        if ($resultado && $resultado->num_rows > 0) {
            $delay = 0;
            while ($row = $resultado->fetch_assoc()) {
        ?>
        <div class="col" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
            <div class="card premium-card h-100 rounded-4 overflow-hidden position-relative">

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <div class="position-absolute top-0 end-0 p-2 z-2 d-flex gap-1 admin-actions">
                    <a href="admin_edit_product.php?id=<?= $row['id'] ?>"
                       class="btn btn-warning btn-sm rounded-pill p-1 d-flex align-items-center justify-content-center"
                       style="width:32px;height:32px;" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                    <button type="button"
                            class="btn btn-danger btn-sm rounded-pill p-1 d-flex align-items-center justify-content-center btn-delete-product"
                            data-id="<?= $row['id'] ?>"
                            data-name="<?= htmlspecialchars($row['nombre']) ?>"
                            style="width:32px;height:32px;" title="Borrar">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
                <?php endif; ?>

                <a href="producto.php?id=<?= $row['id'] ?>"
                   class="premium-img-wrapper d-block text-center text-decoration-none z-1">
                    <img src="img/<?= htmlspecialchars($row['imagen']) ?>"
                         class="img-fluid"
                         alt="<?= htmlspecialchars($row['nombre']) ?>"
                         style="height:200px;width:100%;object-fit:contain;filter:drop-shadow(0 4px 6px rgba(0,0,0,0.05));"
                         onerror="this.src='https://dummyimage.com/200x200/dee2e6/6c757d.jpg&text=Sin+Imagen'">
                </a>

                <div class="card-body d-flex flex-column pt-4 z-1">
                    <a href="producto.php?id=<?= $row['id'] ?>" class="text-decoration-none mb-3">
                        <h5 class="card-title fw-bold text-truncate premium-text"
                            title="<?= htmlspecialchars($row['nombre']) ?>">
                            <?= htmlspecialchars($row['nombre']) ?>
                        </h5>
                    </a>

                    <div class="mt-auto pt-3 border-top" style="border-color:var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-4 fw-black text-success">
                                <?= number_format($row['precio'], 2) ?> €
                            </span>
                            <span class="premium-muted small fw-medium">
                                <?= t('producto_stock') ?> <?= (int)$row['stock'] ?>
                            </span>
                        </div>

                        <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
                        <form action="index.php" method="POST" class="m-0 form-add-cart">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="id"       value="<?= (int)$row['id'] ?>">
                            <input type="hidden" name="nombre"   value="<?= htmlspecialchars($row['nombre']) ?>">
                            <input type="hidden" name="precio"   value="<?= $row['precio'] ?>">
                            <input type="hidden" name="imagen"   value="<?= htmlspecialchars($row['imagen']) ?>">
                            <input type="hidden" name="cantidad" value="1">
                            <button type="submit"
                                    class="btn btn-premium-add w-100 rounded-pill fw-bold py-2 shadow-sm btn-submit-cart"
                                    data-texto-original="<?= t('btn_anadir_carro') ?>"
                                    data-texto-cargando="<?= t('btn_anadiendo') ?>">
                                <i class="bi bi-cart-plus me-2"></i><?= t('btn_anadir_carro') ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
        <?php
            $delay = ($delay < 300) ? $delay + 50 : 0;
            }
        } else {
            echo '<div class="col-12">
                    <div class="alert premium-card text-center py-5 rounded-4" data-aos="zoom-in">
                        <i class="bi bi-inbox fs-1 premium-muted"></i>
                        <h5 class="mt-3 fw-bold premium-text">' . t('catalogo_vacio_titulo') . '</h5>
                        <p class="premium-muted mb-0">' . t('catalogo_vacio_texto') . '</p>
                    </div>
                  </div>';
        }
        ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <nav aria-label="Navegación del catálogo">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                <a class="page-link premium-pagination rounded-start-pill px-4 py-2 fw-bold"
                   href="<?= $base_url ?>pagina=<?= $pagina_actual - 1 ?>">
                    <i class="bi bi-chevron-left me-1"></i><?= t('paginacion_anterior') ?>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?= ($pagina_actual == $i) ? 'active' : '' ?>">
                <a class="page-link premium-pagination py-2 px-3 fw-bold"
                   href="<?= $base_url ?>pagina=<?= $i ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                <a class="page-link premium-pagination rounded-end-pill px-4 py-2 fw-bold"
                   href="<?= $base_url ?>pagina=<?= $pagina_actual + 1 ?>">
                    <?= t('paginacion_siguiente') ?> <i class="bi bi-chevron-right ms-1"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>


<!-- =========================================================================
     FOOTER
     ========================================================================= -->
<footer class="text-center py-5 mt-auto" style="border-top:1px solid var(--border-color);">
    <div class="container">
        <h5 class="fw-bold premium-text mb-3">
            <i class="bi bi-box-seam-fill text-primary"></i> <?= t('marca_nombre') ?>
        </h5>
        <p class="mb-1 fw-medium premium-text opacity-75">
            <?= t('pie_derechos', [date('Y')]) ?>
        </p>
        <p class="mb-0 premium-muted small fw-bold">
            <?= t('pie_autor') ?>
        </p>
    </div>
</footer>


<!-- =========================================================================
     MODAL DE LOGIN INLINE
     ========================================================================= -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal rounded-4 border-0 shadow-lg">

            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold premium-text fs-4" id="loginModalLabel">
                        <i class="bi bi-box-seam-fill text-primary me-2"></i><?= t('marca_nombre') ?>
                    </h5>
                    <p class="premium-muted small mb-0"><?= t('modal_login_subtitulo') ?></p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body px-4 py-3">

                <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
                <div class="alert alert-danger border-0 rounded-3 small py-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= t('modal_login_error') ?>
                </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <input type="hidden" name="redirect" value="index.php">

                    <div class="mb-3">
                        <label class="form-label premium-muted small fw-bold text-uppercase" style="letter-spacing:.5px;">
                            <?= t('modal_login_email') ?>
                        </label>
                        <input type="email" name="email"
                               class="form-control form-control-lg premium-input shadow-none rounded-3"
                               placeholder="tu@correo.com" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label premium-muted small fw-bold text-uppercase" style="letter-spacing:.5px;">
                            <?= t('modal_login_password') ?>
                        </label>
                        <div class="input-group">
                            <input type="password" id="modal-password" name="password"
                                   class="form-control form-control-lg premium-input shadow-none rounded-start-3"
                                   placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary rounded-end-3" type="button"
                                    id="toggle-modal-password"
                                    title="<?= t('modal_login_mostrar') ?>">
                                <i class="bi bi-eye" id="eye-icon-modal"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login"
                            class="btn btn-primary w-100 btn-lg rounded-pill fw-bold shadow-sm"
                            style="background-color:#3b82f6;border:none;">
                        <i class="bi bi-box-arrow-in-right me-2"></i><?= t('modal_login_btn') ?>
                    </button>
                </form>

                <hr class="my-3" style="border-color:var(--border-color);">

                <div class="text-center">
                    <span class="premium-muted small">
                        <?= t('modal_login_sin_cuenta') ?>
                        <a href="registro.php" class="text-primary text-decoration-none fw-bold">
                            <?= t('modal_login_registro') ?>
                        </a>
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>


<!-- Toast de confirmación del carrito -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:1050;">
    <div id="cartToast"
         class="toast align-items-center text-white bg-success border-0 shadow-lg"
         role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body fw-bold fs-6">
                <i class="bi bi-check-circle-fill me-2"></i>
                <!-- El texto del toast se inyecta desde JS para que también sea traducido -->
                <span id="toast-text"><?= t('toast_añadido') ?></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-3 m-auto"
                    data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>


<!-- =========================================================================
     SCRIPTS
     ========================================================================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="tema.js"></script>
<script>

AOS.init({ once: true, offset: 50 });

document.addEventListener("DOMContentLoaded", function () {

    // Abrir modal si login.php devolvió ?error=1
    if (new URLSearchParams(window.location.search).get('error') === '1') {
        new bootstrap.Modal(document.getElementById('loginModal')).show();
    }

    // Botón ojo del modal
    const toggleBtn = document.getElementById('toggle-modal-password');
    const passInput = document.getElementById('modal-password');
    const eyeIcon   = document.getElementById('eye-icon-modal');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passInput.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }

    // Carrito AJAX — los textos del botón vienen de data-attributes para ser traducibles
    document.querySelectorAll('.form-add-cart').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData      = new FormData(this);
            const btn           = this.querySelector('.btn-submit-cart');
            const textoOriginal = btn.getAttribute('data-texto-original');
            const textoCargando = btn.getAttribute('data-texto-cargando');
            const originalHtml  = btn.innerHTML;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + textoCargando;
            btn.disabled  = true;

            fetch('index.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    const badge = document.getElementById('cart-badge');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.style.display = 'inline-block';
                        badge.animate(
                            [{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }],
                            { duration: 350 }
                        );
                    }
                    new bootstrap.Toast(document.getElementById('cartToast')).show();
                }
            })
            .catch(err => console.error('Error AJAX carrito:', err))
            .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
        });
    });

    // Borrado AJAX de productos (solo admin)
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
    document.querySelectorAll('.btn-delete-product').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const productId   = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');

            if (confirm('¿Borrar permanentemente "' + productName + '" de Algorya?')) {
                const orig = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                this.disabled  = true;

                fetch('includes/admin_delete_product_ajax.php', {
                    method: 'POST',
                    body: new URLSearchParams({ 'id': productId, 'action': 'delete' }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        this.innerHTML = orig;
                        this.disabled  = false;
                    }
                })
                .catch(() => {
                    alert('Error de red.');
                    this.innerHTML = orig;
                    this.disabled  = false;
                });
            }
        });
    });
    <?php endif; ?>

});
</script>

</body>
</html>