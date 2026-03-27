<?php
session_start();
require 'includes/lang.php';
require 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$mensaje     = '';
$tipo_alerta = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre           = trim($_POST['nombre']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (strlen($nombre) < 2 || strlen($nombre) > 50) {
        // Claves correctas del diccionario, NO texto libre
        $mensaje     = t('error_generico');
        $tipo_alerta = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje     = t('error_generico');
        $tipo_alerta = "danger";

    } elseif ($password !== $password_confirm) {
        $mensaje     = t('registro_pass_error');
        $tipo_alerta = "danger";

    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
        $mensaje     = t('registro_pass_hint');
        $tipo_alerta = "warning";

    } else {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensaje     = t('error_generico');
            $tipo_alerta = "warning";
            $stmt_check->close();
        } else {
            $stmt_check->close();

            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $token           = bin2hex(random_bytes(16));

            $stmt_insert = $conn->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol, verificado, token_verificacion)
                 VALUES (?, ?, ?, 'cliente', 0, ?)"
            );
            $stmt_insert->bind_param("ssss", $nombre, $email, $password_hashed, $token);

            if ($stmt_insert->execute()) {
                // EMAIL de verificación via Brevo (PHPMailer)
                require_once __DIR__ . '/includes/mailer.php';
                mail_verificacion($email, $nombre, $token);

                $mensaje     = t('exito_generico');
                $tipo_alerta = "success";
            } else {
                $mensaje     = t('error_generico');
                $tipo_alerta = "danger";
            }
            $stmt_insert->close();
        }
    }
}
?>
<!DOCTYPE html>
<!-- CORRECCIÓN: la etiqueta <html> no se cierra sola con </html> en la misma línea -->
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('registro_titulo') ?> | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex align-items-center py-5" style="min-height:100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card premium-card border-0 rounded-4 shadow-sm">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
                        <h2 class="fw-bold mt-2 premium-text"><?= t('registro_titulo') ?></h2>
                        <p class="premium-muted"><?= t('registro_subtitulo') ?></p>
                    </div>

                    <?php if ($mensaje != ''): ?>
                    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show border-0 rounded-3" role="alert">
                        <i class="bi bi-<?= $tipo_alerta === 'success' ? 'check-circle' : ($tipo_alerta === 'warning' ? 'exclamation-triangle' : 'x-circle') ?>-fill me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="registro.php" method="POST" id="form-registro" novalidate>

                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold premium-text"><?= t('registro_nombre') ?></label>
                            <input type="text" class="form-control premium-input shadow-none py-2"
                                   id="nombre" name="nombre" placeholder="Ej. Juan Pérez" maxlength="50"
                                   value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold premium-text"><?= t('registro_email') ?></label>
                            <input type="email" class="form-control premium-input shadow-none py-2"
                                   id="email" name="email" placeholder="tu@correo.com"
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold premium-text"><?= t('registro_pass') ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control premium-input shadow-none py-2"
                                       id="password" name="password" placeholder="<?= t('registro_pass_hint') ?>" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-pass1">
                                    <i class="bi bi-eye" id="eye1"></i>
                                </button>
                            </div>
                            <div id="caps-warning" class="text-warning small fw-bold d-none mt-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i><?= t('registro_caps_aviso') ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label fw-bold premium-text"><?= t('registro_pass_confirm') ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control premium-input shadow-none py-2"
                                       id="password_confirm" name="password_confirm"
                                       placeholder="<?= t('registro_pass_repite') ?>" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-pass2">
                                    <i class="bi bi-eye" id="eye2"></i>
                                </button>
                            </div>
                            <div id="match-feedback" class="small mt-1 d-none"></div>
                        </div>

                        <button type="submit" id="btn-registro"
                                class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm"
                                style="background-color:#3b82f6;border:none;">
                            <i class="bi bi-person-check me-2"></i><?= t('registro_btn') ?>
                        </button>

                    </form>

                    <div class="text-center mt-4">
                        <a href="login.php" class="text-primary text-decoration-none fw-bold">
                            <?= t('registro_ya_cuenta') ?>
                        </a><br>
                        <a href="index.php" class="text-decoration-none premium-muted small mt-2 d-inline-block">
                            <i class="bi bi-arrow-left"></i> <?= t('registro_volver') ?>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const pass1      = document.getElementById('password');
    const pass2      = document.getElementById('password_confirm');
    const capsWarn   = document.getElementById('caps-warning');
    const feedback   = document.getElementById('match-feedback');
    const btnReg     = document.getElementById('btn-registro');
    // Textos de feedback desde PHP para que también sean traducibles
    const txtOk      = '<i class="bi bi-check-circle-fill text-success me-1"></i><span class="text-success"><?= t('registro_pass_ok') ?></span>';
    const txtErr     = '<i class="bi bi-x-circle-fill text-danger me-1"></i><span class="text-danger"><?= t('registro_pass_error') ?></span>';

    pass1.addEventListener('keyup', e => capsWarn.classList.toggle('d-none', !e.getModifierState('CapsLock')));

    function checkPasswords() {
        if (!pass2.value) { feedback.classList.add('d-none'); btnReg.disabled = false; return; }
        feedback.classList.remove('d-none');
        if (pass1.value === pass2.value) { feedback.innerHTML = txtOk;  btnReg.disabled = false; }
        else                             { feedback.innerHTML = txtErr; btnReg.disabled = true;  }
    }
    pass1.addEventListener('input', checkPasswords);
    pass2.addEventListener('input', checkPasswords);

    document.getElementById('form-registro').addEventListener('submit', function (e) {
        if (pass1.value !== pass2.value) { e.preventDefault(); feedback.classList.remove('d-none'); feedback.innerHTML = txtErr; pass2.focus(); }
    });

    function togglePwd(inputId, eyeId) {
        const i = document.getElementById(inputId), e = document.getElementById(eyeId);
        i.type = i.type === 'password' ? 'text' : 'password';
        e.classList.toggle('bi-eye'); e.classList.toggle('bi-eye-slash');
    }
    document.getElementById('toggle-pass1').addEventListener('click', () => togglePwd('password', 'eye1'));
    document.getElementById('toggle-pass2').addEventListener('click', () => togglePwd('password_confirm', 'eye2'));
});
</script>
</body>
</html>