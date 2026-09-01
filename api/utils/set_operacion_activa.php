<?php
require_once '../../core/config.php';
verificarLogin();
requireRole(['super_admin']);

header('Content-Type: application/json');

$operacion_id = $_POST['operacion_id'] ?? null;

if (!$operacion_id) {
    echo json_encode(['success' => false, 'message' => 'Falta operacion_id']);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM operaciones WHERE id = ? AND activo = 1");
$stmt->execute([$operacion_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Operación no válida']);
    exit();
}

$_SESSION['operacion_activa_id'] = (int)$operacion_id;
echo json_encode(['success' => true]);
