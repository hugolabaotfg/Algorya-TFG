<?php
session_start();
require 'includes/db.php';

// Carrito de compras: Añadir producto al carrito (Lógica Backend AJAX)
if (isset($_POST['add_to_cart'])) {
    if (isset($_SESSION['user_id'])) {
        $usuario_id = $_SESSION['user_id'];
        $producto_id = (int) $_POST['id'];
        $sql_check = "SELECT id, cantidad FROM carritos WHERE usuario_id = $usuario_id AND producto_id = $producto_id";
        $res = $conn->query($sql_check);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $nueva_cantidad = $row['cantidad'] + 1;
            $conn->query("UPDATE carritos SET cantidad = $nueva_cantidad, fecha_agregado = CURRENT_TIMESTAMP WHERE id = " . $row['id']);
        } else {
            $conn->query("INSERT INTO carritos (usuario_id, producto_id, cantidad) VALUES ($usuario_id, $producto_id, 1)");
        }
    } else {
        if (!isset($_SESSION['carrito']))
            $_SESSION['carrito'] = [];
        $encontrado = false;
        foreach ($_SESSION['carrito'] as &$item) {
            if ($item['id'] == $_POST['id']) {
                $item['cantidad']++;
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $_SESSION['carrito'][] = [
                'id' => $_POST['id'],
                'nombre' => $_POST['nombre'],
                'precio' => $_POST['precio'],
                'imagen' => $_POST['imagen'],
                'cantidad' => 1
            ];
        }
    }

    $contador_carrito = 0;
    if (isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $res = $conn->query("SELECT SUM(cantidad) as total FROM carritos WHERE usuario_id = $uid");
        if ($res && $row = $res->fetch_assoc())
            $contador_carrito = $row['total'] ? $row['total'] : 0;
    } else {
        if (isset($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item)
                $contador_carrito += $item['cantidad'];
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

// Contador inicial del carrito
$contador_carrito = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $sql_contador = "SELECT SUM(cantidad) as total FROM carritos WHERE usuario_id = $uid";
    $res_contador = $conn->query($sql_contador);
    if ($res_contador && $row_contador = $res_contador->fetch_assoc()) {
        $contador_carrito = $row_contador['total'] ? $row_contador['total'] : 0;
    }
} else {
    if (isset($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item)
            $contador_carrito += $item['cantidad'];
    }
}

// ==========================================
// SISTEMA DE PAGINACIÓN PROFESIONAL
// ==========================================
$productos_por_pagina = 12; // 3 filas de 4 tarjetas
$pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina_actual < 1)
    $pagina_actual = 1;

$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Contar el total de productos para saber cuántas páginas hay
$sql_total = "SELECT COUNT(*) as total FROM productos";
$res_total = $conn->query($sql_total);
$total_productos = $res_total->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);

