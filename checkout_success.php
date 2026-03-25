<?php
// =============================================================================
// ALGORYA - checkout_success.php
// Stripe redirige aquí cuando el pago ha sido APROBADO.
//
// Estructura real de la tabla pedidos:
//   id | usuario_id | total | metodo_pago | fecha | stripe_session_id
//
// ¿Qué hace este archivo?
//   1. Verifica que el session_id de Stripe es legítimo (anti-fraude)
//   2. Consulta a Stripe para confirmar que el pago está en estado "paid"
//   3. Crea el pedido en la BD con transacción ACID
//   4. Vacía el carrito del usuario
//   5. Envía el recibo por email
//   6. Muestra la confirmación premium
// =============================================================================

session_start();
require 'includes/db.php';
require 'includes/config.php';

// Solo usuarios logueados
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];
$nombre_cliente = htmlspecialchars($_SESSION['nombre']);

// ─────────────────────────────────────────────────────────────────────────────
// PASO 1: Validar el session_id que Stripe manda en la URL
// Stripe añade ?session_id=cs_test_... a la URL de éxito
// Lo comparamos con el que guardamos en $_SESSION antes de redirigir a Stripe
// Si no coinciden → posible manipulación → redirigimos sin crear pedido
// ─────────────────────────────────────────────────────────────────────────────
$session_id_url = $_GET['session_id'] ?? '';
$session_id_sesion = $_SESSION['stripe_session_id'] ?? '';

if (empty($session_id_url) || $session_id_url !== $session_id_sesion) {
    header("Location: index.php");
    exit();
}

// Limpiamos la variable de sesión para evitar que esta página
// se pueda recargar infinitas veces creando pedidos duplicados
unset($_SESSION['stripe_session_id']);

// ─────────────────────────────────────────────────────────────────────────────
// PASO 2: Verificar con la API de Stripe que el pago está realmente "paid"
// Aunque Stripe nos haya redirigido aquí, siempre verificamos en el servidor.
// Esto evita que alguien acceda directamente a esta URL sin haber pagado.
// ─────────────────────────────────────────────────────────────────────────────
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($session_id_url));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
    CURLOPT_SSL_VERIFYPEER => false, // false en entorno local con cert. autofirmado
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

// Error de red con cURL (problema de conexión al servidor de Stripe)
if ($curl_err) {
    die("❌ Error de conexión con Stripe: " . htmlspecialchars($curl_err) .
        "<br><a href='checkout.php'>Volver al resumen</a>");
}

$stripe_session = json_decode($response, true);

