<?php
require_once '../../core/config.php';
verificarLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM roturas WHERE id = ? AND operacion_id = ?");
    $stmt->execute([$_GET['id'], getOperacionActiva()]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        echo json_encode(['success' => true, 'record' => $record]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener el registro: ' . $e->getMessage()]);
}
?>
