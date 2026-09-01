<?php
require_once '../../core/config.php';
verificarLogin();

header('Content-Type: application/json');

try {
    if ($_POST['action'] == 'agregar') {
        $stmt = $pdo->prepare("INSERT INTO error_armado (
            fecha, turno, cc, verificador_reporta,
            colaborador_error_1, cantidad_1, descripcion_producto_1, tipo_error_1, placa_1,
            colaborador_error_2, cantidad_2, descripcion_producto_2, tipo_error_2, placa_2,
            colaborador_error_3, cantidad_3, descripcion_producto_3, tipo_error_3, placa_3,
            colaborador_error_4, cantidad_4, descripcion_producto_4, tipo_error_4, placa_4, operacion_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_POST['fecha'], $_POST['turno'], $_POST['cc'], $_POST['verificador_reporta'],
            $_POST['colaborador_error_1'], $_POST['cantidad_1'], $_POST['descripcion_producto_1'], $_POST['tipo_error_1'], $_POST['placa_1'],
            $_POST['colaborador_error_2'], $_POST['cantidad_2'], $_POST['descripcion_producto_2'], $_POST['tipo_error_2'], $_POST['placa_2'],
            $_POST['colaborador_error_3'], $_POST['cantidad_3'], $_POST['descripcion_producto_3'], $_POST['tipo_error_3'], $_POST['placa_3'],
            $_POST['colaborador_error_4'], $_POST['cantidad_4'], $_POST['descripcion_producto_4'], $_POST['tipo_error_4'], $_POST['placa_4'],
            getOperacionActiva()
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Error de armado registrado correctamente']);
        
    } elseif ($_POST['action'] == 'editar') {
        $stmt = $pdo->prepare("UPDATE error_armado SET 
            fecha = ?, turno = ?, cc = ?, verificador_reporta = ?,
            colaborador_error_1 = ?, cantidad_1 = ?, descripcion_producto_1 = ?, tipo_error_1 = ?, placa_1 = ?,
            colaborador_error_2 = ?, cantidad_2 = ?, descripcion_producto_2 = ?, tipo_error_2 = ?, placa_2 = ?,
            colaborador_error_3 = ?, cantidad_3 = ?, descripcion_producto_3 = ?, tipo_error_3 = ?, placa_3 = ?,
            colaborador_error_4 = ?, cantidad_4 = ?, descripcion_producto_4 = ?, tipo_error_4 = ?, placa_4 = ?,
            fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id = ? AND operacion_id = ?");

        $stmt->execute([
            $_POST['fecha'], $_POST['turno'], $_POST['cc'], $_POST['verificador_reporta'],
            $_POST['colaborador_error_1'], $_POST['cantidad_1'], $_POST['descripcion_producto_1'], $_POST['tipo_error_1'], $_POST['placa_1'],
            $_POST['colaborador_error_2'], $_POST['cantidad_2'], $_POST['descripcion_producto_2'], $_POST['tipo_error_2'], $_POST['placa_2'],
            $_POST['colaborador_error_3'], $_POST['cantidad_3'], $_POST['descripcion_producto_3'], $_POST['tipo_error_3'], $_POST['placa_3'],
            $_POST['colaborador_error_4'], $_POST['cantidad_4'], $_POST['descripcion_producto_4'], $_POST['tipo_error_4'], $_POST['placa_4'],
            $_POST['id'], getOperacionActiva()
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Error de armado actualizado correctamente']);
        
    } elseif ($_POST['action'] == 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM error_armado WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$_POST['id'], getOperacionActiva()]);
        
        echo json_encode(['success' => true, 'message' => 'Error de armado eliminado correctamente']);
        
    } elseif ($_POST['action'] == 'obtener') {
        $stmt = $pdo->prepare("SELECT * FROM error_armado WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$_POST['id'], getOperacionActiva()]);
        $error = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($error) {
            echo json_encode($error);
        } else {
            echo json_encode(['error' => 'Error de armado no encontrado']);
        }
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
