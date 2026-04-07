<?php
// =============================================================================
// ALGORYA - nueva_password.php
// Paso 2: El usuario hace clic en el enlace del email y establece nueva contraseña.
// =============================================================================
session_start();
require 'includes/db.php';
require 'includes/lang.php';

if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$token       = trim($_GET['token'] ?? $_POST['token'] ?? '');
$mensaje     = '';
$tipo_alerta = '';
$token_valido = false;
$usuario_id   = null;
$usuario_nombre = '';

// Verificar token
if (!empty($token)) {
    // Añadir columnas si no existen
    $conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL");
    $conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS reset_expira DATETIME NULL");

    $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE reset_token = ? AND reset_expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $u = $res->fetch_assoc();
        $token_valido   = true;
        $usuario_id     = $u['id'];
        $usuario_nombre = $u['nombre'];
    } else {
        $mensaje     = "El enlace no es válido o ha caducado. Solicita uno nuevo.";
        $tipo_alerta = "danger";
    }
    $stmt->close();
}

// Procesar nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_password']) && $token_valido) {
    $pass1 = $_POST['nueva_password']    ?? '';
    $pass2 = $_POST['confirmar_password'] ?? '';

    if (strlen($pass1) < 8) {
        $mensaje     = "La contraseña debe tener al menos 8 caracteres.";
        $tipo_alerta = "danger";
    } elseif ($pass1 !== $pass2) {
        $mensaje     = "Las contraseñas no coinciden.";
        $tipo_alerta = "danger";
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
        $stmt->bind_param("si", $hash, $usuario_id);

        if ($stmt->execute()) {
            $stmt->close();
            // Redirigir al login con mensaje de éxito
            header("Location: login.php?password_cambiada=1");
            exit();
        } else {
            $mensaje     = "Error al actualizar la contraseña. Inténtalo de nuevo.";
            $tipo_alerta = "danger";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

<div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width:440px;">

        <!-- Logo -->
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none d-inline-flex align-items-center gap-2 justify-content-center">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" viewBox="0 0 16 16" fill="none"><path d="M8 1L13 14H10.5L9.5 11H6.5L5.5 14H3L8 1ZM7.2 9H8.8L8 6.5Z" fill="white"/></svg>
                </div>
                <span class="fw-black text-primary" style="font-family:'Outfit',sans-serif;font-size:1.4rem;letter-spacing:-.04em;">Algorya</span>
            </a>
        </div>

        <div class="premium-card rounded-4 p-4 shadow-sm">

            <?php if (!$token_valido): ?>
                <!-- Token inválido o caducado -->
                <div class="text-center py-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:64px;height:64px;background:rgba(239,68,68,.1);">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-2"></i>
                    </div>
                    <h4 class="fw-bold premium-text mb-2">Enlace inválido</h4>
                    <p class="premium-muted mb-4" style="font-size:.9rem;"><?= htmlspecialchars($mensaje) ?></p>
                    <a href="recuperar_password.php" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        Solicitar nuevo enlace
                    </a>
                </div>

            <?php else: ?>
                <!-- Formulario nueva contraseña -->
                <h4 class="fw-bold premium-text mb-1">Nueva contraseña</h4>
                <p class="premium-muted mb-4" style="font-size:.88rem;">
                    Hola <strong><?= htmlspecialchars($usuario_nombre) ?></strong>, elige una contraseña segura.
                </p>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_alerta ?> border-0 rounded-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($mensaje) ?>
                </div>
                <?php endif; ?>

                <form action="nueva_password.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.05em;">
                            Nueva contraseña
                        </label>
                        <input type="password" name="nueva_password"
                               class="form-control form-control-lg premium-input shadow-none"
                               placeholder="Mínimo 8 caracteres" required minlength="8" autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.05em;">
                            Confirmar contraseña
                        </label>
                        <input type="password" name="confirmar_password"
                               class="form-control form-control-lg premium-input shadow-none"
                               placeholder="Repite la contraseña" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">
                        <i class="bi bi-check-lg me-2"></i>Guardar nueva contraseña
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Validación en tiempo real: las contraseñas deben coincidir
const pass1 = document.querySelector('[name="nueva_password"]');
const pass2 = document.querySelector('[name="confirmar_password"]');
if (pass1 && pass2) {
    pass2.addEventListener('input', function() {
        if (this.value && this.value !== pass1.value) {
            this.setCustomValidity('Las contraseñas no coinciden');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });
}
</script>
</body>
</html>