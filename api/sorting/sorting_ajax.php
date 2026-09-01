<?php
require_once '../../core/config.php';
verificarLogin();

header('Content-Type: application/json');

$es_admin = isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'admin';
$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'guardar') {
        $fecha = $_POST['fecha'];
        $colaborador_id = $_SESSION['usuario_id'];
        $turno = limpiarDatos($_POST['turno']);
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fin = $_POST['hora_fin'];
        
        $stmt = $pdo->prepare("INSERT INTO sorting (fecha, colaborador_id, turno, hora_inicio, hora_fin, operacion_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fecha, $colaborador_id, $turno, $hora_inicio, $hora_fin, getOperacionActiva()]);
        
        echo json_encode(['success' => true, 'message' => 'Registro guardado exitosamente']);
        exit;
    }
    
    if ($accion === 'editar') {
        if (!$es_admin) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar']);
            exit;
        }
        
        $id = $_POST['id'];
        $turno = limpiarDatos($_POST['turno']);
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fin = $_POST['hora_fin'];
        
        $stmt = $pdo->prepare("UPDATE sorting SET turno = ?, hora_inicio = ?, hora_fin = ? WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$turno, $hora_inicio, $hora_fin, $id, getOperacionActiva()]);
        
        echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente']);
        exit;
    }
    
    if ($accion === 'eliminar') {
        if (!$es_admin) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar']);
            exit;
        }
        
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM sorting WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$id, getOperacionActiva()]);
        
        echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>