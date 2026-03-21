<?php
session_start();
require 'includes/db.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $pedido_id = (int) $_POST['pedido_id'];
    $nuevo_estado = $conn->real_escape_string($_POST['estado']);
    $sql_update = "UPDATE pedidos SET estado = '$nuevo_estado' WHERE id = $pedido_id";
    if ($conn->query($sql_update)) {
        $mensaje = "<div class='alert alert-success alert-dismissible fade show' role='alert'><i class='bi bi-check-circle-fill me-2'></i>Pedido #$pedido_id actualizado a $nuevo_estado.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}
$sql = "SELECT p.*, u.nombre as cliente_nombre FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.fecha DESC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Pedidos | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
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
                        <li>
                            <h6 class="dropdown-header premium-muted small fw-bold">OPERACIONES</h6>
                        </li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_pedidos.php"><i
                                    class="bi bi-receipt me-2 text-primary"></i>Pedidos</a></li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_usuarios.php"><i
                                    class="bi bi-people me-2 text-primary"></i>Clientes</a></li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_estadisticas.php"><i
                                    class="bi bi-bar-chart me-2 text-primary"></i>Estadísticas</a></li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_mailing.php"><i
                                    class="bi bi-envelope-at me-2 text-primary"></i>Mailing</a></li>
                        <li>
                            <hr class="dropdown-divider" style="border-color: var(--border-color);">
                        </li>
                        <li><a class="dropdown-item premium-text py-2" href="add_product.php"><i
                                    class="bi bi-plus-circle me-2 text-success"></i>Nuevo Producto</a></li>
                    </ul>
                </div>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill"><i
                        class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>
    <div class="container mt-5 flex-grow-1">
        <h2 class="fw-bold premium-text mb-4"><i class="bi bi-receipt text-primary me-2"></i> Gestión de Pedidos</h2>
        <?php echo $mensaje; ?>
        <div class="card premium-card border-0 rounded-4 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="premium-muted small fw-bold">
                            <th class="ps-4">ID</th>
                            <th>CLIENTE</th>
                            <th>FECHA</th>
                            <th>TOTAL</th>
                            <th>ESTADO</th>
                            <th class="text-end pe-4">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody class="premium-text">
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold">#
                                    <?php echo $row['id']; ?>
                                </td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($row['cliente_nombre']); ?>
                                    </div>
                                </td>
                                <td class="small">
                                    <?php echo date('d/m/Y', strtotime($row['fecha'])); ?>
                                </td>
                                <td class="fw-bold text-success">
                                    <?php echo number_format($row['total'], 2); ?> €
                                </td>
                                <td><span class="badge bg-primary rounded-pill px-3">
                                        <?php echo $row['estado']; ?>
                                    </span></td>
                                <td class="text-end pe-4">
                                    <form action="admin_pedidos.php" method="POST" class="d-flex justify-content-end gap-1">
                                        <input type="hidden" name="pedido_id" value="<?php echo $row['id']; ?>">
                                        <select name="estado" class="form-select form-select-sm premium-input w-auto">
                                            <option value="Pendiente">Pendiente</option>
                                            <option value="Enviado">Enviado</option>
                                            <option value="Entregado">Entregado</option>
                                        </select>
                                        <button type="submit" name="actualizar_estado" class="btn btn-sm btn-primary"><i
                                                class="bi bi-check2"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>