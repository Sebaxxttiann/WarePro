<?php
require_once '../../core/config.php';
verificarLogin();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM turnob_registros WHERE id = ? AND operacion_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id, getOperacionActiva()]);
$record = $stmt->fetch();

if ($record) {
    echo json_encode(['success' => true, 'record' => $record]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
}
?>