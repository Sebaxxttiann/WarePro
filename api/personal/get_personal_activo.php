<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    
    $stmt = $pdo->prepare("SELECT DISTINCT nombre FROM personal_activo WHERE estado = 'activo' AND operacion_id = ? ORDER BY nombre ASC");
    $stmt->execute([getOperacionActiva()]);
    $personal = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'data' => $personal
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el personal: ' . $e->getMessage()
    ]);
}
?>