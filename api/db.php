<?php
function getConexion(): mysqli {
    $conn = new mysqli("localhost", "u936997481_ServiCore", "ServiceCore_2026", "u936997481_ServiceCore");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Error de conexion"]);
        exit();
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
