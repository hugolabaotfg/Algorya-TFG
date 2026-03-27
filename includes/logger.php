<?php
require_once 'config.php';

function logSync($mensaje)
{
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    $fecha_archivo = date('Y-m-d');
    $timestamp = date('Y-m-d H:i:s');
    $log_file = LOG_DIR . "sync_{$fecha_archivo}.log";

    $linea = "[$timestamp] $mensaje" . PHP_EOL;
    file_put_contents($log_file, $linea, FILE_APPEND);
}
?>