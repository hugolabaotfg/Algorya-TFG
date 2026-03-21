<?php
session_start();
require 'includes/db.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$u_count = $conn->query("SELECT COUNT(*) as t FROM usuarios")->fetch_assoc()['t'];
$p_count = $conn->query("SELECT COUNT(*) as t FROM productos")->fetch_assoc()['t'];
$s_sum = $conn->query("SELECT SUM(stock) as t FROM productos")->fetch_assoc()['t'];

$nombres = [];
$precios = [];
$top = $conn->query("SELECT nombre, precio FROM productos ORDER BY precio DESC LIMIT 5");
while ($r = $top->fetch_assoc()) {
    $nombres[] = $r['nombre'];
    $precios[] = $r['precio'];
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Estadísticas | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php">
                <i class="bi bi-box-seam-fill text-primary me-1"></i><span class="text-primary">Algorya</span><span
                    class="premium-text" style="font-size: 0.55em;">.Admin</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3 shadow-sm" type="button"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-gear-fill me-1"></i> Panel de Control
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow-lg mt-2 py-2">
                        <li><a class="dropdown-item premium-text py-2" href="admin_pedidos.php">Pedidos</a></li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_usuarios.php">Clientes</a></li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_estadisticas.php">Estadísticas</a>
                        </li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_mailing.php">Mailing</a></li>
                    </ul>
                </div>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill"><i
                        class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>
    <div class="container mt-5 flex-grow-1">
        <h2 class="fw-bold premium-text mb-4"><i class="bi bi-bar-chart-fill text-primary"></i> Estadísticas Algorya
        </h2>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card premium-card border-0 p-4 text-center rounded-4">
                    <h6>CLIENTES</h6>
                    <h1 class="text-primary">
                        <?php echo $u_count; ?>
                    </h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card premium-card border-0 p-4 text-center rounded-4">
                    <h6>PRODUCTOS</h6>
                    <h1 class="text-success">
                        <?php echo $p_count; ?>
                    </h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card premium-card border-0 p-4 text-center rounded-4">
                    <h6>STOCK TOTAL</h6>
                    <h1 class="text-warning">
                        <?php echo $s_sum; ?>
                    </h1>
                </div>
            </div>
        </div>
        <div class="card premium-card border-0 p-4 rounded-4 shadow-sm"><canvas id="graficoPrecios"
                height="100"></canvas></div>
    </div>
    <script>
        const ctx = document.getElementById('graficoPrecios').getContext('2d');
        new Chart(ctx, { type: 'bar', data: { labels: <?php echo json_encode($nombres); ?>, datasets: [{ label: 'Precio (€)', data: <?php echo json_encode($precios); ?>, backgroundColor: '#3b82f6' }] } });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>