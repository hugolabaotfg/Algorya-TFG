<?php
// =============================================================================
// ALGORYA - sync_catalogo.php
// Motor de Sincronización de Catálogo Dropshipping
//
// Ejecutar desde CLI:  php /var/www/tfg/sync_catalogo.php
// Cron (cada dia a las 3 AM): 0 3 * * * /usr/bin/php /var/www/tfg/sync_catalogo.php >> /var/log/tfg_sync.log 2>&1
//
// Fuentes de datos (APIs gratuitas, sin clave):
//   1. FakeStoreAPI   — 20 productos (ropa, electrónica, joyería)
//   2. DummyJSON      — 100 productos (variedad amplia, paginados)
//   3. OpenFoodFacts  — productos de alimentación (complementario)
//
// Algoritmo de Tendencias FOMO:
//   Cada producto recibe una puntuacion de tendencia (trend_score) calculada
//   en base a: categoria de alta demanda, rango de precio "impulso" y
//   simulacion de ventas recientes. Los productos con mayor score se marcan
//   como destacados (destacado = 1) y aparecen primeros en el catálogo.
// =============================================================================

// Solo ejecucion desde CLI para evitar ejecucion accidental desde el navegador
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Este script solo puede ejecutarse desde la linea de comandos.\n");
}

require __DIR__ . '/includes/db.php';

// =============================================================================
// CONFIGURACION
// =============================================================================

// Directorio donde se guardan las imagenes (ruta absoluta para evitar errores)
define('IMG_DIR', __DIR__ . '/img/');

// Fichero de log dentro del propio proyecto (sin necesitar permisos de root)
define('LOG_FILE', __DIR__ . '/logs/sync.log');

// Numero minimo de productos que queremos tener en el catalogo (8 paginas x 12)
define('MIN_PRODUCTOS', 96);

// Categorias de alta demanda segun tendencias de mercado 2025/2026
// Estas categorias reciben un bonus en el algoritmo de tendencias
define('CATEGORIAS_TENDENCIA', [
    "electronics"        => 35,  // Electronica siempre en tendencia
    "smartphones"        => 40,  // Moviles: maxima tendencia
    "laptops"            => 38,
    "mens-clothing"      => 20,
    "womens-clothing"    => 25,
    "jewelery"           => 15,
    "beauty"             => 30,  // Belleza/cosmética: categoria en auge
    "fragrances"         => 22,
    "home-decoration"    => 28,
    "sports-accessories" => 32,
    "sunglasses"         => 18,
    "tops"               => 20,
    "dresses"            => 22,
]);

// =============================================================================
// FUNCION DE LOG — Registra cada accion con timestamp
// =============================================================================
function log_msg(string $msg): void {
    $linea = "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n";
    echo $linea;
    @file_put_contents(LOG_FILE, $linea, FILE_APPEND);
}

// =============================================================================
// FUNCION: Descarga segura de imagen externa al servidor local
// Retorna el nombre del archivo guardado o 'default.jpg' si falla
// =============================================================================
function descargar_imagen(string $url, string $prefijo): string {
    if (empty($url)) return 'default.jpg';

    // Descargamos primero para detectar el Content-Type real y la extensión correcta
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Algorya-Bot/1.0',
    ]);
    $contenido = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (!$contenido || $http_code !== 200 || strlen($contenido) < 500) {
        return 'default.jpg';
    }

    // Detectar extensión real según Content-Type
    $ext = 'jpg'; // fallback
    if (str_contains($content_type, 'png'))  $ext = 'png';
    elseif (str_contains($content_type, 'webp')) $ext = 'webp';
    elseif (str_contains($content_type, 'gif'))  $ext = 'gif';
    elseif (str_contains($content_type, 'svg'))  $ext = 'svg';
    else {
        // Si el Content-Type no es claro, intentar detectar por la URL
        $url_lower = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        if (str_ends_with($url_lower, '.png'))  $ext = 'png';
        elseif (str_ends_with($url_lower, '.webp')) $ext = 'webp';
        elseif (str_ends_with($url_lower, '.gif'))  $ext = 'gif';
        elseif (str_ends_with($url_lower, '.svg'))  $ext = 'svg';
    }

    $nombre_archivo = $prefijo . '_' . substr(md5($url), 0, 12) . '.' . $ext;
    $ruta_destino   = IMG_DIR . $nombre_archivo;

    // Si ya existe con tamaño correcto, no re-descargar
    if (file_exists($ruta_destino) && filesize($ruta_destino) > 500) {
        return $nombre_archivo;
    }

    // Guardar el archivo
    if (file_put_contents($ruta_destino, $contenido) !== false) {
        return $nombre_archivo;
    }

    return 'default.jpg';
}

