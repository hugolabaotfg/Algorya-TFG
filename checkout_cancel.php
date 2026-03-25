<?php
// =============================================================================
// ALGORYA - checkout_cancel.php
// Stripe redirige aquí si el usuario pulsa "Volver" o cierra la ventana de pago.
// NO borramos el carrito — el usuario puede intentarlo de nuevo.
// =============================================================================

session_start();
require 'includes/lang.php';
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('Pago cancelado') ?> | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card premium-card border-0 rounded-4 shadow-sm text-center p-5">

                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width:90px;height:90px;background:rgba(245,158,11,0.15);">
                            <i class="bi bi-x-circle-fill text-warning" style="font-size:3rem;"></i>
                        </div>
                    </div>

                    <h2 class="fw-bold premium-text mb-2"><?= t('Pago cancelado') ?></h2>
                    <p class="premium-muted mb-4">
                       <?= t('No te preocupes, no se ha realizado ningún cargo') ?>.<br>
                       <?= t('Tus productos siguen en el carrito esperándote.') ?>
                    </p>

                    <a href="carrito.php" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold mb-2"
                        style="background: linear-gradient(135deg,#3b82f6,#6366f1); border:none;">
                        <i class="bi bi-cart3 me-2"></i><?= t('Volver al carrito') ?>
                    </a>

                    <a href="index.php" class="btn btn-outline-secondary rounded-pill w-100 fw-semibold">
                        <i class="bi bi-bag me-2"></i><?= t('Seguir comprando') ?>
                    </a>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>