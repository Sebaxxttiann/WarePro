<?php
require_once '../../core/config.php';
verificarLogin();

header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            try {
                $stmt = $pdo->prepare("INSERT INTO recargue_t2 (fecha, verificador, turno, placa, hora_entrada_bahia, hora_salida_bahia, opm1, novedades_salidas_bahia, descripcion_novedad, tiempo, estatus, canal, conteo_vehiculo, operacion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $descripcion_novedad = ($_POST['novedades_salidas_bahia'] === 'SI') ? $_POST['descripcion_novedad'] : null;

                if ($stmt->execute([
                    $_POST['fecha'],
                    $_POST['verificador'],
                    $_POST['turno'],
                    strtoupper($_POST['placa']),
                    $_POST['hora_entrada_bahia'],
                    $_POST['hora_salida_bahia'],
                    $_POST['opm1'],
                    $_POST['novedades_salidas_bahia'],
                    $descripcion_novedad,
                    $_POST['tiempo'],
                    $_POST['estatus'],
                    $_POST['canal'],
                    $_POST['conteo_vehiculo'],
                    getOperacionActiva()
                ])) {
                    echo json_encode(['success' => true, 'message' => 'Registro creado exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al crear el registro']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'read':
            try {
                
                $stmt = $pdo->prepare("SELECT * FROM recargue_t2 WHERE operacion_id = ? ORDER BY fecha DESC, fecha_registro DESC");
                $stmt->execute([getOperacionActiva()]);
                $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $registros]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'update':
            try {
                $stmt = $pdo->prepare("UPDATE recargue_t2 SET fecha=?, verificador=?, turno=?, placa=?, hora_entrada_bahia=?, hora_salida_bahia=?, opm1=?, novedades_salidas_bahia=?, descripcion_novedad=?, tiempo=?, estatus=?, canal=?, conteo_vehiculo=? WHERE id=? AND operacion_id=?");

                $descripcion_novedad = ($_POST['novedades_salidas_bahia'] === 'SI') ? $_POST['descripcion_novedad'] : null;

                if ($stmt->execute([
                    $_POST['fecha'],
                    $_POST['verificador'],
                    $_POST['turno'],
                    strtoupper($_POST['placa']),
                    $_POST['hora_entrada_bahia'],
                    $_POST['hora_salida_bahia'],
                    $_POST['opm1'],
                    $_POST['novedades_salidas_bahia'],
                    $descripcion_novedad,
                    $_POST['tiempo'],
                    $_POST['estatus'],
                    $_POST['canal'],
                    $_POST['conteo_vehiculo'],
                    $_POST['id'],
                    getOperacionActiva()
                ])) {
                    echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar el registro']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM recargue_t2 WHERE id = ? AND operacion_id = ?");
                if ($stmt->execute([$_POST['id'], getOperacionActiva()])) {
                    echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar el registro']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_by_id':
            try {
                $stmt = $pdo->prepare("SELECT * FROM recargue_t2 WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$_POST['id'], getOperacionActiva()]);
                $registro = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($registro) {
                    echo json_encode(['success' => true, 'data' => $registro]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>