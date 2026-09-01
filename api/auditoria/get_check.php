<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');


if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

try {
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }

    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('ID del check requerido');
    }

    $id = intval($data['id']);

    
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre, u.cargo
        FROM check_herramientas c
        INNER JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = ? AND c.operacion_id = ?
    ");

    $stmt->execute([$id, getOperacionActiva()]);
    $check = $stmt->fetch();

    if (!$check) {
        throw new Exception('Check no encontrado');
    }

    echo json_encode([
        'success' => true,
        'check' => $check
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>