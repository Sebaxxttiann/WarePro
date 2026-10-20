<?php
/**
 * Usuarios activos de la operación activa, para listas con buscador.
 * GET ?cargo=auxiliar,verificador (opcional)  ?q=texto (opcional)
 * Respuesta: {success, data:[{id, nombre, cedula, cargo}]}
 */
require_once '../../core/config.php';
verificarLogin();
header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT id, nombre, cedula, cargo FROM usuarios WHERE activo = 1 AND operacion_id = ?";
    $params = [getOperacionActiva()];

    $cargos = array_filter(array_map('trim', explode(',', $_GET['cargo'] ?? '')));
    if ($cargos) {
        $sql .= " AND cargo IN (" . implode(',', array_fill(0, count($cargos), '?')) . ")";
        $params = array_merge($params, $cargos);
    }
    if (!empty($_GET['q'])) {
        $sql .= " AND (nombre LIKE ? OR cedula LIKE ?)";
        $params[] = '%' . $_GET['q'] . '%';
        $params[] = '%' . $_GET['q'] . '%';
    }
    $sql .= " ORDER BY nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar usuarios']);
}
