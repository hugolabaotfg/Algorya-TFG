<?php
// sync_api.php
require 'includes/db.php';

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h2>🔄 Iniciando sincronización con el Proveedor Dropshipping...</h2>";

// 1. CONEXIÓN A LA API EXTERNA (Usamos cURL, el estándar de Linux)
$url_api = "https://fakestoreapi.com/products?limit=6"; // Pedimos 6 productos para probar
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Que nos devuelva los datos, no los imprima
$respuesta_json = curl_exec($ch);
curl_close($ch);

if (!$respuesta_json) {
    die("❌ Error fatal: No se pudo conectar con la API del proveedor.");
}

// 2. TRADUCCIÓN DEL IDIOMA JSON A ARRAY PHP
$productos_externos = json_decode($respuesta_json, true);
// ELIMINAMOS TODOS LOS PRODUCTOS ANTIGUOS
$conn->query("TRUNCATE TABLE productos");

// (Opcional nivel Dios de ASIR: borrar también las imágenes de la carpeta img/ para que el servidor no explote de memoria tras unos meses)
$files = glob('img/prod_*'); // busca todas las fotos descargadas
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file); // las borra físicamente
    }
}

// A PARTIR DE AQUÍ COMIENZAS A INSERTAR LOS NUEVOS...
// 3. BUCLE MÁGICO: Procesar uno a uno
foreach ($productos_externos as $prod) {
    // Extraemos los datos del proveedor
    $nombre = $prod['title'];
    // Acortamos la descripción si es muy larga para que no rompa tu diseño
    $desc = substr($prod['description'], 0, 150) . "..."; 
    $precio = $prod['price'];
    $stock = rand(10, 50); // La API no nos da stock, así que lo simulamos
    $url_imagen_externa = $prod['image'];

    // 4. CONTROL DE DUPLICADOS (Para no meter el mismo producto 20 veces)
    $sql_check = "SELECT id FROM productos WHERE nombre = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $nombre);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();

    if ($resultado_check->num_rows == 0) {
        // EL PRODUCTO NO EXISTE -> LO AÑADIMOS
        
        // A) Descargar la imagen a nuestro servidor
        $contenido_imagen = file_get_contents($url_imagen_externa);
        $nombre_archivo_img = "api_" . uniqid() . ".jpg";
        $ruta_destino = "img/" . $nombre_archivo_img;
        
        // Guardamos el archivo físico en Linux
        file_put_contents($ruta_destino, $contenido_imagen);

        // B) Insertar en MariaDB
        $sql_insert = "INSERT INTO productos (nombre, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ssdis", $nombre, $desc, $precio, $stock, $nombre_archivo_img);
        
        if ($stmt_insert->execute()) {
            echo "<p style='color: green;'>✅ <b>Añadido:</b> $nombre</p>";
        } else {
            echo "<p style='color: red;'>❌ <b>Error BD:</b> " . $conn->error . "</p>";
        }
    } else {
        // EL PRODUCTO YA ESTÁ EN NUESTRA BASE DE DATOS
        echo "<p style='color: gray;'>⏭️ <b>Saltado (Ya existe):</b> $nombre</p>";
    }
}

echo "<h3>🎉 Sincronización completada con éxito.</h3>";
echo "<a href='index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ver Catálogo</a>";
echo "</div>";
?>