<?php
// =============================================================================
// ALGORYA - admin_usuarios.php
// Gestión de clientes registrados en la plataforma.
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

// ─────────────────────────────────────────────────────────────────────────────
// ACCIÓN: Cambiar rol de usuario
// ─────────────────────────────────────────────────────────────────────────────
$mensaje = ''; $tipo_alerta = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $uid      = (int)$_POST['usuario_id'];
    $nuevo_rol = $_POST['rol'] ?? '';
    // No permitir que el admin se quite su propio rol
    if ($uid !== (int)$_SESSION['user_id'] && in_array($nuevo_rol, ['cliente','admin'])) {
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_rol, $uid);
        $ok          = $stmt->execute();
        $mensaje     = $ok ? "Rol actualizado correctamente." : "Error al actualizar.";
        $tipo_alerta = $ok ? "success" : "danger";
        $stmt->close();
    } else {
        $mensaje = "No puedes modificar tu propio rol."; $tipo_alerta = "warning";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FILTROS Y PAGINACIÓN
// ─────────────────────────────────────────────────────────────────────────────
$f_buscar = trim($_GET['buscar'] ?? '');
$f_rol    = $_GET['rol'] ?? '';

$where_parts = []; $params = []; $tipos = '';
if ($f_buscar !== '') { $where_parts[] = "(nombre LIKE ? OR email LIKE ?)"; $params[] = "%$f_buscar%"; $params[] = "%$f_buscar%"; $tipos .= 'ss'; }
if ($f_rol !== '')    { $where_parts[] = "rol = ?"; $params[] = $f_rol; $tipos .= 's'; }
$where_sql = !empty($where_parts) ? 'WHERE '.implode(' AND ',$where_parts) : '';

$por_pagina    = 20;
$pagina_actual = max(1,(int)($_GET['pagina'] ?? 1));

$stmt_c = $conn->prepare("SELECT COUNT(*) as t FROM usuarios $where_sql");
if (!empty($params)) $stmt_c->bind_param($tipos, ...$params);
$stmt_c->execute();
$total = $stmt_c->get_result()->fetch_assoc()['t'];
$stmt_c->close();

$total_paginas = max(1,ceil($total/$por_pagina));
if ($pagina_actual>$total_paginas) $pagina_actual=$total_paginas;
$offset = ($pagina_actual-1)*$por_pagina;

$stmt_u = $conn->prepare("SELECT u.id, u.nombre, u.email, u.rol, u.verificado,
    COUNT(p.id) as num_pedidos, COALESCE(SUM(p.total),0) as total_gastado
    FROM usuarios u
    LEFT JOIN pedidos p ON u.id = p.usuario_id
    $where_sql
    GROUP BY u.id
    ORDER BY u.id DESC
    LIMIT $por_pagina OFFSET $offset");
if (!empty($params)) $stmt_u->bind_param($tipos, ...$params);
$stmt_u->execute();
$resultado = $stmt_u->get_result();
$stmt_u->close();

$filtros_url = http_build_query(array_filter(['buscar'=>$f_buscar,'rol'=>$f_rol]));
$base_url    = '?'.($filtros_url ? $filtros_url.'&' : '');

// KPIs rápidos
$total_clientes = (int)$conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='cliente'")->fetch_assoc()['t'];
$total_admins   = (int)$conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='admin'")->fetch_assoc()['t'];
$verificados    = (int)$conn->query("SELECT COUNT(*) as t FROM usuarios WHERE verificado=1")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | Algorya Admin</title>
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

    <!-- CABECERA -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-black premium-text mb-1" style="font-size:1.6rem;">
                Clientes <span class="text-primary">·</span> <?= $total ?> registros
            </h2>
            <p class="premium-muted mb-0" style="font-size:.82rem;">
                Gestión de usuarios y base de clientes
            </p>
        </div>
        <a href="admin_mailing.php" class="btn btn-primary rounded-pill px-3 fw-bold" style="font-size:.82rem;">
            <i class="bi bi-envelope-at me-1"></i>Enviar mailing
        </a>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show border-0 rounded-3 mb-4">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- KPIs rápidos -->
    <div class="row g-3 mb-4">
        <?php $kpis = [
            ['Total clientes', $total_clientes, 'bi-people-fill',     '#3b82f6','rgba(59,130,246,.1)'],
            ['Administradores',$total_admins,   'bi-shield-fill',      '#8b5cf6','rgba(139,92,246,.1)'],
            ['Cuentas verificadas',$verificados,'bi-patch-check-fill', '#22c55e','rgba(34,197,94,.1)'],
            ['Sin verificar',$total_clientes+$total_admins-$verificados,'bi-exclamation-circle-fill','#f59e0b','rgba(245,158,11,.1)'],
        ];
        foreach ($kpis as [$label,$val,$icon,$color,$subtle]): ?>
        <div class="col-6 col-xl-3">
            <div class="premium-card admin-card rounded-4 p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:40px;height:40px;background:<?= $subtle ?>;color:<?= $color ?>;">
                    <i class="bi <?= $icon ?> fs-5"></i>
                </div>
                <div>
                    <div class="fw-black premium-text" style="font-family:'Outfit',sans-serif;font-size:1.4rem;letter-spacing:-.04em;line-height:1;"><?= $val ?></div>
                    <div class="premium-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700;"><?= $label ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTROS -->
    <form method="GET" action="admin_usuarios.php" class="mb-4">
        <div class="premium-card admin-card rounded-4 p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">
                        <i class="bi bi-search me-1"></i>Buscar
                    </label>
                    <input type="text" name="buscar" class="form-control premium-input shadow-none"
                           placeholder="Nombre o email..." value="<?= htmlspecialchars($f_buscar) ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label premium-muted fw-bold text-uppercase mb-1" style="font-size:.68rem;letter-spacing:.05em;">Rol</label>
                    <select name="rol" class="form-select premium-input shadow-none">
                        <option value="">Todos</option>
                        <option value="cliente" <?= $f_rol==='cliente'?'selected':'' ?>>Clientes</option>
                        <option value="admin"   <?= $f_rol==='admin'  ?'selected':'' ?>>Administradores</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold flex-grow-1">
                        <i class="bi bi-funnel-fill me-1"></i>Filtrar
                    </button>
                    <?php if ($f_buscar||$f_rol): ?>
                    <a href="admin_usuarios.php" class="btn btn-outline-secondary rounded-pill">
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
                        <th class="ps-4">ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-center">Pedidos</th>
                        <th class="text-end">Gastado</th>
                        <th class="text-end pe-4">Cambiar rol</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resultado && $resultado->num_rows > 0):
                    while ($row = $resultado->fetch_assoc()):
                    $es_admin_actual = ((int)$row['id'] === (int)$_SESSION['user_id']);
                ?>
                <tr>
                    <td class="ps-4 premium-muted fw-bold" style="font-size:.82rem;">#<?= $row['id'] ?></td>
                    <td>
                        <div class="fw-semibold premium-text" style="font-size:.9rem;">
                            <?= htmlspecialchars($row['nombre']) ?>
                            <?php if ($es_admin_actual): ?>
                            <span class="badge rounded-pill ms-1 px-2" style="background:rgba(59,130,246,.12);color:#2563eb;font-size:.65rem;">Tú</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="premium-muted" style="font-size:.82rem;"><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <span class="badge-estado <?= $row['rol']==='admin' ? 'badge-enviado' : 'badge-entregado' ?>">
                            <?= $row['rol'] === 'admin' ? '⚙ Admin' : '👤 Cliente' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['verificado']): ?>
                        <span class="badge-estado badge-entregado">✓ Verificado</span>
                        <?php else: ?>
                        <span class="badge-estado badge-pendiente">⏳ Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center fw-bold premium-text"><?= (int)$row['num_pedidos'] ?></td>
                    <td class="text-end fw-bold text-success"><?= number_format($row['total_gastado'],2) ?> €</td>
                    <td class="text-end pe-4">
                        <?php if (!$es_admin_actual): ?>
                        <form action="admin_usuarios.php" method="POST" class="d-flex justify-content-end gap-1">
                            <input type="hidden" name="usuario_id" value="<?= $row['id'] ?>">
                            <select name="rol" class="form-select form-select-sm premium-input shadow-none" style="width:auto;font-size:.8rem;">
                                <option value="cliente" <?= $row['rol']==='cliente'?'selected':'' ?>>Cliente</option>
                                <option value="admin"   <?= $row['rol']==='admin'  ?'selected':'' ?>>Admin</option>
                            </select>
                            <button type="submit" name="cambiar_rol" class="btn btn-sm btn-primary rounded-pill px-2">
                                <i class="bi bi-check2-all"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="premium-muted" style="font-size:.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8" class="text-center premium-muted py-5">
                    <i class="bi bi-people d-block fs-1 mb-3"></i>No se encontraron usuarios.
                </td></tr>
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
                <a class="page-link premium-pagination py-2 px-3 fw-bold"
                   href="<?= $base_url ?>pagina=<?= $i ?>"><?= $i ?></a>
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
</script>
</body>
</html>