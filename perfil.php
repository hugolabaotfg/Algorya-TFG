<?php
session_start();
require 'includes/db.php';
require 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];

$stmt_user = $conn->prepare("SELECT nombre, email, fecha_registro FROM usuarios WHERE id = ?");
$stmt_user->bind_param("i", $uid);
$stmt_user->execute();
$usuario = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$stmt_pedidos = $conn->prepare(
    "SELECT id, total, fecha, metodo_pago FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC"
);
$stmt_pedidos->bind_param("i", $uid);
$stmt_pedidos->execute();
$res_pedidos = $stmt_pedidos->get_result();
$stmt_pedidos->close();

$inicial_avatar = strtoupper(mb_substr($usuario['nombre'], 0, 1, 'UTF-8'));
$anio_registro = date('Y', strtotime($usuario['fecha_registro']));
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= t('perfil_titulo') ?> | Algorya
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
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
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>
                    <?= t('perfil_volver') ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5 flex-grow-1">

        <!-- CABECERA DEL PERFIL -->
        <div class="card premium-card border-0 rounded-4 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-4 p-md-5"
                style="background: linear-gradient(135deg, var(--card-bg) 0%, rgba(59,130,246,0.08) 100%);">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-black fs-2 shadow-sm"
                            style="width:72px;height:72px;background:linear-gradient(135deg,#3b82f6,#6366f1);color:white;">
                            <?= $inicial_avatar ?>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="m-0 fw-bold premium-text">
                            Hola,
                            <?= htmlspecialchars($usuario['nombre']) ?>
                        </h2>
                        <p class="m-0 premium-muted mt-1">
                            <i class="bi bi-envelope me-1"></i>
                            <?= htmlspecialchars($usuario['email']) ?>
                        </p>
                    </div>
                    <div class="col-auto d-none d-md-block text-end">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold"
                            style="background:rgba(59,130,246,0.12);color:var(--text-main);font-size:.8rem;">
                            <i class="bi bi-calendar-check me-1 text-primary"></i>
                            <?= t('perfil_miembro_desde') ?>
                            <?= $anio_registro ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- COLUMNA IZQUIERDA: Ajustes -->
            <div class="col-md-4">
                <div class="card premium-card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-body p-4">

                        <h5 class="fw-bold premium-text mb-3">
                            <i class="bi bi-gear text-primary me-2"></i>
                            <?= t('perfil_ajustes') ?>
                        </h5>

                        <div class="d-flex flex-column gap-1">
                            <a href="ajustes.php?seccion=datos"
                                class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text fw-medium"
                                style="transition:.15s;border:1px solid transparent;"
                                onmouseover="this.style.borderColor='var(--border-color)';this.style.background='var(--hover-bg)';"
                                onmouseout="this.style.borderColor='transparent';this.style.background='transparent';">
                                <i class="bi bi-person-circle text-primary fs-5"></i>
                                <span>
                                    <?= t('perfil_datos') ?>
                                </span>
                                <i class="bi bi-chevron-right ms-auto premium-muted small"></i>
                            </a>
                            <a href="ajustes.php?seccion=seguridad"
                                class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text fw-medium"
                                style="transition:.15s;border:1px solid transparent;"
                                onmouseover="this.style.borderColor='var(--border-color)';this.style.background='var(--hover-bg)';"
                                onmouseout="this.style.borderColor='transparent';this.style.background='transparent';">
                                <i class="bi bi-shield-lock text-primary fs-5"></i>
                                <span>
                                    <?= t('perfil_seguridad') ?>
                                </span>
                                <i class="bi bi-chevron-right ms-auto premium-muted small"></i>
                            </a>
                            <a href="ajustes.php?seccion=notificaciones"
                                class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none premium-text fw-medium"
                                style="transition:.15s;border:1px solid transparent;"
                                onmouseover="this.style.borderColor='var(--border-color)';this.style.background='var(--hover-bg)';"
                                onmouseout="this.style.borderColor='transparent';this.style.background='transparent';">
                                <i class="bi bi-bell text-primary fs-5"></i>
                                <span>
                                    <?= t('perfil_notificaciones') ?>
                                </span>
                                <i class="bi bi-chevron-right ms-auto premium-muted small"></i>
                            </a>
                        </div>

                        <div class="mt-4 pt-3" style="border-top:1px solid var(--border-color);">
                            <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill fw-bold">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                <?= t('perfil_cerrar_sesion') ?>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Historial de pedidos -->
            <div class="col-md-8">
                <div class="card premium-card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-body p-4">

                        <h5 class="fw-bold premium-text mb-4">
                            <i class="bi bi-box-seam text-primary me-2"></i>
                            <?= t('perfil_historial') ?>
                        </h5>

                        <?php if ($res_pedidos && $res_pedidos->num_rows > 0): ?>

                            <div class="table-responsive">
                                <table class="table align-middle mb-0"
                                    style="--bs-table-bg:transparent;--bs-table-color:var(--text-main);">
                                    <thead>
                                        <tr style="border-bottom:2px solid var(--border-color);">
                                            <th class="premium-muted fw-semibold text-uppercase pb-3"
                                                style="font-size:.72rem;letter-spacing:.5px;border:none;">
                                                <?= t('perfil_col_pedido') ?>
                                            </th>
                                            <th class="premium-muted fw-semibold text-uppercase pb-3"
                                                style="font-size:.72rem;letter-spacing:.5px;border:none;">
                                                <?= t('perfil_col_fecha') ?>
                                            </th>
                                            <th class="premium-muted fw-semibold text-uppercase pb-3"
                                                style="font-size:.72rem;letter-spacing:.5px;border:none;">
                                                <?= t('perfil_col_metodo') ?>
                                            </th>
                                            <th class="premium-muted fw-semibold text-uppercase pb-3 text-end"
                                                style="font-size:.72rem;letter-spacing:.5px;border:none;">
                                                <?= t('perfil_col_total') ?>
                                            </th>
                                            <th class="premium-muted fw-semibold text-uppercase pb-3 text-center"
                                                style="font-size:.72rem;letter-spacing:.5px;border:none;">
                                                <?= t('perfil_col_estado') ?>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pedido = $res_pedidos->fetch_assoc()): ?>
                                            <tr style="border-bottom:1px solid var(--border-color);">
                                                <td class="fw-bold premium-text py-3">
                                                    #
                                                    <?= str_pad($pedido['id'], 5, "0", STR_PAD_LEFT) ?>
                                                </td>
                                                <td class="premium-muted py-3">
                                                    <?= date('d/m/Y', strtotime($pedido['fecha'])) ?>
                                                </td>
                                                <td class="py-3" style="font-size:.85rem;">
                                                    <span class="premium-muted">
                                                        <i class="bi bi-credit-card me-1 text-primary"></i>
                                                        <?= htmlspecialchars($pedido['metodo_pago']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold text-success py-3">
                                                    <?= number_format($pedido['total'], 2) ?> €
                                                </td>
                                                <td class="text-center py-3">
                                                    <span class="badge rounded-pill px-3 py-2 fw-semibold"
                                                        style="background:rgba(34,197,94,0.12);color:#16a34a;border:1px solid rgba(34,197,94,0.25);font-size:.75rem;">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        <?= t('perfil_estado_procesado') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php else: ?>

                            <div class="text-center py-5">
                                <i class="bi bi-cart-x fs-1 premium-muted d-block mb-3"></i>
                                <h6 class="fw-bold premium-text">
                                    <?= t('perfil_sin_compras') ?>
                                </h6>
                                <p class="premium-muted small mb-4">
                                    <?= t('perfil_sin_compras_texto') ?>
                                </p>
                                <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                    <i class="bi bi-bag me-1"></i>
                                    <?= t('perfil_ir_compras') ?>
                                </a>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="text-center py-4 mt-auto" style="border-top:1px solid var(--border-color);">
        <p class="mb-0 premium-muted small fw-bold">
            <i class="bi bi-box-seam-fill text-primary"></i>
            Algorya &copy;
            <?= date('Y') ?> —
            <?= t('pie_autor') ?>
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="tema.js"></script>
</body>

</html>