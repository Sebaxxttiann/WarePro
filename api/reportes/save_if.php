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
        elseif (isset($link)) { $conn = $link; }
        else {
            
            echo json_encode([
                'status' => 'error', 
                'message' => 'No se detectó la variable de conexión. Abre tu ../core/config.php, mira cómo se llama la variable (ej. $mi_conexion) y colócala en save_if.php.'
            ]);
            exit;
        }
    }

    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    if (!is_array($data) || empty($data)) {
        echo json_encode(['status' => 'error', 'message' => 'El servidor no recibió los datos.']);
        exit;
    }

    
    $isPDO = ($conn instanceof PDO);

    
    $sql = "INSERT INTO inventario_if
            (fecha_analisis, familia, cod_sku, descripcion_pt, cantidad_unidades, hl, cantidad_estibas, cajas_totales, fecha_vencimiento, dias_faltantes, valor_total, canal, ubicacion, estado, operacion_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $operacionId = getOperacionActiva();

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $errorDB = $isPDO ? json_encode($conn->errorInfo()) : $conn->error;
        echo json_encode(['status' => 'error', 'message' => 'Fallo al preparar SQL: ' . $errorDB]);
        exit;
    }

    $inserted = 0;
    $errores = [];

    foreach ($data as $row) {
        
        $fechaA = $row['fechaAnalisis']; 
        $fam    = $row['familia'];
        $sku    = (string)$row['sku'];
        $desc   = $row['desc'];
        $uni    = (float)$row['unidades'];
        $hl     = (float)$row['hl'];
        $est    = (float)$row['estibas'];
        $cajas  = (float)$row['cajas'];
        $fechaV = $row['fechaVenc'];     
        $dias   = (int)$row['diasFaltantes'];
        $valor  = (float)$row['valorTotal'];
        $canal  = $row['canal'];
        $ubi    = $row['ubicacion'];
        $estado = $row['estado'];

        if ($isPDO) {

            $ex = $stmt->execute([$fechaA, $fam, $sku, $desc, $uni, $hl, $est, $cajas, $fechaV, $dias, $valor, $canal, $ubi, $estado, $operacionId]);
            if ($ex) {
                $inserted++;
            } else {
                $errores[] = "SKU {$sku}: " . implode(" - ", $stmt->errorInfo());
            }
        } else {

            $stmt->bind_param("ssssddddsidsssi",
                $fechaA, $fam, $sku, $desc, $uni, $hl, $est, $cajas, $fechaV, $dias, $valor, $canal, $ubi, $estado, $operacionId
            );
            
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errores[] = "SKU {$sku}: " . $stmt->error;
            }
        }
    }

    if (!$isPDO) {
        $stmt->close();
        $conn->close();
    }

    
    if ($inserted > 0) {
        echo json_encode(['status' => 'success', 'inserted' => $inserted, 'errores' => $errores]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se insertó nada. Error: ' . implode(" | ", $errores)]);
    }

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'ERROR CRÍTICO DEL SERVIDOR: ' . $e->getMessage() . ' en la línea ' . $e->getLine()
    ]);
}
?>