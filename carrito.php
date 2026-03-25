<?php
// =============================================================================
// ALGORYA - carrito.php
// Gestión completa del carrito de la compra.
// Lógica MIXTA:
//   - Usuario LOGUEADO  → los items se leen/modifican en la tabla `carritos` (MariaDB)
//   - Usuario VISITANTE → los items se guardan en $_SESSION['carrito'] (memoria del servidor)
// =============================================================================

session_start();
require 'includes/db.php';

// =============================================================================
// BLOQUE 1: PROCESAMIENTO DE ACCIONES POST
// Se ejecuta ANTES de leer los datos para que los cambios se reflejen al instante.
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- ACCIÓN: Eliminar un item concreto del carrito ---
    if (isset($_POST['eliminar_item'])) {
        $producto_id = (int) $_POST['producto_id']; // (int) evita SQL Injection

        if (isset($_SESSION['user_id'])) {
            // Usuario logueado: borrar de la base de datos
            $uid = (int) $_SESSION['user_id'];
            $stmt = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ? AND producto_id = ?");
            $stmt->bind_param("ii", $uid, $producto_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Visitante: borrar del array de sesión
            if (isset($_SESSION['carrito'])) {
                foreach ($_SESSION['carrito'] as $key => $item) {
                    if ((int) $item['id'] === $producto_id) {
                        unset($_SESSION['carrito'][$key]);
                        break;
                    }
                }
                // Reindexar el array para evitar huecos
                $_SESSION['carrito'] = array_values($_SESSION['carrito']);
            }
        }
        // Redirigir para evitar reenvío del formulario al refrescar (patrón PRG)
        header("Location: carrito.php");
        exit();
    }

    // --- ACCIÓN: Vaciar todo el carrito ---
    if (isset($_POST['vaciar'])) {

        if (isset($_SESSION['user_id'])) {
            // Usuario logueado: borrar todos sus items de la base de datos
            $uid = (int) $_SESSION['user_id'];
            $stmt = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
        } else {
            // Visitante: destruir el array de sesión del carrito
            $_SESSION['carrito'] = [];
        }
        // Redirigir para evitar reenvío del formulario al refrescar (patrón PRG)
        header("Location: carrito.php");
        exit();
    }
}

// =============================================================================
// BLOQUE 2: LEER EL CONTENIDO DEL CARRITO
// Después de procesar cualquier acción, leemos el estado actual para mostrarlo.
// =============================================================================

$carrito_items = []; // Array que contendrá los productos del carrito
$total = 0;          // Variable para acumular el precio total

if (isset($_SESSION['user_id'])) {

    // --- Usuario LOGUEADO: leer de la tabla `carritos` con JOIN a `productos` ---
    $uid = (int) $_SESSION['user_id'];
    $sql = "SELECT c.producto_id AS id, c.cantidad, p.nombre, p.precio, p.imagen
            FROM carritos c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ?
            ORDER BY c.fecha_agregado DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($row = $resultado->fetch_assoc()) {
        $carrito_items[] = $row;
        $total += $row['precio'] * $row['cantidad'];
    }
    $stmt->close();

} else {

    // --- Usuario VISITANTE: leer del array de sesión ---
    if (!empty($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $carrito_items[] = $item;
            $total += $item['precio'] * $item['cantidad'];
        }
    }
}

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

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing: -1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i>
                <span class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size: 0.55em;">.store</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <div id="darkModeToggle" title="Alternar Modo Oscuro" class="me-2">
                    <i class="bi bi-moon-stars-fill fs-6"></i>
                </div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left"></i> Seguir Comprando
                </a>
            </div>
        </div>
    </nav>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="container mt-5 flex-grow-1">
        <h2 class="fw-bold premium-text mb-4">
            <i class="bi bi-cart3 text-primary me-2"></i> Tu Carrito de la Compra
        </h2>

        <?php if (count($carrito_items) > 0): ?>

            <!-- TABLA DE PRODUCTOS -->
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
                                                    class="img-fluid" style="object-fit: contain; width: 100%; height: 100%;"
                                                    onerror="this.src='https://dummyimage.com/60x60/dee2e6/6c757d.jpg&text=?'">
                                            </div>
                                            <span class="fw-bold premium-text">
                                                <?php echo htmlspecialchars($item['nombre']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold premium-text">
                                        <?php echo (int) $item['cantidad']; ?>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?> €
                                    </td>
                                    <td class="text-end">
                                        <!-- Botón eliminar item individual -->
                                        <form action="carrito.php" method="POST" class="m-0">
                                            <input type="hidden" name="producto_id" value="<?php echo (int) $item['id']; ?>">
                                            <button type="submit" name="eliminar_item"
                                                class="btn btn-sm btn-outline-danger rounded-pill" title="Eliminar producto">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TOTAL + BOTONES DE ACCIÓN -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top"
                    style="border-color: var(--border-color) !important;">

                    <h3 class="fw-bold premium-text m-0 mb-3 mb-md-0">
                        Total: <span class="text-primary">
                            <?php echo number_format($total, 2); ?> €
                        </span>
                    </h3>

                    <div class="d-flex gap-2">
                        <!-- Botón vaciar todo el carrito -->
                        <form action="carrito.php" method="POST" class="m-0"
                            onsubmit="return confirm('¿Seguro que quieres vaciar todo el carrito?');">
                            <button type="submit" name="vaciar" class="btn btn-outline-danger rounded-pill px-4">
                                <i class="bi bi-trash me-1"></i> Vaciar
                            </button>
                        </form>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Usuario logueado: puede proceder al pago -->
                            <a href="checkout.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                Procesar Pago Seguro <i class="bi bi-shield-lock ms-1"></i>
                            </a>
                        <?php else: ?>
                            <!-- Visitante: le pedimos que inicie sesión antes de pagar -->
                            <a href="login.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                <i class="bi bi-person-lock me-1"></i> Inicia sesión para pagar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <!-- CARRITO VACÍO -->
            <div class="card premium-card border-0 rounded-4 p-5 text-center shadow-sm">
                <i class="bi bi-cart-x fs-1 premium-muted mb-3"></i>
                <h4 class="fw-bold premium-text">Tu carrito está vacío</h4>
                <p class="premium-muted mb-4">Vuelve al catálogo para descubrir nuestras ofertas exclusivas del día.</p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 mx-auto" style="width: fit-content;">
                    <i class="bi bi-bag me-1"></i> Ir a la Tienda
                </a>
            </div>

        <?php endif; ?>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="text-center py-4 mt-auto" style="border-top: 1px solid var(--border-color);">
        <div class="container">
            <p class="mb-0 premium-muted small fw-bold">
                <i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy;
                <?php echo date("Y"); ?> — Proyecto Final de Grado ASIR
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>