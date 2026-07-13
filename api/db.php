<?php
function getConexion(): mysqli {
    $conn = new mysqli("localhost", "root", "", "servicecore");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Error de conexion"]);
        exit();
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
