<?php
// ==============================================================================
// Script de Ingesta de Datos (API REST) 
// Descripción: Descarga catálogo, procesa JSON, guarda imágenes y actualiza BBDD.
// ==============================================================================

require __DIR__ . '/includes/db.php';

echo "[".date("Y-m-d H:i:s")."] Iniciando sincronización con el proveedor externo...\n";

// 1. Conexión al Endpoint de la API
$api_url = "https://fakestoreapi.com/products";
$json_data = @file_get_contents($api_url);

if ($json_data === FALSE) {
    die("Error: No se pudo establecer conexión con la API externa.\n");
}

// 2. Decodificar el JSON a un array de PHP
$productos = json_decode($json_data, true);
$nuevos = 0;
$actualizados = 0;

foreach ($productos as $item) {
    // Saneamiento de datos para evitar Inyección SQL
    $nombre = $conn->real_escape_string($item['title']);
    // Acortamos la descripción para que las tarjetas de la web no se deformen
    $descripcion = $conn->real_escape_string(substr($item['description'], 0, 200) . "..."); 
    $precio = (float)$item['price'];
    $imagen_url = $item['image'];
    
    // Simulamos un stock aleatorio ya que esta API no gestiona inventario
    $stock_simulado = rand(5, 50);

    // 3. Gestión de Activos (Descarga de imágenes)
    $imagen_nombre = "api_" . $item['id'] . ".jpg";
    $ruta_imagen = __DIR__ . "/img/" . $imagen_nombre;
    
    // Si no tenemos la foto descargada, la traemos del proveedor
    if (!file_exists($ruta_imagen)) {
        $img_content = @file_get_contents($imagen_url);
        if ($img_content) {
            file_put_contents($ruta_imagen, $img_content);
        } else {
            $imagen_nombre = "default.jpg"; // Fallback por si falla la descarga
        }
    }

    // 4. Lógica de Inserción/Actualización (Upsert)
    $sql_check = "SELECT id FROM productos WHERE nombre = '$nombre'";
    $check = $conn->query($sql_check);
    
    if ($check && $check->num_rows > 0) {
        // Si el producto ya existe, solo actualizamos precio y stock (Mantenimiento)
        $conn->query("UPDATE productos SET precio = $precio, stock = $stock_simulado, imagen = '$imagen_nombre' WHERE nombre = '$nombre'");
        $actualizados++;
    } else {
        // Si es nuevo, lo insertamos en el catálogo
        $sql_insert = "INSERT INTO productos (nombre, descripcion, precio, stock, imagen) 
                       VALUES ('$nombre', '$descripcion', $precio, $stock_simulado, '$imagen_nombre')";
        $conn->query($sql_insert);
        $nuevos++;
    }
}

echo "[".date("Y-m-d H:i:s")."] Sincronización completada con éxito.\n";
echo " -> Productos nuevos añadidos: $nuevos\n";
echo " -> Productos actualizados: $actualizados\n";
?>
