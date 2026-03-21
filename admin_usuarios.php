<?php
session_start();
require 'includes/db.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje = '';
// Lógica de cambio de rol y borrado (igual que antes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $id_usuario = (int) $_POST['id_usuario'];
    $nuevo_rol = $conn->real_escape_string($_POST['nuevo_rol']);
    if ($id_usuario !== (int) $_SESSION['user_id']) {
        $conn->query("UPDATE usuarios SET rol = '$nuevo_rol' WHERE id = $id_usuario");
        $mensaje = "<div class='alert alert-success'>Rol actualizado.</div>";
    }
}
$resultado = $conn->query("SELECT * FROM usuarios ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Clientes | Algorya Admin</title>
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
                    <button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3" type="button"
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
        <h2 class="fw-bold premium-text mb-4"><i class="bi bi-people-fill text-primary me-2"></i> Gestión de Clientes
        </h2>
        <?php echo $mensaje; ?>
        <div class="card premium-card border-0 rounded-4 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="premium-muted small fw-bold">
                            <th class="ps-4">USUARIO</th>
                            <th>EMAIL</th>
                            <th>ROL</th>
                            <th class="text-end pe-4">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold premium-text">
                                    <?php echo htmlspecialchars($u['nombre']); ?>
                                </td>
                                <td class="premium-muted">
                                    <?php echo $u['email']; ?>
                                </td>
                                <td><span class="badge bg-secondary rounded-pill">
                                        <?php echo $u['rol']; ?>
                                    </span></td>
                                <td class="text-end pe-4">
                                    <form action="admin_usuarios.php" method="POST"
                                        class="d-flex justify-content-end gap-1">
                                        <input type="hidden" name="id_usuario" value="<?php echo $u['id']; ?>">
                                        <select name="nuevo_rol" class="form-select form-select-sm premium-input w-auto">
                                            <option value="cliente">Cliente</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                        <button type="submit" name="cambiar_rol" class="btn btn-sm btn-primary"><i
                                                class="bi bi-arrow-repeat"></i></button>
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