<?php
// GET    /pagos
// GET    /pagos/:id
// POST   /pagos
// PUT    /pagos/:id
// DELETE /pagos/:id
// GET    /pagos/empresa/:idEmpresa

requireAuth();
$db = getConexion();

// GET /pagos/empresa/:idEmpresa
if ($metodo === 'GET' && $id === 'empresa' && $sub !== null) {
    $idEmpresa = (int)$sub;
    $stmt = $db->prepare("SELECT * FROM pago WHERE id_empresa = ? ORDER BY fecha_pago DESC");
    $stmt->bind_param("i", $idEmpresa);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $db->close();
    responder(200, $rows);
}

switch ($metodo) {

    case 'GET':
        if ($id === null) {
            $rows = $db->query("
                SELECT p.*, e.nombre AS empresa
                FROM pago p JOIN empresa e ON e.id_empresa = p.id_empresa
                ORDER BY p.fecha_pago DESC
            ")->fetch_all(MYSQLI_ASSOC);
            $db->close();
            responder(200, $rows);
        }
        $idPago = (int)$id;
        $stmt   = $db->prepare("
            SELECT p.*, e.nombre AS empresa
            FROM pago p JOIN empresa e ON e.id_empresa = p.id_empresa
            WHERE p.id_pago = ? LIMIT 1
        ");
        $stmt->bind_param("i", $idPago);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Pago no encontrado"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1]);
        $body        = jsonBody();
        $idEmpresa   = (int)($body['id_empresa'] ?? 0);
        $monto       = (float)($body['monto'] ?? 0);
        $metodo_pago = trim($body['metodo_pago'] ?? '');
        $fecha_pago  = trim($body['fecha_pago'] ?? date('Y-m-d H:i:s'));
        $estado      = (int)($body['estado'] ?? 1);
        if (!$idEmpresa || !$monto || empty($metodo_pago)) {
            responder(400, ["error" => "id_empresa, monto y metodo_pago requeridos"]);
        }
        $stmt = $db->prepare("
            INSERT INTO pago (id_empresa, monto, metodo_pago, fecha_pago, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("idssi", $idEmpresa, $monto, $metodo_pago, $fecha_pago, $estado);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Pago registrado", "id_pago" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idPago      = (int)$id;
        $body        = jsonBody();
        $idEmpresa   = (int)($body['id_empresa'] ?? 0);
        $monto       = (float)($body['monto'] ?? 0);
        $metodo_pago = trim($body['metodo_pago'] ?? '');
        $fecha_pago  = trim($body['fecha_pago'] ?? date('Y-m-d H:i:s'));
        $estado      = (int)($body['estado'] ?? 1);
        $stmt = $db->prepare("
            UPDATE pago SET id_empresa=?, monto=?, metodo_pago=?, fecha_pago=?, estado=?
            WHERE id_pago=?
        ");
        $stmt->bind_param("idssii", $idEmpresa, $monto, $metodo_pago, $fecha_pago, $estado, $idPago);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Pago actualizado"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idPago = (int)$id;
        $stmt   = $db->prepare("DELETE FROM pago WHERE id_pago = ?");
        $stmt->bind_param("i", $idPago);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Pago eliminado"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