// Si Stripe no devuelve 200 o el pago no está en estado "paid" → paramos
if ($http_code !== 200 || ($stripe_session['payment_status'] ?? '') !== 'paid') {
    header("Location: checkout.php?error=pago_no_confirmado");
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 3: Leer el carrito del usuario para crear las líneas del pedido
// ─────────────────────────────────────────────────────────────────────────────
$stmt_cart = $conn->prepare(
    "SELECT c.producto_id, c.cantidad, p.nombre, p.precio
     FROM carritos c
     JOIN productos p ON c.producto_id = p.id
     WHERE c.usuario_id = ?"
);
$stmt_cart->bind_param("i", $uid);
$stmt_cart->execute();
$res_cart = $stmt_cart->get_result();
$stmt_cart->close();

// Si el carrito ya está vacío significa que este pago ya fue procesado antes
// (recarga de página) → redirigimos al índice sin crear pedido duplicado
if ($res_cart->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$total_pedido = 0;
$items_pedido = [];
while ($row = $res_cart->fetch_assoc()) {
    $total_pedido += $row['precio'] * $row['cantidad'];
    $items_pedido[] = $row;
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 4: Transacción ACID — crear pedido + líneas + vaciar carrito
//
// La tabla pedidos tiene: id | usuario_id | total | metodo_pago | fecha | stripe_session_id
// No tiene columna "estado" — metodo_pago lo usamos para registrar "Stripe (Sandbox)"
// fecha tiene DEFAULT current_timestamp() → no hace falta insertarla
// ─────────────────────────────────────────────────────────────────────────────
$conn->begin_transaction();
$pedido_id = null;

try {
    // Insertar la cabecera del pedido
    // metodo_pago  → "Stripe (Sandbox)" igual que el DEFAULT que ya tenías
    // stripe_session_id → el ID de la sesión de Stripe para trazabilidad
    $metodo = 'Stripe (Sandbox)';
    $stmt_pedido = $conn->prepare(
        "INSERT INTO pedidos (usuario_id, total, metodo_pago, stripe_session_id)
         VALUES (?, ?, ?, ?)"
    );
    $stmt_pedido->bind_param("idss", $uid, $total_pedido, $metodo, $session_id_url);
    $stmt_pedido->execute();
    $pedido_id = $conn->insert_id; // ID del pedido recién creado
    $stmt_pedido->close();

    // Insertar cada línea de producto asociada a ese pedido
    $stmt_linea = $conn->prepare(
        "INSERT INTO lineas_pedido (pedido_id, producto_id, cantidad, precio_unitario)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($items_pedido as $item) {
        $stmt_linea->bind_param(
            "iiid",
            $pedido_id,
            $item['producto_id'],
            $item['cantidad'],
            $item['precio']
        );
        $stmt_linea->execute();
    }
    $stmt_linea->close();

    // Vaciar el carrito del usuario en la BD
    $stmt_vaciar = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ?");
    $stmt_vaciar->bind_param("i", $uid);
    $stmt_vaciar->execute();
    $stmt_vaciar->close();

    // Todo fue bien → confirmar la transacción
    $conn->commit();

} catch (Exception $e) {
    // Algo falló → deshacer TODO para no dejar datos inconsistentes
    $conn->rollback();
    die("🚨 Error crítico al registrar el pedido: " . htmlspecialchars($e->getMessage()) .
        "<br>Tu pago fue procesado por Stripe (ID: $session_id_url). Contacta con soporte.");
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 5: Enviar recibo por email al cliente
// ─────────────────────────────────────────────────────────────────────────────
$stmt_email = $conn->prepare("SELECT email FROM usuarios WHERE id = ?");
$stmt_email->bind_param("i", $uid);
$stmt_email->execute();
$res_email = $stmt_email->get_result();
$stmt_email->close();

if ($res_email && $row_email = $res_email->fetch_assoc()) {
    $email_cliente = $row_email['email'];
    $asunto = "[Algorya] ✅ Confirmación de tu Pedido #$pedido_id";

    $cuerpo = "Hola $nombre_cliente,\n\n";
    $cuerpo .= "¡Gracias por tu compra en Algorya! Tu pago ha sido procesado correctamente por Stripe.\n\n";
    $cuerpo .= "📦 Resumen del pedido #$pedido_id:\n";
    foreach ($items_pedido as $item) {
        $subtotal = number_format($item['precio'] * $item['cantidad'], 2);
        $cuerpo .= "  - {$item['nombre']} x{$item['cantidad']} → {$subtotal} €\n";
    }
    $cuerpo .= "\nTotal pagado: " . number_format($total_pedido, 2) . " EUR\n";
    $cuerpo .= "Método: Stripe (Sandbox)\n\n";
    $cuerpo .= "En breve comenzaremos a preparar tu envío.\n\n";
    $cuerpo .= "Atentamente,\nEl equipo de Algorya\nhola@algorya.store";

    $cabeceras = "From: hola@algorya.store\r\n" .
        "Reply-To: hola@algorya.store\r\n" .
        "X-Mailer: PHP/" . phpversion();

    @mail($email_cliente, $asunto, $cuerpo, $cabeceras);
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pago completado! | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card premium-card border-0 rounded-4 shadow-sm text-center p-5">

                    <!-- Icono de éxito -->
                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:90px; height:90px; background: rgba(34,197,94,0.15);">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                    </div>

                    <h2 class="fw-bold premium-text mb-2">¡Pago completado!</h2>
                    <p class="premium-muted mb-4">
                        Tu pedido <strong class="text-primary">#
                            <?php echo $pedido_id; ?>
                        </strong>
                        ha sido registrado correctamente.
                    </p>

                    <hr style="border-color: var(--border-color);">

                    <!-- Desglose de productos -->
                    <div class="text-start my-3">
                        <?php foreach ($items_pedido as $item): ?>
                            <div class="d-flex justify-content-between small premium-text py-1">
                                <span>
                                    <?php echo htmlspecialchars($item['nombre']); ?>
                                    <span class="premium-muted">×
                                        <?php echo (int) $item['cantidad']; ?>
                                    </span>
                                </span>
                                <span class="fw-semibold">
                                    <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?> €
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="border-color: var(--border-color);">

                    <!-- Total -->
                    <div class="d-flex justify-content-between align-items-center my-3">
                        <span class="fw-bold premium-text">Total pagado:</span>
                        <span class="fw-black text-primary fs-5">
                            <?php echo number_format($total_pedido, 2); ?> €
                        </span>
                    </div>

                    <!-- ID de Stripe para trazabilidad -->
                    <div class="premium-muted" style="font-size: 0.7rem;">
                        ID de sesión Stripe:
                        <code><?php echo htmlspecialchars(substr($session_id_url, 0, 30)); ?>...</code>
                    </div>

                    <!-- Aviso Sandbox -->
                    <div class="alert border-0 rounded-3 small text-start my-4"
                        style="background: rgba(59,130,246,0.1); color: var(--text-main);">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i>
                        <strong>Modo Sandbox:</strong> Pago simulado con Stripe Test.
                        No se han realizado cargos reales.
                        Te hemos enviado un recibo a tu correo.
                    </div>

                    <!-- Botones de acción -->
                    <a href="index.php" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold mb-2"
                        style="background: linear-gradient(135deg, #3b82f6, #6366f1); border: none;">
                        <i class="bi bi-bag me-2"></i>Seguir comprando
                    </a>
                    <a href="perfil.php" class="btn btn-outline-secondary rounded-pill w-100 fw-semibold">
                        <i class="bi bi-person me-2"></i>Ver mis pedidos
                    </a>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>