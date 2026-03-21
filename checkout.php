<?php
session_start();
require 'includes/db.php';

// Si no está logueado o no hay acción, lo echamos al índice
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$nombre_cliente = $_SESSION['nombre'];

// 1. Obtener los productos actuales del carrito
$sql_cart = "SELECT c.producto_id, c.cantidad, p.nombre, p.precio 
             FROM carritos c 
             JOIN productos p ON c.producto_id = p.id 
             WHERE c.usuario_id = $uid";
$res_cart = $conn->query($sql_cart);

if ($res_cart->num_rows === 0) {
    header("Location: carrito.php"); // Carrito vacío
    exit();
}

$total_pedido = 0;
$items_pedido = [];
while ($row = $res_cart->fetch_assoc()) {
    $total_pedido += ($row['precio'] * $row['cantidad']);
    $items_pedido[] = $row;
}

// ==============================================================================
// INICIO DE TRANSACCIÓN SEGURA (ACID)
// ==============================================================================
$conn->begin_transaction();

try {
    // 2. Crear la cabecera del pedido
    $stmt_pedido = $conn->prepare("INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)");
    $stmt_pedido->bind_param("id", $uid, $total_pedido);
    $stmt_pedido->execute();
    $pedido_id = $conn->insert_id; // Obtenemos el ID del ticket generado
    
    // 3. Insertar las líneas de pedido
    $stmt_linea = $conn->prepare("INSERT INTO lineas_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    foreach ($items_pedido as $item) {
        $stmt_linea->bind_param("iiid", $pedido_id, $item['producto_id'], $item['cantidad'], $item['precio']);
        $stmt_linea->execute();
    }
    
    // 4. Vaciar el carrito del usuario
    $conn->query("DELETE FROM carritos WHERE usuario_id = $uid");
    
    // 5. Confirmar transacción 
    $conn->commit();
    $pago_completado = true;

    // 6. Enviar Recibo por Email (Background)
    $email_cliente = ""; // Recuperamos el email para el recibo
    $res_email = $conn->query("SELECT email FROM usuarios WHERE id = $uid");
    if ($res_email && $row_email = $res_email->fetch_assoc()) {
        $email_cliente = $row_email['email'];
        
        $asunto = "[Algorya] Confirmación de tu Pedido #$pedido_id";
        $cuerpo = "Hola $nombre_cliente,\n\n";
        $cuerpo .= "¡Gracias por tu compra! Hemos procesado tu pedido correctamente mediante nuestra pasarela segura.\n\n";
        $cuerpo .= "Resumen del pedido #$pedido_id:\n";
        $cuerpo .= "Total pagado: " . number_format($total_pedido, 2) . " EUR\n\n";
        $cuerpo .= "En breve comenzaremos a preparar tu envío.\n\nAtentamente,\nEl equipo de Algorya.";
        $cabeceras = "From: hola@algorya.store\r\nReply-To: hola@algorya.store\r\nX-Mailer: PHP/" . phpversion();
        
        @mail($email_cliente, $asunto, $cuerpo, $cabeceras);
    }

} catch (Exception $e) {
    // Si algo falla, deshacemos TODO (Rollback) para evitar inconsistencias
    $conn->rollback();
    die("🚨 Error crítico procesando el pago: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago Completado | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container text-center">
        <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width: 600px;">
            <div class="card-body p-5">
                <i class="bi bi-shield-check text-success" style="font-size: 5rem;"></i>
                <h2 class="fw-bold mt-3">¡Pago Procesado con Éxito!</h2>
                <p class="text-muted fs-5">Tu pedido <strong>#<?php echo $pedido_id; ?></strong> ha sido confirmado y registrado en nuestro sistema.</p>
                <hr class="my-4">
                <div class="d-flex justify-content-between mb-4 fs-5">
                    <span class="fw-bold text-dark">Total pagado:</span>
                    <span class="text-primary fw-bold"><?php echo number_format($total_pedido, 2); ?> €</span>
                </div>
                <div class="alert alert-info border-0 rounded-3 text-start small">
                    <i class="bi bi-info-circle-fill me-2"></i><strong>Modo Sandbox:</strong> Esta es una simulación de pasarela de pago (Stripe). No se han realizado cargos reales. Te hemos enviado un recibo a tu correo.
                </div>
                <a href="index.php" class="btn btn-dark btn-lg rounded-pill w-100 mt-2">Volver al Catálogo</a>
            </div>
        </div>
    </div>
</body>
</html>