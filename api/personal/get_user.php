<?php
/**
 * Datos de un usuario para el modal "Editar Usuario" de modules/personal/perfil.php.
 * Solo admin y super_admin; un admin solo ve usuarios de su operación activa.
 * Nunca devuelve la contraseña.
 */
require_once '../../core/config.php';
verificarLogin();
header('Content-Type: application/json; charset=utf-8');

$cargo = $_SESSION['cargo'] ?? '';
if (!in_array($cargo, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para ver este usuario']);
    exit();
}

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de usuario requerido']);
    exit();
}

$stmt = $pdo->prepare("SELECT id, nombre, cedula, cargo, activo, operacion_id FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit();
}
if ($cargo !== 'super_admin' && (int)$usuario['operacion_id'] !== (int)getOperacionActiva()) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso sobre ese usuario']);
    exit();
}

echo json_encode($usuario, JSON_UNESCAPED_UNICODE);
