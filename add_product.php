<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $desc = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    
    $nombre_imagen = "default.jpg"; 
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $directorio = "img/";
        $nombre_archivo = "producto_" . uniqid() . "_" . basename($_FILES["imagen"]["name"]);
        $ruta_destino = $directorio . $nombre_archivo;
        
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $ruta_destino)) {
            $nombre_imagen = $nombre_archivo;
        } else {
            $mensaje = "Error al subir la imagen al servidor.";
            $tipo_mensaje = "danger";
        }
    }

    if (empty($mensaje)) {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdis", $nombre, $desc, $precio, $stock, $nombre_imagen);
        
        if ($stmt->execute()) {
            $mensaje = "¡Producto guardado en el catálogo!";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error de BD: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Producto | Algorya</title>
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
                        <h4 class="fw-bold m-0 text-dark">Nuevo Producto</h4>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?> border-0 rounded-3 mb-4" role="alert">
                                <i class="bi <?php echo $tipo_mensaje == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'; ?>"></i> <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Nombre del Producto</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold small text-muted">Precio</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="precio" class="form-control border-end-0" required>
                                        <span class="input-group-text bg-transparent border-start-0 text-muted">€</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold small text-muted">Stock Inicial</label>
                                    <input type="number" name="stock" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-bold small text-muted">Fotografía</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-3 shadow-sm">Guardar en Catálogo</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>