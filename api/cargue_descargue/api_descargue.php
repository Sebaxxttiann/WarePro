<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include '../../core/config.php';

verificarLogin();
try {
    $data = array();
    
    $sql = "SELECT 
                s.id AS id_sorting,
                s.fecha_ingreso,
                s.placa,
                s.envase AS tipo_envase,
                s.cajas_sorting,
                s.hora_ingreso,
                s.estado AS estatus_sorting,
                s.usuario_porteria,
                s.usuario_sorting,
                d.id AS id_descargue,
                d.fecha_hora_inicio AS hora_inicio_descargue,
                d.fecha_hora_fin AS hora_fin_descargue,
                d.estado AS estatus_descargue
            FROM sortiing s
            LEFT JOIN descargue d ON s.id = d.id_sortiing
            WHERE s.fecha_ingreso >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY)
            AND s.operacion_id = " . (int)getOperacionActiva() . "
            ORDER BY s.fecha_ingreso ASC, s.hora_ingreso ASC";
    
    if (isset($conn) && $conn instanceof mysqli) {
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) { $data[] = $row; }
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>