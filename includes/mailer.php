<?php
// =============================================================================
// ALGORYA - includes/mailer.php
// Sistema centralizado de envío de emails usando PHPMailer + Brevo SMTP.
//
// Uso:
//   require 'includes/mailer.php';
//   algorya_mail('destino@email.com', 'Nombre', 'Asunto', 'Cuerpo del mensaje');
//
// Todos los mail() del proyecto se sustituyen por esta función.
// =============================================================================

// Cargar las claves secretas (Este archivo debe estar en el .gitignore)
require_once 'claves_smtp.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Autoloader de Composer (PHPMailer)
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Fallback: si Composer no está disponible, intentar mail() nativo
    // Esto solo ocurriría si se borra vendor/ accidentalmente
    function algorya_mail(string $to, string $nombre, string $asunto, string $cuerpo, bool $es_html = false): bool {
        $headers = "From: Algorya <hola@algorya.store>\r\nReply-To: hola@algorya.store\r\nX-Mailer: PHP/" . phpversion();
        return @mail($to, $asunto, $cuerpo, $headers);
    }
    function algorya_mail_admin(string $asunto, string $cuerpo): bool {
        return algorya_mail(ADMIN_EMAIL, 'Admin Algorya', $asunto, $cuerpo);
    }
    define('MAILER_DISPONIBLE', false);
    return;
}

