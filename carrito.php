<?php
session_start();
require 'includes/lang.php';
require 'includes/db.php';

// ─── POST HANDLERS ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['eliminar_item'])) {
        $producto_id = (int) $_POST['producto_id'];
        if (isset($_SESSION['user_id'])) {
            $uid  = (int) $_SESSION['user_id'];
            $stmt = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ? AND producto_id = ?");
            $stmt->bind_param("ii", $uid, $producto_id);
            $stmt->execute();
            $stmt->close();
        } else {
            if (isset($_SESSION['carrito'])) {
                foreach ($_SESSION['carrito'] as $key => $item) {
                    if ((int) $item['id'] === $producto_id) {
                        unset($_SESSION['carrito'][$key]);
                        break;
                    }
                }
                $_SESSION['carrito'] = array_values($_SESSION['carrito']);
            }
        }
        header("Location: carrito.php");
        exit();
    }

    if (isset($_POST['vaciar'])) {
        if (isset($_SESSION['user_id'])) {
            $uid  = (int) $_SESSION['user_id'];
            $stmt = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
        } else {
            $_SESSION['carrito'] = [];
        }
        header("Location: carrito.php");
        exit();
    }

    if (isset($_POST['actualizar_cantidad'])) {
        $producto_id = (int) $_POST['producto_id'];
        $cantidad    = max(1, (int) $_POST['cantidad']);
        if (isset($_SESSION['user_id'])) {
            $uid  = (int) $_SESSION['user_id'];
            $stmt = $conn->prepare("UPDATE carritos SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
            $stmt->bind_param("iii", $cantidad, $uid, $producto_id);
            $stmt->execute();
            $stmt->close();
        } else {
            if (isset($_SESSION['carrito'])) {
                foreach ($_SESSION['carrito'] as &$item) {
                    if ((int) $item['id'] === $producto_id) {
                        $item['cantidad'] = $cantidad;
                        break;
                    }
                }
                unset($item);
            }
        }
        header("Location: carrito.php");
        exit();
    }
}

// ─── CARGAR ITEMS ─────────────────────────────────────────────────────────────

$carrito_items = [];
$total         = 0;

if (isset($_SESSION['user_id'])) {
    $uid  = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT c.producto_id AS id, c.cantidad, p.nombre, p.precio, p.imagen
         FROM carritos c JOIN productos p ON c.producto_id = p.id
         WHERE c.usuario_id = ? ORDER BY c.fecha_agregado DESC"
    );
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) {
        $carrito_items[] = $row;
        $total += $row['precio'] * $row['cantidad'];
    }
    $stmt->close();
} else {
    if (!empty($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $carrito_items[] = $item;
            $total += $item['precio'] * $item['cantidad'];
        }
    }
}

$num_items    = count($carrito_items);
$envio_umbral = 50;
$envio_gratis = ($total >= $envio_umbral);
$falta_gratis = max(0, $envio_umbral - $total);

// ─── NAVBAR HELPERS ───────────────────────────────────────────────────────────

$contador_carrito = 0;
foreach ($carrito_items as $ci) $contador_carrito += (int)$ci['cantidad'];

$url_sin_lang = preg_replace('/([&?])lang=[a-z]{2}(&|$)/', '$1', $_SERVER['REQUEST_URI']);
$url_sin_lang = rtrim($url_sin_lang, '?&');
$sep          = str_contains($url_sin_lang, '?') ? '&' : '?';
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('carrito_titulo') ?> | Algorya</title>
    <?php require 'includes/seo.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">

        <a class="navbar-brand text-decoration-none d-flex align-items-center gap-2" href="index.php">
            <div class="navbar-logo-icon" style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 1L13 14H10.5L9.5 11H6.5L5.5 14H3L8 1ZM7.2 9H8.8L8 6.5Z" fill="white"/></svg>
            </div>
            <div class="d-flex align-items-baseline gap-1">
                <span class="fw-black text-primary" style="font-family:'Outfit',sans-serif;font-size:1.2rem;letter-spacing:-.04em;">Algorya</span><span class="premium-muted fw-semibold" style="font-size:.75rem;">.store</span>
            </div>
        </a>

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
                <i class="bi bi-arrow-left me-1"></i><?= t('carrito_seguir') ?>
            </a>
        </div>

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
                    <i class="bi bi-arrow-left me-1"></i><?= t('carrito_seguir') ?>
                </a>
            </div>
        </div>

    </div>
