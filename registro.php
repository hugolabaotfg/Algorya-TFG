<?php
session_start();
require 'includes/db.php';

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);

    if (strlen($nombre) > 50) {
        $mensaje = "El nombre no puede exceder los 50 caracteres.";
        $tipo_alerta = "danger";
        // No continuamos con el proceso de registro
        echo "<script>window.onload = function() { var alertPlaceholder = document.querySelector('.container'); var alert = document.createElement('div'); alert.className = 'alert alert-danger alert-dismissible fade show'; alert.role = 'alert'; alert.innerHTML = '$mensaje <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>'; alertPlaceholder.prepend(alert); };</script>";
        exit();
    }
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $nombre_seguro = $conn->real_escape_string($nombre);
    $email_seguro = $conn->real_escape_string($email);

    $sql_check = "SELECT id FROM usuarios WHERE email = '$email_seguro'";
    $resultado_check = $conn->query($sql_check);

    if ($resultado_check === false) {
        $mensaje = "Error crítico de BBDD al comprobar correo: " . $conn->error;
        $tipo_alerta = "danger";
    } else if ($resultado_check->num_rows > 0) {
        $mensaje = "El correo ya está registrado en nuestro sistema.";
        $tipo_alerta = "danger";
    } else {
        // Generamos un token criptográficamente seguro de 32 caracteres
        $token = bin2hex(random_bytes(16));
        
        // Insertamos al usuario con verificado = 0 y su token
        $sql = "INSERT INTO usuarios (nombre, email, password, rol, verificado, token_verificacion) VALUES ('$nombre_seguro', '$email_seguro', '$password_hashed', 'cliente', 0, '$token')";
        
        if ($conn->query($sql) === TRUE) {
            
            // Envío de correo con directrices corporativas
            $para = $email;
            $asunto = "[Acción Requerida] Verificación de cuenta segura";
            
            // Se asume HTTPS para enlaces seguros en el entorno de producción/TFG
            $enlace_seguro = "https://172.16.200.247/verificar.php?token=" . $token;
            
            $cuerpo = "Estimado/a $nombre,\n\n";
            $cuerpo .= "Para garantizar la seguridad de su cuenta y finalizar el proceso de alta, es estrictamente necesario verificar su dirección de correo electrónico.\n\n";
            $cuerpo .= "Por favor, acceda al siguiente enlace seguro para activar su cuenta corporativa:\n";
            $cuerpo .= "$enlace_seguro\n\n";
            $cuerpo .= "Atentamente,\n\n";
            $cuerpo .= "--\n";
            $cuerpo .= "Coordinación Departamental de Sistemas\n";
            $cuerpo .= "Algorya - Uso exclusivo profesional\n";
            
            $cabeceras = "From: noreply@dropshiphgl.local\r\n" .
                         "Reply-To: noreply@dropshiphgl.local\r\n" .
                         "X-Mailer: PHP/" . phpversion();

            mail($para, $asunto, $cuerpo, $cabeceras);

            $mensaje = "Registro casi completo. Le hemos enviado un correo con un enlace seguro para verificar su cuenta.";
            $tipo_alerta = "warning";
        } else {
            $mensaje = "Error al crear la cuenta en BBDD: " . $conn->error;
            $tipo_alerta = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
                        <h2 class="fw-bold mt-2">Crear una cuenta</h2>
                        <p class="text-muted">Únete a la tienda y gestiona tus pedidos</p>
                    </div>
                    
                    <?php if ($mensaje != ''): ?>
                        <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="registro.php" method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre completo</label>
                            <input type="text" class="form-control bg-light" maxlength="50" id="nombre" name="nombre" placeholder="Ej. Juan Pérez" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Correo electrónico corporativo / personal</label>
                            <input type="email" class="form-control bg-light" id="email" name="email" placeholder="tu@correo.com" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Contraseña de acceso</label>
                            <input type="password" class="form-control bg-light" id="password" name="password" 
                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                   title="Debe contener al menos un número, una letra mayúscula, una minúscula y 8 caracteres en total." 
                                   required>
                            <div class="form-text">Mínimo 8 caracteres. Debe incluir una mayúscula y un número.</div>
                            
                            <div id="caps-warning" class="text-danger small fw-bold d-none mt-2">
                                <i class="bi bi-exclamation-triangle-fill"></i> ¡Atención! Bloq Mayús está activado.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm">Confirmar Registro</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-decoration-none fw-bold">¿Ya tienes cuenta? Inicia sesión aquí</a><br>
                        <a href="index.php" class="text-decoration-none text-muted small mt-2 d-inline-block"><i class="bi bi-arrow-left"></i> Volver al catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script Profesional para detectar el Bloqueo de Mayúsculas
    document.addEventListener("DOMContentLoaded", function() {
        const passwordInput = document.getElementById('password');
        const capsWarning = document.getElementById('caps-warning');

        passwordInput.addEventListener('keyup', function(event) {
            if (event.getModifierState('CapsLock')) {
                capsWarning.classList.remove('d-none');
            } else {
                capsWarning.classList.add('d-none');
            }
        });
        
        // También comprobar si hacen clic dentro del input teniendo las mayúsculas puestas
        passwordInput.addEventListener('mousedown', function(event) {
            if (event.getModifierState('CapsLock')) {
                capsWarning.classList.remove('d-none');
            } else {
                capsWarning.classList.add('d-none');
            }
        });
    });
</script>
</body>
</html>