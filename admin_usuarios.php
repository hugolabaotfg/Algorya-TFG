<?php
session_start();
require 'includes/db.php';

// Seguridad: Solo el administrador puede entrar aquí
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje = '';

// === LÓGICA DE ACTUALIZACIÓN DE ROL ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $id_usuario = (int) $_POST['id_usuario'];
    $nuevo_rol = $conn->real_escape_string($_POST['nuevo_rol']);

    // Medida de seguridad: El admin no puede quitarse los permisos a sí mismo por error
    if ($id_usuario === (int) $_SESSION['user_id']) {
        $mensaje = "<div class='alert alert-warning'><i class='bi bi-exclamation-triangle-fill me-2'></i>No puedes cambiar tu propio rol por seguridad.</div>";
    } else {
        $sql_update = "UPDATE usuarios SET rol = '$nuevo_rol' WHERE id = $id_usuario";
        if ($conn->query($sql_update)) {
            $mensaje = "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Privilegios actualizados correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error SQL: " . $conn->error . "</div>";
        }
    }
}

// === LÓGICA DE BORRADO DE USUARIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar_usuario'])) {
    $id_usuario = (int) $_POST['id_usuario'];

    if ($id_usuario === (int) $_SESSION['user_id']) {
        $mensaje = "<div class='alert alert-danger'><i class='bi bi-shield-x me-2'></i>Operación denegada. No puedes auto-eliminarte.</div>";
    } else {
        $sql_delete = "DELETE FROM usuarios WHERE id = $id_usuario";
        if ($conn->query($sql_delete)) {
            $mensaje = "<div class='alert alert-success'><i class='bi bi-trash-fill me-2'></i>Usuario eliminado del sistema.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al borrar: " . $conn->error . "</div>";
        }
    }
}

// Extraemos todos los usuarios (los más nuevos primero)
$sql = "SELECT id, nombre, email, rol, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
    <style>
        .table-premium {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            vertical-align: middle;
        }

        .table-premium th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
        }

        .table-premium td {
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0.5rem;
        }

        .badge-admin {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .badge-cliente {
            background-color: rgba(100, 116, 139, 0.1);
            color: var(--text-muted);
            border: 1px solid rgba(100, 116, 139, 0.2);
        }
    </style>
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
                <a href="admin_estadisticas.php" class="btn btn-link premium-text text-decoration-none">Dashboard</a>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill">Ir a la Tienda</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-2"
            style="border-color: var(--border-color) !important;">
            <h2 class="fw-bold premium-text m-0"><i class="bi bi-people-fill text-primary me-2"></i> Gestión de Clientes
            </h2>
            <span class="premium-muted small fw-medium">Total:
                <?php echo $resultado->num_rows; ?> usuarios
            </span>
        </div>

        <?php echo $mensaje; ?>

        <div class="card premium-card border-0 rounded-4 overflow-hidden mb-5">
            <div class="table-responsive">
                <table class="table table-premium table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Usuario</th>
                            <th>Correo Electrónico</th>
                            <th>Fecha Registro</th>
                            <th>Privilegios</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while ($row = $resultado->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold premium-muted">#
                                        <?php echo $row['id']; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                <?php echo strtoupper(substr($row['nombre'], 0, 1)); ?>
                                            </div>
                                            <span class="fw-bold premium-text">
                                                <?php echo htmlspecialchars($row['nombre']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="premium-muted">
                                        <?php echo htmlspecialchars($row['email']); ?>
                                    </td>
                                    <td class="premium-muted small">
                                        <?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($row['rol'] === 'admin'): ?>
                                            <span class="badge badge-admin rounded-pill px-3 py-2"><i
                                                    class="bi bi-shield-lock-fill me-1"></i> Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-cliente rounded-pill px-3 py-2"><i
                                                    class="bi bi-person-fill me-1"></i> Cliente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="admin_usuarios.php" method="POST"
                                            class="d-flex justify-content-end gap-2 m-0"
                                            onsubmit="return confirm('¿Aplicar cambios a este usuario?');">
                                            <input type="hidden" name="id_usuario" value="<?php echo $row['id']; ?>">

                                            <select name="nuevo_rol"
                                                class="form-select form-select-sm premium-input shadow-none d-inline-block w-auto"
                                                style="border-radius: 8px;">
                                                <option value="cliente" <?php echo ($row['rol'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                                                <option value="admin" <?php echo ($row['rol'] === 'admin') ? 'selected' : ''; ?>
                                                    >Admin</option>
                                            </select>

                                            <button type="submit" name="cambiar_rol" class="btn btn-sm btn-outline-primary"
                                                title="Actualizar Rol" style="border-radius: 8px;">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>

                                            <button type="submit" name="borrar_usuario" class="btn btn-sm btn-outline-danger"
                                                title="Eliminar Cuenta" style="border-radius: 8px;" <?php echo ($row['id'] === $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 premium-muted">No hay usuarios registrados en el
                                    sistema.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>