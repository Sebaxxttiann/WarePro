<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');


if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['grupo']) || empty($_GET['grupo'])) {
    echo json_encode(['error' => 'Grupo de registro requerido']);
    exit;
}

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
            grupo_registro,
            fecha_creacion
        FROM vertimiento
        WHERE grupo_registro = ? AND operacion_id = ?
        ORDER BY hora_inicio
    ");

    $stmt->execute([$_GET['grupo'], getOperacionActiva()]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($registros)) {
        echo json_encode(['error' => 'No se encontraron registros']);
        exit;
    }
    
    foreach ($registros as &$registro) {
        $registro['fecha_formateada'] = date('d/m/Y', strtotime($registro['fecha']));
    }
    unset($registro); 
    
    echo json_encode($registros);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]);
}
?>