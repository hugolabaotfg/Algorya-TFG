<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit();
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Permisos insuficientes.']);
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['action']) || $_POST['action'] !== 'delete') {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
    exit();
}

$producto_id = (int) $conn->real_escape_string($_POST['id']);

$sql_img = "SELECT imagen FROM productos WHERE id = $producto_id";
$res_img = $conn->query($sql_img);

if ($res_img && $res_img->num_rows > 0) {
    $img_name = $res_img->fetch_assoc()['imagen'];

    $sql_del = "DELETE FROM productos WHERE id = $producto_id";
    if ($conn->query($sql_del)) {
        // Borramos la imagen de la carpeta img (../ porque estamos dentro de includes)
        $img_path = "../img/" . $img_name;
        if (file_exists($img_path) && !empty($img_name) && $img_name !== 'default.jpg') {
            unlink($img_path);
        }
        echo json_encode(['status' => 'success', 'message' => 'Producto eliminado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado.']);
}
?>