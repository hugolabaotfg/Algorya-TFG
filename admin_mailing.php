<?php
session_start();
require 'includes/db.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}
$usuarios = $conn->query("SELECT email, nombre FROM usuarios WHERE rol != 'admin'");
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Mailing | Algorya Admin</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card premium-card border-0 rounded-4 p-5 shadow-sm">
                    <h3 class="fw-bold premium-text mb-4"><i class="bi bi-envelope-at text-primary me-2"></i> Enviar
                        Comunicación</h3>
                    <form action="admin_mailing.php" method="POST">
                        <div class="mb-3"><label class="form-label fw-bold">DESTINATARIO</label><select name="dest"
                                class="form-select premium-input" required>
                                <option value="">Seleccionar...</option>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo $u['email']; ?>">
                                        <?php echo $u['nombre']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select></div>
                        <div class="mb-3"><label class="form-label fw-bold">ASUNTO</label><input type="text"
                                name="asunto" class="form-control premium-input" required></div>
                        <div class="mb-4"><label class="form-label fw-bold">MENSAJE</label><textarea name="msg"
                                class="form-control premium-input" rows="5" required></textarea></div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">ENVIAR
                            EMAIL</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>