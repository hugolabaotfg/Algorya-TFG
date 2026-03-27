<?php
// =============================================================================
// ALGORYA - admin_mailing.php
// Envío de comunicaciones a clientes desde el panel de administración.
//
// NOTA: El envío real por mail() solo funciona en producción con SPF/DKIM.
// En entorno local el formulario valida y procesa correctamente pero el
// email puede no llegar al destinatario por la configuración del servidor.
// =============================================================================

session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); exit();
}

$pagina_actual_admin = basename($_SERVER['PHP_SELF']);
$nav_items = [
    ['admin_pedidos.php',      'bi-receipt',        'Pedidos'],
    ['admin_usuarios.php',     'bi-people',         'Clientes'],
    ['admin_estadisticas.php', 'bi-bar-chart-fill', 'Dashboard'],
    ['admin_mailing.php',      'bi-envelope-at',    'Mailing'],
];

$mensaje     = '';
$tipo_alerta = '';

// ─────────────────────────────────────────────────────────────────────────────
// PROCESAR ENVÍO DE EMAIL
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $dest   = trim($_POST['dest']   ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $msg    = trim($_POST['msg']    ?? '');
    $nombre_dest = trim($_POST['nombre_dest'] ?? '');

    if (empty($dest) || empty($asunto) || empty($msg)) {
        $mensaje     = "Todos los campos son obligatorios.";
        $tipo_alerta = "warning";
    } elseif (!filter_var($dest, FILTER_VALIDATE_EMAIL) && $dest !== 'todos') {
        $mensaje     = "Dirección de email no válida.";
        $tipo_alerta = "danger";
    } else {
        $enviados = 0;
        $errores  = 0;

        if ($dest === 'todos') {
            $res = $conn->query("SELECT email, nombre FROM usuarios WHERE rol = 'cliente'");
            $destinatarios = $res->fetch_all(MYSQLI_ASSOC);
        } else {
            $destinatarios = [['email' => $dest, 'nombre' => $nombre_dest]];
        }

        require_once __DIR__ . '/includes/mailer.php';

        foreach ($destinatarios as $d) {
            if (algorya_mail($d['email'], $d['nombre'], $asunto, $msg)) {
                $enviados++;
            } else {
                $errores++;
            }
        }

        if ($dest === 'todos') {
            $mensaje = "Campaña enviada: {$enviados} emails enviados" . ($errores > 0 ? ", {$errores} errores." : ".");
        } else {
            $mensaje = "Email enviado correctamente a " . htmlspecialchars($dest) . ".";
        }
        $tipo_alerta = $errores > 0 ? "warning" : "success";
    }
}

// Lista de usuarios para el select
$usuarios = $conn->query("SELECT email, nombre FROM usuarios WHERE rol = 'cliente' ORDER BY nombre ASC");
$lista_usuarios = $usuarios->fetch_all(MYSQLI_ASSOC);

