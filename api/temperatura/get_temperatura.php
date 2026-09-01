<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM temperaturas WHERE id = ? AND operacion_id = ?");
    $stmt->execute([$id, getOperacionActiva()]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($registro) {
        echo json_encode(['success' => true, 'record' => $registro]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener el registro: ' . $e->getMessage()]);
}
?>