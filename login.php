<?php
session_start();
require 'includes/db.php';

// Si ya está logueado, lo mandamos fuera de aquí
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        // CORREGIDO: Redirección al panel de administración real
        header("Location: admin_pedidos.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = $conn->query($sql);

    // Comprobamos si el correo existe
    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificamos la contraseña hasheada
        if (password_verify($password, $usuario['password'])) {

            // Comprobar si la cuenta está verificada
            if ($usuario['verificado'] == 0) {
                $error = "Acceso denegado. Debes verificar tu correo electrónico antes de iniciar sesión.";
            } else {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['rol'];

                if ($_SESSION['rol'] === 'admin') {
                    // CORREGIDO: Redirección post-login para el admin
                    header("Location: admin_pedidos.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }

        } else {
            $error = "Contraseña incorrecta. Inténtalo de nuevo.";
        }
    } else {
        $error = "No existe ninguna cuenta asociada a este correo electrónico.";
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex align-items-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card premium-card border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>
                            <h2 class="fw-bold mt-2 premium-text">Acceso al Sistema</h2>
                            <p class="premium-muted">Introduce tus credenciales para continuar</p>
                        </div>

                        <?php if ($error != ''): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold premium-text">Correo Electrónico</label>
                                <input type="email" class="form-control premium-input shadow-none py-2" id="email"
                                    name="email" placeholder="tu@correo.com" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold premium-text">Contraseña</label>
                                <input type="password" class="form-control premium-input shadow-none py-2" id="password"
                                    name="password" placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm"
                                style="background-color: #3b82f6; border: none;">Entrar</button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="registro.php" class="text-primary text-decoration-none fw-bold">¿No tienes cuenta?
                                Regístrate gratis</a><br>
                            <a href="index.php" class="text-decoration-none premium-muted small mt-2 d-inline-block"><i
                                    class="bi bi-arrow-left"></i> Volver al catálogo</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>