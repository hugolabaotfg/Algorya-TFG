<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje = '';
$producto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $id = (int) $_POST['id'];
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $precio = (float) $_POST['precio'];
    $stock = (int) $_POST['stock'];
    $imagen_actual = $conn->real_escape_string($_POST['imagen_actual']);

    $nueva_imagen = $imagen_actual;

    if (isset($_FILES['foto_nueva']) && $_FILES['foto_nueva']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['foto_nueva']['tmp_name'];
        $file_name = $_FILES['foto_nueva']['name'];
        $file_type = $_FILES['foto_nueva']['type'];

        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($file_type, $allowed_types)) {
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            // Nombre único para no sobreescribir
            $nueva_imagen = 'prod_' . $id . '_' . time() . '.' . $file_ext;
            $dest_path = 'img/' . $nueva_imagen;

            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $img_vieja_path = "img/" . $imagen_actual;
                if (file_exists($img_vieja_path) && !empty($imagen_actual) && $imagen_actual !== 'default.jpg') {
                    unlink($img_vieja_path);
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Error de permisos al guardar la imagen en /img/</div>";
                $nueva_imagen = $imagen_actual; // Revertir si falla
            }
        } else {
            $mensaje = "<div class='alert alert-warning'>Formato no permitido. Usa JPG, PNG o WEBP.</div>";
        }
    }

    // CORREGIDO: Sintaxis SQL limpia sin comillas extra
    $sql_update = "UPDATE productos SET nombre = '$nombre', precio = $precio, stock = $stock, imagen = '$nueva_imagen' WHERE id = $id";

    if ($conn->query($sql_update)) {
        $mensaje = "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Producto actualizado. <a href='index.php' class='alert-link'>Volver</a></div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error SQL: " . $conn->error . "</div>";
    }
    $producto_id = $id;
}

$sql = "SELECT * FROM productos WHERE id = $producto_id";
$resultado = $conn->query($sql);
if ($resultado->num_rows === 0) {
    die("Producto no encontrado.");
}
$producto = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Editar Producto | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php" style="letter-spacing: -1px;">
                <i class="bi bi-box-seam-fill text-primary me-1"></i>
                <span class="text-primary">Algorya</span><span class="premium-text"
                    style="font-size: 0.55em;">.Admin</span>
            </a>
            <div class="d-flex gap-3">
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card premium-card border-0 rounded-4 p-4">
                    <h2 class="fw-bold premium-text mb-4"><i class="bi bi-pencil-square text-warning me-2"></i> Editar
                        Producto</h2>
                    <?php echo $mensaje; ?>

                    <form action="admin_edit_product.php?id=<?php echo $producto['id']; ?>" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                        <input type="hidden" name="imagen_actual" value="<?php echo $producto['imagen']; ?>">

                        <div class="row align-items-center mb-4">
                            <div class="col-md-4 text-center">
                                <div class="premium-img-wrapper rounded-4 border p-2">
                                    <img src="img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="Foto"
                                        style="height: 120px; object-fit: contain;">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label premium-muted fw-bold small">CAMBIAR FOTO</label>
                                <input type="file" name="foto_nueva" class="form-control premium-input"
                                    accept="image/*">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label premium-muted fw-bold small">NOMBRE</label>
                            <input type="text" name="nombre" class="form-control premium-input"
                                value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label premium-muted fw-bold small">PRECIO (€)</label>
                                <input type="number" step="0.01" name="precio" class="form-control premium-input"
                                    value="<?php echo $producto['precio']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label premium-muted fw-bold small">STOCK</label>
                                <input type="number" name="stock" class="form-control premium-input"
                                    value="<?php echo $producto['stock']; ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="actualizar"
                            class="btn btn-warning w-100 rounded-pill fw-bold">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>