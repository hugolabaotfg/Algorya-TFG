<?php
session_start();
// Advertencia 4: Verificación de sesión al inicio
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';
$uid = (int) $_SESSION['usuario_id'];

// Error 3: Implementar la acción 'vaciar'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaciar'])) {
    $stmt = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    header("Location: carrito.php");
    exit;
}

// Error 3: Implementar la acción 'eliminar_item'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_item']) && isset($_POST['producto_id'])) {
    $producto_id = (int) $_POST['producto_id'];
    $stmt = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ? AND producto_id = ?");
    $stmt->bind_param("ii", $uid, $producto_id);
    $stmt->execute();
    $stmt->close();
    header("Location: carrito.php");
    exit;
}

// Error 3: Poblar $carrito_items y calcular $total
$carrito_items = [];
$total = 0;

$stmt_cart = $conn->prepare("SELECT c.producto_id, c.cantidad, p.nombre, p.precio, p.imagen FROM carritos c JOIN productos p ON c.producto_id = p.id WHERE c.usuario_id = ?");
$stmt_cart->bind_param("i", $uid);
$stmt_cart->execute();
$resultado = $stmt_cart->get_result();

while ($row = $resultado->fetch_assoc()) {
    $carrito_items[] = $row;
    $total += $row['precio'] * $row['cantidad'];
}
$stmt_cart->close();

require 'includes/header.php';
?>

<div class="container mt-5">
    <h2>Tu Carrito</h2>
    <?php if (empty($carrito_items)): ?>
        <div class="alert alert-info">Tu carrito está vacío. <a href="index.php">Ir a la tienda</a>.</div>
    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carrito_items as $item): ?>
                    <tr>
                        <td>
                            <img src="assets/img/productos/<?= htmlspecialchars($item['imagen']) ?>" width="50" alt="Imagen">
                            <?= htmlspecialchars($item['nombre']) ?>
                        </td>
                        <td>
                            <?= number_format($item['precio'], 2) ?> €
                        </td>
                        <td>
                            <?= $item['cantidad'] ?>
                        </td>
                        <td>
                            <?= number_format($item['precio'] * $item['cantidad'], 2) ?> €
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="producto_id" value="<?= $item['producto_id'] ?>">
                                <button type="submit" name="eliminar_item" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <h4>Total:
                <?= number_format($total, 2) ?> €
            </h4>
            <div>
                <form method="POST" class="d-inline">
                    <button type="submit" name="vaciar" class="btn btn-warning">Vaciar Carrito</button>
                </form>
                <a href="checkout.php" class="btn btn-success">Proceder al Pago</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>