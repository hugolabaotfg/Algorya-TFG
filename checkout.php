<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';
require_once 'includes/mailer.php';

$uid = (int) $_SESSION['usuario_id'];
$nombre_cliente = $_SESSION['nombre']; // Se sanitizará en la función del mailer
$email_cliente = $_SESSION['email'];

$conn->begin_transaction();

try {
    // Error 2: Obtener carrito con Prepared Statement
    $stmt_cart = $conn->prepare("SELECT c.producto_id, c.cantidad, p.nombre, p.precio FROM carritos c JOIN productos p ON c.producto_id = p.id WHERE c.usuario_id = ?");
    $stmt_cart->bind_param("i", $uid);
    $stmt_cart->execute();
    $resultado = $stmt_cart->get_result();

    $total = 0;
    $items_pedido = [];
    while ($row = $resultado->fetch_assoc()) {
        $total += $row['precio'] * $row['cantidad'];
        $items_pedido[] = $row;
    }
    $stmt_cart->close();

    if (empty($items_pedido)) {
        throw new Exception("El carrito está vacío.");
    }

    // Aquí iría la lógica de creación del pedido en 'pedidos' y 'pedidos_lineas'...
    $pedido_id = rand(1000, 9999); // Simulación de ID de pedido generado

    // Error 2: Vaciar carrito de forma segura
    $stmt_delete = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ?");
    $stmt_delete->bind_param("i", $uid);
    $stmt_delete->execute();
    $stmt_delete->close();

    $conn->commit();

    // Advertencia 5: Email extraído y desacoplado
    enviarConfirmacionPedido($email_cliente, $nombre_cliente, $pedido_id, $total);

    // Redirección a página de éxito
    header("Location: exito.php?pedido=" . $pedido_id);
    exit;

} catch (Exception $e) {
    $conn->rollback();

    // Advertencia 2: Logging interno y mensaje genérico al usuario
    error_log("[" . date('Y-m-d H:i:s') . "] Error en checkout para UID $uid: " . $e->getMessage() . "\n", 3, __DIR__ . "/logs/errores.log");

    // UI con Bootstrap 5 para el error
    $error_msg = "Lo sentimos, ha ocurrido un problema procesando tu pedido. Por favor, inténtalo de nuevo más tarde.";
    require 'includes/header.php';
    echo "<div class='container mt-5'><div class='alert alert-danger'>$error_msg</div></div>";
    require 'includes/footer.php';
    exit;
}
?>