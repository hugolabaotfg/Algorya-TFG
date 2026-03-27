<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$id = $_GET['id'];

$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();

if (!$producto) {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $desc = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    
    $nombre_imagen = $producto['imagen']; 
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $directorio = "img/";
        $nombre_archivo = "producto_" . uniqid() . "_" . basename($_FILES["imagen"]["name"]);
        
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $directorio . $nombre_archivo)) {
            $nombre_imagen = $nombre_archivo;
        }
    }

    $sql_update = "UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, imagen=? WHERE id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssdisi", $nombre, $desc, $precio, $stock, $nombre_imagen, $id);

    if ($stmt_update->execute()) {
        $mensaje = "Producto actualizado correctamente.";
        $tipo_mensaje = "success";
        $producto['nombre'] = $nombre;
        $producto['descripcion'] = $desc;
        $producto['precio'] = $precio;
        $producto['stock'] = $stock;
        $producto['imagen'] = $nombre_imagen;
    } else {
        $mensaje = "Error al actualizar.";
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto | Algorya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f9f9f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; }
        .navbar { background: #fff !important; border-bottom: 1px solid #eaeaea; }
        .navbar-brand { font-weight: 800; color: #111 !important; }
        .admin-card { background: #fff; border: 1px solid #eaeaea; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; }
        .admin-card-header { padding: 25px 30px; border-bottom: 1px solid #eaeaea; background: #fff; }
        .form-control, .input-group-text { border-radius: 8px; border-color: #e2e2e2; background-color: #fafafa; }
        .form-control:focus { border-color: #111; box-shadow: none; background-color: #fff; }
        .btn-dark { border-radius: 30px; font-weight: 600; padding: 10px 24px; }
        .img-preview { width: 80px; height: 80px; object-fit: contain; border: 1px solid #eaeaea; border-radius: 8px; padding: 5px; background: #fff; }
    </style>
</head>
<body>

    <nav class="navbar navbar-light sticky-top shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-bag-check-fill"></i> Algorya</a>
            <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-x-lg"></i> Cerrar Panel</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                
                <div class="admin-card">
                    <div class="admin-card-header d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold m-0 text-dark">Editar Producto</h4>
                        <span class="badge bg-light text-dark border">ID: #<?php echo $id; ?></span>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?> border-0 rounded-3 mb-4" role="alert">
                                <i class="bi <?php echo $tipo_mensaje == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i> <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Nombre</label>
                                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold small text-muted">Precio</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="precio" class="form-control border-end-0" value="<?php echo $producto['precio']; ?>" required>
                                        <span class="input-group-text bg-transparent border-start-0 text-muted">€</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold small text-muted">Stock</label>
                                    <input type="number" name="stock" class="form-control" value="<?php echo $producto['stock']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-5 p-3 bg-light rounded-3 border">
                                <label class="form-label fw-bold small text-muted d-block mb-3">Fotografía Actual</label>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <img src="img/<?php echo $producto['imagen']; ?>" class="img-preview" alt="Miniatura">
                                    <div class="small text-muted">
                                        Dejar vacío para mantener la imagen actual.<br>
                                        Subir nueva para reemplazar.
                                    </div>
                                </div>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-3 shadow-sm">Aplicar Cambios</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>