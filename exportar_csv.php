<?php
session_start();
require 'includes/db.php';

// 1. SEGURIDAD: Solo el administrador puede descargar datos
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Acceso denegado. No tienes permisos para exportar datos.");
}

// Recogemos qué tabla quiere exportar el admin (por defecto, pedidos)
$tipo = $_GET['tipo'] ?? 'pedidos';

// 2. PREPARAR LAS CABECERAS HTTP PARA LA DESCARGA
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Algorya_' . ucfirst($tipo) . '_' . date('Y-m-d_H-i') . '.csv');

// 3. ABRIR EL FLUJO DE SALIDA DIRECTO AL NAVEGADOR
$salida = fopen('php://output', 'w');

// TRUCO PRO: Añadir BOM (Byte Order Mark) de UTF-8. 
// Esto avisa a Microsoft Excel de que el archivo tiene tildes y eñes.
fputs($salida, "\xEF\xBB\xBF");

// 4. LÓGICA DE EXPORTACIÓN SEGÚN EL TIPO
if ($tipo === 'pedidos') {
    
    // Escribir la primera fila
    fputcsv($salida, ['ID Pedido', 'Cliente', 'Email', 'Total (€)', 'Estado', 'Fecha Compra'], ';');

    // AQUÍ ESTABA EL ERROR: Cambiamos "p.creado_en" por "p.fecha"
    $query = "SELECT p.id, u.nombre, u.email, p.total, p.estado, p.fecha 
              FROM pedidos p 
              LEFT JOIN usuarios u ON p.usuario_id = u.id 
              ORDER BY p.fecha DESC";
    $resultado = $conn->query($query);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            fputcsv($salida, [
                $fila['id'],
                $fila['nombre'],
                $fila['email'],
                number_format($fila['total'], 2, ',', ''),
                ucfirst($fila['estado']),
                $fila['fecha'] // <-- Y aquí también lo cambiamos
            ], ';');
        }
    }
} 
elseif ($tipo === 'usuarios') {
    
    fputcsv($salida, ['ID', 'Nombre', 'Email', 'Rol', 'Verificado', 'Usa 2FA'], ';');

    $query = "SELECT id, nombre, email, rol, verificado, usa_2fa FROM usuarios ORDER BY id DESC";
    $resultado = $conn->query($query);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            fputcsv($salida, [
                $fila['id'],
                $fila['nombre'],
                $fila['email'],
                strtoupper($fila['rol']),
                $fila['verificado'] ? 'Sí' : 'No',
                $fila['usa_2fa'] ? 'Activado' : 'Desactivado'
            ], ';');
        }
    }
}

elseif ($tipo === 'estadisticas') {
    
    // Cabecera del reporte
    fputcsv($salida, ['Reporte de Estadísticas Generales - Algorya'], ';');
    fputcsv($salida, ['Fecha de generación:', date('d/m/Y H:i')], ';');
    fputcsv($salida, [], ';'); // Fila en blanco

    // ── 1. VENTAS ────────────────────────────────────────────────────────
    fputcsv($salida, ['--- MÉTRICAS DE VENTAS ---', ''], ';');
    $ingresos = (float)$conn->query("SELECT COALESCE(SUM(total),0) as t FROM pedidos")->fetch_assoc()['t'];
    $pedidos  = (int)$conn->query("SELECT COUNT(*) as t FROM pedidos")->fetch_assoc()['t'];
    $clientes = (int)$conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='cliente'")->fetch_assoc()['t'];
    $ticket   = $pedidos > 0 ? round($ingresos / $pedidos, 2) : 0;

    fputcsv($salida, ['Ingresos Totales (€)', number_format($ingresos, 2, ',', '')], ';');
    fputcsv($salida, ['Pedidos Totales', $pedidos], ';');
    fputcsv($salida, ['Clientes Registrados', $clientes], ';');
    fputcsv($salida, ['Ticket Medio (€)', number_format($ticket, 2, ',', '')], ';');
    fputcsv($salida, [], ';'); 

    // ── 2. CATÁLOGO ──────────────────────────────────────────────────────
    fputcsv($salida, ['--- ESTADO DEL CATÁLOGO ---', ''], ';');
    $productos = (int)$conn->query("SELECT COUNT(*) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
    $stock     = (int)$conn->query("SELECT COALESCE(SUM(stock),0) as t FROM productos WHERE activo=1")->fetch_assoc()['t'];
    $bajos     = (int)$conn->query("SELECT COUNT(*) as t FROM productos WHERE stock<=5 AND activo=1")->fetch_assoc()['t'];

    fputcsv($salida, ['Productos Activos', $productos], ';');
    fputcsv($salida, ['Unidades Totales en Stock', $stock], ';');
    fputcsv($salida, ['Productos con Stock Crítico', $bajos], ';');
    fputcsv($salida, [], ';');

    // ── 3. TELEMETRÍA Y TRÁFICO ──────────────────────────────────────────
    fputcsv($salida, ['--- TRÁFICO WEB ---', ''], ';');
    $visitas_total = (int)$conn->query("SELECT COUNT(*) as t FROM visitas")->fetch_assoc()['t'];
    $visitas_hoy   = (int)$conn->query("SELECT COUNT(*) as t FROM visitas WHERE DATE(fecha)=CURDATE()")->fetch_assoc()['t'];
    fputcsv($salida, ['Visitas Totales (Histórico)', $visitas_total], ';');
    fputcsv($salida, ['Visitas de Hoy', $visitas_hoy], ';');
    fputcsv($salida, [], ';');

    // Dispositivos
    fputcsv($salida, ['--- USO POR DISPOSITIVO ---', ''], ';');
    $res_disp = $conn->query("SELECT dispositivo, COUNT(*) as t FROM visitas GROUP BY dispositivo ORDER BY t DESC");
    while ($r = $res_disp->fetch_assoc()) {
        fputcsv($salida, [$r['dispositivo'], $r['t']], ';');
    }
    fputcsv($salida, [], ';');

    // Países
    fputcsv($salida, ['--- TOP PAÍSES ---', ''], ';');
    $res_pais = $conn->query("SELECT pais, COUNT(*) as t FROM visitas GROUP BY pais ORDER BY t DESC LIMIT 10");
    while ($r = $res_pais->fetch_assoc()) {
        fputcsv($salida, [$r['pais'], $r['t']], ';');
    }
    fputcsv($salida, [], ';');

    // Ciudades
    fputcsv($salida, ['--- TOP CIUDADES ---', ''], ';');
    $res_ciudad = $conn->query("SELECT ciudad, COUNT(*) as t FROM visitas WHERE ciudad != 'Desconocida' GROUP BY ciudad ORDER BY t DESC LIMIT 10");
    while ($r = $res_ciudad->fetch_assoc()) {
        fputcsv($salida, [$r['ciudad'], $r['t']], ';');
    }
}
// 5. CERRAR EL FLUJO Y SALIR
fclose($salida);
exit();
?>