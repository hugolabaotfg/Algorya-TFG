<?php
// =============================================================================
// ALGORYA - checkout.php
// Página de resumen del pedido.
// Muestra los productos del carrito y el total antes de redirigir a Stripe.
// NO procesa el pago aquí — eso lo hace stripe_create_session.php
// =============================================================================

session_start();
require 'includes/db.php';

// Solo usuarios logueados pueden llegar aquí
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];
$nombre_cliente = htmlspecialchars($_SESSION['nombre']);

// Leer el carrito del usuario desde la base de datos
$stmt = $conn->prepare(
    "SELECT c.producto_id, c.cantidad, p.nombre, p.precio, p.imagen
     FROM carritos c
     JOIN productos p ON c.producto_id = p.id
     WHERE c.usuario_id = ?
     ORDER BY c.fecha_agregado DESC"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$res_cart = $stmt->get_result();
$stmt->close();

// Si el carrito está vacío, redirigir al carrito
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
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing: -1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i>
                <span class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size:0.55em;">.store</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <div id="darkModeToggle" title="Alternar Modo Oscuro" class="me-2">
                    <i class="bi bi-moon-stars-fill fs-6"></i>
                </div>
                <a href="carrito.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>Volver al carrito
                </a>
            </div>
        </div>
    </nav>

    <!-- CONTENIDO -->
    <div class="container my-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <!-- Título -->
                <h2 class="fw-bold premium-text mb-4">
                    <i class="bi bi-bag-check text-primary me-2"></i>Confirmar tu pedido
                </h2>

                <!-- Tarjeta con el resumen -->
                <div class="card premium-card border-0 rounded-4 shadow-sm mb-4">
                    <div class="card-body p-4">

                        <h6 class="fw-bold premium-muted text-uppercase mb-3"
                            style="letter-spacing:.5px; font-size:.75rem;">
                            <i class="bi bi-list-ul me-1"></i>Resumen de productos
                        </h6>

                        <!-- Lista de productos del carrito -->
                        <?php foreach ($items_pedido as $item): ?>
                            <div class="d-flex align-items-center gap-3 py-2 border-bottom"
                                style="border-color: var(--border-color) !important;">
                                <div class="flex-shrink-0 bg-white rounded-3 border p-1" style="width:55px;height:55px;">
                                    <img src="img/<?php echo htmlspecialchars($item['imagen']); ?>" alt=""
                                        style="width:100%;height:100%;object-fit:contain;"
                                        onerror="this.src='https://dummyimage.com/55x55/dee2e6/6c757d.jpg&text=?'">
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold premium-text" style="font-size:.9rem;">
                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                    </p>
                                    <small class="premium-muted">Cantidad:
                                        <?php echo (int) $item['cantidad']; ?>
                                    </small>
                                </div>
                                <span class="fw-bold text-success">
                                    <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?> €
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <!-- Total -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                            <span class="fw-bold premium-text fs-5">Total a pagar:</span>
                            <span class="fw-black text-primary fs-4">
                                <?php echo number_format($total_pedido, 2); ?> €
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Aviso Sandbox -->
                <div class="alert border-0 rounded-3 mb-4 d-flex gap-2 align-items-start"
                    style="background: rgba(59,130,246,0.1); color: var(--text-main);">
                    <i class="bi bi-info-circle-fill text-primary mt-1 flex-shrink-0"></i>
                    <div>
                        <strong>Modo Sandbox (Pruebas)</strong> — No se realizarán cargos reales.<br>
                        <span class="small premium-muted">
                            Usa la tarjeta de prueba de Stripe:
                            <code class="text-primary fw-bold">4242 4242 4242 4242</code>
                            · Fecha: cualquiera futura · CVC: cualquier 3 dígitos
                        </span>
                    </div>
                </div>

                <!-- Botón de pago — envía a stripe_create_session.php -->
                <form action="stripe_create_session.php" method="POST">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3"
                        style="background: linear-gradient(135deg, #3b82f6, #6366f1); border: none; font-size: 1.1rem;">
                        <i class="bi bi-stripe me-2"></i>
                        Pagar
                        <?php echo number_format($total_pedido, 2); ?> € con Stripe
                    </button>
                </form>

                <p class="text-center premium-muted small mt-3">
                    <i class="bi bi-lock-fill me-1"></i>
                    Pago seguro procesado por Stripe. Algorya nunca almacena datos de tu tarjeta.
                </p>

            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="text-center py-4 mt-auto" style="border-top: 1px solid var(--border-color);">
        <p class="mb-0 premium-muted small fw-bold">
            <i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy;
            <?php echo date("Y"); ?>
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>