</nav>

<div class="container my-4 my-md-5 flex-grow-1">

    <div class="mb-4">
        <h2 class="fw-black premium-text mb-1" style="font-family:'Outfit',sans-serif;font-size:clamp(1.6rem,4vw,2.2rem);letter-spacing:-.04em;">
            <i class="bi bi-cart3 text-primary me-2"></i><?= t('carrito_titulo') ?>
        </h2>
        <?php if ($num_items > 0): ?>
        <p class="premium-muted mb-0" style="font-size:.9rem;">
            <?= $num_items === 1
                ? (LANG === 'es' ? '1 producto en tu carrito' : '1 product in your cart')
                : (LANG === 'es' ? "$num_items productos en tu carrito" : "$num_items products in your cart") ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if ($num_items > 0): ?>

    <div class="row g-4 align-items-start">

        <div class="col-lg-8">
            <div class="premium-card admin-card rounded-4 overflow-hidden shadow-sm">
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead>
                            <tr>
                                <th style="padding-left:1.25rem;" colspan="2"><?= t('carrito_col_producto') ?></th>
                                <th class="text-center d-none d-md-table-cell"><?= t('carrito_col_precio') ?> / u.</th>
                                <th class="text-center"><?= t('carrito_col_cantidad') ?></th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carrito_items as $item): ?>
                            <tr>
                                <td style="width:66px;padding-left:1.25rem;">
                                    <div class="rounded-3 overflow-hidden d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;background:var(--hover-bg);border:1px solid var(--border-color);">
                                        <img src="img/<?= htmlspecialchars($item['imagen']) ?>"
                                             alt="<?= htmlspecialchars($item['nombre']) ?>"
                                             style="width:100%;height:100%;object-fit:contain;opacity:var(--img-opacity);"
                                             onerror="this.src='https://dummyimage.com/50x50/dee2e6/6c757d.jpg&text=?'">
                                    </div>
                                </td>
                                <td class="premium-text fw-semibold" style="font-size:.9rem;vertical-align:middle;">
                                    <?= htmlspecialchars($item['nombre']) ?>
                                </td>
                                <td class="text-center premium-muted d-none d-md-table-cell" style="font-size:.88rem;">
                                    <?= number_format($item['precio'], 2) ?> €
                                </td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <form action="carrito.php" method="POST" class="m-0">
                                            <input type="hidden" name="producto_id" value="<?= (int)$item['id'] ?>">
                                            <input type="hidden" name="cantidad"    value="<?= max(1, (int)$item['cantidad'] - 1) ?>">
                                            <button type="submit" name="actualizar_cantidad"
                                                    class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width:28px;height:28px;padding:0;border:1px solid var(--border-color);background:var(--hover-bg);color:var(--text-main);"
                                                    <?= (int)$item['cantidad'] <= 1 ? 'disabled' : '' ?>>
                                                <i class="bi bi-dash" style="font-size:.8rem;"></i>
                                            </button>
                                        </form>
                                        <span class="fw-bold premium-text" style="min-width:24px;text-align:center;font-family:'Outfit',sans-serif;">
                                            <?= (int)$item['cantidad'] ?>
                                        </span>
                                        <form action="carrito.php" method="POST" class="m-0">
                                            <input type="hidden" name="producto_id" value="<?= (int)$item['id'] ?>">
                                            <input type="hidden" name="cantidad"    value="<?= (int)$item['cantidad'] + 1 ?>">
                                            <button type="submit" name="actualizar_cantidad"
                                                    class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width:28px;height:28px;padding:0;border:1px solid var(--border-color);background:var(--hover-bg);color:var(--text-main);">
                                                <i class="bi bi-plus" style="font-size:.8rem;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td class="text-end fw-bold" style="font-family:'Outfit',sans-serif;color:var(--text-main);">
                                    <?= number_format($item['precio'] * $item['cantidad'], 2) ?> €
                                </td>
                                <td class="text-end" style="padding-right:1.25rem;">
                                    <form action="carrito.php" method="POST" class="m-0">
                                        <input type="hidden" name="producto_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" name="eliminar_item"
                                                class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center ms-auto"
                                                style="width:30px;height:30px;padding:0;border:1px solid rgba(220,38,38,.25);background:rgba(239,68,68,.06);color:#dc2626;"
                                                title="<?= LANG === 'es' ? 'Eliminar' : 'Remove' ?>">
                                            <i class="bi bi-trash3" style="font-size:.75rem;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="premium-card admin-card rounded-4 p-4 shadow-sm" style="position:sticky;top:80px;">

                <h5 class="fw-bold premium-text mb-4" style="font-family:'Outfit',sans-serif;letter-spacing:-.02em;">
                    <?= LANG === 'es' ? 'Resumen del pedido' : 'Order summary' ?>
                </h5>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="premium-muted" style="font-size:.9rem;">
                        Subtotal <span class="premium-subtle">(<?= $num_items ?> <?= $num_items === 1 ? (LANG === 'es' ? 'art.' : 'item') : (LANG === 'es' ? 'art.' : 'items') ?>)</span>
                    </span>
                    <span class="fw-semibold premium-text"><?= number_format($total, 2) ?> €</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="premium-muted" style="font-size:.9rem;">
                        <?= LANG === 'es' ? 'Envío' : 'Shipping' ?>
                    </span>
                    <?php if ($envio_gratis): ?>
                    <span class="fw-semibold text-success" style="font-size:.88rem;">
                        <i class="bi bi-check-circle-fill me-1"></i><?= LANG === 'es' ? 'Gratis' : 'Free' ?>
                    </span>
                    <?php else: ?>
                    <span class="premium-muted" style="font-size:.82rem;">
                        <?= LANG === 'es' ? 'Al pagar' : 'At checkout' ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!$envio_gratis): ?>
                <div class="mb-4 p-3 rounded-3" style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);">
                    <span class="d-block mb-2" style="font-size:.78rem;font-weight:600;color:#2563eb;">
                        <i class="bi bi-truck me-1"></i>
                        <?= LANG === 'es'
                            ? 'Te faltan ' . number_format($falta_gratis, 2) . ' € para envío gratis'
                            : 'Add ' . number_format($falta_gratis, 2) . ' € more for free shipping' ?>
                    </span>
                    <div class="progress" style="height:5px;background:rgba(59,130,246,.15);border-radius:99px;">
                        <div class="progress-bar" role="progressbar"
                             style="width:<?= min(100, round($total / $envio_umbral * 100)) ?>%;background:linear-gradient(90deg,#3b82f6,#6366f1);border-radius:99px;"></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="mb-4 p-3 rounded-3" style="background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.2);">
                    <span style="font-size:.8rem;font-weight:600;color:#16a34a;">
                        <i class="bi bi-truck me-1"></i>
                        <?= LANG === 'es' ? '¡Envío gratis en tu pedido!' : 'Free shipping on your order!' ?>
                    </span>
                </div>
                <?php endif; ?>

                <hr style="border-color:var(--border-color);opacity:1;margin:0 0 1rem;">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold premium-text" style="font-family:'Outfit',sans-serif;font-size:1rem;"><?= t('carrito_total') ?></span>
                    <span class="fw-black text-primary" style="font-family:'Outfit',sans-serif;font-size:1.8rem;letter-spacing:-.04em;line-height:1;">
                        <?= number_format($total, 2) ?> €
                    </span>
                </div>

                <div class="d-flex flex-column gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="checkout.php" class="btn btn-primary rounded-pill fw-bold py-3 shadow-sm" style="font-family:'Outfit',sans-serif;font-size:1rem;">
                        <i class="bi bi-shield-lock me-2"></i><?= t('carrito_pagar') ?>
                    </a>
                    <?php else: ?>
                    <button type="button" class="btn btn-primary rounded-pill fw-bold py-3 shadow-sm" style="font-family:'Outfit',sans-serif;font-size:1rem;" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-person-lock me-2"></i><?= t('carrito_login_pagar') ?>
                    </button>
                    <?php endif; ?>

                    <a href="index.php" class="btn btn-outline-secondary rounded-pill fw-semibold py-2" style="font-size:.88rem;">
                        <i class="bi bi-arrow-left me-1"></i><?= t('carrito_seguir') ?>
                    </a>

                    <button type="button" class="btn btn-outline-danger rounded-pill fw-semibold py-2" style="font-size:.85rem;" data-bs-toggle="modal" data-bs-target="#modalVaciar">
                        <i class="bi bi-trash me-1"></i><?= t('carrito_vaciar') ?>
                    </button>
                </div>

                <div class="text-center mt-3 premium-muted" style="font-size:.75rem;">
                    <i class="bi bi-shield-check text-success me-1"></i><?= t('checkout_seguro') ?>
                </div>

            </div>
        </div>

    </div><?php else: ?>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="premium-card rounded-4 p-5 text-center shadow-sm">
                <div class="mx-auto mb-4 d-flex align-items-center justify-content-center" style="width:80px;height:80px;border-radius:50%;background:rgba(59,130,246,.08);">
                    <i class="bi bi-cart-x" style="font-size:2.2rem;color:#3b82f6;"></i>
                </div>
                <h4 class="fw-bold premium-text mb-2" style="font-family:'Outfit',sans-serif;"><?= t('carrito_vacio_titulo') ?></h4>
                <p class="premium-muted mb-4" style="font-size:.9rem;"><?= t('carrito_vacio_texto') ?></p>
                <a href="index.php" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-sm" style="font-family:'Outfit',sans-serif;">
                    <i class="bi bi-bag me-2"></i><?= t('carrito_ir_tienda') ?>
                </a>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div><div class="modal fade" id="modalVaciar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content premium-modal rounded-4 border-0">
            <div class="modal-body p-4 text-center">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px;height:60px;border-radius:50%;background:rgba(239,68,68,.10);">
                    <i class="bi bi-trash3-fill" style="font-size:1.5rem;color:#dc2626;"></i>
                </div>
                <h5 class="fw-bold premium-text mb-2" style="font-family:'Outfit',sans-serif;">
                    <?= LANG === 'es' ? '¿Vaciar el carrito?' : 'Empty the cart?' ?>
                </h5>
                <p class="premium-muted mb-4" style="font-size:.88rem;"><?= t('carrito_vaciar_confirm') ?></p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1 fw-semibold" data-bs-dismiss="modal">
                        <?= LANG === 'es' ? 'Cancelar' : 'Cancel' ?>
                    </button>
                    <form action="carrito.php" method="POST" class="flex-grow-1 m-0">
                        <button type="submit" name="vaciar" class="btn btn-danger rounded-pill w-100 fw-bold">
                            <i class="bi bi-trash3 me-1"></i><?= t('carrito_vaciar') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal rounded-4 border-0">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold premium-text"><i class="bi bi-box-seam-fill text-primary me-1"></i>Algorya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <h4 class="fw-bold mb-3 premium-text"><?= t('login_titulo') ?></h4>
                <a href="google_login.php" class="btn w-100 rounded-pill fw-semibold mb-3 d-flex align-items-center justify-content-center gap-2"
                   style="border:1.5px solid var(--border-color);background:var(--bg-card);color:var(--text-main);font-size:.9rem;padding:.6rem 1rem;">
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
                    <a href="recuperar_password.php" class="premium-muted text-decoration-none" style="font-size:.82rem;">
                        <?= LANG === 'es' ? '¿Olvidaste tu contraseña?' : 'Forgot your password?' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-4 mt-auto" style="border-top:1px solid var(--border-color);">
    <p class="mb-0 premium-muted small fw-bold">
        <i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy; <?= date('Y') ?>
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
</body>
</html>