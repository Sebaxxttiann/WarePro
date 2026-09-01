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
                id, 
                supervisor_turno, 
                turno, 
                persona_rotura, 
                placa_montacarga, 
                placa_camion,
                canal,
                cargo_persona, 
                tipo_producto, 
                descripcion_material, 
                unidades, 
                zona, 
                casual, 
                registro_fotografico, 
                observaciones, 
                primer_porque, 
                segundo_porque, 
                tercer_porque, 
                fecha_registro
            FROM roturas
            WHERE operacion_id = " . (int)getOperacionActiva() . "
            ORDER BY fecha_registro DESC";
    
    if (isset($conn) && $conn instanceof mysqli) {
        $result = $conn->query($sql);
        if ($result === false) {
            throw new Exception("Error en consulta MySQLi: " . $conn->error);
        }
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } elseif (isset($conexion)) {
        $result = mysql_query($sql, $conexion);
        if (!$result) {
            throw new Exception("Error en consulta MySQL: " . mysql_error());
        }
        while ($row = mysql_fetch_assoc($result)) {
            $data[] = $row;
        }
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception("No se detectó una conexión válida a la base de datos");
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>