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
            $stmt = $pdo->prepare("INSERT INTO pi_despachados (
                fecha, cd, verificador, distribuidor, placa, cajas_recibidas, envases_recibidos,
                cajas_resividas, envases_resividos, descripcion_envase, unidades_rotas,
                unidades_faltantes, unidades_otras_companias, unidades_antiguo_formato,
                unidades_nr, unidades_mal_estado, unidades_mal_clasificadas,
                plasticos_mal_estado, unidades_cuerpo_extrano, envases_sucios_recuperables,
                estibas_mal_estado, estibas_buen_estado, operacion_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['cd']),
                limpiarDatos($_POST['verificador']),
                limpiarDatos($_POST['distribuidor']),
                strtoupper(limpiarDatos($_POST['placa'])),
                intval($_POST['cajas_recibidas'] ?? 0),
                intval($_POST['envases_recibidos'] ?? 0),
                intval($_POST['cajas_resividas'] ?? 0),
                intval($_POST['envases_resividos'] ?? 0),
                limpiarDatos($_POST['descripcion_envase']),
                limpiarDatos($_POST['unidades_rotas']),
                limpiarDatos($_POST['unidades_faltantes']),
                intval($_POST['unidades_otras_companias'] ?? 0),
                intval($_POST['unidades_antiguo_formato'] ?? 0),
                intval($_POST['unidades_nr'] ?? 0),
                intval($_POST['unidades_mal_estado'] ?? 0),
                intval($_POST['unidades_mal_clasificadas'] ?? 0),
                intval($_POST['plasticos_mal_estado'] ?? 0),
                intval($_POST['unidades_cuerpo_extrano'] ?? 0),
                intval($_POST['envases_sucios_recuperables'] ?? 0),
                intval($_POST['estibas_mal_estado'] ?? 0),
                intval($_POST['estibas_buen_estado'] ?? 0),
                getOperacionActiva()
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro de PI despachado creado exitosamente']);
            break;
            
        case 'editar':
            $stmt = $pdo->prepare("UPDATE pi_despachados SET 
                fecha = ?, cd = ?, verificador = ?, distribuidor = ?, placa = ?,
                cajas_recibidas = ?, envases_recibidos = ?, cajas_resividas = ?,
                envases_resividos = ?, descripcion_envase = ?, unidades_rotas = ?,
                unidades_faltantes = ?, unidades_otras_companias = ?, unidades_antiguo_formato = ?,
                unidades_nr = ?, unidades_mal_estado = ?, unidades_mal_clasificadas = ?,
                plasticos_mal_estado = ?, unidades_cuerpo_extrano = ?, envases_sucios_recuperables = ?,
                estibas_mal_estado = ?, estibas_buen_estado = ?
                WHERE id = ? AND operacion_id = ?");

            $stmt->execute([
                limpiarDatos($_POST['fecha']),
                limpiarDatos($_POST['cd']),
                limpiarDatos($_POST['verificador']),
                limpiarDatos($_POST['distribuidor']),
                strtoupper(limpiarDatos($_POST['placa'])),
                intval($_POST['cajas_recibidas'] ?? 0),
                intval($_POST['envases_recibidos'] ?? 0),
                intval($_POST['cajas_resividas'] ?? 0),
                intval($_POST['envases_resividos'] ?? 0),
                limpiarDatos($_POST['descripcion_envase']),
                limpiarDatos($_POST['unidades_rotas']),
                limpiarDatos($_POST['unidades_faltantes']),
                intval($_POST['unidades_otras_companias'] ?? 0),
                intval($_POST['unidades_antiguo_formato'] ?? 0),
                intval($_POST['unidades_nr'] ?? 0),
                intval($_POST['unidades_mal_estado'] ?? 0),
                intval($_POST['unidades_mal_clasificadas'] ?? 0),
                intval($_POST['plasticos_mal_estado'] ?? 0),
                intval($_POST['unidades_cuerpo_extrano'] ?? 0),
                intval($_POST['envases_sucios_recuperables'] ?? 0),
                intval($_POST['estibas_mal_estado'] ?? 0),
                intval($_POST['estibas_buen_estado'] ?? 0),
                intval($_POST['id']),
                getOperacionActiva()
            ]);
            echo json_encode(['success' => true, 'message' => 'Registro actualizado exitosamente']);
            break;
            
        case 'eliminar':
            $stmt = $pdo->prepare("DELETE FROM pi_despachados WHERE id = ? AND operacion_id = ?");
            $stmt->execute([intval($_POST['id']), getOperacionActiva()]);
            echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente']);
            break;
            
        case 'obtener':
            $stmt = $pdo->prepare("SELECT * FROM pi_despachados WHERE id = ? AND operacion_id = ?");
            $stmt->execute([intval($_POST['id']), getOperacionActiva()]);
            $registro = $stmt->fetch();
            if ($registro) {
                echo json_encode(['success' => true, 'data' => $registro]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            }
            break;
            
        case 'listar':
            $stmt = $pdo->prepare("SELECT * FROM pi_despachados WHERE operacion_id = ? ORDER BY fecha DESC, id DESC");
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
