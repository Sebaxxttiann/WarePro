<?php
require_once '../../core/config.php';

header('Content-Type: application/json');

try {
    verificarLogin();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $fecha = limpiarDatos($_POST['fecha']);
            $hora = limpiarDatos($_POST['hora']);
            $vehiculos_planeados = (int)$_POST['vehiculos_planeados'];
            $vehiculos_cargados = (int)$_POST['vehiculos_cargados'];
            $franja = (int)$_POST['franja'];
            $usuario_id = $_SESSION['usuario_id'];
            
            if (empty($fecha) || empty($hora) || $vehiculos_planeados < 0 || $vehiculos_cargados < 0 || $franja < 0) {
                throw new Exception('Todos los campos son obligatorios y deben ser válidos');
            }
            
            $stmt = $pdo->prepare("INSERT INTO ows_cargue (fecha, hora, vehiculos_planeados, vehiculos_cargados, franja, usuario_id, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fecha, $hora, $vehiculos_planeados, $vehiculos_cargados, $franja, $usuario_id, getOperacionActiva()]);
            
            echo json_encode(['success' => true, 'message' => 'Registro creado correctamente']);
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $fecha = limpiarDatos($_POST['fecha']);
            $hora = limpiarDatos($_POST['hora']);
            $vehiculos_planeados = (int)$_POST['vehiculos_planeados'];
            $vehiculos_cargados = (int)$_POST['vehiculos_cargados'];
            $franja = (int)$_POST['franja'];
            
            if ($id <= 0 || empty($fecha) || empty($hora) || $vehiculos_planeados < 0 || $vehiculos_cargados < 0 || $franja < 0) {
                throw new Exception('Todos los campos son obligatorios y deben ser válidos');
            }
            
            $stmt = $pdo->prepare("UPDATE ows_cargue SET fecha = ?, hora = ?, vehiculos_planeados = ?, vehiculos_cargados = ?, franja = ? WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$fecha, $hora, $vehiculos_planeados, $vehiculos_cargados, $franja, $id, getOperacionActiva()]);
            
            echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            $stmt = $pdo->prepare("DELETE FROM ows_cargue WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$id, getOperacionActiva()]);
            
            echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);
            break;
            
        case 'get':
            $id = (int)$_POST['id'];
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM ows_cargue WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$id, getOperacionActiva()]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception('Registro no encontrado');
            }
            
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_all':
            $stmt = $pdo->prepare("
                SELECT c.*, u.nombre as usuario_nombre
                FROM ows_cargue c
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                WHERE c.operacion_id = ?
                ORDER BY c.fecha DESC, c.hora DESC
            ");
            $stmt->execute([getOperacionActiva()]);
            $results = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $results]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>