// ─────────────────────────────────────────────────────────────────────────────
// CONFIGURACIÓN BREVO SMTP
// ─────────────────────────────────────────────────────────────────────────────
define('SMTP_HOST',     'smtp-relay.brevo.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'a61dc4001@smtp-brevo.com');
define('FROM_EMAIL',    'hola@algorya.store');
define('FROM_NAME',     'Algorya');
define('ADMIN_EMAIL',   'hola@algorya.store'); // Email del administrador para alertas
define('MAILER_DISPONIBLE', true);

// ─────────────────────────────────────────────────────────────────────────────
// FUNCIÓN PRINCIPAL: algorya_mail()
// Envía un email individual a un destinatario.
//
// @param string $to        Email del destinatario
// @param string $nombre    Nombre del destinatario (para el saludo)
// @param string $asunto    Asunto del email
// @param string $cuerpo    Cuerpo del mensaje (texto plano)
// @param bool   $es_html   Si true, el cuerpo se envía como HTML
// @return bool             true si se envió correctamente
// ─────────────────────────────────────────────────────────────────────────────
function algorya_mail(string $to, string $nombre, string $asunto, string $cuerpo, bool $es_html = false): bool {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host        = SMTP_HOST;
        $mail->SMTPAuth    = true;
        $mail->Username    = SMTP_USER;
        // Aquí le inyectamos la clave secreta desde el archivo oculto
        $mail->Password    = BREVO_SMTP_KEY; 
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port        = SMTP_PORT;
        $mail->CharSet     = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $nombre);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);

        // Contenido
        $mail->Subject = $asunto;
        if ($es_html) {
            $mail->isHTML(true);
            $mail->Body    = $cuerpo;
            $mail->AltBody = strip_tags($cuerpo);
        } else {
            $mail->isHTML(false);
            $mail->Body = $cuerpo;
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log del error sin detener la ejecución
        $log_dir = __DIR__ . '/../logs/';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        $log_line = "[" . date("Y-m-d H:i:s") . "] ERROR mailer a {$to}: " . $mail->ErrorInfo . "\n";
        @file_put_contents($log_dir . 'mail_errors.log', $log_line, FILE_APPEND);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FUNCIÓN: algorya_mail_admin()
// Envía una alerta al administrador.
// Útil para: errores del sync, pedidos fallidos, stock crítico, etc.
// ─────────────────────────────────────────────────────────────────────────────
function algorya_mail_admin(string $asunto, string $cuerpo): bool {
    return algorya_mail(ADMIN_EMAIL, 'Admin Algorya', '[ALGORYA ADMIN] ' . $asunto, $cuerpo);
}

// ─────────────────────────────────────────────────────────────────────────────
// PLANTILLAS DE EMAIL
// Funciones de alto nivel para cada tipo de email del sistema.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Email de verificación de cuenta al registrarse
 */
function mail_verificacion(string $email, string $nombre, string $token): bool {
    $enlace  = "https://algorya.store/verificar.php?token=" . $token;
    $asunto  = "[Algorya] Verifica tu cuenta para empezar a comprar";
    $cuerpo  = "Hola {$nombre},\n\n";
    $cuerpo .= "Gracias por registrarte en Algorya. Para activar tu cuenta y empezar a comprar,\n";
    $cuerpo .= "haz clic en el siguiente enlace:\n\n";
    $cuerpo .= "  {$enlace}\n\n";
    $cuerpo .= "El enlace es de un solo uso y caduca en 24 horas.\n\n";
    $cuerpo .= "Si no solicitaste este registro, ignora este mensaje.\n\n";
    $cuerpo .= "---\nEl equipo de Algorya\nhola@algorya.store\nhttps://algorya.store";

    return algorya_mail($email, $nombre, $asunto, $cuerpo);
}

/**
 * Email de confirmación de pedido tras pago con Stripe
 */
function mail_confirmacion_pedido(string $email, string $nombre, int $pedido_id, array $items, float $total, string $stripe_id): bool {
    $asunto  = "[Algorya] Pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT) . " confirmado";
    $cuerpo  = "Hola {$nombre},\n\n";
    $cuerpo .= "Tu pago ha sido procesado correctamente. Aqui tienes el resumen:\n\n";
    $cuerpo .= "Pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT) . "\n";
    $cuerpo .= str_repeat("-", 40) . "\n";
    foreach ($items as $item) {
        $subtotal = number_format($item['precio'] * $item['cantidad'], 2);
        $cuerpo  .= "  {$item['nombre']} x{$item['cantidad']} ......... {$subtotal} EUR\n";
    }
    $cuerpo .= str_repeat("-", 40) . "\n";
    $cuerpo .= "  TOTAL: " . number_format($total, 2) . " EUR\n\n";
    $cuerpo .= "Metodo de pago: Stripe\n";
    $cuerpo .= "ID de sesion: " . substr($stripe_id, 0, 30) . "...\n\n";
    $cuerpo .= "En breve comenzaremos a preparar tu envio.\n\n";
    $cuerpo .= "Puedes ver el estado de tu pedido en:\n";
    $cuerpo .= "  https://algorya.store/perfil.php\n\n";
    $cuerpo .= "---\nEl equipo de Algorya\nhola@algorya.store";

    return algorya_mail($email, $nombre, $asunto, $cuerpo);
}

/**
 * Email de actualización de estado de pedido (admin cambia estado)
 */
function mail_estado_pedido(string $email, string $nombre, int $pedido_id, string $estado): bool {
    $iconos = [
        'Enviado'   => 'Tu pedido ya está en camino. Recibirás tu paquete en los próximos días hábiles.',
        'Entregado' => '¡Tu pedido ha sido entregado! Esperamos que lo disfrutes.',
        'Cancelado' => 'Tu pedido ha sido cancelado. Si tienes dudas contacta con nosotros.',
        'Pendiente' => 'Tu pedido está siendo procesado.',
    ];

    $msg_estado = $iconos[$estado] ?? 'El estado de tu pedido ha cambiado.';
    $asunto     = "[Algorya] Pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT) . " - Estado: {$estado}";

    $cuerpo  = "Hola {$nombre},\n\n";
    $cuerpo .= "El estado de tu pedido #" . str_pad($pedido_id, 5, '0', STR_PAD_LEFT) . " ha cambiado a: {$estado}\n\n";
    $cuerpo .= $msg_estado . "\n\n";
    $cuerpo .= "Ver mis pedidos: https://algorya.store/perfil.php\n\n";
    $cuerpo .= "---\nEl equipo de Algorya\nhola@algorya.store";

    return algorya_mail($email, $nombre, $asunto, $cuerpo);
}

/**
 * Alerta al admin: stock crítico (producto con stock <= 5)
 */
function mail_alerta_stock(array $productos_criticos): bool {
    $cuerpo  = "ALERTA DE STOCK CRITICO - Algorya\n";
    $cuerpo .= date("d/m/Y H:i") . "\n";
    $cuerpo .= str_repeat("=", 40) . "\n\n";
    $cuerpo .= "Los siguientes productos tienen stock <= 5 unidades:\n\n";
    foreach ($productos_criticos as $p) {
        $cuerpo .= "  - {$p['nombre']}: {$p['stock']} uds. restantes\n";
    }
    $cuerpo .= "\nAccede al panel para actualizar el stock:\n";
    $cuerpo .= "  https://algorya.store/admin_estadisticas.php\n";

    return algorya_mail_admin("STOCK CRITICO - " . count($productos_criticos) . " productos", $cuerpo);
}

/**
 * Alerta al admin: error en la sincronización del catálogo
 */
function mail_alerta_sync(string $error): bool {
    $cuerpo  = "ERROR EN SINCRONIZACION DEL CATALOGO\n";
    $cuerpo .= date("d/m/Y H:i") . "\n";
    $cuerpo .= str_repeat("=", 40) . "\n\n";
    $cuerpo .= "Se ha producido un error durante la sincronizacion automatica:\n\n";
    $cuerpo .= $error . "\n\n";
    $cuerpo .= "Revisa el log completo en:\n";
    $cuerpo .= "  /var/www/tfg/logs/sync_" . date("Y-m-d") . ".log\n";

    return algorya_mail_admin("ERROR sync catalogo " . date("d/m/Y"), $cuerpo);
}