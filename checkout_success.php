<?php
// =============================================================================
// ALGORYA - checkout_success.php
// Stripe redirige aquí cuando el pago ha sido APROBADO.
//
// Estructura real de la tabla pedidos:
//   id | usuario_id | total | metodo_pago | fecha | stripe_session_id
//
// REGLA i18n: en bloque PHP usar t('clave') directamente.
// En HTML usar la sintaxis de echo corto. Email en texto fijo sin traducir.
// =============================================================================

session_start();
require 'includes/db.php';
require 'includes/config.php';
require 'includes/lang.php';

// Solo usuarios logueados
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid            = (int) $_SESSION['user_id'];
$nombre_cliente = htmlspecialchars($_SESSION['nombre']);

// ─────────────────────────────────────────────────────────────────────────────
// PASO 1: Validar el session_id de Stripe contra el guardado en $_SESSION
// Si no coinciden -> posible acceso directo fraudulento -> redirigir
// ─────────────────────────────────────────────────────────────────────────────
$session_id_url    = $_GET['session_id']            ?? '';
$session_id_sesion = $_SESSION['stripe_session_id'] ?? '';

if (empty($session_id_url) || $session_id_url !== $session_id_sesion) {
    header("Location: index.php");
    exit();
}

// Eliminar el session_id de sesión para prevenir pedidos duplicados por recarga
unset($_SESSION['stripe_session_id']);

// ─────────────────────────────────────────────────────────────────────────────
// PASO 2: Verificar con Stripe que el pago está realmente en estado "paid"
// ─────────────────────────────────────────────────────────────────────────────
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($session_id_url));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_SSL_VERIFYPEER => false, // false en entorno local con certificado autofirmado
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    die("Error de conexión con Stripe: " . htmlspecialchars($curl_err) .
        "<br><a href='checkout.php'>Volver al resumen</a>");
}

$stripe_session = json_decode($response, true);

if ($http_code !== 200 || ($stripe_session['payment_status'] ?? '') !== 'paid') {
    header("Location: checkout.php?error=pago_no_confirmado");
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 3: Leer el carrito del usuario
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

// Si el carrito ya está vacío es una recarga -> no crear pedido duplicado
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
// PASO 4: Transacción ACID — crear pedido + líneas de pedido + vaciar carrito
// ─────────────────────────────────────────────────────────────────────────────
$conn->begin_transaction();
$pedido_id = null;

try {
    $metodo = 'Stripe (Sandbox)';

    $stmt_pedido = $conn->prepare(
        "INSERT INTO pedidos (usuario_id, total, metodo_pago, stripe_session_id)
         VALUES (?, ?, ?, ?)"
    );
    $stmt_pedido->bind_param("idss", $uid, $total_pedido, $metodo, $session_id_url);
    $stmt_pedido->execute();
    $pedido_id = $conn->insert_id;
    $stmt_pedido->close();

    $stmt_linea = $conn->prepare(
        "INSERT INTO lineas_pedido (pedido_id, producto_id, cantidad, precio_unitario)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($items_pedido as $item) {
        $stmt_linea->bind_param("iiid", $pedido_id, $item['producto_id'], $item['cantidad'], $item['precio']);
        $stmt_linea->execute();
    }
    $stmt_linea->close();

    $stmt_vaciar = $conn->prepare("DELETE FROM carritos WHERE usuario_id = ?");
    $stmt_vaciar->bind_param("i", $uid);
    $stmt_vaciar->execute();
    $stmt_vaciar->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    // Error critico -> deshacer TODO para no dejar datos inconsistentes
    die("Error crítico al registrar el pedido: " . htmlspecialchars($e->getMessage()) .
        "<br>Tu pago fue procesado por Stripe (ID: " . htmlspecialchars($session_id_url) . "). Contacta con soporte.");
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 5: Enviar recibo por email
// NOTA: el email se escribe en texto fijo (no usa t()) porque:
//   1. Se envía al servidor de correo, no se muestra en pantalla
//   2. El cliente puede tener cualquier idioma configurado pero queremos
//      que el recibo siempre sea legible -> usamos español
// ─────────────────────────────────────────────────────────────────────────────
// Email de confirmación via Brevo (PHPMailer)
$stmt_email = $conn->prepare("SELECT email FROM usuarios WHERE id = ?");
$stmt_email->bind_param("i", $uid);
$stmt_email->execute();
$res_email = $stmt_email->get_result();
$stmt_email->close();

if ($res_email && $row_email = $res_email->fetch_assoc()) {
    require_once __DIR__ . '/includes/mailer.php';
    mail_confirmacion_pedido(
        $row_email['email'],
        $nombre_cliente,
        $pedido_id,
        $items_pedido,
        $total_pedido,
        $session_id_url
    );
}
// =============================================================================
// A partir de aquí empieza el HTML.
// A partir de aqui empieza el HTML. Textos visibles traducidos con t('clave')
// =============================================================================
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('success_titulo') ?> | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
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

                <h2 class="fw-bold premium-text mb-2"><?= t('success_titulo') ?></h2>
                <p class="premium-muted mb-4">
                    <?= t('success_subtitulo', ['#' . $pedido_id]) ?>
                </p>

                <hr style="border-color: var(--border-color);">

                <!-- Desglose de productos -->
                <div class="text-start my-3">
                    <?php foreach ($items_pedido as $item): ?>
                    <div class="d-flex justify-content-between small premium-text py-1">
                        <span>
                            <?= htmlspecialchars($item['nombre']) ?>
                            <span class="premium-muted">×<?= (int)$item['cantidad'] ?></span>
                        </span>
                        <span class="fw-semibold">
                            <?= number_format($item['precio'] * $item['cantidad'], 2) ?> €
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr style="border-color: var(--border-color);">

                <!-- Total pagado -->
                <div class="d-flex justify-content-between align-items-center my-3">
                    <span class="fw-bold premium-text"><?= t('success_total') ?></span>
                    <span class="fw-black text-primary fs-5">
                        <?= number_format($total_pedido, 2) ?> €
                    </span>
                </div>

                <!-- ID de sesión Stripe (trazabilidad) -->
                <div class="premium-muted mb-3" style="font-size: 0.7rem;">
                    ID Stripe: <code><?= htmlspecialchars(substr($session_id_url, 0, 30)) ?>...</code>
                </div>

                <!-- Aviso Sandbox -->
                <div class="alert border-0 rounded-3 small text-start mb-4"
                     style="background: rgba(59,130,246,0.1); color: var(--text-main);">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>
                    <?= t('success_sandbox') ?>
                </div>

                <!-- Botones de acción -->
                <a href="index.php"
                   class="btn btn-primary btn-lg rounded-pill w-100 fw-bold mb-2"
                   style="background: linear-gradient(135deg, #3b82f6, #6366f1); border: none;">
                    <i class="bi bi-bag me-2"></i><?= t('success_btn_seguir') ?>
                </a>
                <a href="perfil.php"
                   class="btn btn-outline-secondary rounded-pill w-100 fw-semibold">
                    <i class="bi bi-person me-2"></i><?= t('success_btn_pedidos') ?>
                </a>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
</body>
</html>