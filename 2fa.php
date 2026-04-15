<?php
session_start();
require 'includes/db.php';
require 'includes/lang.php';

// Si no viene del login, lo echamos
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_ingresado = trim($_POST['codigo']);
    $user_id = $_SESSION['temp_user_id'];

    // Buscar al usuario y su código temporal
    $stmt = $conn->prepare("SELECT id, nombre, rol, codigo_2fa, expira_2fa FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user && $user['codigo_2fa'] === $codigo_ingresado) {
        // Comprobar si el código ha caducado
        if (strtotime($user['expira_2fa']) > time()) {
            
            // ¡ÉXITO! Destruimos el código por seguridad y creamos la sesión real
            $conn->query("UPDATE usuarios SET codigo_2fa = NULL, expira_2fa = NULL WHERE id = " . $user['id']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre']  = $user['nombre'];
            $_SESSION['rol']     = $user['rol'];
            unset($_SESSION['temp_user_id']); // Borramos la sesión temporal

            // Redirigir según el rol
            header("Location: " . ($user['rol'] === 'admin' ? 'admin_estadisticas.php' : 'index.php'));
            exit();
        } else {
            $mensaje = "El código ha caducado. Vuelve a iniciar sesión.";
            $tipo_alerta = "warning";
        }
    } else {
        $mensaje = "El código de seguridad es incorrecto.";
        $tipo_alerta = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación en dos pasos | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex align-items-center py-5" style="min-height:100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card premium-card border-0 rounded-4 shadow-sm">
                <div class="card-body p-5 text-center">

                    <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                    <h2 class="fw-bold mt-3 premium-text">Seguridad 2FA</h2>
                    <p class="premium-muted mb-4">Hemos enviado un código de 6 dígitos a tu correo electrónico. Introdúcelo para continuar.</p>

                    <?php if ($mensaje != ''): ?>
                    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show border-0 rounded-3 text-start" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i><?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="2fa.php" method="POST" autocomplete="off">
                        <div class="mb-4">
                            <input type="text" class="form-control form-control-lg text-center premium-input fw-bold tracking-widest" 
                                   id="codigo" name="codigo" placeholder="000000" maxlength="6" pattern="\d{6}" 
                                   style="letter-spacing: 0.5em; font-size: 1.5rem;" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" style="background-color:#3b82f6;border:none;">
                            Verificar y Entrar
                        </button>
                    </form>

                    <div class="mt-4">
                        <a href="login.php" class="text-decoration-none premium-muted small">
                            <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>