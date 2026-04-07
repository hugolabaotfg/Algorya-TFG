<?php
// =============================================================================
// ALGORYA - recuperar_password.php
// Paso 1: El usuario introduce su email y recibe un enlace de recuperación.
// =============================================================================
session_start();
require 'includes/db.php';
require 'includes/lang.php';

if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$mensaje     = '';
$tipo_alerta = '';
$paso        = 'solicitar'; // solicitar | enviado

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar'])) {
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje     = 'Introduce un email válido.';
        $tipo_alerta = 'danger';
    } else {
        // Verificar si el email existe
        $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ? AND verificado = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $usuario = $res->fetch_assoc();
            $token   = bin2hex(random_bytes(32));
            $expira  = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Guardar token en BD
            // Primero verificar si existe la columna, si no crearla
            $conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL");
            $conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS reset_expira DATETIME NULL");

            $stmt2 = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
            $stmt2->bind_param("ssi", $token, $expira, $usuario['id']);
            $stmt2->execute();
            $stmt2->close();

            // Enviar email
            require_once __DIR__ . '/includes/mailer.php';
            $enlace = "https://algorya.store/nueva_password.php?token=" . $token;
            $asunto = "[Algorya] Recupera tu contraseña";
            $cuerpo = "Hola {$usuario['nombre']},\n\n";
            $cuerpo .= "Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.\n\n";
            $cuerpo .= "Haz clic en el siguiente enlace para crear una nueva contraseña:\n\n";
            $cuerpo .= "  {$enlace}\n\n";
            $cuerpo .= "Este enlace caduca en 1 hora. Si no solicitaste este cambio, ignora este mensaje.\n\n";
            $cuerpo .= "---\nEl equipo de Algorya\nhola@algorya.store";

            algorya_mail($email, $usuario['nombre'], $asunto, $cuerpo);
        }

        // Siempre mostramos el mismo mensaje (seguridad anti-enumeración)
        $paso        = 'enviado';
        $mensaje     = "Si ese email está registrado, recibirás un enlace en breve. Revisa también tu carpeta de spam.";
        $tipo_alerta = 'success';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | Algorya</title>
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

            <?php if ($paso === 'enviado'): ?>
                <!-- Estado: email enviado -->
                <div class="text-center py-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:64px;height:64px;background:rgba(34,197,94,.1);">
                        <i class="bi bi-envelope-check-fill text-success fs-2"></i>
                    </div>
                    <h4 class="fw-bold premium-text mb-2">¡Email enviado!</h4>
                    <p class="premium-muted mb-4" style="font-size:.9rem;"><?= htmlspecialchars($mensaje) ?></p>
                    <a href="index.php" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-arrow-left me-2"></i>Volver al catálogo
                    </a>
                </div>

            <?php else: ?>
                <!-- Estado: formulario de solicitud -->
                <h4 class="fw-bold premium-text mb-1">Recuperar contraseña</h4>
                <p class="premium-muted mb-4" style="font-size:.88rem;">
                    Introduce tu email y te enviaremos un enlace para crear una nueva contraseña.
                </p>

                <?php if ($mensaje && $tipo_alerta === 'danger'): ?>
                <div class="alert alert-danger border-0 rounded-3 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($mensaje) ?>
                </div>
                <?php endif; ?>

                <form action="recuperar_password.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.05em;">
                            Correo electrónico
                        </label>
                        <input type="email" name="email" class="form-control form-control-lg premium-input shadow-none"
                               placeholder="tu@email.com" required autofocus>
                    </div>
                    <button type="submit" name="solicitar" class="btn btn-primary w-100 rounded-pill fw-bold py-2 mb-3">
                        <i class="bi bi-send me-2"></i>Enviar enlace de recuperación
                    </button>
                </form>

                <div class="text-center">
                    <a href="login.php" class="text-decoration-none premium-muted" style="font-size:.85rem;">
                        <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>