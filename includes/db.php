<?php
$host     = 'localhost';
$dbname   = 'tienda_db';
$username = 'algorya';
$password = 'AlgoryaDB2026!';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
