<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');


if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}


$stmt_user = $pdo->prepare("SELECT cargo FROM usuarios WHERE id = ?");
$stmt_user->execute([$_SESSION['usuario_id']]);
$usuario = $stmt_user->fetch();

if (!$usuario || $usuario['cargo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Permisos insuficientes. Solo los administradores pueden eliminar registros.']);
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

    
    $stmt_check = $pdo->prepare("SELECT id FROM check_herramientas WHERE id = ? AND operacion_id = ?");
    $stmt_check->execute([$id, getOperacionActiva()]);

    if (!$stmt_check->fetch()) {
        throw new Exception('Check no encontrado');
    }


    $stmt_delete = $pdo->prepare("DELETE FROM check_herramientas WHERE id = ? AND operacion_id = ?");
    $success = $stmt_delete->execute([$id, getOperacionActiva()]);

    if (!$success) {
        throw new Exception('Error al eliminar el check');
    }

    if ($stmt_delete->rowCount() === 0) {
        throw new Exception('No se pudo eliminar el check');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Check eliminado correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>