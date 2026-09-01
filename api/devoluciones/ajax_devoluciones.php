<?php
require_once '../../core/config.php';
verificarLogin();
header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createDevolucion();
            break;
        case 'update':
            updateDevolucion();
            break;
        case 'get':
            getDevolucion();
            break;
        case 'delete':
            deleteDevolucion();
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createDevolucion() {
    global $pdo;
    
    try {
        $sql = "INSERT INTO devoluciones (
            fecha, canal, operador, sku, dt, unidades, casual,
            verificador, facturador, status, placa, operacion_id, fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['fecha'] ?? null,
            $_POST['canal'] ?? null,
            $_POST['operador'] ?? null,
            $_POST['sku'] ?? null,
            $_POST['dt'] ?? null,
            $_POST['unidades'] ?? null,
            $_POST['casual'] ?? null,
            $_POST['verificador'] ?? null,
            $_POST['facturador'] ?? null,
            $_POST['status'] ?? null,
            $_POST['placa'] ?? null,
            getOperacionActiva()
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Devolución creada exitosamente',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al crear la devolución: ' . $e->getMessage());
    }
}

function updateDevolucion() {
    global $pdo;
    
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            throw new Exception('ID requerido para actualizar');
        }
        
        $sql = "UPDATE devoluciones SET
            fecha = ?, canal = ?, operador = ?, sku = ?, dt = ?,
            unidades = ?, casual = ?, verificador = ?, facturador = ?,
            status = ?, placa = ?
            WHERE id = ? AND operacion_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['fecha'] ?? null,
            $_POST['canal'] ?? null,
            $_POST['operador'] ?? null,
            $_POST['sku'] ?? null,
            $_POST['dt'] ?? null,
            $_POST['unidades'] ?? null,
            $_POST['casual'] ?? null,
            $_POST['verificador'] ?? null,
            $_POST['facturador'] ?? null,
            $_POST['status'] ?? null,
            $_POST['placa'] ?? null,
            $id,
            getOperacionActiva()
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Devolución actualizada exitosamente'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al actualizar la devolución: ' . $e->getMessage());
    }
}

function getDevolucion() {
    global $pdo;
    
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            throw new Exception('ID requerido');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM devoluciones WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$id, getOperacionActiva()]);
        $devolucion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$devolucion) {
            throw new Exception('Devolución no encontrada');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $devolucion
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error al obtener la devolución: ' . $e->getMessage());
    }
}

function deleteDevolucion() {
    global $pdo;
    
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            throw new Exception('ID requerido para eliminar');
        }
        
        $stmt = $pdo->prepare("DELETE FROM devoluciones WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$id, getOperacionActiva()]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Devolución eliminada exitosamente'
            ]);
        } else {
            throw new Exception('No se encontró la devolución a eliminar');
        }
        
    } catch (Exception $e) {
        throw new Exception('Error al eliminar la devolución: ' . $e->getMessage());
    }
}
?>