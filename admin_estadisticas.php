<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); exit();
}

// Navbar compartido — se carga aquí para que las variables PHP estén disponibles
$pagina_actual_admin = basename($_SERVER['PHP_SELF']);
$nav_items = [
    ['admin_pedidos.php',      'bi-receipt',        'Pedidos'],
    ['admin_usuarios.php',     'bi-people',         'Clientes'],
    ['admin_estadisticas.php', 'bi-bar-chart-fill', 'Dashboard'],
    ['admin_mailing.php',      'bi-envelope-at',    'Mailing'],
];

// ── KPIs VENTAS ──────────────────────────────────────────────────────────────
$ingresos_totales = (float)$conn->query("SELECT COALESCE(SUM(total),0) as t FROM pedidos")->fetch_assoc()['t'];
$pedidos_total    = (int)  $conn->query("SELECT COUNT(*) as t FROM pedidos")->fetch_assoc()['t'];
$clientes_total   = (int)  $conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='cliente'")->fetch_assoc()['t'];
$ticket_medio     = $pedidos_total > 0 ? round($ingresos_totales / $pedidos_total, 2) : 0;
$ingresos_mes     = (float)$conn->query("SELECT COALESCE(SUM(total),0) as t FROM pedidos WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")->fetch_assoc()['t'];
$pedidos_mes      = (int)  $conn->query("SELECT COUNT(*) as t FROM pedidos WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())")->fetch_assoc()['t'];