// =============================================================================
// ALGORITMO DE TENDENCIAS FOMO
//
// Calcula una puntuacion de 0-100 para cada producto.
// Esta puntuacion determina si el producto se marca como "destacado"
// y su posicion en el catalogo.
//
// Factores del algoritmo:
//   1. Categoria de alta demanda        → hasta 40 puntos
//   2. Rango de precio "impulso"        → hasta 25 puntos
//      (productos de 15-200€: precio optimo de compra impulsiva)
//   3. Simulacion de ventas recientes   → hasta 20 puntos
//      (basado en el rating y numero de reviews de la API)
//   4. Factor de escasez (stock bajo)   → hasta 15 puntos
//      (stock < 15 unidades genera urgencia FOMO)
// =============================================================================
function calcular_trend_score(string $categoria, float $precio, float $rating, int $num_reviews, int $stock): int {

    $score = 0;

    // FACTOR 1: Categoria de alta demanda
    $categorias = CATEGORIAS_TENDENCIA;
    $cat_lower   = strtolower($categoria);
    foreach ($categorias as $cat_key => $bonus) {
        if (str_contains($cat_lower, $cat_key)) {
            $score += $bonus;
            break;
        }
    }
    // Bonus base para cualquier categoria aunque no este en la lista
    if ($score === 0) $score += 10;

    // FACTOR 2: Rango de precio optimo para compra impulsiva
    if ($precio >= 15 && $precio <= 50)   $score += 25; // Precio de impulso bajo
    elseif ($precio > 50 && $precio <= 150) $score += 20; // Precio medio
    elseif ($precio > 150 && $precio <= 300) $score += 12; // Precio alto
    elseif ($precio < 15)                 $score += 15; // Muy barato (atrae clics)
    else                                  $score += 5;  // Precio premium

    // FACTOR 3: Popularidad (rating + num_reviews simula ventas recientes)
    // Rating de 4.5+ con mas de 200 reviews = producto validado por el mercado
    if ($rating >= 4.5 && $num_reviews >= 200) $score += 20;
    elseif ($rating >= 4.0 && $num_reviews >= 100) $score += 15;
    elseif ($rating >= 3.5) $score += 8;
    else $score += 3;

    // FACTOR 4: Escasez simulada (FOMO - Fear Of Missing Out)
    // Stock bajo genera urgencia de compra
    if ($stock <= 5)  $score += 15; // Casi agotado
    elseif ($stock <= 10) $score += 10; // Stock bajo
    elseif ($stock <= 20) $score += 5;  // Stock moderado

    return min($score, 100); // Maximo 100 puntos
}

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// =============================================================================
// INICIO DEL SCRIPT
// =============================================================================

log_msg("============================================================");
log_msg("ALGORYA - Inicio de sincronizacion de catalogo");
log_msg("============================================================");

// Verificar que el directorio de imagenes existe y tiene permisos de escritura
if (!is_dir(IMG_DIR)) {
    log_msg("ERROR CRITICO: El directorio de imagenes no existe: " . IMG_DIR);
    exit(1);
}
if (!is_writable(IMG_DIR)) {
    log_msg("ERROR CRITICO: Sin permisos de escritura en: " . IMG_DIR);
    exit(1);
}

$total_nuevos       = 0;
$total_actualizados = 0;
$total_errores      = 0;
$todos_productos    = [];

// NOTA: La limpieza de productos anteriores se hace MÁS ABAJO,
// solo después de confirmar que las APIs han devuelto datos suficientes.
// Así nunca nos quedamos sin catálogo si una API falla.

// =============================================================================
// HANDLER DE ERRORES FATALES
// Si el script muere por un error no controlado, envía email al admin
// y registra el error en el log antes de terminar.
// =============================================================================
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = "Error fatal en sync_catalogo.php:\n";
        $msg .= "  Tipo:    " . $error['type'] . "\n";
        $msg .= "  Mensaje: " . $error['message'] . "\n";
        $msg .= "  Archivo: " . $error['file'] . " (linea " . $error['line'] . ")\n";
        $msg .= "\nEl catalogo puede haberse quedado vacio. Revisa la BD urgentemente.";

        @file_put_contents(LOG_FILE, "[" . date("Y-m-d H:i:s") . "] ERROR FATAL: " . $error['message'] . "\n", FILE_APPEND);

        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/includes/mailer.php';
            mail_alerta_sync($msg);
        }
    }
});

