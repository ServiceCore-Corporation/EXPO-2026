<?php
// GET    /archivos
// GET    /archivos/:id
// POST   /archivos
// DELETE /archivos/:id
// GET    /archivos/ticket/:idTicket
// GET    /archivos/download/:id

requireAuth();
$db = getConexion();

// GET /archivos/download/:id
if ($metodo === 'GET' && $id === 'download' && $sub !== null) {
    $idArchivo = (int)$sub;
    $stmt = $db->prepare("SELECT * FROM archivo WHERE id_archivo = ? LIMIT 1");
    $stmt->bind_param("i", $idArchivo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close(); $db->close();
    if (!$row) responder(404, ["error" => "Archivo no encontrado"]);

    $ruta = $row['ruta'];
    if (!file_exists($ruta)) responder(404, ["error" => "Archivo no existe en disco"]);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($row['nombre']) . '"');
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: must-revalidate');
    readfile($ruta);
    exit();
}

// GET /archivos/ticket/:idTicket
if ($metodo === 'GET' && $id === 'ticket' && $sub !== null) {
    $idTicket = (int)$sub;
    $stmt = $db->prepare("SELECT * FROM archivo WHERE id_ticket = ? ORDER BY fecha_subida DESC");
    $stmt->bind_param("i", $idTicket);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("SELECT * FROM archivo ORDER BY fecha_subida DESC")->fetch_all(MYSQLI_ASSOC);
            $db->close(); responder(200, $rows);
        }
        $idArchivo = (int)$id;
        $stmt = $db->prepare("SELECT * FROM archivo WHERE id_archivo = ? LIMIT 1");
        $stmt->bind_param("i", $idArchivo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Archivo no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
        // Subida de archivo via multipart/form-data
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            responder(400, ["error" => "Archivo no recibido o error en la subida"]);
        }

        $id_ticket = (int)($_POST['id_ticket'] ?? 0);
        if (!$id_ticket) responder(400, ["error" => "id_ticket requerido"]);

        $uploadDir = __DIR__ . '/../../../../uploads/tickets/' . $id_ticket . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $nombreOriginal = basename($_FILES['archivo']['name']);
        $nombreSeguro   = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        $rutaFinal      = $uploadDir . $nombreSeguro;

        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaFinal)) {
            responder(500, ["error" => "Error al guardar el archivo"]);
        }

        $fecha = date('Y-m-d H:i:s');
        $stmt  = $db->prepare("
            INSERT INTO archivo (id_ticket, nombre, ruta, fecha_subida)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $id_ticket, $nombreSeguro, $rutaFinal, $fecha);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Archivo subido", "id_archivo" => $newId, "nombre" => $nombreSeguro]);
        break;

    case 'DELETE':
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idArchivo = (int)$id;
        $stmt = $db->prepare("SELECT ruta FROM archivo WHERE id_archivo = ? LIMIT 1");
        $stmt->bind_param("i", $idArchivo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && file_exists($row['ruta'])) unlink($row['ruta']);
        $stmt = $db->prepare("DELETE FROM archivo WHERE id_archivo = ?");
        $stmt->bind_param("i", $idArchivo);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Archivo eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
