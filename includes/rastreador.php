<?php
// =============================================================================
// ALGORYA - Motor de Telemetría y Geolocalización (Optimizado)
// =============================================================================

if (!isset($conn)) { return; }

// 1. FORZAR ZONA HORARIA ESPAÑOLA
date_default_timezone_set('Europe/Madrid');
$fecha_actual = date('Y-m-d H:i:s');

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$uri_cruda = $_SERVER['REQUEST_URI'] ?? '/';

// 2. TRADUCIR RUTAS A NOMBRES AMIGABLES
if ($uri_cruda === '/' || strpos($uri_cruda, 'index.php') !== false) {
    $pagina = 'Inicio';
} else {
    // Limpiamos los parámetros GET (ej: de /producto.php?id=5 se queda en producto.php)
    $pagina = basename(parse_url($uri_cruda, PHP_URL_PATH)); 
}

if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    return;
}

$dispositivo = 'Desktop';
if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($user_agent))) {
    $dispositivo = 'Tablet';
} elseif (preg_match('/(mobile|iphone|ipod|android|blackberry|opera mini|windows ce|palm|smartphone|iemobile)/i', strtolower($user_agent))) {
    $dispositivo = 'Mobile';
}

$stmt_check = $conn->prepare("SELECT id FROM visitas WHERE ip = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt_check->bind_param("s", $ip);
$stmt_check->execute();
$ya_visitado = $stmt_check->get_result()->num_rows > 0;
$stmt_check->close();

if (!$ya_visitado && $ip !== '::1' && $ip !== '127.0.0.1') { 
    
    $pais = 'Desconocido';
    $ciudad = 'Desconocida';
    
    $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,city");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    $json = curl_exec($ch);
    curl_close($ch);

    if ($json) {
        $datos = json_decode($json, true);
        if (isset($datos['status']) && $datos['status'] === 'success') {
            $pais = $datos['country'] ?? 'Desconocido';
            $ciudad = $datos['city'] ?? 'Desconocida';
        }
    }

    // Pasamos explícitamente nuestra fecha ajustada a la base de datos
    $stmt_in = $conn->prepare("INSERT INTO visitas (ip, user_agent, pagina_visitada, pais, ciudad, dispositivo, fecha) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_in->bind_param("sssssss", $ip, $user_agent, $pagina, $pais, $ciudad, $dispositivo, $fecha_actual);
    $stmt_in->execute();
    $stmt_in->close();
}
?>