<?php
session_start();
require 'includes/lang.php';
require 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['rol'] === 'admin' ? 'admin_pedidos.php' : 'index.php'));
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $viene_del_modal = isset($_POST['redirect']) && $_POST['redirect'] === 'index.php';

    $stmt = $conn->prepare("SELECT id, nombre, password, rol, verificado FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if (password_verify($password, $usuario['password'])) {
            if ($usuario['verificado'] == 0) {
                // t() correcto: dentro de PHP, usando la clave del diccionario
                $error = t('modal_login_error');
            } else {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre']  = $usuario['nombre'];
                $_SESSION['rol']     = $usuario['rol'];

                // ─────────────────────────────────────────────────────────
                // FUSIÓN DE CARRITO: si el visitante tenía productos en
                // el carrito de sesión antes de iniciar sesión, los
                // incorporamos al carrito de BD sin perder los que ya
                // tuviera guardados. Si el producto ya existe en BD,
                // sumamos las cantidades.
                // ─────────────────────────────────────────────────────────
                if (!empty($_SESSION['carrito'])) {
                    $uid_login = (int)$_SESSION['user_id'];
                    $stmt_check_cart = $conn->prepare(
                        "SELECT id, cantidad FROM carritos WHERE usuario_id = ? AND producto_id = ?"
                    );
                    $stmt_update_cart = $conn->prepare(
                        "UPDATE carritos SET cantidad = cantidad + ? WHERE usuario_id = ? AND producto_id = ?"
                    );
                    $stmt_insert_cart = $conn->prepare(
                        "INSERT INTO carritos (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)"
                    );

                    foreach ($_SESSION['carrito'] as $item) {
                        $prod_id  = (int)$item['id'];
                        $cantidad = (int)$item['cantidad'];

                        $stmt_check_cart->bind_param("ii", $uid_login, $prod_id);
                        $stmt_check_cart->execute();
                        $stmt_check_cart->store_result();

                        if ($stmt_check_cart->num_rows > 0) {
                            // Ya existe en BD → sumar cantidades
                            $stmt_update_cart->bind_param("iii", $cantidad, $uid_login, $prod_id);
                            $stmt_update_cart->execute();
                        } else {
                            // No existe → insertar
                            $stmt_insert_cart->bind_param("iii", $uid_login, $prod_id, $cantidad);
                            $stmt_insert_cart->execute();
                        }
                        $stmt_check_cart->free_result();
                    }

                    $stmt_check_cart->close();
                    $stmt_update_cart->close();
                    $stmt_insert_cart->close();

                    // Vaciar el carrito de sesión — ya está en la BD
                    unset($_SESSION['carrito']);
                }
                $stmt->close();
                header("Location: " . ($_SESSION['rol'] === 'admin' ? 'admin_pedidos.php' : 'index.php'));
                exit();
            }
        } else {
            $error = t('modal_login_error');
        }
    } else {
        $error = t('modal_login_error');
    }

    $stmt->close();

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
    <title><?= t('login_titulo') ?> | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card premium-card border-0 rounded-4 shadow-sm">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock-fill fs-1 text-primary"></i>
                        <h2 class="fw-bold mt-2 premium-text"><?= t('login_titulo') ?></h2>
                        <p class="premium-muted"><?= t('login_subtitulo') ?></p>
                    </div>

                    <?php if ($error != ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold premium-text">
                                <?= t('login_email') ?>
                            </label>
                            <input type="email" class="form-control premium-input shadow-none py-2"
                                   id="email" name="email" placeholder="tu@correo.com" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold premium-text">
                                <?= t('login_password') ?>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control premium-input shadow-none py-2"
                                       id="password" name="password" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        id="toggle-password" title="<?= t('modal_login_mostrar') ?>">
                                    <i class="bi bi-eye" id="eye-icon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" name="login"
                                class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm"
                                style="background-color:#3b82f6;border:none;">
                            <i class="bi bi-box-arrow-in-right me-2"></i><?= t('login_btn') ?>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="registro.php" class="text-primary text-decoration-none fw-bold">
                            <?= t('login_sin_cuenta') ?>
                        </a><br>
                        <a href="index.php" class="text-decoration-none premium-muted small mt-2 d-inline-block">
                            <i class="bi bi-arrow-left"></i> <?= t('login_volver') ?>
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
document.getElementById('toggle-password').addEventListener('click', function () {
    const input   = document.getElementById('password');
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