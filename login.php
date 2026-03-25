<?php
// =============================================================================
// ALGORYA - login.php
// Autenticación de usuarios con prepared statements (sin SQL Injection)
// Soporta dos flujos:
//   1. Acceso directo a login.php  → redirige a index.php o admin_pedidos.php
//   2. Viene del modal de index.php → si hay error, vuelve a index.php?error=1
//      para que el modal se reabra automáticamente
// =============================================================================

session_start();
require 'includes/lang.php';
require 'includes/db.php';

// Si ya tiene sesión iniciada, lo mandamos donde corresponde
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['rol'] === 'admin' ? 'admin_pedidos.php' : 'index.php'));
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    // Recogemos los datos del formulario (sin escapar manualmente — eso lo hace el prepared statement)
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Leemos si el formulario viene del modal de index.php
    // (tiene un campo oculto name="redirect" value="index.php")
    $viene_del_modal = isset($_POST['redirect']) && $_POST['redirect'] === 'index.php';

    // -------------------------------------------------------------------------
    // PREPARED STATEMENT — Eliminamos el SQL Injection que había antes
    // Antes era:  "SELECT * FROM usuarios WHERE email = '$email'"  ← INSEGURO
    // Ahora es:   parámetro ? que MariaDB trata como dato, nunca como código SQL
    // -------------------------------------------------------------------------
    $stmt = $conn->prepare("SELECT id, nombre, password, rol, verificado FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);  // "s" = string
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificar contraseña con Bcrypt (password_verify compara con el hash guardado)
        if (password_verify($password, $usuario['password'])) {

            // Comprobar si la cuenta está verificada por email
            if ($usuario['verificado'] == 0) {
                $error = t("Debes verificar tu correo electrónico antes de iniciar sesión.");
            } else {
                // ¡Todo correcto! Iniciamos sesión
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['rol'];

                $stmt->close();

                // Redirigir según el rol
                if ($_SESSION['rol'] === 'admin') {
                    header("Location: admin_pedidos.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }

        } else {
            $error = t("Contraseña incorrecta. Inténtalo de nuevo.");
        }

    } else {
        $error = t("No existe ninguna cuenta asociada a este correo electrónico.");
    }

    $stmt->close();

    // -------------------------------------------------------------------------
    // Si hay error Y el formulario vino del modal de index.php,
    // redirigimos a index.php?error=1 para que el JS reabra el modal con el aviso
    // -------------------------------------------------------------------------
    if ($error && $viene_del_modal) {
        header("Location: index.php?error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('Iniciar Sesión') ?> | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex align-items-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card premium-card border-0 rounded-4 shadow-sm">
                    <div class="card-body p-5">

                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>
                            <h2 class="fw-bold mt-2 premium-text"><?= t('Acceso al Sistema') ?></h2>
                            <p class="premium-muted"><?= t('Introduce tus credenciales para continuar') ?></p>
                        </div>

                        <?php if ($error != ''): ?>
                            <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold premium-text"><?= t('Correo Electrónico') ?></label>
                                <input type="email" class="form-control premium-input shadow-none py-2" id="email"
                                    name="email" placeholder="tu@correo.com" required autofocus>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold premium-text"><?= t('Contraseña') ?></label>
                                <div class="input-group">
                                    <input type="password" class="form-control premium-input shadow-none py-2"
                                        id="password" name="password" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password"
                                        title="Mostrar/ocultar contraseña">
                                        <i class="bi bi-eye" id="eye-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" name="login"
                                class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm"
                                style="background-color: #3b82f6; border: none;">
                                <i class="bi bi-box-arrow-in-right me-2"></i><?= t('Entrar') ?>
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="registro.php" class="text-primary text-decoration-none fw-bold">
                                <?= t('¿No tienes cuenta? Regístrate gratis') ?>
                            </a>
                            <br>
                            <a href="index.php" class="text-decoration-none premium-muted small mt-2 d-inline-block">
                                <i class="bi bi-arrow-left"></i> <?= t('Volver al catálogo') ?>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Botón ojo para mostrar/ocultar contraseña
        document.getElementById('toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>

</body>

</html>