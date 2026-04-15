<?php
// =============================================================================
// ALGORYA - admin_pedidos.php
// Gestión completa de pedidos.
//
// EMAIL AL CAMBIAR ESTADO:
//   Función preparada — descomentar la llamada a enviar_email_estado() cuando
//   el servidor esté en producción con SPF/DKIM configurado.
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

// =============================================================================
// FUNCIÓN: Enviar email al cliente cuando cambia el estado de su pedido
// ESTADO: Preparada. Descomentar la llamada en el bloque POST cuando
//         el servidor esté en producción con email configurado.
// =============================================================================
function enviar_email_estado(string $email, string $nombre, int $pedido_id, string $estado): void {
    $asunto = "[Algorya] Actualización de tu pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT);

    $icono = match($estado) {
        'Enviado'   => '🚚',
        'Entregado' => '✅',
        'Cancelado' => '❌',
        default     => '📦',
    };

    $msg_estado = match($estado) {
        'Enviado'   => "Tu pedido ya está en camino. Recibirás tu paquete en los próximos días.",
        'Entregado' => "Tu pedido ha sido entregado. ¡Esperamos que lo disfrutes!",
        'Cancelado' => "Tu pedido ha sido cancelado. Contacta con nosotros si tienes dudas.",
        default     => "Tu pedido está siendo procesado.",
    };

    $cuerpo  = "Hola {$nombre},\n\n";
    $cuerpo .= "{$icono} El estado de tu pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT) . " ha cambiado a: {$estado}\n\n";
    $cuerpo .= "{$msg_estado}\n\n";
    $cuerpo .= "Puedes ver el estado actualizado en tu perfil:\n";
    $cuerpo .= "https://algorya.store/perfil.php\n\n";
    $cuerpo .= "Gracias por confiar en Algorya.\nEl equipo de Algorya\nhola@algorya.store";

    $headers = "From: noreply@algorya.store\r\nReply-To: hola@algorya.store\r\nX-Mailer: PHP/" . phpversion();
    @mail($email, $asunto, $cuerpo, $headers);
}

$mensaje     = '';
$tipo_alerta = '';

// =============================================================================
// ACCIÓN: Cambiar estado de pedido
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id    = (int)$_POST['pedido_id'];
    $nuevo_estado = $_POST['estado'] ?? '';
    $estados_validos = ['Pendiente', 'Enviado', 'Entregado', 'Cancelado'];

    if (in_array($nuevo_estado, $estados_validos) && $pedido_id > 0) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);

        if ($stmt->execute()) {
            $mensaje     = "Pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT) . " actualizado a «{$nuevo_estado}».";
            $tipo_alerta = "success";

            // EMAIL AL CLIENTE — Activo via Brevo (PHPMailer)
            $stmt_mail = $conn->prepare(
                "SELECT u.email, u.nombre FROM pedidos p JOIN usuarios u ON p.usuario_id=u.id WHERE p.id=?"
            );
            $stmt_mail->bind_param("i", $pedido_id);
            $stmt_mail->execute();
            $mail_data = $stmt_mail->get_result()->fetch_assoc();
            $stmt_mail->close();
            if ($mail_data) {
                require_once __DIR__ . '/includes/mailer.php';
                mail_estado_pedido($mail_data['email'], $mail_data['nombre'], $pedido_id, $nuevo_estado);
            }
        } else {
            $mensaje = "Error al actualizar."; $tipo_alerta = "danger";
        }
        $stmt->close();
    }
}

// =============================================================================
// FILTROS
// =============================================================================
$f_estado  = $_GET['estado']  ?? '';
$f_desde   = $_GET['desde']   ?? '';
$f_hasta   = $_GET['hasta']   ?? '';
$f_cliente = trim($_GET['cliente'] ?? '');

$where_parts = []; $params = []; $tipos = '';

if ($f_estado  !== '') { $where_parts[] = "p.estado = ?";       $params[] = $f_estado;           $tipos .= 's'; }
if ($f_desde   !== '') { $where_parts[] = "DATE(p.fecha) >= ?"; $params[] = $f_desde;            $tipos .= 's'; }
if ($f_hasta   !== '') { $where_parts[] = "DATE(p.fecha) <= ?"; $params[] = $f_hasta;            $tipos .= 's'; }
if ($f_cliente !== '') { $where_parts[] = "u.nombre LIKE ?";    $params[] = '%'.$f_cliente.'%';  $tipos .= 's'; }

$where_sql = !empty($where_parts) ? 'WHERE '.implode(' AND ',$where_parts) : '';

// Paginación
$por_pagina    = 15;
$pagina_actual = max(1,(int)($_GET['pagina'] ?? 1));

$stmt_c = $conn->prepare("SELECT COUNT(*) as t FROM pedidos p JOIN usuarios u ON p.usuario_id=u.id $where_sql");
if (!empty($params)) $stmt_c->bind_param($tipos, ...$params);
$stmt_c->execute();
$total = $stmt_c->get_result()->fetch_assoc()['t'];
$stmt_c->close();

$total_paginas = max(1,ceil($total/$por_pagina));
if ($pagina_actual>$total_paginas) $pagina_actual=$total_paginas;
$offset = ($pagina_actual-1)*$por_pagina;