// =============================================================================
// FUENTE 1: FakeStoreAPI (20 productos — Ropa, electronica, joyeria)
// =============================================================================
log_msg("Conectando con FakeStoreAPI...");

$ch = curl_init("https://fakestoreapi.com/products");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Algorya-Bot/1.0',
]);
$json = curl_exec($ch);
curl_close($ch);

if ($json) {
    $productos = json_decode($json, true);
    if (is_array($productos) && count($productos) > 0) {
        foreach ($productos as $p) {
            $todos_productos[] = [
                'nombre'      => $p['title']       ?? 'Producto sin nombre',
                'descripcion' => substr($p['description'] ?? '', 0, 200),
                'precio'      => (float)($p['price'] ?? 9.99),
                'imagen_url'  => $p['image']        ?? '',
                'categoria'   => $p['category']     ?? 'general',
                'rating'      => (float)($p['rating']['rate']  ?? 3.5),
                'reviews'     => (int)($p['rating']['count']   ?? 50),
                'fuente'      => 'fakestore',
            ];
        }
        log_msg("FakeStoreAPI: " . count($productos) . " productos obtenidos.");
    } else {
        log_msg("AVISO: FakeStoreAPI devolvio respuesta invalida. Continuando con otras fuentes...");
    }
} else {
    log_msg("AVISO: FakeStoreAPI no respondio. Continuando con otras fuentes...");
}

// ─────────────────────────────────────────────────────────────────────────────
// AUTO-RECUPERACION: Si FakeStoreAPI fallo, usar API de respaldo
// para completar los 96 productos objetivo.
// ─────────────────────────────────────────────────────────────────────────────
if (count($todos_productos) === 0) {
    log_msg("Activando API de respaldo (Platzi Fake Store)...");
    $ch_backup = curl_init("https://api.escuelajs.co/api/v1/products?offset=0&limit=20");
    curl_setopt_array($ch_backup, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Algorya-Bot/1.0',
    ]);
    $json_backup = curl_exec($ch_backup);
    curl_close($ch_backup);

    if ($json_backup) {
        $productos_backup = json_decode($json_backup, true);
        if (is_array($productos_backup) && count($productos_backup) > 0) {
            foreach ($productos_backup as $p) {
                $imagenes = $p['images'] ?? [];
                $img_url  = !empty($imagenes) ? $imagenes[0] : '';
                // Platzi a veces devuelve URLs con comillas — limpiarlas
                $img_url  = trim($img_url, '"[]');
                $todos_productos[] = [
                    'nombre'      => $p['title']       ?? 'Producto sin nombre',
                    'descripcion' => substr($p['description'] ?? '', 0, 200),
                    'precio'      => (float)($p['price'] ?? 9.99),
                    'imagen_url'  => $img_url,
                    'categoria'   => $p['category']['name'] ?? 'general',
                    'rating'      => 4.0,
                    'reviews'     => rand(50, 300),
                    'fuente'      => 'fakestore',
                ];
            }
            log_msg("API respaldo: " . count($productos_backup) . " productos obtenidos.");

            // Notificar al admin que se usó el respaldo
            if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                require_once __DIR__ . '/includes/mailer.php';
                mail_alerta_sync(
                    "FakeStoreAPI no respondio. Se ha usado la API de respaldo automaticamente.\n" .
                    "El catalogo se ha sincronizado correctamente con " . count($todos_productos) . " productos.\n" .
                    "No se requiere accion inmediata."
                );
            }
        } else {
            log_msg("AVISO: API de respaldo tambien fallo. Solo se usara DummyJSON.");
        }
    }
}

// =============================================================================
// FUENTE 2: DummyJSON (100 productos en 5 paginas de 20 — Gran variedad)
// =============================================================================
log_msg("Conectando con DummyJSON...");

$dummyjson_total = 0;
// Necesitamos 96 productos totales (8 paginas x 12).
// FakeStoreAPI da 20, por lo que DummyJSON debe dar 76.
// Hacemos 3 paginas de 20 (60) + 1 pagina de 16 = 76 productos.
$dummyjson_config = [
    ['limit' => 20, 'skip' => 0],
    ['limit' => 20, 'skip' => 20],
    ['limit' => 20, 'skip' => 40],
    ['limit' => 16, 'skip' => 60],  // Ultima pagina: solo 16 para llegar a 76
];

