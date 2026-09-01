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
            $stmt = $pdo->prepare("INSERT INTO temperatura_au (fecha, hora, lugar, temperatura, persona, operacion_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['hora']),
                limpiarDatos($_POST['lugar']),
                floatval($_POST['temperatura']),
                limpiarDatos($_POST['persona']),
                getOperacionActiva()
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro de temperatura creado exitosamente']);
            break;
            
        case 'editar':
            $stmt = $pdo->prepare("UPDATE temperatura_au SET fecha = ?, hora = ?, lugar = ?, temperatura = ?, persona = ? WHERE id = ? AND operacion_id = ?");
            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['hora']),
                limpiarDatos($_POST['lugar']),
                floatval($_POST['temperatura']),
                limpiarDatos($_POST['persona']),
                intval($_POST['id']),
                getOperacionActiva()
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente']);
            break;
            
        case 'eliminar':
            $stmt = $pdo->prepare("DELETE FROM temperatura_au WHERE id = ? AND operacion_id = ?");
            $stmt->execute([intval($_POST['id']), getOperacionActiva()]);
            echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
            break;
            
        case 'obtener':
            $stmt = $pdo->prepare("SELECT * FROM temperatura_au WHERE id = ? AND operacion_id = ?");
            $stmt->execute([intval($_POST['id']), getOperacionActiva()]);
            $registro = $stmt->fetch();
            if ($registro) {
                echo json_encode(['success' => true, 'data' => $registro]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            }
            break;
            
        case 'listar':
            $stmt = $pdo->prepare("SELECT * FROM temperatura_au WHERE operacion_id = ? ORDER BY fecha DESC, hora DESC");
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
