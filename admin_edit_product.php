<?php
session_start();
require 'includes/db.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}
$id = (int) $_GET['id'];
$p = $conn->query("SELECT * FROM productos WHERE id = $id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Editar | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php"><span
                    class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size: 0.55em;">.Admin</span></a>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3" type="button"
                        data-bs-toggle="dropdown"><i class="bi bi-gear-fill me-1"></i> Gestión</button>
                    <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow-lg mt-2 py-2">
                        <li><a class="dropdown-item premium-text py-2" href="admin_pedidos.php">Pedidos</a></li>
                        <li><a class="dropdown-item premium-text py-2" href="admin_usuarios.php">Clientes</a></li>
                    </ul>
                </div>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
            </div>
        </div>
    </nav>
    <div class="container mt-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card premium-card border-0 rounded-4 p-4 shadow-sm">
                    <h3 class="fw-bold premium-text mb-4">Editar Producto</h3>
                    <form action="admin_edit_product.php?id=<?php echo $id; ?>" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <div class="mb-3"><label class="fw-bold small">NOMBRE</label><input type="text" name="nombre"
                                class="form-control premium-input" value="<?php echo $p['nombre']; ?>" required></div>
                        <div class="row mb-3">
                            <div class="col"><label class="fw-bold small">PRECIO</label><input type="number" step="0.01"
                                    name="precio" class="form-control premium-input" value="<?php echo $p['precio']; ?>"
                                    required></div>
                            <div class="col"><label class="fw-bold small">STOCK</label><input type="number" name="stock"
                                    class="form-control premium-input" value="<?php echo $p['stock']; ?>" required>
                            </div>
                        </div>
                        <div class="mb-4"><label class="fw-bold small">FOTO NUEVA</label><input type="file"
                                name="foto_nueva" class="form-control premium-input"></div>
                        <button type="submit" name="actualizar"
                            class="btn btn-warning w-100 rounded-pill py-2 fw-bold">GUARDAR CAMBIOS</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>