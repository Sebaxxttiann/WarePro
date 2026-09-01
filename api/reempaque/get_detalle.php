<?php
require_once '../../core/config.php';

if (function_exists('verificarLogin')) {
    verificarLogin();
} else if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['grupo']) || empty($_GET['grupo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Grupo de registro no especificado']);
    exit;
}

try {
    if (!isset($pdo)) {
        throw new Exception('Conexión a base de datos no disponible');
    }

    $stmt = $pdo->prepare("
        SELECT 
            sku,
            producto_nombre,
            unidades,
            hora_inicio,
            hora_fin,
            horas_trabajadas,
            cumplimiento_individual,
            cumplimiento_general,
            estado_ciclo,
            evidencia_5_porque,
            cumple_meta,
            actividad,
            fecha,
            nombre,
            observaciones,
            fecha_creacion
        FROM reempaque1
        WHERE grupo_registro = ? AND operacion_id = ?
        ORDER BY fecha_creacion ASC
    ");

    $stmt->execute([$_GET['grupo'], getOperacionActiva()]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($detalles)) {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontraron detalles para el grupo: ' . $_GET['grupo']]);
        exit;
    }
    
    foreach ($detalles as &$detalle) {
        $detalle['fecha_formateada'] = date('d/m/Y', strtotime($detalle['fecha']));
    }
    unset($detalle);
    
    echo json_encode($detalles);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>