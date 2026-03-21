<?php
$host = 'localhost';
$user = 'admin_tienda';
$password = '1234';
$db = 'tienda_db';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>