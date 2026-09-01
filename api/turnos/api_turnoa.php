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
                fecha_registro, 
                supervisor, 
                proyeccion_turno, 
                manejo_handling, 
                vh_t1, 
                tiempos_t1, 
                vh_t2, 
                tiempos_t2, 
                vh_t4, 
                tiempos_t4, 
                vh_mkp, 
                horas_reempaque, 
                cajas_reempacadas, 
                horas_limpieza_clasificacion, 
                cajas_clasificadas, 
                horas_lavado_unidades, 
                cajas_lavadas, 
                horas_vertimiento, 
                cajas_vertidas, 
                horas_revision_rn, 
                cajas_rn, 
                horas_revision_nr, 
                cajas_nr, 
                toma_temperatura, 
                surtido_picking, 
                estibas_sider_certificados, 
                video_dpo, 
                auxiliar_entrevistado, 
                fecha_creacion
            FROM turnoa_registros
            WHERE operacion_id = " . (int)getOperacionActiva() . "
            ORDER BY fecha_creacion DESC";
    
    
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
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'warepro.logisticos.com.co'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'warepro.logisticos.com.co'
    ]);
}
?>