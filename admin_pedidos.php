<?php
session_start();
require 'includes/db.php';

// Control de Acceso Estricto (RBAC)
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. Calcular Facturación Total
$res_total = $conn->query("SELECT SUM(total) as ingresos FROM pedidos");
$ingresos_totales = ($res_total && $row_total = $res_total->fetch_assoc()) ? $row_total['ingresos'] : 0;

// 2. Obtener la lista de pedidos con los datos del cliente
$sql_pedidos = "SELECT p.id, p.total, p.fecha, p.metodo_pago, u.nombre, u.email 
                FROM pedidos p 
                JOIN usuarios u ON p.usuario_id = u.id 
                ORDER BY p.fecha DESC";
$res_pedidos = $conn->query($sql_pedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Ventas | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shield-lock text-warning"></i> Administración TFG
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><i class="bi bi-person-badge"></i> <?php echo $_SESSION['nombre']; ?></span>
                <a href="add_product.php" class="btn btn-outline-light btn-sm me-2">Catálogo</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Gestión de Pedidos</h2>
                <p class="text-muted">Monitorización en tiempo real de las ventas del modelo Dropshipping.</p>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow border-0 rounded-4">
                    <div class="card-body text-center">
                        <h6 class="text-uppercase fw-bold opacity-75 mb-1">Facturación Total</h6>
                        <h2 class="mb-0"><?php echo number_format($ingresos_totales ? $ingresos_totales : 0, 2); ?> €</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">ID Pedido</th>
                                <th>Fecha y Hora</th>
                                <th>Cliente</th>
                                <th>Email Contacto</th>
                                <th>Método</th>
                                <th class="text-end px-4">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_pedidos && $res_pedidos->num_rows > 0): ?>
                                <?php while ($pedido = $res_pedidos->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-4"><span class="badge bg-secondary">#<?php echo str_pad($pedido['id'], 5, "0", STR_PAD_LEFT); ?></span></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($pedido['nombre']); ?></td>
                                        <td><a href="mailto:<?php echo $pedido['email']; ?>" class="text-decoration-none"><?php echo $pedido['email']; ?></a></td>
                                        <td><i class="bi bi-credit-card me-1"></i><?php echo $pedido['metodo_pago']; ?></td>
                                        <td class="text-end px-4 fw-bold text-success"><?php echo number_format($pedido['total'], 2); ?> €</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Aún no hay pedidos registrados en el sistema.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>