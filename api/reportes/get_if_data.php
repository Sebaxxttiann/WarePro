<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    include '../../core/config.php'; 
    
    
verificarLogin();
    if (!isset($conn)) {
        if (isset($conexion)) { $conn = $conexion; }
        elseif (isset($db)) { $conn = $db; }
        elseif (isset($pdo)) { $conn = $pdo; }
        elseif (isset($mysqli)) { $conn = $mysqli; }
        else {
            echo json_encode(['status' => 'error', 'message' => 'No se detectó la variable de conexión.']);
            exit;
        }
    }

    $isPDO = ($conn instanceof PDO);
    $sql = "SELECT * FROM inventario_if WHERE operacion_id = " . (int)getOperacionActiva() . " ORDER BY fecha_analisis ASC";
    $results = [];

    if ($isPDO) {
        $stmt = $conn->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }

    echo json_encode(['status' => 'success', 'data' => $results]);

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'ERROR: ' . $e->getMessage()]);
}
?>