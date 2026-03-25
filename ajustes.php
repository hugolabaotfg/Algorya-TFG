<?php
// =============================================================================
// ALGORYA - ajustes.php
// Panel de configuración del usuario: datos personales, contraseña y notificaciones.
// Correcciones aplicadas respecto a la versión anterior:
//   - Modo oscuro nativo (variables CSS, data-bs-theme, estilos.css + tema.js)
//   - Eliminados todos los colores hardcodeados (bg-white, bg-light, color:#555...)
//   - Prepared statements en UPDATE de datos y contraseña
//   - Marca actualizada a Algorya
//   - i18n integrado
// =============================================================================

session_start();
require 'includes/db.php';
require 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';
$seccion_activa = $_GET['seccion'] ?? 'datos';

// ─────────────────────────────────────────────────────────────────────────────
// ACCIÓN: Actualizar datos personales
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['actualizar_datos'])) {
    $nuevo_nombre = trim($_POST['nombre']);

    if (strlen($nuevo_nombre) >= 2 && strlen($nuevo_nombre) <= 50) {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_nombre, $uid);
        if ($stmt->execute()) {
            $_SESSION['nombre'] = $nuevo_nombre;
            $mensaje = "Datos personales actualizados correctamente.";
            $tipo_mensaje = "success";
        }
        $stmt->close();
    } else {
        $mensaje = "El nombre debe tener entre 2 y 50 caracteres.";
        $tipo_mensaje = "danger";
    }
    $seccion_activa = 'datos';
}

// ─────────────────────────────────────────────────────────────────────────────
// ACCIÓN: Cambiar contraseña
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['cambiar_password'])) {
    $pass_actual = $_POST['pass_actual'];
    $pass_nueva = $_POST['pass_nueva'];
    $pass_confirma = $_POST['pass_confirma'];

    // Obtener el hash actual con prepared statement
    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($pass_actual, $row['password'])) {
        $mensaje = "La contraseña actual es incorrecta.";
        $tipo_mensaje = "danger";
    } elseif ($pass_nueva !== $pass_confirma) {
        $mensaje = "Las contraseñas nuevas no coinciden.";
        $tipo_mensaje = "warning";
    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $pass_nueva)) {
        $mensaje = "La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.";
        $tipo_mensaje = "warning";
    } else {
        $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt2->bind_param("si", $nuevo_hash, $uid);
        $stmt2->execute();
        $stmt2->close();
        $mensaje = "Contraseña cambiada con éxito. Usa tu nueva clave la próxima vez.";
        $tipo_mensaje = "success";
    }
    $seccion_activa = 'seguridad';
}

