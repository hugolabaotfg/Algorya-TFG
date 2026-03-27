<?php
// =============================================================================
// ALGORYA - admin_edit_product.php
// Edición de productos del catálogo.
// CORRECCIÓN: Se añade el bloque PHP de procesamiento POST que faltaba
// completamente — el formulario enviaba datos pero nadie los procesaba.
// =============================================================================

session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET['id'];
$mensaje     = '';
$tipo_alerta = '';

// =============================================================================
// PROCESAR EL FORMULARIO CUANDO SE ENVÍA (POST)
// Este bloque es el que FALTABA completamente en la versión anterior
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {

    $nombre = trim($_POST['nombre']);
    $precio = (float) $_POST['precio'];
    $stock  = (int)   $_POST['stock'];
    $id_post = (int)  $_POST['id'];

    // Obtener la imagen actual para no perderla si no se sube una nueva
    $stmt_img = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt_img->bind_param("i", $id_post);
    $stmt_img->execute();
    $imagen_actual = $stmt_img->get_result()->fetch_assoc()['imagen'];
    $stmt_img->close();

    $imagen_final = $imagen_actual; // Por defecto mantenemos la imagen actual

    // Si se ha subido una nueva imagen, procesarla
    if (isset($_FILES['foto_nueva']) && $_FILES['foto_nueva']['error'] === UPLOAD_ERR_OK) {
        $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['foto_nueva']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $ext_permitidas)) {
            $nombre_nuevo = 'prod_' . $id_post . '_' . time() . '.' . $ext;
            $ruta_destino = __DIR__ . '/img/' . $nombre_nuevo;

            if (move_uploaded_file($_FILES['foto_nueva']['tmp_name'], $ruta_destino)) {
                // Borrar imagen antigua si existe y no es default.jpg
                if ($imagen_actual && $imagen_actual !== 'default.jpg') {
                    $ruta_antigua = __DIR__ . '/img/' . $imagen_actual;
                    if (file_exists($ruta_antigua)) {
                        @unlink($ruta_antigua);
                    }
                }
                $imagen_final = $nombre_nuevo;
            }
        }
    }

    // Ejecutar UPDATE con prepared statement
    $stmt = $conn->prepare(
        "UPDATE productos SET nombre = ?, precio = ?, stock = ?, imagen = ? WHERE id = ?"
    );
    $stmt->bind_param("sdisi", $nombre, $precio, $stock, $imagen_final, $id_post);

    if ($stmt->execute()) {
        $mensaje     = "Producto actualizado correctamente.";
        $tipo_alerta = "success";
        // Recargar los datos actualizados para mostrarlos en el formulario
        $id = $id_post;
    } else {
        $mensaje     = "Error al actualizar: " . $conn->error;
        $tipo_alerta = "danger";
    }
    $stmt->close();
}

// Cargar datos actuales del producto (siempre, para rellenar el formulario)
$stmt_load = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt_load->bind_param("i", $id);
$stmt_load->execute();
$p = $stmt_load->get_result()->fetch_assoc();
$stmt_load->close();

if (!$p) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php">
            <span class="text-primary">Algorya</span><span class="premium-text" style="font-size:0.55em;">.Admin</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-primary btn-sm rounded-pill dropdown-toggle px-3"
                        type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear-fill me-1"></i> Gestión
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-card border-0 shadow-lg mt-2 py-2">
                    <li><a class="dropdown-item premium-text py-2" href="admin_pedidos.php"><i class="bi bi-cart-check me-2"></i>Pedidos</a></li>
                    <li><a class="dropdown-item premium-text py-2" href="admin_usuarios.php"><i class="bi bi-people me-2"></i>Clientes</a></li>
                    <li><a class="dropdown-item premium-text py-2" href="admin_estadisticas.php"><i class="bi bi-bar-chart me-2"></i>Estadísticas</a></li>
                    <li><hr class="dropdown-divider" style="border-color:var(--border-color);"></li>
                    <li><a class="dropdown-item premium-text py-2" href="index.php"><i class="bi bi-arrow-left me-2"></i>Volver al catálogo</a></li>
                </ul>
            </div>
            <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
        </div>
    </div>
</nav>

<div class="container mt-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card premium-card border-0 rounded-4 p-4 shadow-sm">

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 class="fw-bold premium-text m-0">
                        <i class="bi bi-pencil-square text-warning me-2"></i>Editar Producto
                    </h3>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_alerta ?> border-0 rounded-3 alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $tipo_alerta === 'success' ? 'check-circle' : 'x-circle' ?>-fill me-2"></i>
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form action="admin_edit_product.php?id=<?= $id ?>" method="POST"
                      enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label class="form-label fw-bold premium-muted small text-uppercase" style="letter-spacing:.4px;">
                            Nombre del producto
                        </label>
                        <input type="text" name="nombre"
                               class="form-control premium-input shadow-none"
                               value="<?= htmlspecialchars($p['nombre']) ?>" required>
                    </div>

                    <!-- Precio y Stock -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold premium-muted small text-uppercase" style="letter-spacing:.4px;">
                                Precio (€)
                            </label>
                            <input type="number" step="0.01" min="0" name="precio"
                                   class="form-control premium-input shadow-none"
                                   value="<?= number_format((float)$p['precio'], 2, '.', '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold premium-muted small text-uppercase" style="letter-spacing:.4px;">
                                Stock (uds.)
                            </label>
                            <input type="number" min="0" name="stock"
                                   class="form-control premium-input shadow-none"
                                   value="<?= (int)$p['stock'] ?>" required>
                        </div>
                    </div>

                    <!-- Imagen actual + nueva -->
                    <div class="mb-4">
                        <label class="form-label fw-bold premium-muted small text-uppercase" style="letter-spacing:.4px;">
                            Imagen actual
                        </label>
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2"
                             style="border:1px solid var(--border-color);">
                            <img src="img/<?= htmlspecialchars($p['imagen']) ?>"
                                 style="height:60px;width:60px;object-fit:contain;"
                                 onerror="this.src='https://dummyimage.com/60x60/dee2e6/6c757d.jpg&text=?'">
                            <span class="premium-muted small"><?= htmlspecialchars($p['imagen']) ?></span>
                        </div>
                        <label class="form-label fw-bold premium-muted small text-uppercase" style="letter-spacing:.4px;">
                            Subir nueva imagen (opcional)
                        </label>
                        <input type="file" name="foto_nueva" accept="image/*"
                               class="form-control premium-input shadow-none">
                        <div class="form-text premium-muted">
                            Si no subes imagen, se mantiene la actual.
                        </div>
                    </div>

                    <button type="submit" name="actualizar"
                            class="btn btn-warning w-100 rounded-pill py-2 fw-bold">
                        <i class="bi bi-check-lg me-2"></i>GUARDAR CAMBIOS
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="tema.js"></script>
</body>
</html>