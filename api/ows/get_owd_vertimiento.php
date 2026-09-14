<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

verificarCsrf();

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            $stmt = $pdo->prepare("INSERT INTO ows_vertimiento (fecha, hora, unidades, usuario_id, operacion_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['hora']),
                floatval($_POST['cantidad']),
                $_SESSION['usuario_id'],
                getOperacionActiva()
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro creado exitosamente']);
            break;
            
        case 'editar':
            $stmt = $pdo->prepare("UPDATE ows_vertimiento SET fecha = ?, hora = ?, unidades = ? WHERE id = ? AND operacion_id = ?");
            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['hora']),
                floatval($_POST['cantidad']),
                intval($_POST['id']),
                getOperacionActiva()
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente']);
            break;
            
        case 'eliminar':
            $stmt = $pdo->prepare("DELETE FROM ows_vertimiento WHERE id = ? AND operacion_id = ?");
            $stmt->execute([intval($_POST['id']), getOperacionActiva()]);
            echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
            break;
            
        case 'obtener':
            $stmt = $pdo->prepare("SELECT id, fecha, hora, usuario_id AS colaborador, unidades AS cantidad, operacion_id FROM ows_vertimiento WHERE id = ? AND operacion_id = ?");
            $stmt->execute([intval($_POST['id']), getOperacionActiva()]);
            $registro = $stmt->fetch();
            if ($registro) {
                echo json_encode(['success' => true, 'data' => $registro]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            }
            break;
            
        case 'listar':
            $stmt = $pdo->prepare("SELECT id, fecha, hora, usuario_id AS colaborador, unidades AS cantidad, operacion_id FROM ows_vertimiento WHERE operacion_id = ? ORDER BY fecha DESC, hora DESC");
            $stmt->execute([getOperacionActiva()]);
            $registros = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $registros]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
