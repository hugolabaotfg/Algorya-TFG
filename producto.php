<?php
session_start();
require 'includes/db.php';

// Validar que nos llega un ID por la URL y que es un número
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Convertimos a entero por seguridad (Evita Inyección SQL)
$id_producto = (int)$_GET['id'];

// Obtener datos del producto de la base de datos
// (Asegúrate de que estas columnas coinciden con las de tu tabla 'productos')
$sql = "SELECT id, nombre, descripcion, precio, imagen FROM productos WHERE id = $id_producto";
$res = $conn->query($sql);

// Red de seguridad: Si la consulta falla, que nos diga por qué
if (!$res) {
    die("Error crítico de SQL: " . $conn->error);
}

if ($res->num_rows == 0) {
    // Si el producto no existe (alguien puso un ID falso en la URL)
    header("Location: index.php");
    exit();
}

$producto = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f9f9f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-light bg-white shadow-sm mb-5 sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-decoration-none text-dark" href="index.php">
                <i class="bi bi-arrow-left me-2"></i> Volver al catálogo
            </a>
            <div class="d-flex">
                <a href="carrito.php" class="btn btn-outline-dark position-relative rounded-pill">
                    <i class="bi bi-cart"></i> Mi Carrito
                </a>
            </div>
        </div>
    </nav>

    <div class="container mb-5" style="max-width: 1000px;">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="row g-0">
                <div class="col-md-6 bg-white d-flex align-items-center justify-content-center p-5 border-end">
                    <?php if (!empty($producto['imagen'])): ?>
                        <img src="img/<?php echo htmlspecialchars($producto['imagen']); ?>" class="img-fluid rounded"
                            alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="max-height: 450px; object-fit: contain;">
                    <?php else: ?>
                        <div class="text-muted text-center py-5">
                            <i class="bi bi-image fs-1 d-block mb-2"></i> Sin imagen disponible
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 bg-white">
                    <div class="card-body p-5 d-flex flex-column h-100">
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-3 align-self-start px-3 py-2 rounded-pill"><i class="bi bi-globe me-1"></i> Envío Dropshipping</span>
                        
                        <h2 class="fw-bold mb-3 lh-sm"><?php echo htmlspecialchars($producto['nombre']); ?></h2>
                        
                        <h2 class="text-success fw-bold mb-4"><?php echo number_format($producto['precio'], 2); ?> €</h2>
                        
                        <div class="mb-4 flex-grow-1">
                            <h6 class="fw-bold text-muted text-uppercase small tracking-wide mb-3">Descripción del artículo</h6>
                            <p class="text-secondary" style="line-height: 1.6; font-size: 0.95rem;">
                                <?php 
                                // Controlamos si la API de Dropshipping trajo descripción o no
                                if (!empty($producto['descripcion'])) {
                                    echo nl2br(htmlspecialchars($producto['descripcion'])); 
                                } else {
                                    echo "Este artículo ha sido seleccionado automáticamente de nuestros proveedores internacionales por sus altas tendencias de venta. Calidad garantizada para importación directa.";
                                }
                                ?>
                            </p>
                        </div>

                        <div class="mt-auto pt-4 border-top">
                            <form id="form-add-detail" class="m-0">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <input type="hidden" name="precio" value="<?php echo $producto['precio']; ?>">
                                <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($producto['imagen']); ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                            
                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <input type="number" name="cantidad" class="form-control form-control-lg text-center rounded-pill" value="1"
                                            min="1" max="10">
                                    </div>
                                    <div class="col-8">
                                        <button type="submit" id="btn-add-detail" class="btn btn-dark btn-lg w-100 rounded-pill fw-bold shadow-sm">
                                            <i class="bi bi-cart-plus me-2"></i> Añadir
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <p class="text-center text-muted small mt-3 mb-0"><i class="bi bi-shield-check text-success me-1"></i> Transacción segura y cifrada</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1050;">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="bi bi-check-circle-fill me-2"></i> ¡Añadido al carrito con éxito!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('form-add-detail').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-add-detail');
            const formData = new FormData(this);
            
            btn.disabled = true;
            btn.innerHTML = 'Añadiendo...';

            // Enviamos a index.php que es donde tienes la lógica de añadir
            fetch('index.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                 if(data.status === 'success') {
                    // 1. Mostrar la tostada verde
                    const toast = new bootstrap.Toast(document.getElementById('cartToast'));
                    toast.show();

                    // 2. Actualizar el numerito rojo (BADGE)
                    const badge = document.getElementById('cart-badge');
                    if(badge) {
                    badge.textContent = data.cart_count;
                    badge.style.display = 'inline-block'; // Por si estaba oculto (carrito vacío)
                    
                    // Efecto visual de "latido" para que el usuario lo vea
                    badge.animate([
                        { transform: 'scale(1)' },
                        { transform: 'scale(1.3)' },
                        { transform: 'scale(1)' }
                    ], { duration: 300 });
                    }
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cart-plus me-2"></i> Añadir';
            });
        });
    </script>
</body>
</html>