<?php
// logout.php
session_start(); // Recupera la sesión actual
session_destroy(); // La destruye por completo
header("Location: index.php"); // Te manda de vuelta a la portada
exit();
?>