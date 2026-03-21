<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Extraer datos para los gráficos
// 1. Usuarios registrados
$res_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol != 'admin'");
$total_usuarios = $res_usuarios->fetch_assoc()['total'];

// 2. Productos en catálogo
$res_productos = $conn->query("SELECT COUNT(*) as total FROM productos");
$total_productos = $res_productos->fetch_assoc()['total'];

// 3. Stock total (suma de unidades)
$res_stock = $conn->query("SELECT SUM(stock) as total_stock FROM productos");
$total_stock = $res_stock->fetch_assoc()['total_stock'];

// 4. Los 5 productos más caros (Para el gráfico de barras)
$nombres_prod = [];
$precios_prod = [];
$res_top = $conn->query("SELECT nombre, precio FROM productos ORDER BY precio DESC LIMIT 5");
while ($row = $res_top->fetch_assoc()) {
    $nombres_prod[] = $row['nombre'];
    $precios_prod[] = $row['precio'];
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
            <div class="d-flex gap-3 align-items-center">
                <a href="admin_pedidos.php" class="btn btn-link premium-text text-decoration-none">Pedidos</a>
                <a href="admin_usuarios.php" class="btn btn-link premium-text text-decoration-none">Clientes</a>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill">Ir a la Tienda</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <h2 class="fw-bold premium-text mb-4"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Dashboard de
            Inteligencia de Negocio</h2>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card premium-card border-0 p-4 rounded-4 text-center">
                    <h6 class="premium-muted fw-bold">CLIENTES REGISTRADOS</h6>
                    <h1 class="display-4 fw-bold text-primary">
                        <?php echo $total_usuarios; ?>
                    </h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card premium-card border-0 p-4 rounded-4 text-center">
                    <h6 class="premium-muted fw-bold">PRODUCTOS ACTIVOS</h6>
                    <h1 class="display-4 fw-bold text-success">
                        <?php echo $total_productos; ?>
                    </h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card premium-card border-0 p-4 rounded-4 text-center">
                    <h6 class="premium-muted fw-bold">UNIDADES EN STOCK</h6>
                    <h1 class="display-4 fw-bold text-warning">
                        <?php echo $total_stock; ?>
                    </h1>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card premium-card border-0 p-4 rounded-4">
                    <h5 class="premium-text fw-bold mb-4">Top 5 Productos con Mayor Valor (Distribución de Precios)</h5>
                    <canvas id="graficoPrecios" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuración dinámica del gráfico inyectando PHP en JS
        const ctx = document.getElementById('graficoPrecios').getContext('2d');
        const nombres = <?php echo json_encode($nombres_prod); ?>;
        const precios = <?php echo json_encode($precios_prod); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: nombres,
                datasets: [{
                    label: 'Precio (€)',
                    data: precios,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>

</html>