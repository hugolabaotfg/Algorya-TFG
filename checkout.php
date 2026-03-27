<?php
session_start();
require 'includes/lang.php';
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];
$nombre_cliente = htmlspecialchars($_SESSION['nombre']);

$stmt = $conn->prepare(
    "SELECT c.producto_id, c.cantidad, p.nombre, p.precio, p.imagen
     FROM carritos c JOIN productos p ON c.producto_id = p.id
     WHERE c.usuario_id = ? ORDER BY c.fecha_agregado DESC"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$res_cart = $stmt->get_result();
$stmt->close();

if ($res_cart->num_rows === 0) {
    header("Location: carrito.php");
    exit();
}

$total_pedido = 0;
$items_pedido = [];
while ($row = $res_cart->fetch_assoc()) {
    $total_pedido += $row['precio'] * $row['cantidad'];
    $items_pedido[] = $row;
}
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= t('checkout_titulo') ?> | Algorya
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
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
                <a href="carrito.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>
                    <?= t('checkout_volver') ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <h2 class="fw-bold premium-text mb-4">
                    <i class="bi bi-bag-check text-primary me-2"></i>
                    <?= t('checkout_titulo') ?>
                </h2>

                <div class="card premium-card border-0 rounded-4 shadow-sm mb-4">
                    <div class="card-body p-4">

                        <h6 class="fw-bold premium-muted text-uppercase mb-3"
                            style="letter-spacing:.5px;font-size:.75rem;">
                            <i class="bi bi-list-ul me-1"></i>
                            <?= t('checkout_resumen') ?>
                        </h6>

                        <?php foreach ($items_pedido as $item): ?>
                            <div class="d-flex align-items-center gap-3 py-2 border-bottom"
                                style="border-color:var(--border-color) !important;">
                                <div class="flex-shrink-0 bg-white rounded-3 border p-1" style="width:55px;height:55px;">
                                    <img src="img/<?= htmlspecialchars($item['imagen']) ?>" alt=""
                                        style="width:100%;height:100%;object-fit:contain;"
                                        onerror="this.src='https://dummyimage.com/55x55/dee2e6/6c757d.jpg&text=?'">
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold premium-text" style="font-size:.9rem;">
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </p>
                                    <small class="premium-muted">
                                        <?= t('checkout_cantidad') ?>
                                        <?= (int) $item['cantidad'] ?>
                                    </small>
                                </div>
                                <span class="fw-bold text-success">
                                    <?= number_format($item['precio'] * $item['cantidad'], 2) ?> €
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                            <span class="fw-bold premium-text fs-5">
                                <?= t('checkout_total') ?>
                            </span>
                            <span class="fw-black text-primary fs-4">
                                <?= number_format($total_pedido, 2) ?> €
                            </span>
                        </div>
                    </div>
                </div>

                <div class="alert border-0 rounded-3 mb-4 d-flex gap-2 align-items-start"
                    style="background:rgba(59,130,246,0.1);color:var(--text-main);">
                    <i class="bi bi-info-circle-fill text-primary mt-1 flex-shrink-0"></i>
                    <div>
                        <strong>
                            <?= t('checkout_sandbox_titulo') ?>
                        </strong> —
                        <?= t('checkout_sandbox_texto') ?><br>
                        <span class="small premium-muted">
                            <?= t('checkout_sandbox_tarj') ?>
                            <code class="text-primary fw-bold">4242 4242 4242 4242</code>
                        </span>
                    </div>
                </div>

                <form action="stripe_create_session.php" method="POST">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3"
                        style="background:linear-gradient(135deg,#3b82f6,#6366f1);border:none;font-size:1.1rem;">
                        <i class="bi bi-stripe me-2"></i>
                        <?= t('checkout_btn_pagar', [number_format($total_pedido, 2)]) ?>
                    </button>
                </form>

                <p class="text-center premium-muted small mt-3">
                    <i class="bi bi-lock-fill me-1"></i>
                    <?= t('checkout_seguro') ?>
                </p>

            </div>
        </div>
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