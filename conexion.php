<?php

$servername = "localhost";
$username = "u936997481_ServiCore";
$password = "ServiceCore_2026";
$database = "u936997481_ServiceCore";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}
