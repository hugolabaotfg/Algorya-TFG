<?php
session_start();
require 'includes/db.php'; // Necesitamos la conexión a BBDD ahora

// ARRAY UNIFICADO: Aquí guardaremos lo que se va a mostrar (venga de BBDD o de Sesión)
$items_carrito = [];
$total = 0;

// 1. CARGAR DATOS DEL CARRITO
if (isset($_SESSION['user_id'])) {
    // Modo Usuario Logueado: Leer de la Base de Datos
    $uid = $_SESSION['user_id'];
    $sql_cart = "SELECT c.id as cart_id, c.cantidad, p.id, p.nombre, p.precio, p.imagen 
                 FROM carritos c 
                 JOIN productos p ON c.producto_id = p.id 
                 WHERE c.usuario_id = $uid";
    $res_cart = $conn->query($sql_cart);
    
    if ($res_cart) {
        while ($row = $res_cart->fetch_assoc()) {
            $items_carrito[] = [
                'cart_id' => $row['cart_id'], // El ID de la línea del carrito
                'id' => $row['id'],           // El ID del producto
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'imagen' => $row['imagen'] ? $row['imagen'] : 'default.jpg',
                'cantidad' => $row['cantidad']
            ];
            $total += ($row['precio'] * $row['cantidad']);
        }
    }
} else {
    // Modo Visitante: Leer de la Sesión Temporal
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    $items_carrito = $_SESSION['carrito'];
    foreach ($items_carrito as $item) {
        $total += ($item['precio'] * $item['cantidad']);
    }
}

// 2. VACIAR CARRITO
if (isset($_GET['vaciar'])) {
    if (isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $conn->query("DELETE FROM carritos WHERE usuario_id = $uid");
    } else {
        $_SESSION['carrito'] = [];
    }
    header("Location: carrito.php");
    exit();
}

// 3. FINALIZAR COMPRA (Simulación)
$compra_ok = false;
if (isset($_GET['comprar']) && count($items_carrito) > 0) {
    if (isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $conn->query("DELETE FROM carritos WHERE usuario_id = $uid"); // Vaciamos tras comprar
    } else {
        $_SESSION['carrito'] = []; 
    }
    $items_carrito = []; // Vaciamos el array visual
    $total = 0;
    $compra_ok = true;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f9f9f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .navbar { background: #fff !important; border-bottom: 1px solid #eaeaea; }
        .navbar-brand { font-weight: 800; color: #111 !important; }
        .cart-card { background: #fff; border: 1px solid #eaeaea; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .item-img { width: 70px; height: 70px; object-fit: contain; background: #fff; border-radius: 8px; border: 1px solid #eaeaea; padding: 5px; }
        .btn-dark { border-radius: 30px; font-weight: 600; padding: 12px 24px; }
    </style>
</head>
<body class="pb-5">

    <nav class="navbar navbar-light sticky-top shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-bag-check-fill"></i> Algorya</a>
            <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> Seguir Comprando</a>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <h3 class="fw-bold mb-4"><i class="bi bi-cart3"></i> Tu Carrito de la Compra</h3>

                <?php if ($compra_ok): ?>
                    <div class="alert alert-success border-0 rounded-4 shadow-sm p-4 text-center mb-4">
                        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                        <h4 class="fw-bold text-dark">¡Pedido realizado con éxito!</h4>
                        <p class="mb-0 text-muted">Esta es una simulación. Tus datos están seguros (porque no hemos pedido ninguno 😂).</p>
                    </div>
                <?php endif; ?>

                <div class="cart-card p-4">
                    <?php if (count($items_carrito) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle">
                                <thead class="border-bottom">
                                    <tr class="text-muted small text-uppercase">
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items_carrito as $item): ?>
                                        <tr class="border-bottom">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="img/<?php echo htmlspecialchars($item['imagen']); ?>" class="item-img">
                                                    <span class="fw-bold" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block;">
                                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-light text-dark border px-3 py-2"><?php echo $item['cantidad']; ?></span></td>
                                            <td class="text-end fw-bold"><?php echo number_format($item['precio'] * $item['cantidad'], 2); ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <h4 class="fw-bold mb-0">Total: <span class="text-primary"><?php echo number_format($total, 2); ?> €</span></h4>
                            <div class="d-flex gap-2">
                                <a href="carrito.php?vaciar=1" class="btn btn-outline-danger rounded-pill px-4">Vaciar</a>
                                <a href="checkout.php" class="btn btn-dark">Procesar Pago Seguro <i class="bi bi-shield-lock ms-1"></i></a>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 fw-bold text-dark">Tu carrito está vacío</h5>
                            <p class="text-muted">Aún no has añadido ningún producto top ventas.</p>
                            <a href="index.php" class="btn btn-dark mt-2">Ir al Catálogo</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

</body>
</html>