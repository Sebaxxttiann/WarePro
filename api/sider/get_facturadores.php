<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT nombre
        FROM personal_activo
        WHERE estado = 'activo'
        AND (cargo LIKE '%FACTURAC%' OR cargo LIKE '%FACTURAD%' OR cargo = 'AUXILIAR DE FACTURACIÓN')
        AND operacion_id = ?
        ORDER BY nombre ASC
    ");
    $stmt->execute([getOperacionActiva()]);
    $facturadores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'facturadores' => $facturadores
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los facturadores: ' . $e->getMessage()
    ]);
}
?>