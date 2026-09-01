<?php
require_once '../../core/config.php';

verificarLogin();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['grupo']) || empty($_GET['grupo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro grupo requerido']);
    exit;
}

$grupo_registro = $_GET['grupo'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            fecha,
            auxiliar_id,
            nombre,
            actividad,
            turno,
            producto_id,
            sku,
            producto_nombre,
            unidades,
            hora_inicio,
            hora_fin,
            horas_trabajadas,
            cumplimiento_individual,
            cumplimiento_general,
            cumple_meta,
            estado_ciclo,
            evidencia_5_porque,
            observaciones,
            fecha_creacion
        FROM revision
        WHERE grupo_registro = ? AND operacion_id = ?
        ORDER BY fecha_creacion ASC
    ");

    $stmt->execute([$grupo_registro, getOperacionActiva()]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($registros as &$registro) {
        $registro['fecha_formateada'] = date('d/m/Y', strtotime($registro['fecha']));
    }
    
    if (empty($registros)) {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontraron registros para este grupo']);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode($registros);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>