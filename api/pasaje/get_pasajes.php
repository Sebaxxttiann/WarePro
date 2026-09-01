<?php
require_once '../../core/config.php';


verificarLogin();


date_default_timezone_set('America/Bogota');


header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'agregar':
            
            $required_fields = ['marca_temporal', 'placa_sider', 'origen', 'verificador', 'descripcion_material', 'cantidad_cajas', 'peso_kg'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    throw new Exception("El campo $field es requerido");
                }
            }

            
            $marca_temporal = $_POST['marca_temporal'];
            $placa_sider = strtoupper(trim($_POST['placa_sider']));
            $origen = trim($_POST['origen']);
            $verificador = trim($_POST['verificador']);
            $descripcion_material = trim($_POST['descripcion_material']);
            $cantidad_cajas = intval($_POST['cantidad_cajas']);
            $peso_kg = floatval($_POST['peso_kg']);
            $observaciones = 'NO OK'; 
            $observaciones2 = trim($_POST['observaciones2'] ?? '');

            
            $stmt = $pdo->prepare("
                INSERT INTO pasajes (
                    marca_temporal, placa_sider, origen, verificador,
                    descripcion_material, cantidad_cajas, peso_kg,
                    observaciones, observaciones2, operacion_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $marca_temporal, $placa_sider, $origen, $verificador,
                $descripcion_material, $cantidad_cajas, $peso_kg,
                $observaciones, $observaciones2, getOperacionActiva()
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Pasaje agregado exitosamente'
            ]);
            break;

        case 'editar':
            
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('ID de pasaje inválido');
            }

            
            $required_fields = ['marca_temporal', 'placa_sider', 'origen', 'verificador', 'descripcion_material', 'cantidad_cajas', 'peso_kg'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    throw new Exception("El campo $field es requerido");
                }
            }

            
            $id = intval($_POST['id']);
            $marca_temporal = $_POST['marca_temporal'];
            $placa_sider = strtoupper(trim($_POST['placa_sider']));
            $origen = trim($_POST['origen']);
            $verificador = trim($_POST['verificador']);
            $descripcion_material = trim($_POST['descripcion_material']);
            $cantidad_cajas = intval($_POST['cantidad_cajas']);
            $peso_kg = floatval($_POST['peso_kg']);
            $observaciones = 'NO OK'; 
            $observaciones2 = trim($_POST['observaciones2'] ?? '');

            
            $stmt = $pdo->prepare("
                UPDATE pasajes SET
                    marca_temporal = ?, placa_sider = ?, origen = ?, verificador = ?,
                    descripcion_material = ?, cantidad_cajas = ?, peso_kg = ?,
                    observaciones = ?, observaciones2 = ?
                WHERE id = ? AND operacion_id = ?
            ");

            $stmt->execute([
                $marca_temporal, $placa_sider, $origen, $verificador,
                $descripcion_material, $cantidad_cajas, $peso_kg,
                $observaciones, $observaciones2, $id, getOperacionActiva()
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pasaje actualizado exitosamente'
                ]);
            } else {
                throw new Exception('No se pudo actualizar el pasaje o no se encontró');
            }
            break;

        case 'obtener':
            
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('ID de pasaje inválido');
            }

            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("SELECT * FROM pasajes WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$id, getOperacionActiva()]);
            $pasaje = $stmt->fetch();

            if ($pasaje) {
                echo json_encode($pasaje);
            } else {
                throw new Exception('Pasaje no encontrado');
            }
            break;

        case 'eliminar':
            
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('ID de pasaje inválido');
            }

            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM pasajes WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$id, getOperacionActiva()]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pasaje eliminado exitosamente'
                ]);
            } else {
                throw new Exception('No se pudo eliminar el pasaje o no se encontró');
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
