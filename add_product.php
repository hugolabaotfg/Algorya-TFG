<?php
session_start();
require 'includes/db.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre']);
    $precio = (float) $_POST['precio'];
    $stock = (int) $_POST['stock'];

    // Lógica básica de subida de foto
    $foto = 'default.jpg';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = 'prod_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'img/' . $foto);
    }

    $stmt_ins = $conn->prepare("INSERT INTO productos (nombre, precio, stock, imagen) VALUES (?, ?, ?, ?)");
    $stmt_ins->bind_param("sdis", $nombre, $precio, $stock, $foto);
    $stmt_ins->execute();
    $stmt_ins->close();
    header("Location: index.php");
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Añadir | Algorya Admin</title>
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
                <div class="dropdown"><button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3"
                        type="button" data-bs-toggle="dropdown">Gestión</button>
                    <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow-lg mt-2 py-2">
                        <li><a class="dropdown-item premium-text py-2" href="admin_pedidos.php">Pedidos</a></li>
                    </ul>
                </div>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
            </div>
        </div>
    </nav>
    <div class="container mt-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card premium-card border-0 rounded-4 p-5 shadow-sm">
                    <h3 class="fw-bold premium-text mb-4"><i class="bi bi-plus-circle text-success me-2"></i> Nuevo
                        Producto</h3>
                    <form action="add_product.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3"><label class="fw-bold small">NOMBRE</label><input type="text" name="nombre"
                                class="form-control premium-input" required></div>
                        <div class="row mb-3">
                            <div class="col"><label class="fw-bold small">PRECIO</label><input type="number" step="0.01"
                                    name="precio" class="form-control premium-input" required></div>
                            <div class="col"><label class="fw-bold small">STOCK</label><input type="number" name="stock"
                                    class="form-control premium-input" required></div>
                        </div>
                        <div class="mb-4"><label class="fw-bold small">FOTO PRODUCTO</label><input type="file"
                                name="foto" class="form-control premium-input" required></div>
                        <button type="submit" name="guardar"
                            class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow-sm">PUBLICAR EN
                            CATÁLOGO</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>