<?php
function enviarConfirmacionPedido($email_destino, $nombre_cliente, $pedido_id, $total)
{
    // Error 5: Sanitización estricta de variables para prevenir Header Injection
    $email_limpio = filter_var($email_destino, FILTER_SANITIZE_EMAIL);
    $nombre_limpio = strip_tags(trim($nombre_cliente));

    if (!filter_var($email_limpio, FILTER_VALIDATE_EMAIL)) {
        error_log("Fallo al enviar email: Dirección inválida ($email_destino).");
        return false;
    }

    // Asunto conciso y descriptivo
    $asunto = "Confirmación de Pedido #$pedido_id - Algorya";

    // Redacción clara, profesional y sin datos sensibles innecesarios
    $mensaje = "Estimado/a $nombre_limpio,\n\n";
    $mensaje .= "Gracias por confiar en Algorya. Su pedido #$pedido_id ha sido procesado correctamente.\n";
    $mensaje .= "El total de su compra es de: " . number_format($total, 2) . " €.\n\n";
    $mensaje .= "Recibirá un nuevo aviso cuando el paquete sea expedido.\n\n";
    $mensaje .= "Atentamente,\nEl equipo de Algorya.";

    // Cabeceras seguras (Uso de CCO para privacidad si hubiera múltiples, remitente veraz)
    $headers = "From: no-reply@algorya.com\r\n";
    $headers .= "Reply-To: soporte@algorya.com\r\n";
    $headers .= "Bcc: auditoria@algorya.com\r\n"; // Ejemplo de uso correcto de CCO
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Capturar el booleano sin usar el silenciador @
    $enviado = mail($email_limpio, $asunto, $mensaje, $headers);

    if (!$enviado) {
        error_log("Fallo en la función mail() de PHP al enviar pedido $pedido_id a $email_limpio.");
        return false;
    }

    return true;
}
?>