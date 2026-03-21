<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';

// Detectar qué pestaña debe estar activa (por defecto: datos)
$seccion_activa = isset($_GET['seccion']) ? $_GET['seccion'] : 'datos';

// Procesar actualización de datos personales
if (isset($_POST['actualizar_datos'])) {
    $nuevo_nombre = $conn->real_escape_string($_POST['nombre']);
    $sql = "UPDATE usuarios SET nombre = '$nuevo_nombre' WHERE id = $uid";
    if ($conn->query($sql)) {
        $_SESSION['nombre'] = $nuevo_nombre;
        $mensaje = "Datos personales actualizados correctamente.";
        $tipo_mensaje = "success";
        $seccion_activa = 'datos'; // Mantener en la misma pestaña tras guardar
    }
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_password'])) {
    $pass_actual = $_POST['pass_actual'];
    $pass_nueva = $_POST['pass_nueva'];
    $pass_confirma = $_POST['pass_confirma'];

    $res = $conn->query("SELECT password FROM usuarios WHERE id = $uid");
    $row = $res->fetch_assoc();

    if (password_verify($pass_actual, $row['password'])) {
        if ($pass_nueva === $pass_confirma) {
            $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $conn->query("UPDATE usuarios SET password = '$nuevo_hash' WHERE id = $uid");
            $mensaje = "Contraseña cambiada con éxito. Usa tu nueva clave la próxima vez.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Las contraseñas nuevas no coinciden.";
            $tipo_mensaje = "warning";
        }
    } else {
        $mensaje = "La contraseña actual es incorrecta.";
        $tipo_mensaje = "danger";
    }
    $seccion_activa = 'seguridad'; // Mantener en la pestaña de seguridad tras guardar
}
// Procesar preferencias de notificaciones
if (isset($_POST['guardar_notificaciones'])) {
    // Truco PHP: Los checkboxes no envían '0' si están desmarcados, directamente no existen en $_POST.
    // Usamos un operador ternario: si existe, es 1. Si no existe, es 0.
    $notif_promos = isset($_POST['notif_promos']) ? 1 : 0;
    $notif_pedidos = isset($_POST['notif_pedidos']) ? 1 : 0;

    $sql = "UPDATE usuarios SET notif_promos = $notif_promos, notif_pedidos = $notif_pedidos WHERE id = $uid";
    if ($conn->query($sql)) {
        $mensaje = "Preferencias de notificaciones actualizadas correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al guardar las preferencias.";
        $tipo_mensaje = "danger";
    }
    $seccion_activa = 'notificaciones'; // Nos quedamos en esta pestaña
}
// Obtener datos actuales
$sql_user = "SELECT nombre, email, notif_promos, notif_pedidos FROM usuarios WHERE id = $uid";$res_user = $conn->query($sql_user);
$usuario = $res_user->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <style>
    /* Estilos dinámicos para las pestañas activas */
        .nav-tabs .nav-link {
            border: none !important;
            border-bottom: 3px solid transparent !important;
            color: #555;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent !important;
            color: #111;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #0d6efd !important; /* Azul de Bootstrap */
            color: #0d6efd !important;
            background-color: transparent !important;
        }
    </style>
    <meta charset="UTF-8">
    <title>Ajustes de Cuenta | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="perfil.php">
                <i class="bi bi-arrow-left me-2"></i> Volver al Perfil
            </a>
        </div>
    </nav>

    <div class="container" style="max-width: 800px;">
        <h2 class="fw-bold mb-4"><i class="bi bi-gear text-secondary me-2"></i>Ajustes de la Cuenta</h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <ul class="nav nav-tabs border-bottom-0 px-3" id="ajustesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold <?php echo ($seccion_activa == 'datos') ? 'active' : ''; ?>" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">Datos Personales</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold <?php echo ($seccion_activa == 'seguridad') ? 'active' : ''; ?>" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button" role="tab">Seguridad</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold <?php echo ($seccion_activa == 'notificaciones') ? 'active' : ''; ?>" id="notificaciones-tab" data-bs-toggle="tab" data-bs-target="#notificaciones" type="button" role="tab">Notificaciones</button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4 bg-white">
                <div class="tab-content" id="ajustesTabsContent">
                    
                    <div class="tab-pane fade <?php echo ($seccion_activa == 'datos') ? 'show active' : ''; ?>" id="datos" role="tabpanel">
                        <form action="ajustes.php?seccion=datos" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Nombre completo</label>
                                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Correo electrónico (No modificable)</label>
                                <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                            </div>
                            <button type="submit" name="actualizar_datos" class="btn btn-primary rounded-pill px-4 mt-2">Guardar Cambios</button>
                        </form>
                    </div>

                    <div class="tab-pane fade <?php echo ($seccion_activa == 'seguridad') ? 'show active' : ''; ?>" id="seguridad" role="tabpanel">
                        <form action="ajustes.php?seccion=seguridad" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Contraseña Actual</label>
                                <input type="password" name="pass_actual" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Nueva Contraseña</label>
                                    <input type="password" name="pass_nueva" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Confirmar Nueva Contraseña</label>
                                    <input type="password" name="pass_confirma" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" name="cambiar_password" class="btn btn-dark rounded-pill px-4 mt-2">Actualizar Contraseña</button>
                        </form>
                        
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border">
                            <div>
                                <h6 class="mb-1 fw-bold"><i class="bi bi-shield-lock-fill text-success me-2"></i>Verificación en 2 Pasos (2FA)</h6>
                                <p class="mb-0 small text-muted">Añade una capa extra de seguridad a tu cuenta.</p>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" disabled>Próximamente</button>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo ($seccion_activa == 'notificaciones') ? 'show active' : ''; ?>" id="notificaciones"
                        role="tabpanel">
                        <form action="ajustes.php?seccion=notificaciones" method="POST">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notif_promos" name="notif_promos" <?php echo ($usuario['notif_promos'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notif_promos">
                                    <strong>Correos promocionales</strong><br>
                                    <span class="text-muted small">Recibe avisos sobre nuevos productos en tendencia.</span>
                                </label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notif_pedidos" name="notif_pedidos" <?php echo ($usuario['notif_pedidos'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notif_pedidos">
                                    <strong>Actualizaciones de pedido</strong><br>
                                    <span class="text-muted small">Te avisaremos cuando tu pedido cambie de estado.</span>
                                </label>
                            </div>
                            <button type="submit" name="guardar_notificaciones" class="btn btn-primary rounded-pill px-4 mt-3">Guardar
                                Preferencias</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                let alerta = document.querySelector('.alert');
                if (alerta) {
                    // Usamos la función nativa de Bootstrap para cerrarla con animación
                    let bsAlert = new bootstrap.Alert(alerta);
                    bsAlert.close();
                }
            }, 3000); // 3000 milisegundos = 3 segundos
        });
    // </script>
</body>
</html>