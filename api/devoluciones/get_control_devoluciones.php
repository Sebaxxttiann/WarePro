<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'get') {
            $stmt = $pdo->prepare("SELECT * FROM devoluciones WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$_POST['id'], getOperacionActiva()]);
            $devolucion = $stmt->fetch();
            
            if ($devolucion) {
                echo json_encode(['success' => true, 'data' => $devolucion]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
