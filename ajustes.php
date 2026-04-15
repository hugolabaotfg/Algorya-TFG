<?php
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

if (isset($_POST['actualizar_datos'])) {
    $nuevo_nombre = trim($_POST['nombre']);
    if (strlen($nuevo_nombre) >= 2 && strlen($nuevo_nombre) <= 50) {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_nombre, $uid);
        if ($stmt->execute()) {
            $_SESSION['nombre'] = $nuevo_nombre;
            $mensaje = t('exito_generico');
            $tipo_mensaje = "success";
        }
        $stmt->close();
    } else {
        $mensaje = t('error_generico');
        $tipo_mensaje = "danger";
    }
    $seccion_activa = 'datos';
}

if (isset($_POST['cambiar_password'])) {
    $pass_actual = $_POST['pass_actual'];
    $pass_nueva = $_POST['pass_nueva'];
    $pass_confirma = $_POST['pass_confirma'];

    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($pass_actual, $row['password'])) {
        $mensaje = t('modal_login_error');
        $tipo_mensaje = "danger";
    } elseif ($pass_nueva !== $pass_confirma) {
        $mensaje = t('ajustes_pass_no_coinciden');
        $tipo_mensaje = "warning";
    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $pass_nueva)) {
        $mensaje = t('registro_pass_hint');
        $tipo_mensaje = "warning";
    } else {
        $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt2->bind_param("si", $nuevo_hash, $uid);
        $stmt2->execute();
        $stmt2->close();
        $mensaje = t('exito_generico');
        $tipo_mensaje = "success";
    }
    $seccion_activa = 'seguridad';
}

if (isset($_POST['guardar_notificaciones'])) {
    $notif_promos = isset($_POST['notif_promos']) ? 1 : 0;
    $notif_pedidos = isset($_POST['notif_pedidos']) ? 1 : 0;
    $stmt = $conn->prepare("UPDATE usuarios SET notif_promos = ?, notif_pedidos = ? WHERE id = ?");
    $stmt->bind_param("iii", $notif_promos, $notif_pedidos, $uid);
    $mensaje = $stmt->execute() ? t('exito_generico') : t('error_generico');
    $tipo_mensaje = $stmt->execute() ? "success" : "danger";
    $stmt->close();
    $seccion_activa = 'notificaciones';
}
// --- NUEVO BLOQUE: ACTIVAR/DESACTIVAR 2FA ---
if (isset($_POST['toggle_2fa'])) {
    // Averiguamos cómo lo tiene ahora para ponerlo al revés
    $stmt_check = $conn->prepare("SELECT usa_2fa FROM usuarios WHERE id = ?");
    $stmt_check->bind_param("i", $uid);
    $stmt_check->execute();
    $estado_actual = $stmt_check->get_result()->fetch_assoc()['usa_2fa'];
    $stmt_check->close();

    $nuevo_estado = ($estado_actual == 1) ? 0 : 1;

    $stmt_upd = $conn->prepare("UPDATE usuarios SET usa_2fa = ? WHERE id = ?");
    $stmt_upd->bind_param("ii", $nuevo_estado, $uid);
    $stmt_upd->execute();
    $stmt_upd->close();

    $mensaje = t('exito_generico');
    $tipo_mensaje = "success";
    $seccion_activa = 'seguridad';
}