foreach ($dummyjson_config as $cfg) {
    $url = "https://dummyjson.com/products?limit={$cfg['limit']}&skip={$cfg['skip']}&select=title,description,price,thumbnail,category,rating,stock";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Algorya-Bot/1.0',
    ]);
    $json = curl_exec($ch);
    curl_close($ch);

    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['products'])) {
            foreach ($data['products'] as $p) {
                $todos_productos[] = [
                    'nombre'      => $p['title']       ?? 'Producto sin nombre',
                    'descripcion' => substr($p['description'] ?? '', 0, 200),
                    'precio'      => (float)($p['price'] ?? 9.99),
                    'imagen_url'  => $p['thumbnail']    ?? '',
                    'categoria'   => $p['category']     ?? 'general',
                    'rating'      => (float)($p['rating'] ?? 3.5),
                    'reviews'     => rand(50, 500), // DummyJSON no da numero de reviews
                    'fuente'      => 'dummyjson',
                ];
                $dummyjson_total++;
            }
        }
    }
    usleep(300000); // 0.3 segundos entre peticiones
}
log_msg("DummyJSON: {$dummyjson_total} productos obtenidos.");

// =============================================================================
// VERIFICAR QUE TENEMOS SUFICIENTES PRODUCTOS
// =============================================================================
$total_obtenidos = count($todos_productos);
log_msg("Total productos obtenidos de todas las fuentes: {$total_obtenidos}");

if ($total_obtenidos < 10) {
    $err_msg = "Menos de 10 productos obtenidos ({$total_obtenidos}). Abortando sync para no vaciar el catalogo.";
    log_msg("ERROR CRITICO: " . $err_msg);
    // Alerta al administrador via Brevo
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/includes/mailer.php';
        mail_alerta_sync($err_msg);
    }
    exit(1);
}

// =============================================================================
// LIMPIEZA SEGURA: Solo borramos productos anteriores DESPUÉS de confirmar
// que las APIs devolvieron datos suficientes. Asi nunca nos quedamos sin
// catalogo si una API falla durante la noche.
// =============================================================================
log_msg("Limpiando productos de API anteriores (datos nuevos confirmados)...");

$res_imgs = $conn->query(
    "SELECT imagen FROM productos
     WHERE imagen LIKE 'fakestore_%'
        OR imagen LIKE 'dummyjson_%'
        OR imagen LIKE 'api_%'"
);
$imagenes_a_borrar = [];
while ($row = $res_imgs->fetch_assoc()) {
    $imagenes_a_borrar[] = $row['imagen'];
}

$conn->query(
    "DELETE FROM productos
     WHERE imagen LIKE 'fakestore_%'
        OR imagen LIKE 'dummyjson_%'
        OR imagen LIKE 'api_%'"
);
$borrados_bd  = $conn->affected_rows;
$borrados_img = 0;
foreach ($imagenes_a_borrar as $img) {
    $ruta = IMG_DIR . $img;
    if ($img !== 'default.jpg' && file_exists($ruta)) {
        @unlink($ruta);
        $borrados_img++;
    }
}
log_msg("  -> Registros BD eliminados : {$borrados_bd}");
log_msg("  -> Imagenes fisicas borradas: {$borrados_img}");

// =============================================================================
// PROCESAMIENTO: CALCULAR TREND_SCORE Y PREPARAR DATOS
// =============================================================================
log_msg("Calculando puntuaciones de tendencia (trend_score)...");

foreach ($todos_productos as &$prod) {
    $stock_simulado = rand(3, 50); // Stock simulado para el algoritmo FOMO

    $prod['stock']       = $stock_simulado;
    $prod['trend_score'] = calcular_trend_score(
        $prod['categoria'],
        $prod['precio'],
        $prod['rating'],
        $prod['reviews'],
        $stock_simulado
    );
    // Los productos con trend_score >= 70 se marcan como destacados
    $prod['destacado'] = ($prod['trend_score'] >= 70) ? 1 : 0;
}
unset($prod); // Romper la referencia del foreach

// Ordenar por trend_score descendente (los mas trendy primero)
usort($todos_productos, fn($a, $b) => $b['trend_score'] - $a['trend_score']);

log_msg("Productos con trend_score >= 70 (DESTACADOS): " .
    count(array_filter($todos_productos, fn($p) => $p['destacado'] === 1))
);

// =============================================================================
// SINCRONIZACION CON LA BASE DE DATOS
// Estrategia UPSERT: si existe -> actualizar; si no existe -> insertar
// NO hacemos TRUNCATE para no perder productos manualmente añadidos por el admin
// =============================================================================
log_msg("Iniciando insercion/actualizacion en MariaDB...");

