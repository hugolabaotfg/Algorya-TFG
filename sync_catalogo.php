<?php
require_once 'includes/db.php';
// Incluimos la función de log (Mejora 3) que definiremos más abajo
require_once 'includes/logger.php';

// Mejora 1: Asumimos que la URL viene de una constante o configuración
$api_url = defined('API_URL') ? API_URL : "https://fakestoreapi.com/products";
$json_data = file_get_contents($api_url);
$items = json_decode($json_data, true);

if (!$items) {
    logSync("Error: No se pudo conectar a la API o el JSON es inválido.");
    exit("Error conectando a la API.");
}

// Preparar las sentencias una sola vez fuera del bucle (Optimización de rendimiento)
$stmt_check = $conn->prepare("SELECT id FROM productos WHERE api_id = ?");
$stmt_update = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ? WHERE api_id = ?");
$stmt_insert = $conn->prepare("INSERT INTO productos (api_id, nombre, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)");

$nuevos = 0;
$actualizados = 0;

foreach ($items as $item) {
    $api_id = (int) $item['id'];
    $nombre = $item['title'];
    $precio = (float) $item['price'];
    $stock_simulado = rand(10, 50); // Simulación de stock

    // Error 4: Truncado condicional seguro para caracteres multibyte (acentos, eñes)
    $descripcion_raw = $item['description'];
    $descripcion = (mb_strlen($descripcion_raw) > 200) ? mb_substr($descripcion_raw, 0, 200) . "..." : $descripcion_raw;

    // Advertencia 3: Validación real de imagen
    $imagen_url = $item['image'];
    $imagen_nombre = 'default.jpg';
    $img_content = @file_get_contents($imagen_url);

    if ($img_content !== false) {
        $img_info = @getimagesizefromstring($img_content);
        // Verificamos que sea realmente una imagen
        if ($img_info !== false && in_array($img_info['mime'], ['image/jpeg', 'image/png', 'image/webp'])) {
            $imagen_nombre = 'prod_' . $api_id . '.jpg';
            file_put_contents('assets/img/productos/' . $imagen_nombre, $img_content);
        }
    }

    // Advertencia 1 & Error 1: Upsert basado en api_id con Prepared Statements
    $stmt_check->bind_param("i", $api_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Existe, actualizamos
        $stmt_update->bind_param("ssdisi", $nombre, $descripcion, $precio, $stock_simulado, $imagen_nombre, $api_id);
        $stmt_update->execute();
        $actualizados++;
    } else {
        // No existe, insertamos
        $stmt_insert->bind_param("issdis", $api_id, $nombre, $descripcion, $precio, $stock_simulado, $imagen_nombre);
        $stmt_insert->execute();
        $nuevos++;
    }
}

$stmt_check->close();
$stmt_update->close();
$stmt_insert->close();

logSync("Sincronización completada. Nuevos: $nuevos. Actualizados: $actualizados.");
echo "Sincronización completada con éxito.";
?>