$stmt_p = $conn->prepare(
    "SELECT p.id,p.total,p.fecha,p.estado,p.metodo_pago,p.stripe_session_id,
            u.nombre as cn, u.email as ce
     FROM pedidos p JOIN usuarios u ON p.usuario_id=u.id
     $where_sql ORDER BY p.fecha DESC LIMIT $por_pagina OFFSET $offset"
);
if (!empty($params)) $stmt_p->bind_param($tipos, ...$params);
$stmt_p->execute();
$resultado = $stmt_p->get_result();
$stmt_p->close();

$filtros_url = http_build_query(array_filter(['estado'=>$f_estado,'desde'=>$f_desde,'hasta'=>$f_hasta,'cliente'=>$f_cliente]));
$base_url    = '?'.($filtros_url ? $filtros_url.'&' : '');

// Helper badge
function badge_cls(string $e): string {
    return 'badge-estado badge-'.strtolower($e);
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex flex-column min-vh-100">

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
            <div class="dropdown d-lg-none">
                <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size:.78rem;">
                    <i class="bi bi-grid-fill me-1"></i>Menú
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Panel Admin</h6></li>
                    <?php foreach ($nav_items as [$href, $icon, $label]): ?>
                    <li><a class="dropdown-item <?= $pagina_actual_admin===$href?'fw-bold':'' ?>" href="<?= $href ?>">
                        <i class="bi <?= $icon ?> me-2 text-primary"></i><?= $label ?>
                    </a></li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="add_product.php"><i class="bi bi-plus-circle me-2 text-success"></i>Nuevo producto</a></li>
                    <li><a class="dropdown-item" href="index.php"><i class="bi bi-arrow-left me-2"></i>Ver tienda</a></li>
                </ul>
            </div>
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
                    <li>
                        <div class="px-3 py-2">
                            <div class="premium-text fw-semibold" style="font-size:.85rem;"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Admin') ?></div>
                            <div class="premium-muted" style="font-size:.72rem;">Administrador</div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-semibold" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 mt-4 pb-5 flex-grow-1">

    <!-- CABECERA -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-black premium-text mb-1" style="font-size:1.6rem;">
                Pedidos <span class="text-primary">·</span> <?= $total ?> registros
            </h2>
            <p class="premium-muted mb-0" style="font-size:.82rem;">
                Haz clic en cualquier fila para ver el detalle de productos
            </p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="exportar_csv.php?tipo=pedidos" class="btn btn-success rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center" style="font-size:.85rem;">
                <i class="bi bi-file-earmark-spreadsheet me-2 fs-6"></i>Exportar Pedidos
            </a>
            <a href="admin_estadisticas.php" class="btn btn-outline-primary rounded-pill px-3 fw-bold d-flex align-items-center" style="font-size:.82rem;">
                <i class="bi bi-bar-chart me-1 fs-6"></i>Dashboard
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show border-0 rounded-3 mb-4">
        <i class="bi bi-<?= $tipo_alerta==='success'?'check-circle':'exclamation-triangle' ?>-fill me-2"></i>
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- FILTROS -->
    <form method="GET" action="admin_pedidos.php" class="mb-4">
        <div class="premium-card admin-card rounded-4 p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">
                        <i class="bi bi-search me-1"></i>Cliente
                    </label>
                    <input type="text" name="cliente" class="form-control premium-input shadow-none"
                           placeholder="Nombre del cliente..." value="<?= htmlspecialchars($f_cliente) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Estado</label>
                    <select name="estado" class="form-select premium-input shadow-none">
                        <option value="">Todos</option>
                        <?php foreach (['Pendiente','Enviado','Entregado','Cancelado'] as $e): ?>
                        <option value="<?= $e ?>" <?= $f_estado===$e?'selected':'' ?>><?= $e ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Desde</label>
                    <input type="date" name="desde" class="form-control premium-input shadow-none" value="<?= htmlspecialchars($f_desde) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Hasta</label>
                    <input type="date" name="hasta" class="form-control premium-input shadow-none" value="<?= htmlspecialchars($f_hasta) ?>">
                </div>
                <div class="col-6 col-md-3 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>
                    <?php if ($f_estado||$f_desde||$f_hasta||$f_cliente): ?>
                    <a href="admin_pedidos.php" class="btn btn-outline-secondary rounded-pill">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>

    <!-- TABLA -->
    <div class="premium-card admin-card rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-admin mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Pedido</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Cambiar estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resultado && $resultado->num_rows > 0):
                    while ($row = $resultado->fetch_assoc()):
                    $estado_actual = $row['estado'] ?? 'Pendiente';

                    // Líneas del pedido para la fila expandible
                    $stmt_l = $conn->prepare("SELECT lp.cantidad,lp.precio_unitario,p.nombre FROM lineas_pedido lp JOIN productos p ON lp.producto_id=p.id WHERE lp.pedido_id=?");
                    $stmt_l->bind_param("i", $row['id']);
                    $stmt_l->execute();
                    $lineas = $stmt_l->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt_l->close();
                ?>
                <!-- Fila principal — clic para expandir -->
                <tr data-bs-toggle="collapse" data-bs-target="#det-<?= $row['id'] ?>">
                    <td class="ps-4 fw-bold premium-text">
                        <i class="bi bi-chevron-down premium-subtle me-1" style="font-size:.65rem;transition:transform .2s;"></i>
                        #<?= str_pad($row['id'],5,'0',STR_PAD_LEFT) ?>
                    </td>
                    <td>
                        <div class="fw-semibold premium-text" style="font-size:.9rem;"><?= htmlspecialchars($row['cn']) ?></div>
                        <div class="premium-muted" style="font-size:.75rem;"><?= htmlspecialchars($row['ce']) ?></div>
                    </td>
                    <td class="premium-muted" style="font-size:.85rem;">
                        <?= date('d/m/Y', strtotime($row['fecha'])) ?>
                        <div style="font-size:.75rem;"><?= date('H:i', strtotime($row['fecha'])) ?></div>
                    </td>
                    <td class="fw-bold text-success"><?= number_format($row['total'],2) ?> €</td>
                    <td><span class="<?= badge_cls($estado_actual) ?>"><?= htmlspecialchars($estado_actual) ?></span></td>
                    <td class="text-end pe-4" onclick="event.stopPropagation();">
                        <form action="admin_pedidos.php" method="POST" class="d-flex justify-content-end gap-1">
                            <input type="hidden" name="pedido_id" value="<?= $row['id'] ?>">
                            <select name="estado" class="form-select form-select-sm premium-input shadow-none" style="width:auto;font-size:.8rem;">
                                <?php foreach (['Pendiente','Enviado','Entregado','Cancelado'] as $e): ?>
                                <option value="<?= $e ?>" <?= $estado_actual===$e?'selected':'' ?>><?= $e ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="actualizar_estado" class="btn btn-sm btn-primary rounded-pill px-2">
                                <i class="bi bi-check2-all"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Fila detalle expandible -->
                <tr class="collapse" id="det-<?= $row['id'] ?>" style="background:var(--hover-bg);">
                    <td colspan="6" class="px-4 py-3">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php foreach ($lineas as $l): ?>
                            <span class="badge rounded-pill px-3 py-2 fw-normal premium-text"
                                  style="background:var(--accent-subtle);font-size:.8rem;border:1px solid var(--border-color);">
                                <strong><?= (int)$l['cantidad'] ?>×</strong>
                                <?= htmlspecialchars($l['nombre']) ?>
                                <span class="text-success fw-bold ms-1"><?= number_format($l['precio_unitario']*$l['cantidad'],2) ?> €</span>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($row['stripe_session_id']): ?>
                        <div class="premium-muted" style="font-size:.72rem;">
                            <i class="bi bi-stripe me-1 text-primary"></i>
                            Stripe: <code class="premium-muted"><?= htmlspecialchars(substr($row['stripe_session_id'],0,42)) ?>…</code>
                        </div>
                        <?php endif; ?>
                        <!-- NOTA: Email automático preparado — activo en producción -->
                        <div class="premium-subtle mt-1" style="font-size:.7rem;">
                            <i class="bi bi-envelope me-1"></i>
                            Email de actualización de estado preparado · Se enviará a <?= htmlspecialchars($row['ce']) ?> cuando el servidor esté en producción.
                        </div>
                    </td>
                </tr>

                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" class="text-center premium-muted py-5">
                        <i class="bi bi-inbox d-block fs-1 mb-3"></i>
                        No se encontraron pedidos con los filtros aplicados.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $pagina_actual<=1?'disabled':'' ?>">
                <a class="page-link premium-pagination rounded-start-pill px-4 py-2 fw-bold"
                   href="<?= $base_url ?>pagina=<?= $pagina_actual-1 ?>">
                    <i class="bi bi-chevron-left me-1"></i>Ant
                </a>
            </li>
            <?php for ($i=1;$i<=$total_paginas;$i++): ?>
            <li class="page-item <?= $pagina_actual===$i?'active':'' ?>">
                <a class="page-link premium-pagination py-2 px-3 fw-bold" href="<?= $base_url ?>pagina=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina_actual>=$total_paginas?'disabled':'' ?>">
                <a class="page-link premium-pagination rounded-end-pill px-4 py-2 fw-bold"
                   href="<?= $base_url ?>pagina=<?= $pagina_actual+1 ?>">
                    Sig<i class="bi bi-chevron-right ms-1"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
<script>
const alerta = document.querySelector('.alert');
if (alerta) setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerta).close(), 3500);

// Rotar chevron al expandir fila
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(row => {
    const target = document.querySelector(row.dataset.bsTarget);
    if (!target) return;
    target.addEventListener('show.bs.collapse',  () => row.querySelector('.bi-chevron-down')?.classList.add('rotate-180'));
    target.addEventListener('hide.bs.collapse',  () => row.querySelector('.bi-chevron-down')?.classList.remove('rotate-180'));
});
</script>
<style>
.rotate-180 { transform: rotate(180deg); }
.bi-chevron-down { transition: transform .2s ease; }
</style>
</body>
</html>