// ─────────────────────────────────────────────────────────────────────────────
// ACCIÓN: Guardar preferencias de notificaciones
// Los checkboxes NO envían valor si están desmarcados, por eso usamos isset()
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['guardar_notificaciones'])) {
    $notif_promos = isset($_POST['notif_promos']) ? 1 : 0;
    $notif_pedidos = isset($_POST['notif_pedidos']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE usuarios SET notif_promos = ?, notif_pedidos = ? WHERE id = ?");
    $stmt->bind_param("iii", $notif_promos, $notif_pedidos, $uid);
    if ($stmt->execute()) {
        $mensaje = "Preferencias de notificaciones actualizadas correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al guardar las preferencias.";
        $tipo_mensaje = "danger";
    }
    $stmt->close();
    $seccion_activa = 'notificaciones';
}

// Leer datos actuales del usuario para rellenar los formularios
$stmt_user = $conn->prepare("SELECT nombre, email, notif_promos, notif_pedidos FROM usuarios WHERE id = ?");
$stmt_user->bind_param("i", $uid);
$stmt_user->execute();
$usuario = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes de Cuenta | Algorya</title>
    <!--
        ORDEN CORRECTO:
        1. Bootstrap CSS
        2. Bootstrap Icons
        3. estilos.css  ← variables CSS del sistema de diseño premium
        tema.js se carga al FINAL del body
    -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <style>
        /*
         * Estilos de las pestañas de navegación (tabs).
         * Usamos var(--text-main) y var(--border-color) para que respeten
         * el modo oscuro. Antes usaban #555 y #111 hardcodeados.
         */
        .nav-tabs .nav-link {
            border: none !important;
            border-bottom: 3px solid transparent !important;
            color: var(--text-muted);
            font-weight: 600;
            transition: color .15s, border-color .15s;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent !important;
            color: var(--text-main);
        }

        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #3b82f6 !important;
            color: #3b82f6 !important;
            background-color: transparent !important;
        }

        /* Separador entre el header de las tabs y el cuerpo */
        .nav-tabs {
            border-bottom: 1px solid var(--border-color) !important;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- =========================================================================
     NAVBAR
     ========================================================================= -->
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing:-1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i>
                <span class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size:0.55em;">.store</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <div id="darkModeToggle" title="<?= t('nav_modo_oscuro') ?>" class="me-1">
                    <i class="bi bi-moon-stars-fill fs-6"></i>
                </div>
                <a href="perfil.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>Volver al Perfil
                </a>
            </div>
        </div>
    </nav>

    <!-- =========================================================================
     CONTENIDO PRINCIPAL
     ========================================================================= -->
    <div class="container my-5 flex-grow-1" style="max-width: 820px;">

        <h2 class="fw-bold premium-text mb-4">
            <i class="bi bi-gear text-primary me-2"></i>Ajustes de la Cuenta
        </h2>

        <!-- Mensaje de resultado (éxito / error / aviso) — desaparece solo en 3 segundos -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
                <i
                    class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>-fill me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tarjeta principal con las pestañas -->
        <div class="card premium-card border-0 rounded-4 shadow-sm overflow-hidden">

            <!-- Cabecera con las pestañas de navegación -->
            <div class="card-header border-0 pt-3 pb-0 px-4" style="background: var(--card-bg);">
                <ul class="nav nav-tabs border-0" id="ajustesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($seccion_activa == 'datos') ? 'active' : '' ?>" id="datos-tab"
                            data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
                            <i class="bi bi-person me-1"></i>Datos Personales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($seccion_activa == 'seguridad') ? 'active' : '' ?>"
                            id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button"
                            role="tab">
                            <i class="bi bi-shield-lock me-1"></i>Seguridad
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($seccion_activa == 'notificaciones') ? 'active' : '' ?>"
                            id="notificaciones-tab" data-bs-toggle="tab" data-bs-target="#notificaciones" type="button"
                            role="tab">
                            <i class="bi bi-bell me-1"></i>Notificaciones
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Cuerpo con el contenido de cada pestaña -->
            <div class="card-body p-4" style="background: var(--card-bg);">
                <div class="tab-content" id="ajustesTabsContent">

                    <!-- ── PESTAÑA 1: DATOS PERSONALES ─────────────────────── -->
                    <div class="tab-pane fade <?= ($seccion_activa == 'datos') ? 'show active' : '' ?>" id="datos"
                        role="tabpanel">

                        <p class="premium-muted small mb-4">
                            Actualiza tu nombre de perfil. El correo electrónico no puede modificarse.
                        </p>

                        <form action="ajustes.php?seccion=datos" method="POST">
                            <div class="mb-3">
                                <label class="form-label premium-muted small fw-bold text-uppercase"
                                    style="letter-spacing:.4px;">
                                    Nombre completo
                                </label>
                                <input type="text" name="nombre" class="form-control premium-input shadow-none py-2"
                                    value="<?= htmlspecialchars($usuario['nombre']) ?>" maxlength="50" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label premium-muted small fw-bold text-uppercase"
                                    style="letter-spacing:.4px;">
                                    Correo electrónico <span class="text-muted">(no modificable)</span>
                                </label>
                                <input type="email" class="form-control premium-input shadow-none py-2"
                                    value="<?= htmlspecialchars($usuario['email']) ?>" readonly
                                    style="opacity:.65; cursor:not-allowed;">
                            </div>
                            <button type="submit" name="actualizar_datos"
                                class="btn btn-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-check-lg me-1"></i>Guardar Cambios
                            </button>
                        </form>
                    </div>

                    <!-- ── PESTAÑA 2: SEGURIDAD ────────────────────────────── -->
                    <div class="tab-pane fade <?= ($seccion_activa == 'seguridad') ? 'show active' : '' ?>"
                        id="seguridad" role="tabpanel">

                        <p class="premium-muted small mb-4">
                            Para cambiar tu contraseña debes confirmar primero la contraseña actual.
                        </p>

                        <form action="ajustes.php?seccion=seguridad" method="POST">
                            <div class="mb-3">
                                <label class="form-label premium-muted small fw-bold text-uppercase"
                                    style="letter-spacing:.4px;">
                                    Contraseña actual
                                </label>
                                <input type="password" name="pass_actual"
                                    class="form-control premium-input shadow-none py-2" placeholder="••••••••" required>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label premium-muted small fw-bold text-uppercase"
                                        style="letter-spacing:.4px;">
                                        Nueva contraseña
                                    </label>
                                    <input type="password" name="pass_nueva" id="pass_nueva"
                                        class="form-control premium-input shadow-none py-2"
                                        placeholder="Mínimo 8 caracteres" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label premium-muted small fw-bold text-uppercase"
                                        style="letter-spacing:.4px;">
                                        Confirmar nueva contraseña
                                    </label>
                                    <input type="password" name="pass_confirma" id="pass_confirma"
                                        class="form-control premium-input shadow-none py-2"
                                        placeholder="Repite la nueva contraseña" required>
                                </div>
                                <!-- Feedback de coincidencia en tiempo real -->
                                <div class="col-12">
                                    <div id="pass-match-feedback" class="small d-none"></div>
                                </div>
                            </div>
                            <button type="submit" name="cambiar_password" id="btn-cambiar-pass"
                                class="btn btn-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-key me-1"></i>Actualizar Contraseña
                            </button>
                        </form>

                        <!-- Bloque 2FA (próximamente) -->
                        <hr class="my-4" style="border-color: var(--border-color);">
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-3"
                            style="border: 1px solid var(--border-color); background: var(--hover-bg);">
                            <div>
                                <h6 class="mb-1 fw-bold premium-text">
                                    <i class="bi bi-shield-lock-fill text-success me-2"></i>Verificación en 2 Pasos
                                    (2FA)
                                </h6>
                                <p class="mb-0 small premium-muted">Añade una capa extra de seguridad a tu cuenta.</p>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill" disabled>
                                Próximamente
                            </button>
                        </div>
                    </div>

                    <!-- ── PESTAÑA 3: NOTIFICACIONES ───────────────────────── -->
                    <div class="tab-pane fade <?= ($seccion_activa == 'notificaciones') ? 'show active' : '' ?>"
                        id="notificaciones" role="tabpanel">

                        <p class="premium-muted small mb-4">
                            Elige qué correos quieres recibir de Algorya.
                        </p>

                        <form action="ajustes.php?seccion=notificaciones" method="POST">

                            <!-- Switch: correos promocionales -->
                            <div class="d-flex align-items-start justify-content-between p-3 mb-3 rounded-3"
                                style="border: 1px solid var(--border-color);">
                                <div class="me-3">
                                    <p class="fw-bold premium-text mb-1">
                                        <i class="bi bi-megaphone text-primary me-2"></i>Correos promocionales
                                    </p>
                                    <p class="premium-muted small mb-0">
                                        Recibe avisos sobre nuevos productos en tendencia y ofertas especiales.
                                    </p>
                                </div>
                                <div class="form-check form-switch flex-shrink-0 ms-2 mt-1">
                                    <input class="form-check-input" type="checkbox" id="notif_promos"
                                        name="notif_promos" role="switch" <?= ($usuario['notif_promos'] == 1) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <!-- Switch: actualizaciones de pedido -->
                            <div class="d-flex align-items-start justify-content-between p-3 mb-4 rounded-3"
                                style="border: 1px solid var(--border-color);">
                                <div class="me-3">
                                    <p class="fw-bold premium-text mb-1">
                                        <i class="bi bi-box-seam text-primary me-2"></i>Actualizaciones de pedido
                                    </p>
                                    <p class="premium-muted small mb-0">
                                        Te avisaremos cuando tu pedido cambie de estado o sea enviado.
                                    </p>
                                </div>
                                <div class="form-check form-switch flex-shrink-0 ms-2 mt-1">
                                    <input class="form-check-input" type="checkbox" id="notif_pedidos"
                                        name="notif_pedidos" role="switch" <?= ($usuario['notif_pedidos'] == 1) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <button type="submit" name="guardar_notificaciones"
                                class="btn btn-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-check-lg me-1"></i>Guardar Preferencias
                            </button>
                        </form>
                    </div>

                </div><!-- /tab-content -->
            </div><!-- /card-body -->
        </div><!-- /card -->

    </div>

    <!-- =========================================================================
     FOOTER
     ========================================================================= -->
    <footer class="text-center py-4 mt-auto" style="border-top: 1px solid var(--border-color);">
        <p class="mb-0 premium-muted small fw-bold">
            <i class="bi bi-box-seam-fill text-primary"></i>
            Algorya &copy;
            <?= date('Y') ?>
        </p>
    </footer>

    <!-- Scripts — Bootstrap JS primero, tema.js al final -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="tema.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            // ── Cerrar alertas automáticamente tras 3 segundos ──────────────────────
            const alerta = document.querySelector('.alert');
            if (alerta) {
                setTimeout(() => new bootstrap.Alert(alerta).close(), 3000);
            }

            // ── Validación de coincidencia de contraseñas en tiempo real ────────────
            const passNueva = document.getElementById('pass_nueva');
            const passConfirma = document.getElementById('pass_confirma');
            const feedback = document.getElementById('pass-match-feedback');
            const btnCambiar = document.getElementById('btn-cambiar-pass');

            if (passNueva && passConfirma) {
                function checkPasswords() {
                    if (!passConfirma.value) {
                        feedback.classList.add('d-none');
                        btnCambiar.disabled = false;
                        return;
                    }
                    feedback.classList.remove('d-none');
                    if (passNueva.value === passConfirma.value) {
                        feedback.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i><span class="text-success">Las contraseñas coinciden.</span>';
                        btnCambiar.disabled = false;
                    } else {
                        feedback.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i><span class="text-danger">Las contraseñas no coinciden.</span>';
                        btnCambiar.disabled = true;
                    }
                }
                passNueva.addEventListener('input', checkPasswords);
                passConfirma.addEventListener('input', checkPasswords);
            }

        });
    </script>
</body>

</html>