<?php
// =============================================================================
// ALGORYA - stripe_create_session.php
// Este archivo es el "puente" entre Algorya y Stripe.
//
// ¿Qué hace exactamente?
//   1. Lee el carrito del usuario de la base de datos
//   2. Llama a la API de Stripe con los productos y precios
//   3. Stripe devuelve una URL de pago única y temporal
//   4. Este script redirige al usuario a esa URL de Stripe
//   5. Stripe procesará el pago y redirigirá a checkout_success.php o checkout_cancel.php
//
// IMPORTANTE: Este archivo NO tiene HTML. Solo hace lógica y redirige.
// =============================================================================

session_start();
require 'includes/db.php';
require 'includes/config.php'; // Aquí están las claves de Stripe (ver instrucciones)

// Solo usuarios logueados
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Solo acepta POST (desde el formulario de checkout.php)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];

// Leer el carrito desde la BD
$stmt = $conn->prepare(
    "SELECT c.cantidad, p.nombre, p.precio
     FROM carritos c
     JOIN productos p ON c.producto_id = p.id
     WHERE c.usuario_id = ?"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

if ($res->num_rows === 0) {
    header("Location: carrito.php");
    exit();
}

// Construimos el array de "line_items" que Stripe necesita
// Stripe necesita los precios en CÉNTIMOS (enteros), no en euros con decimales
// Ejemplo: 19.99 € → 1999 céntimos
$line_items = [];
while ($row = $res->fetch_assoc()) {
    $precio_centimos = (int) round($row['precio'] * 100); // Convertir a céntimos
    $line_items[] = [
        'price_data' => [
            'currency' => 'eur',
            'unit_amount' => $precio_centimos,
            'product_data' => [
                'name' => $row['nombre'],
            ],
        ],
        'quantity' => (int) $row['cantidad'],
    ];
}

// =============================================================================
// LLAMADA A LA API DE STRIPE
// Usamos cURL (cliente HTTP de PHP) para comunicarnos con Stripe.
// No necesitamos instalar ninguna librería extra — cURL ya viene con PHP.
// =============================================================================

// Determinar la URL base del servidor para las redirecciones
// En local: https://172.16.200.247  |  En producción: https://algorya.store
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    ? 'https://' . $_SERVER['HTTP_HOST']
    : 'http://' . $_SERVER['HTTP_HOST'];

// Preparamos el payload (los datos que enviamos a Stripe)
$payload = [
    'mode' => 'payment',  // Pago único (no suscripción)
    'line_items' => $line_items,
    // URL a la que Stripe redirigirá si el pago fue exitoso
    'success_url' => $base_url . '/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
    // URL a la que Stripe redirigirá si el usuario cancela
    'cancel_url' => $base_url . '/checkout_cancel.php',
];

// Codificamos el payload al formato que acepta Stripe (application/x-www-form-urlencoded)
// Stripe NO acepta JSON en este endpoint, necesita este formato especial
function stripe_encode($data, $prefix = '')
{
    $result = [];
    foreach ($data as $key => $value) {
        $full_key = $prefix ? "{$prefix}[{$key}]" : $key;
        if (is_array($value)) {
            $result = array_merge($result, stripe_encode($value, $full_key));
        } else {
            $result[] = urlencode($full_key) . '=' . urlencode($value);
        }
    }
    return $result;
}
$body = implode('&', stripe_encode($payload));

// Hacemos la petición HTTP a la API de Stripe con cURL
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    // La clave secreta de Stripe va como usuario en la autenticación HTTP Basic
    // Stripe usa este formato: usuario = sk_test_TU_CLAVE, contraseña = vacía
    CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
    CURLOPT_SSL_VERIFYPEER => true, // En producción SIEMPRE true. En local puede ser false si hay error SSL
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Comprobamos si cURL tuvo algún error de red
if ($curl_error) {
    die("❌ Error de conexión con Stripe: " . htmlspecialchars($curl_error) .
        "<br><a href='checkout.php'>Volver</a>");
}

// Decodificamos la respuesta JSON de Stripe
$session = json_decode($response, true);

// Comprobamos si Stripe devolvió un error (código HTTP diferente de 200)
if ($http_code !== 200 || !isset($session['url'])) {
    $error_msg = $session['error']['message'] ?? 'Error desconocido de Stripe';
    die("❌ Error al crear la sesión de pago: " . htmlspecialchars($error_msg) .
        "<br><a href='checkout.php'>Volver al resumen</a>");
}

// Guardamos el session_id de Stripe en sesión PHP para verificarlo después
// Esto es importante para seguridad: cuando Stripe nos devuelva el usuario,
// comprobaremos que el session_id coincide y no ha sido manipulado
$_SESSION['stripe_session_id'] = $session['id'];

// ¡Todo correcto! Redirigimos al usuario a la página de pago de Stripe
header("Location: " . $session['url']);
exit();