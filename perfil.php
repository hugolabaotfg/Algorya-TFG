<?php
session_start();
require 'includes/db.php';

// Si no hay sesión, te envía al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// 1. Obtener datos del usuario
$sql_user = "SELECT nombre, email, fecha_registro FROM usuarios WHERE id = $uid";
$res_user = $conn->query($sql_user);
$usuario = $res_user->fetch_assoc();

// 2. Obtener historial de pedidos
$sql_pedidos = "SELECT id, total, fecha, metodo_pago FROM pedidos WHERE usuario_id = $uid ORDER BY fecha DESC";
$res_pedidos = $conn->query($sql_pedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f9f9f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .navbar { background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); border-bottom: 1px solid #eaeaea; }
        .profile-header { background: linear-gradient(135deg, #111 0%, #333 100%); color: white; padding: 40px 0; border-radius: 16px; margin-bottom: 30px; }
        .card { border: 1px solid #eaeaea; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light sticky-top shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-bag-check-fill text-primary"></i> Dropship<span class="text-primary">TFG</span>
            </a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> Volver a la tienda</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-header px-5 shadow-sm">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                        <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                    </div>
                </div>
                <div class="col">
                    <h2 class="m-0 fw-bold">Hola, <?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                    <p class="m-0 opacity-75"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?></p>
                </div>
                <div class="col-auto text-end d-none d-md-block">
                    <span class="badge bg-light text-dark opacity-75">Miembro desde <?php echo date('Y', strtotime($usuario['fecha_registro'])); ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card bg-white h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-gear text-secondary me-2"></i>Ajustes de Cuenta</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0"><a href="ajustes.php?seccion=datos" class="text-decoration-none text-dark"><i class="bi bi-person me-2"></i> Mis Datos Personales</a></li>
                            <li class="list-group-item px-0"><a href="ajustes.php?seccion=seguridad" class="text-decoration-none text-dark"><i class="bi bi-shield-lock me-2"></i> Seguridad y Contraseña</a></li>
                            <li class="list-group-item px-0"><a href="ajustes.php?seccion=notificaciones" class="text-decoration-none text-dark"><i class="bi bi-bell me-2"></i> Notificaciones</a></li>
                        </ul>
                        <div class="mt-4 pt-3 border-top">
                            <a href="logout.php" class="btn btn-light border w-100 text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8 mb-4">
                <div class="card bg-white h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4"><i class="bi bi-box-seam text-primary me-2"></i>Mi Historial de Pedidos</h5>
                        
                        <?php if ($res_pedidos && $res_pedidos->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light text-muted small text-uppercase">
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Fecha</th>
                                            <th>Método</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pedido = $res_pedidos->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold">#<?php echo str_pad($pedido['id'], 5, "0", STR_PAD_LEFT); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?></td>
                                                <td class="small text-muted"><i class="bi bi-credit-card me-1"></i><?php echo $pedido['metodo_pago']; ?></td>
                                                <td class="text-end fw-bold"><?php echo number_format($pedido['total'], 2); ?> €</td>
                                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">Procesado</span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-1 d-block mb-3 opacity-50"></i>
                                <h6>Aún no has realizado ninguna compra</h6>
                                <p class="small">Cuando hagas tu primer pedido, aparecerá aquí.</p>
                                <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill mt-2">Ir de compras</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