$stmt_user = $conn->prepare("SELECT nombre, email, notif_promos, notif_pedidos, usa_2fa FROM usuarios WHERE id = ?");
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
    <title>
        <?= t('ajustes_titulo') ?> | Algorya
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <style>
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

        .nav-tabs {
            border-bottom: 1px solid var(--border-color) !important;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

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
                    <i class="bi bi-arrow-left me-1"></i>
                    <?= t('ajustes_volver') ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5 flex-grow-1" style="max-width:820px;">

        <h2 class="fw-bold premium-text mb-4">
            <i class="bi bi-gear text-primary me-2"></i>
            <?= t('ajustes_titulo') ?>
        </h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
                <i
                    class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>-fill me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card premium-card border-0 rounded-4 shadow-sm overflow-hidden">

            <div class="card-header border-0 pt-3 pb-0 px-4" style="background:var(--card-bg);">
                <ul class="nav nav-tabs border-0" id="ajustesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($seccion_activa == 'datos') ? 'active' : '' ?>" id="datos-tab"
                            data-bs-toggle="tab" data-bs-target="#datos" type="button">
                            <i class="bi bi-person me-1"></i>
                            <?= t('ajustes_tab_datos') ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($seccion_activa == 'seguridad') ? 'active' : '' ?>"
                            id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button">
                            <i class="bi bi-shield-lock me-1"></i>
                            <?= t('ajustes_tab_seguridad') ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($seccion_activa == 'notificaciones') ? 'active' : '' ?>"
                            id="notificaciones-tab" data-bs-toggle="tab" data-bs-target="#notificaciones" type="button">
                            <i class="bi bi-bell me-1"></i>
                            <?= t('ajustes_tab_notif') ?>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4" style="background:var(--card-bg);">
                <div class="tab-content">

                    <!-- PESTAÑA 1: DATOS PERSONALES -->
                    <div class="tab-pane fade <?= ($seccion_activa == 'datos') ? 'show active' : '' ?>" id="datos">
                        <p class="premium-muted small mb-4">
                            <?= t('ajustes_datos_desc') ?>
                        </p>
                        <form action="ajustes.php?seccion=datos" method="POST">
                            <div class="mb-3">
                                <label class="form-label premium-muted small fw-bold text-uppercase"
                                    style="letter-spacing:.4px;">
                                    <?= t('ajustes_nombre') ?>
                                </label>
                                <input type="text" name="nombre" class="form-control premium-input shadow-none py-2"
                                    value="<?= htmlspecialchars($usuario['nombre']) ?>" maxlength="50" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label premium-muted small fw-bold text-uppercase"
                                    style="letter-spacing:.4px;">
                                    <?= t('ajustes_email') ?> <span class="text-muted">
                                        <?= t('ajustes_email_readonly') ?>
                                    </span>
                                </label>
                                <input type="email" class="form-control premium-input shadow-none py-2"
                                    value="<?= htmlspecialchars($usuario['email']) ?>" readonly
                                    style="opacity:.65;cursor:not-allowed;">
                            </div>
                            <button type="submit" name="actualizar_datos"
                                class="btn btn-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-check-lg me-1"></i>
                                <?= t('ajustes_guardar') ?>
                            </button>
                        </form>
                    </div>

                    <!-- PESTAÑA 2: SEGURIDAD -->
                    <div class="tab-pane fade <?= ($seccion_activa == 'seguridad') ? 'show active' : '' ?>"
                        id="seguridad">
                        <p class="premium-muted small mb-4">
                            <?= t('ajustes_seg_desc') ?>
                        </p>
                        <form action="ajustes.php?seccion=seguridad" method="POST">
                            <div class="mb-3">
                                <label class="form-label premium-muted small fw-bold text-uppercase"
                                    style="letter-spacing:.4px;">
                                    <?= t('ajustes_pass_actual') ?>
                                </label>
                                <input type="password" name="pass_actual"
                                    class="form-control premium-input shadow-none py-2" placeholder="••••••••" required>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label premium-muted small fw-bold text-uppercase"
                                        style="letter-spacing:.4px;">
                                        <?= t('ajustes_pass_nueva') ?>
                                    </label>
                                    <input type="password" name="pass_nueva" id="pass_nueva"
                                        class="form-control premium-input shadow-none py-2"
                                        placeholder="<?= t('ajustes_pass_nueva_hint') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label premium-muted small fw-bold text-uppercase"
                                        style="letter-spacing:.4px;">
                                        <?= t('ajustes_pass_confirmar') ?>
                                    </label>
                                    <input type="password" name="pass_confirma" id="pass_confirma"
                                        class="form-control premium-input shadow-none py-2"
                                        placeholder="<?= t('ajustes_pass_repite') ?>" required>
                                </div>
                                <div class="col-12">
                                    <div id="pass-match-feedback" class="small d-none"></div>
                                </div>
                            </div>
                            <button type="submit" name="cambiar_password" id="btn-cambiar-pass"
                                class="btn btn-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-key me-1"></i>
                                <?= t('ajustes_actualizar_pass') ?>
                            </button>
                        </form>

                        <hr class="my-4" style="border-color:var(--border-color);">
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-3"
                            style="border:1px solid var(--border-color);background:var(--hover-bg);">
                            <div>
                                <h6 class="mb-1 fw-bold premium-text">
                                    <i class="bi bi-shield-lock-fill text-success me-2"></i>
                                    <?= t('ajustes_2fa_titulo') ?>
                                </h6>
                                <p class="mb-0 small premium-muted">
                                    <?= t('ajustes_2fa_texto') ?>
                                </p>
                            </div>
                            
                            <form action="ajustes.php?seccion=seguridad" method="POST" class="m-0">
                                <?php if ($usuario['usa_2fa'] == 1): ?>
                                    <button type="submit" name="toggle_2fa" class="btn btn-danger btn-sm rounded-pill fw-bold px-3">
                                        <i class="bi bi-x-circle me-1"></i> Desactivar
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="toggle_2fa" class="btn btn-success btn-sm rounded-pill fw-bold px-3">
                                        <i class="bi bi-shield-check me-1"></i> Activar
                                    </button>
                                <?php endif; ?>
                            </form>
                            
                        </div>

                    <!-- PESTAÑA 3: NOTIFICACIONES -->
                    <div class="tab-pane fade <?= ($seccion_activa == 'notificaciones') ? 'show active' : '' ?>"
                        id="notificaciones">
                        <p class="premium-muted small mb-4">
                            <?= t('ajustes_notif_desc') ?>
                        </p>
                        <form action="ajustes.php?seccion=notificaciones" method="POST">

                            <div class="d-flex align-items-start justify-content-between p-3 mb-3 rounded-3"
                                style="border:1px solid var(--border-color);">
                                <div class="me-3">
                                    <p class="fw-bold premium-text mb-1">
                                        <i class="bi bi-megaphone text-primary me-2"></i>
                                        <?= t('ajustes_notif_promos') ?>
                                    </p>
                                    <p class="premium-muted small mb-0">
                                        <?= t('ajustes_notif_promos_text') ?>
                                    </p>
                                </div>
                                <div class="form-check form-switch flex-shrink-0 ms-2 mt-1">
                                    <input class="form-check-input" type="checkbox" id="notif_promos"
                                        name="notif_promos" role="switch" <?= ($usuario['notif_promos'] == 1) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="d-flex align-items-start justify-content-between p-3 mb-4 rounded-3"
                                style="border:1px solid var(--border-color);">
                                <div class="me-3">
                                    <p class="fw-bold premium-text mb-1">
                                        <i class="bi bi-box-seam text-primary me-2"></i>
                                        <?= t('ajustes_notif_pedidos') ?>
                                    </p>
                                    <p class="premium-muted small mb-0">
                                        <?= t('ajustes_notif_pedidos_text') ?>
                                    </p>
                                </div>
                                <div class="form-check form-switch flex-shrink-0 ms-2 mt-1">
                                    <input class="form-check-input" type="checkbox" id="notif_pedidos"
                                        name="notif_pedidos" role="switch" <?= ($usuario['notif_pedidos'] == 1) ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <button type="submit" name="guardar_notificaciones"
                                class="btn btn-primary rounded-pill px-4 fw-bold">
                                <i class="bi bi-check-lg me-1"></i>
                                <?= t('ajustes_guardar_notif') ?>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <footer class="text-center py-4 mt-auto" style="border-top:1px solid var(--border-color);">
        <p class="mb-0 premium-muted small fw-bold">
            <i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy;
            <?= date('Y') ?>
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="tema.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const alerta = document.querySelector('.alert');
            if (alerta) setTimeout(() => new bootstrap.Alert(alerta).close(), 3000);

            const passNueva = document.getElementById('pass_nueva');
            const passConfirma = document.getElementById('pass_confirma');
            const feedback = document.getElementById('pass-match-feedback');
            const btnCambiar = document.getElementById('btn-cambiar-pass');
            const txtOk = '<i class="bi bi-check-circle-fill text-success me-1"></i><span class="text-success"><?= t('ajustes_pass_coinciden') ?></span>';
            const txtErr = '<i class="bi bi-x-circle-fill text-danger me-1"></i><span class="text-danger"><?= t('ajustes_pass_no_coinciden') ?></span>';

            if (passNueva && passConfirma) {
                function checkPass() {
                    if (!passConfirma.value) { feedback.classList.add('d-none'); btnCambiar.disabled = false; return; }
                    feedback.classList.remove('d-none');
                    if (passNueva.value === passConfirma.value) { feedback.innerHTML = txtOk; btnCambiar.disabled = false; }
                    else { feedback.innerHTML = txtErr; btnCambiar.disabled = true; }
                }
                passNueva.addEventListener('input', checkPass);
                passConfirma.addEventListener('input', checkPass);
            }
        });
    </script>
</body>

</html>