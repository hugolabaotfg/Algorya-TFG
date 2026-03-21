<?php
// Script de background para ejecución vía CRON
require __DIR__ . '/includes/db.php'; 

// Buscamos usuarios con productos en el carrito desde hace más de 24 horas y que NO hayan sido avisados
$sql = "SELECT DISTINCT u.id, u.nombre, u.email 
        FROM carritos c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.fecha_agregado < NOW() - INTERVAL 24 HOUR 
        AND c.avisado = 0";

$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $email = $row['email'];
        $nombre = $row['nombre'];
        $uid = $row['id'];

        $asunto = "[Algorya] Tienes artículos olvidados en tu carrito";
        
        $cuerpo = "Hola $nombre,\n\n";
        $cuerpo .= "Hemos notado que dejaste algunos productos fantásticos en tu carrito.\n";
        $cuerpo .= "¡No te quedes sin ellos! El stock de nuestra selección viral vuela rápido.\n\n";
        $cuerpo .= "Accede a tu cuenta para finalizar tu pedido de forma segura:\n";
        $cuerpo .= "https://172.16.200.247/carrito.php\n\n";
        $cuerpo .= "Atentamente,\nEl equipo de Algorya.";

        $cabeceras = "From: noreply@dropshiphgl.local\r\n" .
                     "Reply-To: noreply@dropshiphgl.local\r\n" .
                     "X-Mailer: PHP/" . phpversion();

        // Si el correo se envía bien, actualizamos la base de datos para no hacer spam
        if (mail($email, $asunto, $cuerpo, $cabeceras)) {
            $conn->query("UPDATE carritos SET avisado = 1 WHERE usuario_id = $uid");
            echo "[".date("Y-m-d H:i:s")."] Aviso enviado con éxito a: $email\n";
        }
    }
} else {
    echo "[".date("Y-m-d H:i:s")."] No hay carritos abandonados pendientes de aviso.\n";
}
?>