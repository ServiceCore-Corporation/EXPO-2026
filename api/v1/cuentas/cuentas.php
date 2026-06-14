<?php
// GET    /cuentas
// GET    /cuentas/:id
// POST   /cuentas
// PUT    /cuentas/:id
// DELETE /cuentas/:id
// GET    /cuentas/empresa/:idEmpresa

requireAuth();
$db = getConexion();

// GET /cuentas/empresa/:idEmpresa
if ($metodo === 'GET' && $id === 'empresa' && $sub !== null) {
    $idEmpresa = (int)$sub;
    $stmt = $db->prepare("SELECT * FROM cuenta WHERE id_empresa = ?");
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
                SELECT c.*, e.nombre AS empresa
                FROM cuenta c JOIN empresa e ON e.id_empresa = c.id_empresa
                ORDER BY c.id_cuenta DESC
            ")->fetch_all(MYSQLI_ASSOC);
            $db->close();
            responder(200, $rows);
        }
        $idCuenta = (int)$id;
        $stmt = $db->prepare("
            SELECT c.*, e.nombre AS empresa
            FROM cuenta c JOIN empresa e ON e.id_empresa = c.id_empresa
            WHERE c.id_cuenta = ? LIMIT 1
        ");
        $stmt->bind_param("i", $idCuenta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close(); $db->close();
        if (!$row) responder(404, ["error" => "Cuenta no encontrada"]);
        responder(200, $row);
        break;

    case 'POST':
        requireRol([1]);
        $body      = jsonBody();
        $idEmpresa = (int)($body['id_empresa'] ?? 0);
        if (!$idEmpresa) responder(400, ["error" => "id_empresa requerido"]);
        $stmt = $db->prepare("INSERT INTO cuenta (id_empresa) VALUES (?)");
        $stmt->bind_param("i", $idEmpresa);
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close(); $db->close();
        responder(201, ["mensaje" => "Cuenta creada", "id_cuenta" => $newId]);
        break;

    case 'PUT':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idCuenta  = (int)$id;
        $body      = jsonBody();
        $idEmpresa = (int)($body['id_empresa'] ?? 0);
        if (!$idEmpresa) responder(400, ["error" => "id_empresa requerido"]);
        $stmt = $db->prepare("UPDATE cuenta SET id_empresa = ? WHERE id_cuenta = ?");
        $stmt->bind_param("ii", $idEmpresa, $idCuenta);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Cuenta actualizada"]);
        break;

    case 'DELETE':
        requireRol([1]);
        if ($id === null) responder(400, ["error" => "ID requerido"]);
        $idCuenta = (int)$id;
        $stmt = $db->prepare("DELETE FROM cuenta WHERE id_cuenta = ?");
        $stmt->bind_param("i", $idCuenta);
        $stmt->execute();
        $stmt->close(); $db->close();
        responder(200, ["mensaje" => "Cuenta eliminada"]);
        break;

    default:
        $db->close();
        responder(405, ["error" => "Metodo no permitido"]);
}
