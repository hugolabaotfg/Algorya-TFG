<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mail'])) {
    $para = $_POST['destinatario'];
    $asunto = $_POST['asunto'];
    $mensaje_cuerpo = $_POST['mensaje'];

    $cabeceras = "From: hola@algorya.store\r\n";
    $cabeceras .= "Reply-To: hola@algorya.store\r\n";
    $cabeceras .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Intentamos enviar el mail
    if (mail($para, $asunto, $mensaje_cuerpo, $cabeceras)) {
        $mensaje_status = "<div class='alert alert-success'><i class='bi bi-send-check-fill me-2'></i>Email enviado correctamente a $para</div>";
    } else {
        $mensaje_status = "<div class='alert alert-danger'><i class='bi bi-exclamation-octagon me-2'></i>Error al procesar el envío. Revisa el servicio SMTP del servidor.</div>";
    }
}

// Sacamos lista de usuarios para el selector
$usuarios = $conn->query("SELECT email, nombre FROM usuarios WHERE rol != 'admin'");
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Mailing | Algorya Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <script src="tema.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3 text-decoration-none" href="index.php">
                <i class="bi bi-box-seam-fill text-primary me-1"></i><span class="text-primary">Algorya</span><span
                    class="premium-text" style="font-size: 0.55em;">.Admin</span>
            </a>
            <div class="d-flex gap-3 align-items-center">
                <a href="admin_usuarios.php" class="btn btn-link premium-text text-decoration-none">Clientes</a>
                <a href="admin_estadisticas.php" class="btn btn-link premium-text text-decoration-none">Estadísticas</a>
                <div id="darkModeToggle"><i class="bi bi-moon-stars-fill fs-6"></i></div>
            </div>
        </div>
    </nav>

    <div class="container mt-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card premium-card border-0 rounded-4 p-4 shadow-sm">
                    <h3 class="fw-bold premium-text mb-4"><i class="bi bi-envelope-paper-fill text-primary me-2"></i>
                        Comunicación con Clientes</h3>

                    <?php echo $mensaje_status; ?>

                    <form action="admin_mailing.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label premium-muted fw-bold small">SELECCIONAR DESTINATARIO</label>
                            <select name="destinatario" class="form-select premium-input shadow-none" required>
                                <option value="" selected disabled>Elegir un cliente...</option>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo $u['email']; ?>">
                                        <?php echo $u['nombre']; ?> (
                                        <?php echo $u['email']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label premium-muted fw-bold small">ASUNTO DEL MENSAJE</label>
                            <input type="text" name="asunto" class="form-control premium-input shadow-none"
                                placeholder="Ej: Oferta exclusiva en Algorya" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label premium-muted fw-bold small">CUERPO DEL EMAIL (HTML
                                Permitido)</label>
                            <textarea name="mensaje" class="form-control premium-input shadow-none" rows="8"
                                placeholder="Escribe aquí tu mensaje profesional..." required></textarea>
                        </div>

                        <button type="submit" name="enviar_mail"
                            class="btn btn-primary w-100 btn-lg rounded-pill fw-bold py-3 shadow-sm">
                            <i class="bi bi-send-fill me-2"></i> Realizar Envío Seguro
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>