// ── KPIs CATÁLOGO ─────────────────────────────────────────────────────────────
$productos_activos   = (int)$conn->query("SELECT COUNT(*) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
$productos_destacados= (int)$conn->query("SELECT COUNT(*) as t FROM productos WHERE destacado=1 AND activo=1")->fetch_assoc()['t'];
$stock_total         = (int)$conn->query("SELECT COALESCE(SUM(stock),0) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
$stock_bajo          = (int)$conn->query("SELECT COUNT(*) as t FROM productos WHERE stock<=5 AND activo=1")->fetch_assoc()['t'];

// ── TELEMETRÍA (VISITAS) ─────────────────────────────────────────────────────
$visitas_total = (int)$conn->query("SELECT COUNT(*) as t FROM visitas")->fetch_assoc()['t'];
$visitas_hoy   = (int)$conn->query("SELECT COUNT(*) as t FROM visitas WHERE DATE(fecha)=CURDATE()")->fetch_assoc()['t'];

// Gráfico: Dispositivos
$res_disp = $conn->query("SELECT dispositivo, COUNT(*) as t FROM visitas GROUP BY dispositivo");
$g_disp_l = []; $g_disp_d = [];
while ($r = $res_disp->fetch_assoc()) { $g_disp_l[] = $r['dispositivo']; $g_disp_d[] = (int)$r['t']; }

// Top Países
$res_pais = $conn->query("SELECT pais, COUNT(*) as t FROM visitas GROUP BY pais ORDER BY t DESC LIMIT 4");
$top_paises = $res_pais->fetch_all(MYSQLI_ASSOC);

// Top Ciudades
$res_ciudad = $conn->query("SELECT ciudad, COUNT(*) as t FROM visitas WHERE ciudad != 'Desconocida' GROUP BY ciudad ORDER BY t DESC LIMIT 4");
$top_ciudades = $res_ciudad->fetch_all(MYSQLI_ASSOC);

// ── GRÁFICO: Ingresos 30 días ─────────────────────────────────────────────────
$res = $conn->query("SELECT DATE(fecha) as dia, SUM(total) as td FROM pedidos WHERE fecha>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY DATE(fecha) ORDER BY dia ASC");
$g_dias = []; $g_totales = [];
while ($r = $res->fetch_assoc()) { $g_dias[] = date('d/m', strtotime($r['dia'])); $g_totales[] = (float)$r['td']; }

// ── GRÁFICO: Top 5 productos vendidos ─────────────────────────────────────────
$res = $conn->query("SELECT p.nombre, SUM(lp.cantidad) as uv FROM lineas_pedido lp JOIN productos p ON lp.producto_id=p.id GROUP BY lp.producto_id ORDER BY uv DESC LIMIT 5");
$g_top_n = []; $g_top_v = [];
while ($r = $res->fetch_assoc()) { $g_top_n[] = strlen($r['nombre'])>22 ? substr($r['nombre'],0,22).'…' : $r['nombre']; $g_top_v[] = (int)$r['uv']; }

// ── GRÁFICO: Distribución de estados ─────────────────────────────────────────
$res = $conn->query("SELECT COALESCE(estado,'Pendiente') as estado, COUNT(*) as t FROM pedidos GROUP BY estado");
$g_est_l = []; $g_est_d = [];
while ($r = $res->fetch_assoc()) { $g_est_l[] = $r['estado']; $g_est_d[] = (int)$r['t']; }

// ── Últimos 5 pedidos ─────────────────────────────────────────────────────────
$res_ultimos = $conn->query("SELECT p.id,p.total,p.fecha,p.estado,u.nombre as cliente FROM pedidos p JOIN usuarios u ON p.usuario_id=u.id ORDER BY p.fecha DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                Dashboard <span class="text-primary">·</span> Algorya
            </h2>
            <p class="premium-muted mb-0" style="font-size:.82rem;">
                Actualizado el <?= date('d/m/Y \a \l\a\s H:i') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="exportar_csv.php?tipo=estadisticas" class="btn btn-success rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center" style="font-size:.85rem;">
                <i class="bi bi-file-earmark-spreadsheet me-2 fs-6"></i>Exportar KPIs
            </a>
            <a href="admin_pedidos.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center" style="font-size:.85rem;">
                <i class="bi bi-receipt me-2 fs-6"></i>Ver pedidos
            </a>
        </div>
    </div>

    <!-- ── FILA 1: KPIs VENTAS ─────────────────────────────────────────────── -->
     <div class="row g-3 mb-4">
        
        <div class="col-lg-4">
            <div class="premium-card admin-card rounded-4 p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold premium-text mb-0" style="font-size:.85rem;letter-spacing:-.01em;">
                        <i class="bi bi-radar text-primary me-2"></i>Tráfico General
                    </h6>
                </div>
                <div class="d-flex gap-3 mb-4">
                    <div class="flex-grow-1 p-3 rounded-3" style="background:var(--hover-bg); border:1px solid var(--border-color);">
                        <div class="premium-muted mb-1" style="font-size:.7rem; font-weight:700; text-transform:uppercase;">Visitas Hoy</div>
                        <div class="fw-black text-primary" style="font-size:1.4rem; font-family:'Outfit',sans-serif;"><?= $visitas_hoy ?></div>
                    </div>
                    <div class="flex-grow-1 p-3 rounded-3" style="background:var(--hover-bg); border:1px solid var(--border-color);">
                        <div class="premium-muted mb-1" style="font-size:.7rem; font-weight:700; text-transform:uppercase;">Visitas Totales</div>
                        <div class="fw-black premium-text" style="font-size:1.4rem; font-family:'Outfit',sans-serif;"><?= $visitas_total ?></div>
                    </div>
                </div>
                
                <h6 class="fw-bold premium-text mb-3" style="font-size:.75rem; text-transform:uppercase;">Top Ciudades</h6>
                <div class="d-flex flex-column gap-2 flex-grow-1 justify-content-center">
                    <?php if(empty($top_ciudades)): ?>
                        <div class="premium-muted small text-center">Sin datos de ciudades</div>
                    <?php else: ?>
                        <?php foreach($top_ciudades as $c): ?>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded-2" style="border:1px solid var(--border-color);">
                            <span class="premium-text" style="font-size:.8rem;"><i class="bi bi-geo-alt-fill text-danger me-2"></i><?= htmlspecialchars($c['ciudad']) ?></span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= $c['t'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="premium-card admin-card rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold premium-text mb-0" style="font-size:.85rem;">
                        <i class="bi bi-globe-americas text-success me-2"></i>Top Países
                    </h6>
                </div>
                <div class="d-flex flex-column gap-4 mt-2">
                    <?php if (empty($top_paises)): ?>
                        <div class="text-center premium-muted py-3"><i class="bi bi-map fs-2 mb-2 d-block"></i>Sin datos</div>
                    <?php else: ?>
                        <?php foreach($top_paises as $p):
                            $porcentaje = $visitas_total > 0 ? round(($p['t'] / $visitas_total) * 100) : 0;
                        ?>
                        <div>
                            <div class="d-flex justify-content-between mb-2" style="font-size:.8rem;">
                                <span class="fw-semibold premium-text"><?= htmlspecialchars($p['pais']) ?></span>
                                <span class="premium-muted fw-bold"><?= $p['t'] ?> <span class="fw-normal">(<?= $porcentaje ?>%)</span></span>
                            </div>
                            <div class="progress" style="height: 8px; background: var(--hover-bg); border-radius:10px;">
                                <div class="progress-bar bg-success" style="width: <?= $porcentaje ?>%; border-radius:10px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="premium-card admin-card rounded-4 p-4 h-100">
                <h6 class="fw-bold premium-text mb-3" style="font-size:.85rem;letter-spacing:-.01em;">
                    <i class="bi bi-laptop text-info me-2"></i>Dispositivos
                </h6>
                <?php if (empty($g_disp_l)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center premium-muted py-4">
                    <i class="bi bi-pie-chart fs-1 mb-2"></i>
                    <span style="font-size:.85rem;">Sin datos</span>
                </div>
                <?php else: ?>
                <canvas id="graficoDispositivos" height="180"></canvas>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    <div class="row g-3 mb-3">

        <div class="col-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center gap-3"
                 style="--kpi-color:#3b82f6;--kpi-color-subtle:rgba(59,130,246,.1);">
                <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="kpi-label">Ingresos totales</div>
                    <div class="kpi-value"><?= number_format($ingresos_totales,2) ?> €</div>
                    <div class="kpi-sub text-success">
                        <i class="bi bi-arrow-up-short"></i><?= number_format($ingresos_mes,2) ?> € este mes
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center gap-3"
                 style="--kpi-color:#22c55e;--kpi-color-subtle:rgba(34,197,94,.1);">
                <div class="kpi-icon"><i class="bi bi-bag-check"></i></div>
                <div>
                    <div class="kpi-label">Pedidos totales</div>
                    <div class="kpi-value"><?= $pedidos_total ?></div>
                    <div class="kpi-sub text-success">
                        <i class="bi bi-arrow-up-short"></i><?= $pedidos_mes ?> este mes
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center gap-3"
                 style="--kpi-color:#f59e0b;--kpi-color-subtle:rgba(245,158,11,.1);">
                <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="kpi-label">Clientes</div>
                    <div class="kpi-value"><?= $clientes_total ?></div>
                    <div class="kpi-sub">registrados</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="kpi-card d-flex align-items-center gap-3"
                 style="--kpi-color:#8b5cf6;--kpi-color-subtle:rgba(139,92,246,.1);">
                <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="kpi-label">Ticket medio</div>
                    <div class="kpi-value"><?= number_format($ticket_medio,2) ?> €</div>
                    <div class="kpi-sub">por pedido</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FILA 2: KPIs CATÁLOGO ──────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <?php
        $kpis_cat = [
            ['Productos activos',   $productos_activos,    'bi-grid-3x3-gap-fill', '#3b82f6', 'rgba(59,130,246,.1)'],
            ['En tendencia',        $productos_destacados,  'bi-fire',              '#f59e0b', 'rgba(245,158,11,.1)'],
            ['Unidades en stock',   number_format($stock_total), 'bi-boxes',       '#22c55e', 'rgba(34,197,94,.1)'],
            ['Stock crítico (≤5)',  $stock_bajo,            'bi-exclamation-triangle-fill', $stock_bajo>0?'#ef4444':'#22c55e', $stock_bajo>0?'rgba(239,68,68,.1)':'rgba(34,197,94,.1)'],
        ];
        foreach ($kpis_cat as [$label, $val, $icon, $color, $subtle]):
        ?>
        <div class="col-6 col-xl-3">
            <div class="premium-card admin-card rounded-4 p-3 text-center">
                <div class="rounded-3 mx-auto mb-2 d-flex align-items-center justify-content-center"
                     style="width:40px;height:40px;background:<?= $subtle ?>;color:<?= $color ?>;">
                    <i class="<?= $icon ?> fs-5"></i>
                </div>
                <div class="fw-black premium-text" style="font-family:'Outfit',sans-serif;font-size:1.5rem;letter-spacing:-.04em;"><?= $val ?></div>
                <div class="premium-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700;"><?= $label ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── FILA 3: GRÁFICOS ───────────────────────────────────────────────── -->
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="premium-card admin-card rounded-4 p-4 h-100">
                <h6 class="fw-bold premium-text mb-3" style="font-size:.85rem;letter-spacing:-.01em;">
                    <i class="bi bi-graph-up text-primary me-2"></i>Ingresos — últimos 30 días
                </h6>
                <?php if (empty($g_dias)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center premium-muted py-5">
                    <i class="bi bi-bar-chart fs-1 mb-2"></i>
                    <span style="font-size:.85rem;">Sin pedidos en los últimos 30 días</span>
                </div>
                <?php else: ?>
                <canvas id="graficoIngresos" height="130"></canvas>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="premium-card admin-card rounded-4 p-4 h-100">
                <h6 class="fw-bold premium-text mb-3" style="font-size:.85rem;letter-spacing:-.01em;">
                    <i class="bi bi-pie-chart text-primary me-2"></i>Estado de pedidos
                </h6>
                <?php if (empty($g_est_l)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center premium-muted py-5">
                    <i class="bi bi-pie-chart fs-1 mb-2"></i>
                    <span style="font-size:.85rem;">Sin datos</span>
                </div>
                <?php else: ?>
                <canvas id="graficoEstados" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── FILA 4: TOP PRODUCTOS + ÚLTIMOS PEDIDOS ────────────────────────── -->
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="premium-card admin-card rounded-4 p-4 h-100">
                <h6 class="fw-bold premium-text mb-3" style="font-size:.85rem;letter-spacing:-.01em;">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>Top productos más vendidos
                </h6>
                <?php if (empty($g_top_n)): ?>
                <div class="d-flex flex-column align-items-center justify-content-center premium-muted py-5">
                    <i class="bi bi-trophy fs-1 mb-2"></i>
                    <span style="font-size:.85rem;">Aún no hay ventas registradas</span>
                </div>
                <?php else: ?>
                <canvas id="graficoTop" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="premium-card admin-card rounded-4 overflow-hidden h-100">
                <div class="px-4 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold premium-text mb-0" style="font-size:.85rem;letter-spacing:-.01em;">
                        <i class="bi bi-clock-history text-primary me-2"></i>Últimos pedidos
                    </h6>
                    <a href="admin_pedidos.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" style="font-size:.78rem;">
                        Ver todos <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Pedido</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th class="pe-4">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($res_ultimos && $res_ultimos->num_rows > 0):
                            while ($p = $res_ultimos->fetch_assoc()):
                            $est = $p['estado'] ?? 'Pendiente';
                            $badge_cls = strtolower($est);
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold premium-text">#<?= str_pad($p['id'],5,'0',STR_PAD_LEFT) ?></td>
                            <td class="premium-text" style="font-size:.88rem;"><?= htmlspecialchars($p['cliente']) ?></td>
                            <td class="fw-bold text-success"><?= number_format($p['total'],2) ?> €</td>
                            <td><span class="badge-estado badge-<?= $badge_cls ?>"><?= htmlspecialchars($est) ?></span></td>
                            <td class="premium-muted pe-4" style="font-size:.82rem;"><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center premium-muted py-5">
                            <i class="bi bi-inbox d-block fs-2 mb-2"></i>Sin pedidos todavía
                        </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
<script>
const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
const clr = {
    text:  isDark ? '#e6edf3' : '#0f172a',
    muted: isDark ? '#8b949e' : '#64748b',
    grid:  isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
};
const fontFamily = "'Outfit', 'DM Sans', sans-serif";

Chart.defaults.font.family = fontFamily;

<?php if (!empty($g_dias)): ?>
new Chart(document.getElementById('graficoIngresos'), {
    type: 'line',
    data: {
        labels: <?= json_encode($g_dias) ?>,
        datasets: [{ label: 'Ingresos (€)', data: <?= json_encode($g_totales) ?>,
            borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.07)',
            borderWidth: 2, fill: true, tension: 0.45,
            pointRadius: 3, pointBackgroundColor: '#3b82f6', pointBorderWidth: 0 }]
    },
    options: { responsive: true,
        plugins: { legend: { display: false },
            tooltip: { callbacks: { label: c => ' ' + c.parsed.y.toFixed(2) + ' €' }, cornerRadius: 8 }},
        scales: {
            x: { ticks: { color: clr.muted, font: { size: 10 } }, grid: { color: clr.grid } },
            y: { ticks: { color: clr.muted, font: { size: 10 }, callback: v => v+'€' }, grid: { color: clr.grid } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($g_est_l)): ?>
new Chart(document.getElementById('graficoEstados'), {
    type: 'doughnut',
    data: { labels: <?= json_encode($g_est_l) ?>,
        datasets: [{ data: <?= json_encode($g_est_d) ?>,
            backgroundColor: ['#f59e0b','#3b82f6','#22c55e','#ef4444'],
            borderWidth: 0, hoverOffset: 5 }]
    },
    options: { cutout: '68%',
        plugins: { legend: { position: 'bottom',
            labels: { color: clr.text, font: { size: 11, family: fontFamily }, padding: 12, usePointStyle: true }
        }}
    }
});
<?php endif; ?>

<?php if (!empty($g_top_n)): ?>
new Chart(document.getElementById('graficoTop'), {
    type: 'bar',
    data: { labels: <?= json_encode($g_top_n) ?>,
        datasets: [{ label: 'Unidades', data: <?= json_encode($g_top_v) ?>,
            backgroundColor: ['#3b82f6','#6366f1','#8b5cf6','#a78bfa','#c4b5fd'],
            borderRadius: 6, borderSkipped: false }]
    },
    options: { indexAxis: 'y', responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: clr.muted, font: { size: 10 } }, grid: { color: clr.grid } },
            y: { ticks: { color: clr.text,  font: { size: 10 } }, grid: { display: false } }
        }
    }
});
<?php endif; ?>
<?php if (!empty($g_disp_l)): ?>
new Chart(document.getElementById('graficoDispositivos'), {
    type: 'doughnut',
    data: { labels: <?= json_encode($g_disp_l) ?>,
        datasets: [{ data: <?= json_encode($g_disp_d) ?>,
            backgroundColor: ['#0dcaf0', '#3b82f6', '#6366f1'],
            borderWidth: 0, hoverOffset: 5 }]
    },
    options: { cutout: '70%',
        plugins: { legend: { position: 'bottom',
            labels: { color: clr.text, font: { size: 11, family: fontFamily }, padding: 15, usePointStyle: true }
        }}
    }
});
<?php endif; ?>
</script>
</body>
</html>