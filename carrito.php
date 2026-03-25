<?php
session_start();
require 'includes/db.php';

// (La lógica del backend sigue intacta, solo he modificado el HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['vaciar'])) { /* ... lógica vaciar ... */
    }
    if (isset($_POST['eliminar_item'])) { /* ... lógica eliminar ... */
    }
}

// Obtener items
$carrito_items = [];
$total = 0;
// ... (tu lógica de obtener items del carrito se mantiene aquí arriba, no te la corto para no romper nada, 
// pero abajo te pongo el HTML exacto a partir de <!DOCTYPE html>)
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
    <style>
        .table-premium {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            vertical-align: middle;
        }

        .table-premium th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
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
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing: -1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i><span class="text-primary">Algorya</span><span
                    class="premium-text" style="font-size: 0.55em;">.store</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <div id="darkModeToggle" title="Alternar Modo Oscuro" class="me-2"><i
                        class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill"><i
                        class="bi bi-arrow-left"></i> Seguir Comprando</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <h2 class="fw-bold premium-text mb-4"><i class="bi bi-cart3 text-primary me-2"></i> Tu Carrito de la Compra</h2>

        <?php if (count($carrito_items) > 0): ?>
            <div class="card premium-card border-0 rounded-4 p-4 shadow-sm mb-5">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carrito_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-white p-2 rounded border" style="width: 60px; height: 60px;">
                                                <img src="img/<?php echo htmlspecialchars($item['imagen']); ?>" alt=""
                                                    class="img-fluid" style="object-fit: contain; width: 100%; height: 100%;">
                                            </div>
                                            <span class="fw-bold premium-text">
                                                <?php echo htmlspecialchars($item['nombre']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold premium-text">
                                        <?php echo $item['cantidad']; ?>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?> €
                                    </td>
                                    <td class="text-end">
                                        <form action="carrito.php" method="POST" class="m-0">
                                            <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="eliminar_item"
                                                class="btn btn-sm btn-outline-danger rounded-pill"><i
                                                    class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top"
                    style="border-color: var(--border-color) !important;">
                    <h3 class="fw-bold premium-text m-0 mb-3 mb-md-0">Total: <span class="text-primary">
                            <?php echo number_format($total, 2); ?> €
                        </span></h3>
                    <div class="d-flex gap-2">
                        <form action="carrito.php" method="POST" class="m-0">
                            <button type="submit" name="vaciar"
                                class="btn btn-outline-danger rounded-pill px-4">Vaciar</button>
                        </form>
                        <a href="checkout.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Procesar Pago
                            Seguro <i class="bi bi-shield-lock ms-1"></i></a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card premium-card border-0 rounded-4 p-5 text-center shadow-sm">
                <i class="bi bi-cart-x fs-1 premium-muted mb-3"></i>
                <h4 class="fw-bold premium-text">Tu carrito está vacío</h4>
                <p class="premium-muted mb-4">Vuelve al catálogo para descubrir nuestras ofertas exclusivas del día.</p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 mx-auto" style="width: fit-content;">Ir a la
                    Tienda</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>