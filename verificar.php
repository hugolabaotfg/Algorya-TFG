<?php
require 'includes/db.php';

$mensaje = "";
$tipo = "";

if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Buscamos a ver si existe alguien con ese token
    $sql = "SELECT id FROM usuarios WHERE token_verificacion = '$token' LIMIT 1";
    $resultado = $conn->query($sql);
    
    if ($resultado->num_rows == 1) {
        // Activamos la cuenta y borramos el token por seguridad
        $update = "UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE token_verificacion = '$token'";
        if ($conn->query($update)) {
            $mensaje = "¡Cuenta verificada con éxito! Ya puedes iniciar sesión de forma segura.";
            $tipo = "success";
        } else {
            $mensaje = "Error interno al verificar la cuenta.";
            $tipo = "danger";
        }
    } else {
        $mensaje = "El enlace de verificación no es válido o la cuenta ya ha sido activada.";
        $tipo = "danger";
    }
} else {
    $mensaje = "No se ha proporcionado ningún token de seguridad.";
    $tipo = "warning";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container text-center">
        <div class="card shadow-sm mx-auto" style="max-width: 500px;">
            <div class="card-body p-5">
                <h3 class="mb-4">Estado de la Verificación</h3>
                <div class="alert alert-<?php echo $tipo; ?>" role="alert">
                    <?php echo $mensaje; ?>
                </div>
                <a href="login.php" class="btn btn-primary mt-3">Ir al Login</a>
            </div>
        </div>
    </div>
</body>
</html>