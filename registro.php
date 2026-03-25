<?php
// =============================================================================
// ALGORYA - registro.php
// Alta de nuevos clientes con:
//   - Doble campo de contraseña (verificación de que no se equivocó)
//   - Prepared statements (sin SQL Injection)
//   - Estética premium unificada con el resto de la web
//   - Detección de Bloq Mayús
//   - Token de verificación por email
// =============================================================================

session_start();
require 'includes/db.php';

// Si ya tiene sesión, no tiene sentido registrarse
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // --- Validaciones en el backend (aunque el JS ya las hace en el cliente) ---

    if (strlen($nombre) < 2 || strlen($nombre) > 50) {
        $mensaje = "El nombre debe tener entre 2 y 50 caracteres.";
        $tipo_alerta = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El formato del correo electrónico no es válido.";
        $tipo_alerta = "danger";

    } elseif ($password !== $password_confirm) {
        // Comprobación clave: las dos contraseñas deben ser idénticas
        $mensaje = "Las contraseñas no coinciden. Vuelve a escribirlas.";
        $tipo_alerta = "danger";

    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
        $mensaje = "La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.";
        $tipo_alerta = "danger";

    } else {

        // --- Comprobar si el email ya existe (prepared statement) ---
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensaje = "Este correo ya está registrado. ¿Quieres <a href='login.php' class='alert-link'>iniciar sesión</a>?";
            $tipo_alerta = "warning";
            $stmt_check->close();

        } else {
            $stmt_check->close();

            // --- Hash seguro de la contraseña con Bcrypt ---
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // --- Token criptográfico para verificar el email ---
            $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales únicos

            // --- Insertar el nuevo usuario (prepared statement) ---
            $stmt_insert = $conn->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol, verificado, token_verificacion)
                 VALUES (?, ?, ?, 'cliente', 0, ?)"
            );
            $stmt_insert->bind_param("ssss", $nombre, $email, $password_hashed, $token);

            if ($stmt_insert->execute()) {

                // --- Enviar email de verificación ---
                $enlace = "https://172.16.200.247/verificar.php?token=" . $token;
                $asunto = "[Acción Requerida] Verifica tu cuenta en Algorya";
                $cuerpo = "Hola $nombre,\n\n";
                $cuerpo .= "Gracias por registrarte en Algorya. Para activar tu cuenta, haz clic en el siguiente enlace:\n\n";
                $cuerpo .= "$enlace\n\n";
                $cuerpo .= "Si no solicitaste este registro, ignora este mensaje.\n\n";
                $cuerpo .= "Atentamente,\nEl equipo de Algorya";
                $cabeceras = "From: noreply@algorya.store\r\nReply-To: noreply@algorya.store\r\nX-Mailer: PHP/" . phpversion();

                @mail($email, $asunto, $cuerpo, $cabeceras);

                $mensaje = "¡Registro completado! Revisa tu correo (<strong>$email</strong>) y haz clic en el enlace de verificación para activar tu cuenta.";
                $tipo_alerta = "success";

            } else {
                $mensaje = "Error al crear la cuenta. Inténtalo de nuevo.";
                $tipo_alerta = "danger";
            }

            $stmt_insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex align-items-center py-5" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card premium-card border-0 rounded-4 shadow-sm">
                    <div class="card-body p-5">

                        <!-- Cabecera -->
                        <div class="text-center mb-4">
                            <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
                            <h2 class="fw-bold mt-2 premium-text">Crear una cuenta</h2>
                            <p class="premium-muted">Únete a Algorya y gestiona tus pedidos</p>
                        </div>

                        <!-- Mensaje de resultado (éxito, error, aviso) -->
                        <?php if ($mensaje != ''): ?>
                            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show border-0 rounded-3"
                                role="alert">
                                <i
                                    class="bi bi-<?php echo $tipo_alerta === 'success' ? 'check-circle' : ($tipo_alerta === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>-fill me-2"></i>
                                <?php echo $mensaje; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="registro.php" method="POST" id="form-registro" novalidate>

                            <!-- Nombre -->
                            <div class="mb-3">
                                <label for="nombre" class="form-label fw-bold premium-text">Nombre completo</label>
                                <input type="text" class="form-control premium-input shadow-none py-2" id="nombre"
                                    name="nombre" placeholder="Ej. Juan Pérez" maxlength="50"
                                    value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold premium-text">Correo electrónico</label>
                                <input type="email" class="form-control premium-input shadow-none py-2" id="email"
                                    name="email" placeholder="tu@correo.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required>
                            </div>

                            <!-- Contraseña -->
                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold premium-text">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control premium-input shadow-none py-2"
                                        id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-pass1"
                                        title="Mostrar/ocultar">
                                        <i class="bi bi-eye" id="eye1"></i>
                                    </button>
                                </div>
                                <div class="form-text premium-muted">
                                    Mínimo 8 caracteres, una mayúscula y un número.
                                </div>
                                <!-- Aviso Bloq Mayús -->
                                <div id="caps-warning" class="text-warning small fw-bold d-none mt-1">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>¡Bloq Mayús activado!
                                </div>
                            </div>

                            <!-- Confirmar contraseña (campo nuevo) -->
                            <div class="mb-4">
                                <label for="password_confirm" class="form-label fw-bold premium-text">Confirmar
                                    contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control premium-input shadow-none py-2"
                                        id="password_confirm" name="password_confirm" placeholder="Repite la contraseña"
                                        required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-pass2"
                                        title="Mostrar/ocultar">
                                        <i class="bi bi-eye" id="eye2"></i>
                                    </button>
                                </div>
                                <!-- Mensaje de coincidencia en tiempo real -->
                                <div id="match-feedback" class="small mt-1 d-none"></div>
                            </div>

                            <button type="submit" id="btn-registro"
                                class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm"
                                style="background-color: #3b82f6; border: none;">
                                <i class="bi bi-person-check me-2"></i>Confirmar Registro
                            </button>

                        </form>

                        <div class="text-center mt-4">
                            <a href="login.php" class="text-primary text-decoration-none fw-bold">
                                ¿Ya tienes cuenta? Inicia sesión aquí
                            </a>
                            <br>
                            <a href="index.php" class="text-decoration-none premium-muted small mt-2 d-inline-block">
                                <i class="bi bi-arrow-left"></i> Volver al catálogo
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const pass1Input = document.getElementById('password');
            const pass2Input = document.getElementById('password_confirm');
            const capsWarning = document.getElementById('caps-warning');
            const matchFeedback = document.getElementById('match-feedback');
            const btnRegistro = document.getElementById('btn-registro');

            // ------------------------------------------------------------------
            // 1. Detección de Bloq Mayús en el campo de contraseña
            // ------------------------------------------------------------------
            pass1Input.addEventListener('keyup', function (e) {
                capsWarning.classList.toggle('d-none', !e.getModifierState('CapsLock'));
            });

            // ------------------------------------------------------------------
            // 2. Verificación en tiempo real: ¿las dos contraseñas coinciden?
            // Se activa mientras el usuario escribe en el segundo campo.
            // ------------------------------------------------------------------
            function checkPasswords() {
                const v1 = pass1Input.value;
                const v2 = pass2Input.value;

                if (v2.length === 0) {
                    // El usuario no ha empezado a escribir en el 2º campo → no mostramos nada
                    matchFeedback.classList.add('d-none');
                    btnRegistro.disabled = false;
                    return;
                }

                matchFeedback.classList.remove('d-none');

                if (v1 === v2) {
                    matchFeedback.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i><span class="text-success">Las contraseñas coinciden.</span>';
                    btnRegistro.disabled = false;
                } else {
                    matchFeedback.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i><span class="text-danger">Las contraseñas no coinciden.</span>';
                    // Deshabilitamos el botón para evitar envíos con error
                    btnRegistro.disabled = true;
                }
            }

            pass1Input.addEventListener('input', checkPasswords);
            pass2Input.addEventListener('input', checkPasswords);

            // ------------------------------------------------------------------
            // 3. Validación final antes de enviar el formulario
            // Aunque el botón esté deshabilitado, hacemos una última comprobación
            // ------------------------------------------------------------------
            document.getElementById('form-registro').addEventListener('submit', function (e) {
                if (pass1Input.value !== pass2Input.value) {
                    e.preventDefault();
                    matchFeedback.classList.remove('d-none');
                    matchFeedback.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i><span class="text-danger">Las contraseñas no coinciden.</span>';
                    pass2Input.focus();
                }
            });

            // ------------------------------------------------------------------
            // 4. Botones ojo para mostrar/ocultar cada campo de contraseña
            // ------------------------------------------------------------------
            function togglePassword(inputId, eyeId) {
                const input = document.getElementById(inputId);
                const eyeIcon = document.getElementById(eyeId);
                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            }

            document.getElementById('toggle-pass1').addEventListener('click', () => togglePassword('password', 'eye1'));
            document.getElementById('toggle-pass2').addEventListener('click', () => togglePassword('password_confirm', 'eye2'));

        });
    </script>

</body>

</html>