// Estadísticas rápidas para el sidebar
$total_clientes   = count($lista_usuarios);
$clientes_notif   = (int)$conn->query("SELECT COUNT(*) as t FROM usuarios WHERE notif_promos=1 AND rol='cliente'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailing | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- NAVBAR -->
<nav class="navbar sticky-top shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-black text-decoration-none d-flex align-items-center gap-2" href="admin_estadisticas.php">
            <div class="rounded-2 d-flex align-items-center justify-content-center"
                 style="width:28px;height:28px;background:linear-gradient(135deg,#3b82f6,#6366f1);">
                <i class="bi bi-box-seam-fill text-white" style="font-size:.75rem;"></i>
            </div>
            <span class="text-primary" style="font-size:1.1rem;letter-spacing:-.04em;">Algorya</span>
            <span class="premium-muted" style="font-size:.65rem;font-weight:500;letter-spacing:.04em;text-transform:uppercase;margin-left:-2px;">Admin</span>
        </a>
        <div class="d-none d-lg-flex align-items-center gap-1">
            <?php foreach ($nav_items as [$href, $icon, $label]): ?>
            <a href="<?= $href ?>" class="admin-nav-link <?= $pagina_actual_admin === $href ? 'active' : '' ?>">
                <i class="bi <?= $icon ?>"></i><?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-1 fw-semibold" style="font-size:.78rem;">
                <i class="bi bi-arrow-left"></i>Tienda
            </a>
            <div id="darkModeToggle" title="Alternar modo oscuro">
                <i class="bi bi-moon-stars-fill" style="font-size:.9rem;"></i>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm rounded-circle border-0 p-0 d-flex align-items-center justify-content-center fw-black"
                        style="width:34px;height:34px;background:linear-gradient(135deg,#3b82f6,#6366f1);color:white;font-family:'Outfit',sans-serif;font-size:.85rem;"
                        data-bs-toggle="dropdown">
                    <?= strtoupper(mb_substr($_SESSION['nombre'] ?? 'A', 0, 1, 'UTF-8')) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:160px;">
                    <li><div class="px-3 py-2">
                        <div class="premium-text fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Admin') ?></div>
                        <div class="premium-muted" style="font-size:.72rem;">Administrador</div>
                    </div></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-semibold" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 mt-4 pb-5 flex-grow-1">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-black premium-text mb-1" style="font-size:1.6rem;">
                Mailing <span class="text-primary">·</span> Comunicaciones
            </h2>
            <p class="premium-muted mb-0" style="font-size:.82rem;">
                Envía emails individuales o campañas a toda la base de clientes
            </p>
        </div>
        <a href="admin_estadisticas.php" class="btn btn-outline-primary rounded-pill px-3 fw-bold" style="font-size:.82rem;">
            <i class="bi bi-bar-chart me-1"></i>Dashboard
        </a>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show border-0 rounded-3 mb-4">
        <i class="bi bi-<?= $tipo_alerta==='success'?'check-circle':'exclamation-triangle' ?>-fill me-2"></i>
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- COLUMNA IZQUIERDA: Formulario -->
        <div class="col-lg-8">
            <div class="premium-card admin-card rounded-4 p-4">

                <h5 class="fw-bold premium-text mb-4" style="font-size:.95rem;">
                    <i class="bi bi-send text-primary me-2"></i>Redactar mensaje
                </h5>

                <form action="admin_mailing.php" method="POST">

                    <!-- Destinatario -->
                    <div class="mb-3">
                        <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">
                            Destinatario
                        </label>
                        <select name="dest" id="select-dest" class="form-select premium-input shadow-none" required
                                onchange="actualizarNombre(this)">
                            <option value="">Seleccionar destinatario...</option>
                            <option value="todos" style="font-weight:700;">
                                📢 Todos los clientes (<?= $total_clientes ?> contactos)
                            </option>
                            <optgroup label="── Clientes individuales ──────────────────">
                            <?php foreach ($lista_usuarios as $u): ?>
                            <option value="<?= htmlspecialchars($u['email']) ?>"
                                    data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                <?= htmlspecialchars($u['nombre']) ?> · <?= htmlspecialchars($u['email']) ?>
                            </option>
                            <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <input type="hidden" name="nombre_dest" id="nombre-dest" value="">
                    </div>

                    <!-- Asunto -->
                    <div class="mb-3">
                        <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">
                            Asunto del email
                        </label>
                        <input type="text" name="asunto" class="form-control premium-input shadow-none"
                               placeholder="Ej: Novedades exclusivas de Algorya" required>
                    </div>

                    <!-- Mensaje -->
                    <div class="mb-4">
                        <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">
                            Cuerpo del mensaje
                        </label>
                        <textarea name="msg" class="form-control premium-input shadow-none"
                                  rows="8" placeholder="Escribe aquí el contenido del email..."
                                  required id="textarea-msg"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div class="premium-muted" style="font-size:.72rem;">
                                El saludo «Hola, [nombre]» y la firma de Algorya se añaden automáticamente.
                            </div>
                            <div class="premium-muted" id="char-count" style="font-size:.72rem;">0 caracteres</div>
                        </div>
                    </div>

                    <!-- Aviso sandbox -->
                    <div class="alert border-0 rounded-3 mb-4 d-flex gap-2"
                         style="background:rgba(245,158,11,.08);color:var(--text-main);">
                        <i class="bi bi-info-circle-fill text-warning mt-1 flex-shrink-0"></i>
                        <div style="font-size:.82rem;">
                            <strong>Entorno local:</strong> El email se procesará correctamente pero puede no llegar al destinatario hasta que el servidor esté en producción con SPF/DKIM configurado.
                        </div>
                    </div>

                    <button type="submit" name="enviar"
                            class="btn btn-primary w-100 rounded-pill py-2 fw-bold"
                            style="font-family:'Outfit',sans-serif;font-size:.95rem;">
                        <i class="bi bi-send-fill me-2"></i>Enviar comunicación
                    </button>
                </form>
            </div>
        </div>

        <!-- COLUMNA DERECHA: Info y plantillas -->
        <div class="col-lg-4 d-flex flex-column gap-3">

            <!-- Stats de la base de clientes -->
            <div class="premium-card admin-card rounded-4 p-4">
                <h6 class="fw-bold premium-text mb-3" style="font-size:.85rem;">
                    <i class="bi bi-people-fill text-primary me-2"></i>Base de clientes
                </h6>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-3"
                         style="background:var(--hover-bg);">
                        <span class="premium-muted" style="font-size:.82rem;">Total clientes</span>
                        <span class="fw-bold premium-text"><?= $total_clientes ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-3"
                         style="background:var(--hover-bg);">
                        <span class="premium-muted" style="font-size:.82rem;">Aceptan promos</span>
                        <span class="fw-bold text-success"><?= $clientes_notif ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded-3"
                         style="background:var(--hover-bg);">
                        <span class="premium-muted" style="font-size:.82rem;">No aceptan promos</span>
                        <span class="fw-bold text-warning"><?= $total_clientes - $clientes_notif ?></span>
                    </div>
                </div>
            </div>

            <!-- Plantillas rápidas -->
            <div class="premium-card admin-card rounded-4 p-4">
                <h6 class="fw-bold premium-text mb-3" style="font-size:.85rem;">
                    <i class="bi bi-lightning-fill text-warning me-2"></i>Plantillas rápidas
                </h6>
                <div class="d-flex flex-column gap-2">
                    <?php
                    $plantillas = [
                        ['🎁 Oferta especial', 'Tenemos una oferta exclusiva preparada para ti. Visita nuestro catálogo y descubre los productos más tendencia de hoy a precios increíbles. ¡No te lo pierdas!'],
                        ['📦 Nuevo catálogo', 'Hemos actualizado nuestro catálogo con los productos más virales del momento. Nuestro algoritmo ha seleccionado las mejores tendencias para ti. ¡Descúbrelos ahora en Algorya!'],
                        ['⭐ Solicitud de reseña', '¿Has recibido tu pedido? Nos encantaría conocer tu experiencia. Tu opinión nos ayuda a mejorar y a que otros clientes encuentren los mejores productos.'],
                    ];
                    foreach ($plantillas as [$titulo, $texto]):
                    ?>
                    <button type="button"
                            class="btn btn-sm rounded-3 text-start fw-semibold premium-text"
                            style="background:var(--hover-bg);border:1px solid var(--border-color);font-size:.8rem;padding:.5rem .75rem;"
                            onclick="aplicarPlantilla(<?= json_encode($texto) ?>)">
                        <?= $titulo ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="premium-muted mt-2 mb-0" style="font-size:.72rem;">
                    Haz clic para rellenar el cuerpo del mensaje con la plantilla.
                </p>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
<script>
// Actualizar nombre oculto al seleccionar destinatario
function actualizarNombre(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('nombre-dest').value = opt.dataset.nombre || '';
}

// Contador de caracteres
const textarea = document.getElementById('textarea-msg');
const counter  = document.getElementById('char-count');
textarea.addEventListener('input', () => {
    counter.textContent = textarea.value.length + ' caracteres';
});

// Aplicar plantilla
function aplicarPlantilla(texto) {
    textarea.value = texto;
    counter.textContent = texto.length + ' caracteres';
    textarea.focus();
}

// Cerrar alertas automáticamente
const alerta = document.querySelector('.alert-success, .alert-warning');
if (alerta) setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerta).close(), 4000);
</script>
</body>
</html>