// Obtener solo los productos de la página actual
$sql = "SELECT * FROM productos ORDER BY id DESC LIMIT $productos_por_pagina OFFSET $offset";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algorya | Catálogo Exclusivo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="estilos.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing: -1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i>
                <span class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size: 0.55em; margin-left: 1px;">.store</span>
            </a>

            <div class="d-flex align-items-center gap-2 gap-md-3">
                <div id="darkModeToggle" title="Alternar Modo Oscuro">
                    <i class="bi bi-moon-stars-fill fs-6"></i>
                </div>

                <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
                    <a href="carrito.php"
                        class="btn btn-outline-primary btn-sm rounded-pill px-3 position-relative d-flex align-items-center">
                        <i class="bi bi-cart3 me-1"></i> <span class="d-none d-md-inline">Carrito</span>
                        <span id="cart-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm"
                            <?php echo ($contador_carrito > 0) ? '' : 'style="display:none;"'; ?>>
                            <?php echo $contador_carrito; ?>
                        </span>
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="perfil.php" class="premium-text d-none d-md-inline text-decoration-none fw-semibold">
                        <i class="bi bi-person-circle text-primary"></i>
                        <?php echo $_SESSION['nombre']; ?>
                    </a>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <a href="admin_pedidos.php" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-graph-up"></i>
                            Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger btn-sm border-0 rounded-pill"><i
                            class="bi bi-box-arrow-right"></i></a>
                <?php else: ?>
                    <a href="#" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal"
                        data-bs-target="#loginModal">Login</a>
                    <a href="registro.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">Crear Cuenta</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="hero-section mb-5">
        <div class="container text-center" data-aos="zoom-in" data-aos-duration="800">
            <h1 class="fw-bold mb-3 premium-text" style="font-size: 3.5rem; letter-spacing: -2px;">Descubre lo más viral
            </h1>
            <p class="premium-muted fs-5 mx-auto" style="max-width: 600px;">Nuestro algoritmo rastrea el mercado global
                para traerte los productos top ventas antes que nadie.</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-2"
            style="border-color: var(--border-color) !important;">
            <h3 class="fw-bold m-0 premium-text">Catálogo del día</h3>
            <span class="text-success small fw-semibold"><i class="bi bi-cloud-check me-1"></i>Sincronizado hoy</span>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
            <?php
            if ($resultado->num_rows > 0) {
                $delay = 0;
                while ($row = $resultado->fetch_assoc()) {
                    ?>
                    <div class="col" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="card premium-card h-100 rounded-4 overflow-hidden position-relative">

                            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                <div class="position-absolute top-0 end-0 p-2 z-2 d-flex gap-1 admin-actions">
                                    <a href="admin_edit_product.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-warning btn-sm rounded-pill p-1 d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;" title="Editar producto">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <button type="button"
                                        class="btn btn-danger btn-sm rounded-pill p-1 d-flex align-items-center justify-content-center btn-delete-product"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['nombre']); ?>"
                                        style="width: 32px; height: 32px;" title="Borrar producto">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <a href="producto.php?id=<?php echo $row['id']; ?>"
                                class="premium-img-wrapper d-block text-center text-decoration-none z-1">
                                <img src="img/<?php echo htmlspecialchars($row['imagen']); ?>" class="img-fluid"
                                    alt="<?php echo htmlspecialchars($row['nombre']); ?>"
                                    style="height: 200px; width: 100%; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));"
                                    onerror="this.src='https://dummyimage.com/200x200/dee2e6/6c757d.jpg&text=Sin+Imagen'">
                            </a>

                            <div class="card-body d-flex flex-column pt-4 z-1">
                                <a href="producto.php?id=<?php echo $row['id']; ?>" class="text-decoration-none mb-3">
                                    <h5 class="card-title fw-bold text-truncate premium-text"
                                        title="<?php echo htmlspecialchars($row['nombre']); ?>">
                                        <?php echo $row['nombre']; ?>
                                    </h5>
                                </a>

                                <div class="mt-auto pt-3 border-top" style="border-color: var(--border-color) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fs-4 fw-black text-success">
                                            <?php echo number_format($row['precio'], 2); ?> €
                                        </span>
                                        <span class="premium-muted small fw-medium">Stock:
                                            <?php echo $row['stock']; ?>
                                        </span>
                                    </div>

                                    <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin'): ?>
                                        <form action="carrito.php" method="POST" class="m-0 form-add-cart">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="nombre"
                                                value="<?php echo htmlspecialchars($row['nombre']); ?>">
                                            <input type="hidden" name="precio" value="<?php echo $row['precio']; ?>">
                                            <input type="hidden" name="imagen"
                                                value="<?php echo htmlspecialchars($row['imagen']); ?>">
                                            <input type="hidden" name="cantidad" value="1">
                                            <input type="hidden" name="add_to_cart" value="1">

                                            <button type="submit"
                                                class="btn btn-premium-add w-100 rounded-pill fw-bold py-2 shadow-sm btn-submit-cart">
                                                <i class="bi bi-cart-plus me-2"></i> Añadir al carro
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
                echo '<div class="col-12"><div class="alert premium-card text-center py-5 rounded-4" data-aos="zoom-in"><i class="bi bi-inbox fs-1 premium-muted"></i><h5 class="mt-3 fw-bold premium-text">El catálogo está vacío</h5><p class="premium-muted mb-0">La API se sincronizará esta madrugada.</p></div></div>';
            }
            ?>
        </div>

        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegación del catálogo">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link premium-pagination rounded-start-pill px-4 py-2 fw-bold"
                            href="?pagina=<?php echo $pagina_actual - 1; ?>"><i class="bi bi-chevron-left me-1"></i> Ant</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                            <a class="page-link premium-pagination py-2 px-3 fw-bold" href="?pagina=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                        <a class="page-link premium-pagination rounded-end-pill px-4 py-2 fw-bold"
                            href="?pagina=<?php echo $pagina_actual + 1; ?>">Sig <i
                                class="bi bi-chevron-right ms-1"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <footer class="text-center py-5 mt-auto" style="border-top: 1px solid var(--border-color);">
        <div class="container">
            <h5 class="fw-bold premium-text mb-3"><i class="bi bi-box-seam-fill text-primary"></i> Algorya</h5>
            <p class="mb-1 fw-medium premium-text opacity-75">©
                <?php echo date("Y"); ?> Todos los derechos reservados.
            </p>
            <p class="mb-0 premium-muted small fw-bold">Proyecto Final de Grado de ASIR realizado por Hugo Labao
                González</p>
        </div>
    </footer>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal rounded-4 border-0">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold premium-text" id="loginModalLabel">
                        <i class="bi bi-box-seam-fill text-primary"></i> Algorya
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-2">
                    <h4 class="fw-bold mb-4 premium-text">Bienvenido de nuevo</h4>
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label premium-muted small fw-bold">CORREO ELECTRÓNICO</label>
                            <input type="email" name="email"
                                class="form-control form-control-lg premium-input shadow-none" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label premium-muted small fw-bold">CONTRASEÑA</label>
                            <input type="password" name="password"
                                class="form-control form-control-lg premium-input shadow-none" required>
                        </div>
                        <button type="submit" name="login"
                            class="btn btn-primary w-100 btn-lg rounded-pill fw-bold shadow-sm"
                            style="background-color: #3b82f6; border: none;">Iniciar Sesión</button>
                    </form>
                    <div class="text-center mt-4">
                        <span class="premium-muted small">¿No tienes cuenta? <a href="registro.php"
                                class="text-primary text-decoration-none fw-bold">Regístrate aquí</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1050;">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert"
            aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body fw-bold fs-6">
                    <i class="bi bi-check-circle-fill me-2"></i> ¡Añadido al carrito con éxito!
                </div>
                <button type="button" class="btn-close btn-close-white me-3 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script src="tema.js"></script>

    <script>
        // 1. Inicializar AOS
        AOS.init({ once: true, offset: 50 });

        // 2. Lógica del Carrito AJAX (Sin recargar)
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
                                const toast = new bootstrap.Toast(document.getElementById('cartToast'));
                                toast.show();
                            }
                        })
                        .catch(error => console.error('Error:', error))
                        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
                });
            });
        });

        // 3. Lógica de Borrado de Producto para Admin (AJAX) Blindada
        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                document.addEventListener("DOMContentLoaded", function () {
                    const deleteButtons = document.querySelectorAll('.btn-delete-product');
                    deleteButtons.forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault(); // Evita que el navegador haga cosas raras
                            e.stopPropagation(); // Evita el "Event Bubbling"

                            const productId = this.getAttribute('data-id');
                            const productName = this.getAttribute('data-name');

                            if (confirm('¿Estás seguro de que quieres borrar permanentemente "' + productName + '" de Algorya?')) {

                                // Cambiar el icono a un spinner de carga
                                const originalHtml = this.innerHTML;
                                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                                this.disabled = true;

                                fetch('includes/admin_delete_product_ajax.php', {
                                    method: 'POST',
                                    body: new URLSearchParams({
                                        'id': productId,
                                        'action': 'delete'
                                    }),
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            window.location.reload(); // Recarga limpia al borrar
                                        } else {
                                            alert('Error al borrar: ' + data.message);
                                            this.innerHTML = originalHtml;
                                            this.disabled = false;
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        alert('Ocurrió un error de red.');
                                        this.innerHTML = originalHtml;
                                        this.disabled = false;
                                    });
                            }
                        });
                    });
                });
            <?php endif; ?>
    </script>
</body>

</html>