// Primero reseteamos todos los destacados para recalcularlos fresh
$conn->query("UPDATE productos SET destacado = 0 WHERE api_id IS NOT NULL");

$stmt_check  = $conn->prepare("SELECT id FROM productos WHERE nombre = ?");
$stmt_update = $conn->prepare(
    "UPDATE productos SET descripcion=?, precio=?, stock=?, destacado=?, imagen=?, activo=1 WHERE nombre=?"
);
$stmt_insert = $conn->prepare(
    "INSERT INTO productos (nombre, descripcion, precio, stock, imagen, destacado, activo) VALUES (?,?,?,?,?,?,1)"
);

foreach ($todos_productos as $prod) {
    // Sanitizar precio — asegurar que es un float válido (evita error DECIMAL con productos manuales)
    $precio_safe      = (float) preg_replace('/[^0-9.]/', '', (string)$prod['precio']);
    if ($precio_safe <= 0) $precio_safe = 9.99; // Precio de fallback

    $nombre      = $prod['nombre'];
    $descripcion = $prod['descripcion'];
    $precio      = $precio_safe;
    $stock       = (int) $prod['stock'];
    $destacado   = (int) $prod['destacado'];

    // Descargar imagen al servidor local
    $prefijo_fuente = $prod['fuente'];
    $imagen = descargar_imagen($prod['imagen_url'], $prefijo_fuente);

    // Comprobar si el producto ya existe
    $stmt_check->bind_param("s", $nombre);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // ACTUALIZAR producto existente
        // Tipos: s=string, d=double, i=int, i=int, s=string, i=int, s=string
        $stmt_update->bind_param("sdiiis", $descripcion, $precio, $stock, $destacado, $imagen, $nombre);
        if ($stmt_update->execute()) {
            $total_actualizados++;
        } else {
            log_msg("  ERROR update '{$nombre}': " . $stmt_update->error);
            $total_errores++;
        }
    } else {
        // INSERTAR producto nuevo
        $stmt_insert->bind_param("ssdisi", $nombre, $descripcion, $precio, $stock, $imagen, $destacado);
        if ($stmt_insert->execute()) {
            $total_nuevos++;
        } else {
            log_msg("  ERROR al insertar '{$nombre}': " . $conn->error);
            $total_errores++;
        }
    }
    $stmt_check->free_result();
}

$stmt_check->close();
$stmt_update->close();
$stmt_insert->close();

// =============================================================================
// RESUMEN FINAL
// =============================================================================
$total_en_bd = $conn->query("SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'];
$destacados_en_bd = $conn->query("SELECT COUNT(*) as total FROM productos WHERE destacado = 1")->fetch_assoc()['total'];

log_msg("------------------------------------------------------------");
log_msg("SINCRONIZACION COMPLETADA:");
log_msg("  -> Productos nuevos insertados : {$total_nuevos}");
log_msg("  -> Productos actualizados      : {$total_actualizados}");
log_msg("  -> Errores                     : {$total_errores}");
log_msg("  -> Total productos en catalogo : {$total_en_bd}");
log_msg("  -> Productos DESTACADOS        : {$destacados_en_bd}");
log_msg("  -> Paginas en el catalogo      : " . ceil($total_en_bd / 12) . " (a 12 productos/pagina)");
log_msg("============================================================");
log_msg("Fin de sincronizacion.");

// ─────────────────────────────────────────────────────────────────────────────
// EMAIL DE RESUMEN AL ADMIN
// Se envía solo si hay errores o el catálogo tiene menos de 50 productos.
// Si todo va bien no se envía email para no saturar.
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/includes/mailer.php';
    if ($total_errores > 0 || $total_en_bd < 50) {
        $resumen  = "Sincronizacion completada CON ADVERTENCIAS el " . date("d/m/Y H:i") . "\n\n";
        $resumen .= "  Nuevos      : {$total_nuevos}\n";
        $resumen .= "  Actualizados: {$total_actualizados}\n";
        $resumen .= "  Errores     : {$total_errores}\n";
        $resumen .= "  Total BD    : {$total_en_bd} productos\n";
        $resumen .= "  Paginas     : " . ceil($total_en_bd / 12) . "\n\n";
        if ($total_en_bd < 50) {
            $resumen .= "ATENCION: El catalogo tiene menos de 50 productos. Revisa las APIs.\n";
        }
        mail_alerta_sync($resumen);
    }
}