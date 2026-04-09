<?php
session_start();
require 'includes/lang.php';
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['eliminar_item'])) {
        $producto_id = (int) $_POST['producto_id'];
        if (isset($_SESSION['user_id'])) {
            $uid = (int) $_SESSION['user_id'];
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
            $uid = (int) $_SESSION['user_id'];
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
}

$carrito_items = [];
$total = 0;

if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
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
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= t('carrito_titulo') ?> | Algorya
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <style>
        .table-premium {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            vertical-align: middle;
        }

        .table-premium th {
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .5px;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
        }

        .table-premium td {
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing:-1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i>
                <span class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size:.55em;">.store</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <div id="darkModeToggle" title="<?= t('nav_modo_oscuro') ?>" class="me-2">
                    <i class="bi bi-moon-stars-fill fs-6"></i>
                </div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>
                    <?= t('carrito_seguir') ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <h2 class="fw-bold premium-text mb-4">
            <i class="bi bi-cart3 text-primary me-2"></i>
            <?= t('carrito_titulo') ?>
        </h2>

        <?php if (count($carrito_items) > 0): ?>
            <div class="card premium-card border-0 rounded-4 p-4 shadow-sm mb-5">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead>
                            <tr>
                                <th>
                                    <?= t('carrito_col_producto') ?>
                                </th>
                                <th class="text-center">
                                    <?= t('carrito_col_cantidad') ?>
                                </th>
                                <th class="text-end">
                                    <?= t('carrito_col_precio') ?>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carrito_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-white p-2 rounded border" style="width:60px;height:60px;">
                                                <img src="img/<?= htmlspecialchars($item['imagen']) ?>" alt="" class="img-fluid"
                                                    style="object-fit:contain;width:100%;height:100%;"
                                                    onerror="this.src='https://dummyimage.com/60x60/dee2e6/6c757d.jpg&text=?'">
                                            </div>
                                            <span class="fw-bold premium-text">
                                                <?= htmlspecialchars($item['nombre']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold premium-text">
                                        <?= (int) $item['cantidad'] ?>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        <?= number_format($item['precio'] * $item['cantidad'], 2) ?> €
                                    </td>
                                    <td class="text-end">
                                        <form action="carrito.php" method="POST" class="m-0">
                                            <input type="hidden" name="producto_id" value="<?= (int) $item['id'] ?>">
                                            <button type="submit" name="eliminar_item"
                                                class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top"
                    style="border-color:var(--border-color) !important;">
                    <h3 class="fw-bold premium-text m-0 mb-3 mb-md-0">
                        <?= t('carrito_total') ?> <span class="text-primary">
                            <?= number_format($total, 2) ?> €
                        </span>
                    </h3>
                    <div class="d-flex gap-2">
                        <form action="carrito.php" method="POST" class="m-0">
                            <button type="submit" name="vaciar" class="btn btn-outline-danger rounded-pill px-4">
                                <i class="bi bi-trash me-1"></i>
                                <?= t('carrito_vaciar') ?>
                            </button>
                        </form>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="checkout.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                <?= t('carrito_pagar') ?> <i class="bi bi-shield-lock ms-1"></i>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                <i class="bi bi-person-lock me-1"></i>
                                <?= t('carrito_login_pagar') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="card premium-card border-0 rounded-4 p-5 text-center shadow-sm">
                <i class="bi bi-cart-x fs-1 premium-muted mb-3"></i>
                <h4 class="fw-bold premium-text">
                    <?= t('carrito_vacio_titulo') ?>
                </h4>
                <p class="premium-muted mb-4">
                    <?= t('carrito_vacio_texto') ?>
                </p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 mx-auto" style="width:fit-content;">
                    <i class="bi bi-bag me-1"></i>
                    <?= t('carrito_ir_tienda') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="text-center py-4 mt-auto" style="border-top:1px solid var(--border-color);">
        <p class="mb-0 premium-muted small fw-bold">
            <i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy;
            <?= date('Y') ?>
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="tema.js"></script>
</body>

</html>