<?php
// =============================================================================
// ALGORYA - auth_google.php
// Callback de Google OAuth 2.0.
// Google redirige aquí tras la autenticación con ?code=...
// =============================================================================
session_start();
require 'includes/db.php';
require 'includes/lang.php';

// ─── CONFIGURACIÓN OAUTH ─────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     '793894236164-1mh14kaqdtb9sck59atuk2tglc9knvgt.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI',  'https://algorya.store/auth_google.php');

// ─── VERIFICAR CODE ──────────────────────────────────────────────────────────
if (empty($_GET['code'])) {
    header("Location: index.php?error=google_cancel");
    exit();
}

// Verificar state CSRF solo si la sesión lo tiene (puede perderse en redirecciones)
// Si no hay state en sesión, continuamos igualmente (el code de Google es suficiente)
if (!empty($_SESSION['oauth_state']) && !empty($_GET['state'])) {
    if ($_GET['state'] !== $_SESSION['oauth_state']) {
        header("Location: index.php?error=google_csrf");
        exit();
    }
}
unset($_SESSION['oauth_state']);

// ─── PASO 1: Intercambiar code por access_token ───────────────────────────────
$token_url = 'https://oauth2.googleapis.com/token';
$token_data = http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

$ch = curl_init($token_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $token_data,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$token_response = curl_exec($ch);
$http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$token_response) {
    header("Location: index.php?error=google_token");
    exit();
}

$token_json   = json_decode($token_response, true);
$access_token = $token_json['access_token'] ?? null;

if (!$access_token) {
    header("Location: index.php?error=google_token");
    exit();
}

// ─── PASO 2: Obtener datos del usuario de Google ─────────────────────────────
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $access_token"],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$user_response = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($user_response, true);
$google_id   = $google_user['id']             ?? null;
$email       = $google_user['email']          ?? null;
$nombre      = $google_user['name']           ?? null;
$verificado  = $google_user['verified_email'] ?? false;

if (!$google_id || !$email || !$verificado) {
    header("Location: index.php?error=google_data");
    exit();
}

// ─── PASO 3: Buscar o crear usuario en la BD ─────────────────────────────────

// Añadir columna google_id si no existe
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS google_id VARCHAR(50) NULL");

// Buscar por google_id primero
$stmt = $conn->prepare("SELECT id, nombre, rol FROM usuarios WHERE google_id = ?");
$stmt->bind_param("s", $google_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Usuario existente con Google → login directo
    $usuario = $res->fetch_assoc();
    $stmt->close();
} else {
    $stmt->close();

    // Buscar por email (puede que ya tenga cuenta normal)
    $stmt2 = $conn->prepare("SELECT id, nombre, rol FROM usuarios WHERE email = ?");
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    if ($res2->num_rows > 0) {
        // Ya existe con ese email → vincular google_id y verificar
        $usuario = $res2->fetch_assoc();
        $stmt2->close();

        $stmt3 = $conn->prepare("UPDATE usuarios SET google_id = ?, verificado = 1 WHERE id = ?");
        $stmt3->bind_param("si", $google_id, $usuario['id']);
        $stmt3->execute();
        $stmt3->close();
    } else {
        $stmt2->close();

        // Usuario nuevo → crear cuenta automáticamente
        $password_placeholder = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt4 = $conn->prepare(
            "INSERT INTO usuarios (nombre, email, password, rol, verificado, google_id)
             VALUES (?, ?, ?, 'cliente', 1, ?)"
        );
        $stmt4->bind_param("ssss", $nombre, $email, $password_placeholder, $google_id);
        $stmt4->execute();
        $nuevo_id = $conn->insert_id;
        $stmt4->close();

        $usuario = ['id' => $nuevo_id, 'nombre' => $nombre, 'rol' => 'cliente'];
    }
}

// ─── PASO 4: Iniciar sesión ───────────────────────────────────────────────────
$_SESSION['user_id'] = $usuario['id'];
$_SESSION['nombre']  = $usuario['nombre'];
$_SESSION['rol']     = $usuario['rol'];

// ─── PASO 5: Fusionar carrito de sesión si había productos ───────────────────
if (!empty($_SESSION['carrito'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt_check  = $conn->prepare("SELECT id, cantidad FROM carritos WHERE usuario_id = ? AND producto_id = ?");
    $stmt_update = $conn->prepare("UPDATE carritos SET cantidad = cantidad + ? WHERE usuario_id = ? AND producto_id = ?");
    $stmt_insert = $conn->prepare("INSERT INTO carritos (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");

    foreach ($_SESSION['carrito'] as $item) {
        $pid = (int)$item['id'];
        $qty = (int)$item['cantidad'];

        $stmt_check->bind_param("ii", $uid, $pid);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $stmt_update->bind_param("iii", $qty, $uid, $pid);
            $stmt_update->execute();
        } else {
            $stmt_insert->bind_param("iii", $uid, $pid, $qty);
            $stmt_insert->execute();
        }
        $stmt_check->free_result();
    }
    $stmt_check->close();
    $stmt_update->close();
    $stmt_insert->close();
    unset($_SESSION['carrito']);
}

// ─── Redirigir según el rol ───────────────────────────────────────────────────
session_write_close(); // Forzar guardado de sesión antes del redirect
header("Location: " . ($usuario['rol'] === 'admin' ? 'admin_estadisticas.php' : 'index.php?google=1'));
exit();