<?php
require_once '../../core/config.php';

verificarLogin();
header('Content-Type: application/json');


if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

try {
    
    if (!isset($_POST['action'])) {
        throw new Exception('Acción no especificada');
    }

    $action = $_POST['action'];

    if ($action === 'guardar_check') {
        
        guardarNuevoCheck();
    } elseif ($action === 'editar_check') {
        
        editarCheck();
    } else {
        throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function guardarNuevoCheck() {
    global $pdo;
    
    
    $campos_requeridos = ['marca_temporal', 'estado_fisico', 'enchuf_conectores', 'epp_operador', 'almacenamiento', 'capacitacion'];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("El campo $campo es requerido");
        }
    }
    
    
    $usuario_id = $_SESSION['usuario_id'];
    $herramienta = 'Pistola de Calor'; 
    $marca_temporal = $_POST['marca_temporal']; 
    $estado_fisico = $_POST['estado_fisico'];
    $enchuf_conectores = $_POST['enchuf_conectores'];
    $epp_operador = $_POST['epp_operador'];
    $almacenamiento = $_POST['almacenamiento'];
    $capacitacion = $_POST['capacitacion'];
    
    
    $valores_validos = ['SI', 'NO'];
    $campos_validar = [$estado_fisico, $enchuf_conectores, $epp_operador, $almacenamiento, $capacitacion];
    
    foreach ($campos_validar as $valor) {
        if (!in_array($valor, $valores_validos)) {
            throw new Exception('Valores no válidos en los campos');
        }
    }
    
    
    $resultado = ($estado_fisico === 'SI' && $enchuf_conectores === 'SI' && $epp_operador === 'SI' && $almacenamiento === 'SI' && $capacitacion === 'SI') ? 'APROBADO' : 'RECHAZADO';
    
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $marca_temporal)) {
        throw new Exception('Formato de fecha inválido');
    }
    
    
    $stmt = $pdo->prepare("
        INSERT INTO check_herramientas
        (usuario_id, herramienta, marca_temporal, estado_fisico, enchuf_conectores, epp_operador, almacenamiento, capacitacion, resultado, operacion_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([
        $usuario_id,
        $herramienta,
        $marca_temporal,
        $estado_fisico,
        $enchuf_conectores,
        $epp_operador,
        $almacenamiento,
        $capacitacion,
        $resultado,
        getOperacionActiva()
    ]);
    
    if (!$success) {
        throw new Exception('Error al guardar en la base de datos');
    }
    
    echo json_encode([
        'success' => true,
        'message' => $resultado === 'APROBADO' ? 
            'Check completado exitosamente. Todos los criterios han sido aprobados.' : 
            'Check completado con observaciones. Algunos criterios requieren atención.',
        'resultado' => $resultado
    ]);
}

function editarCheck() {
    global $pdo;
    
    
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ID del check requerido');
    }
    
    $id = intval($_POST['id']);
    
    
    $campos_requeridos = ['estado_fisico', 'enchuf_conectores', 'epp_operador', 'almacenamiento', 'capacitacion'];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("El campo $campo es requerido");
        }
    }
    
    
    $estado_fisico = $_POST['estado_fisico'];
    $enchuf_conectores = $_POST['enchuf_conectores'];
    $epp_operador = $_POST['epp_operador'];
    $almacenamiento = $_POST['almacenamiento'];
    $capacitacion = $_POST['capacitacion'];
    
    
    $valores_validos = ['SI', 'NO'];
    $campos_validar = [$estado_fisico, $enchuf_conectores, $epp_operador, $almacenamiento, $capacitacion];
    
    foreach ($campos_validar as $valor) {
        if (!in_array($valor, $valores_validos)) {
            throw new Exception('Valores no válidos en los campos');
        }
    }
    
    
    $resultado = ($estado_fisico === 'SI' && $enchuf_conectores === 'SI' && $epp_operador === 'SI' && $almacenamiento === 'SI' && $capacitacion === 'SI') ? 'APROBADO' : 'RECHAZADO';
    
    
    $stmt_check = $pdo->prepare("SELECT id FROM check_herramientas WHERE id = ? AND operacion_id = ?");
    $stmt_check->execute([$id, getOperacionActiva()]);

    if (!$stmt_check->fetch()) {
        throw new Exception('Check no encontrado');
    }


    $stmt_update = $pdo->prepare("
        UPDATE check_herramientas
        SET estado_fisico = ?,
            enchuf_conectores = ?,
            epp_operador = ?,
            almacenamiento = ?,
            capacitacion = ?,
            resultado = ?
        WHERE id = ? AND operacion_id = ?
    ");
    
    $success = $stmt_update->execute([
        $estado_fisico,
        $enchuf_conectores,
        $epp_operador,
        $almacenamiento,
        $capacitacion,
        $resultado,
        $id,
        getOperacionActiva()
    ]);

    if (!$success) {
        throw new Exception('Error al actualizar en la base de datos');
    }
    
    if ($stmt_update->rowCount() === 0) {
        throw new Exception('No se realizaron cambios');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Check actualizado correctamente',
        'resultado' => $resultado
    ]);
}
?>