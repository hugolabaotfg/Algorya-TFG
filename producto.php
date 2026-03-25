<?php
session_start();
require 'includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$producto_id = (int) $_GET['id'];
$sql = "SELECT * FROM productos WHERE id = $producto_id";
$resultado = $conn->query($sql);

if ($resultado->num_rows === 0) {
    die("Producto no encontrado.");
}
$producto = $resultado->fetch_assoc();

// Calculamos carrito para el badge superior
$contador_carrito = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = $conn->query("SELECT SUM(cantidad) as t FROM carritos WHERE usuario_id = $uid");
    if ($res && $row = $res->fetch_assoc())
        $contador_carrito = $row['t'] ? $row['t'] : 0;
} else {
    if (isset($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $i)
            $contador_carrito += $i['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($producto['nombre']); ?> | Algorya
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing: -1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i><span class="text-primary">Algorya</span><span
                    class="premium-text" style="font-size: 0.55em;">.store</span>
            </a>
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <div id="darkModeToggle" title="Alternar Modo Oscuro"><i class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="carrito.php"
                    class="btn btn-outline-primary btn-sm rounded-pill px-3 position-relative d-flex align-items-center">
                    <i class="bi bi-cart3 me-1"></i> <span class="d-none d-md-inline">Carrito</span>
                    <span id="cart-badge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm"
                        <?php echo ($contador_carrito > 0) ? '' : 'style="display:none;"'; ?>>
                        <?php echo $contador_carrito; ?>
                    </span>
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill"><i
                        class="bi bi-arrow-left"></i> Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <div class="card premium-card border-0 rounded-4 overflow-hidden shadow-sm">
            <div class="row g-0">
                <div class="col-md-6 premium-img-wrapper d-flex align-items-center justify-content-center p-5">
                    <img src="img/<?php echo htmlspecialchars($producto['imagen']); ?>" class="img-fluid rounded"
                        alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                        style="max-height: 400px; object-fit: contain;">
                </div>
                <div class="col-md-6 p-4 p-md-5 d-flex flex-column justify-content-center">
                    <span class="badge bg-primary rounded-pill mb-3 d-inline-block" style="width: fit-content;"><i
                            class="bi bi-lightning-charge-fill me-1"></i> Envío Dropshipping</span>
                    <h1 class="fw-bold premium-text mb-3">
                        <?php echo htmlspecialchars($producto['nombre']); ?>
                    </h1>
                    <h2 class="text-success fw-black mb-4">
                        <?php echo number_format($producto['precio'], 2); ?> €
                    </h2>

                    <p class="premium-muted mb-4">
                        Este artículo exclusivo ha sido seleccionado por nuestro algoritmo de tendencias. Stock actual
                        en almacén distribuidor: <strong>
                            <?php echo $producto['stock']; ?> uds
                        </strong>.
                    </p>

                    <form action="index.php" method="POST" class="form-add-cart mt-auto">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <input type="hidden" name="precio" value="<?php echo $producto['precio']; ?>">
                        <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($producto['imagen']); ?>">
                        <input type="hidden" name="add_to_cart" value="1">

                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div class="input-group" style="width: 130px;">
                                <span class="input-group-text premium-input bg-transparent border-end-0">Cant:</span>
                                <input type="number" name="cantidad" value="1" min="1"
                                    max="<?php echo $producto['stock']; ?>"
                                    class="form-control premium-input border-start-0 ps-0 text-center fw-bold" required>
                            </div>
                            <button type="submit"
                                class="btn btn-primary btn-lg rounded-pill fw-bold flex-grow-1 shadow-sm btn-submit-cart">
                                <i class="bi bi-cart-plus me-2"></i> Añadir
                            </button>
                        </div>
                        <div class="text-center small premium-muted"><i
                                class="bi bi-shield-check text-success me-1"></i> Transacción segura y cifrada</div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1050;">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert"
            aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body fw-bold fs-6"><i class="bi bi-check-circle-fill me-2"></i> ¡Añadido al carrito
                    con éxito!</div>
                <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lógica AJAX para el carrito
        document.addEventListener("DOMContentLoaded", function () {
            const forms = document.querySelectorAll('.form-add-cart');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const btn = this.querySelector('.btn-submit-cart');
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Procesando...';
                    btn.disabled = true;
                    // Llamamos a index.php que es el motor
                    fetch('index.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const badge = document.getElementById('cart-badge');
                                if (badge) {
                                    badge.textContent = data.cart_count;
                                    badge.style.display = 'inline-block';
                                    badge.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.3)' }, { transform: 'scale(1)' }], { duration: 300 });
                                }
                                new bootstrap.Toast(document.getElementById('cartToast')).show();
                            }
                        })
                        .catch(error => console.error('Error:', error))
                        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
                });
            });
        });
    </script>
</body>

</html>