<?php
// =============================================================================
// ALGORYA - includes/admin_delete_product_ajax.php
// Endpoint AJAX para eliminar productos del catálogo.
//
// Estrategia de borrado en dos niveles (igual que Amazon/Shopify):
//   1. Si el producto NO tiene pedidos asociados → borrado FÍSICO completo
//      (se elimina de la BD y se borra la imagen del disco)
//   2. Si el producto SÍ tiene pedidos asociados → borrado LÓGICO
//      (activo = 0: desaparece del catálogo pero el historial queda intacto)
//
// Esto es imprescindible para la integridad referencial de la BD y para
// cuando se integren proveedores reales — los pedidos históricos siempre
// deben poder consultarse aunque el producto ya no esté en venta.
// =============================================================================

session_start();
require __DIR__ . '/db.php';

header('Content-Type: application/json');

// Solo admins y solo peticiones AJAX
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit();
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['status' => 'error', 'message' => 'Peticion no valida.']);
    exit();
}

$id     = (int)($_POST['id']     ?? 0);
$accion = $_POST['action'] ?? '';

if ($id <= 0 || $accion !== 'delete') {
    echo json_encode(['status' => 'error', 'message' => 'Parametros incorrectos.']);
    exit();
}

// Obtener datos del producto
$stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado.']);
    exit();
}

// Comprobar si tiene pedidos asociados en lineas_pedido
$stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM lineas_pedido WHERE producto_id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$tiene_pedidos = $stmt_check->get_result()->fetch_assoc()['total'] > 0;
$stmt_check->close();

if ($tiene_pedidos) {
    // ─────────────────────────────────────────────────────────────────────────
    // BORRADO LÓGICO: el producto tiene historial de ventas
    // Lo marcamos como inactivo (activo = 0) — desaparece del catálogo
    // pero los pedidos históricos siguen referenciándolo correctamente
    // ─────────────────────────────────────────────────────────────────────────
    $stmt_soft = $conn->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    $stmt_soft->bind_param("i", $id);

    if ($stmt_soft->execute()) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Producto desactivado del catálogo (tiene pedidos asociados).',
            'tipo'    => 'logico'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al desactivar: ' . $conn->error]);
    }
    $stmt_soft->close();

} else {
    // ─────────────────────────────────────────────────────────────────────────
    // BORRADO FÍSICO: el producto no tiene historial de ventas
    // Se elimina completamente de la BD y su imagen del disco
    // ─────────────────────────────────────────────────────────────────────────
    $stmt_del = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt_del->bind_param("i", $id);

    if ($stmt_del->execute()) {
        // Borrar imagen física si no es default.jpg
        $imagen = $producto['imagen'];
        if ($imagen && $imagen !== 'default.jpg') {
            $ruta = dirname(__DIR__) . '/img/' . $imagen;
            if (file_exists($ruta)) @unlink($ruta);
        }
        echo json_encode([
            'status'  => 'success',
            'message' => 'Producto eliminado permanentemente.',
            'tipo'    => 'fisico'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $conn->error]);
    }
    $stmt_del->close();
}