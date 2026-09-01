<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            $stmt = $pdo->prepare("INSERT INTO owd_vertimientos (fecha, colaborador, hora, cantidad) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['colaborador']),
                limpiarDatos($_POST['hora']),
                floatval($_POST['cantidad'])
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro creado exitosamente']);
            break;
            
        case 'editar':
            $stmt = $pdo->prepare("UPDATE owd_vertimientos SET fecha = ?, colaborador = ?, hora = ?, cantidad = ? WHERE id = ?");
            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['colaborador']),
                limpiarDatos($_POST['hora']),
                floatval($_POST['cantidad']),
                intval($_POST['id'])
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente']);
            break;
            
        case 'eliminar':
            $stmt = $pdo->prepare("DELETE FROM owd_vertimientos WHERE id = ?");
            $stmt->execute([intval($_POST['id'])]);
            echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
            break;
            
        case 'obtener':
            $stmt = $pdo->prepare("SELECT * FROM owd_vertimientos WHERE id = ?");
            $stmt->execute([intval($_POST['id'])]);
            $registro = $stmt->fetch();
            if ($registro) {
                echo json_encode(['success' => true, 'data' => $registro]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            }
            break;
            
        case 'listar':
            $stmt = $pdo->query("SELECT * FROM owd_vertimientos ORDER BY fecha DESC, hora DESC");
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
