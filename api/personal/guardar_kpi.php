<?php
include '../../core/config.php'; 
verificarLogin(); 

header('Content-Type: application/json');


$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        
        
        case 'get_indicadores':
            $stmt = $pdo->prepare("SELECT * FROM kpi_indicadores WHERE operacion_id = ? ORDER BY id ASC");
            $stmt->execute([getOperacionActiva()]);
            $indicadores = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $indicadores]);
            break;

        
        case 'save_indicador':
            $id = $data['id'] ?? null;
            $nombre = limpiarDatos($data['nombre']); 
            $tipo = $data['tipo'];
            $temporalidad = $data['temporalidad'];
            $unidad_medida = $data['unidad_medida'];
            $unidad_especifica = $data['unidad_especifica'] ?? null;
            $meta_operador = $data['meta_operador'];
            $meta_valor = $data['meta_valor'];
            $disparador_operador = $data['disparador_operador'];
            $disparador_valor = $data['disparador_valor'];
            $updateHist = $data['updateHist'] ?? 'no';

            if ($id) {

                $sql = "UPDATE kpi_indicadores SET nombre=?, tipo=?, temporalidad=?, unidad_medida=?, unidad_especifica=?, meta_operador=?, meta_valor=?, disparador_operador=?, disparador_valor=? WHERE id=? AND operacion_id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $tipo, $temporalidad, $unidad_medida, $unidad_especifica, $meta_operador, $meta_valor, $disparador_operador, $disparador_valor, $id, getOperacionActiva()]);


                if ($updateHist === 'yes') {
                    $sqlHist = "UPDATE kpi_valores SET meta_operador_hist=?, meta_valor_hist=?, disparador_operador_hist=?, disparador_valor_hist=? WHERE indicador_id=? AND operacion_id=?";
                    $stmtHist = $pdo->prepare($sqlHist);
                    $stmtHist->execute([$meta_operador, $meta_valor, $disparador_operador, $disparador_valor, $id, getOperacionActiva()]);
                }
                echo json_encode(['status' => 'success', 'msg' => 'Indicador actualizado.']);
            } else {

                $sql = "INSERT INTO kpi_indicadores (nombre, tipo, temporalidad, unidad_medida, unidad_especifica, meta_operador, meta_valor, disparador_operador, disparador_valor, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $tipo, $temporalidad, $unidad_medida, $unidad_especifica, $meta_operador, $meta_valor, $disparador_operador, $disparador_valor, getOperacionActiva()]);
                echo json_encode(['status' => 'success', 'msg' => 'Indicador creado.']);
            }
            break;


        case 'delete_indicador':
            $id = $data['id'];
            $stmt = $pdo->prepare("DELETE FROM kpi_indicadores WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$id, getOperacionActiva()]);
            echo json_encode(['status' => 'success', 'msg' => 'Indicador eliminado.']);
            break;

        
        case 'get_valores':
            $year = $data['year'];
            $month = str_pad($data['month'], 2, "0", STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT * FROM kpi_valores WHERE DATE_FORMAT(fecha, '%Y-%m') = ? AND operacion_id = ?");
            $stmt->execute(["$year-$month", getOperacionActiva()]);
            $valores = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $valores]);
            break;

        
        case 'save_valor':
            $indicador_id = $data['indicador_id'];
            $fecha = $data['fecha'];
            $valor = $data['valor'];
            $mOp = $data['meta_operador'];
            $mVal = $data['meta_valor'];
            $dOp = $data['disparador_operador'];
            $dVal = $data['disparador_valor'];

            if ($valor === '') {

                $stmt = $pdo->prepare("DELETE FROM kpi_valores WHERE indicador_id = ? AND fecha = ? AND operacion_id = ?");
                $stmt->execute([$indicador_id, $fecha, getOperacionActiva()]);
            } else {

                $sql = "INSERT INTO kpi_valores (indicador_id, fecha, valor, meta_operador_hist, meta_valor_hist, disparador_operador_hist, disparador_valor_hist, operacion_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE valor = VALUES(valor), meta_operador_hist = VALUES(meta_operador_hist), meta_valor_hist = VALUES(meta_valor_hist), disparador_operador_hist = VALUES(disparador_operador_hist), disparador_valor_hist = VALUES(disparador_valor_hist)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$indicador_id, $fecha, $valor, $mOp, $mVal, $dOp, $dVal, getOperacionActiva()]);
            }
            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>