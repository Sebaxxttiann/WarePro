<?php
require_once '../../core/config.php';
verificarLogin();
header('Content-Type: application/json');

try {
    
    $stmt_personas = $pdo->prepare("SELECT DISTINCT nombre FROM personal_activo WHERE estado = 'activo' AND operacion_id = ? ORDER BY nombre ASC");
    $stmt_personas->execute([getOperacionActiva()]);
    $personas = $stmt_personas->fetchAll(PDO::FETCH_COLUMN);


    $stmt_cargos = $pdo->prepare("SELECT DISTINCT cargo FROM personal_activo WHERE estado = 'activo' AND operacion_id = ? ORDER BY cargo ASC");
    $stmt_cargos->execute([getOperacionActiva()]);
    $cargos = $stmt_cargos->fetchAll(PDO::FETCH_COLUMN);
    
    
    echo json_encode([
        'success' => true,
        'personas' => $personas,
        'cargos' => $cargos,
        'message' => 'Datos cargados correctamente'
    ]);
    
} catch (Exception $e) {
    
    error_log("Error en get_personal1.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'personas' => [],
        'cargos' => [],
        'message' => 'Error al obtener el personal: ' . $e->getMessage()
    ]);
}
?>