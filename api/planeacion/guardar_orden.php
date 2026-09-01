<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/con_universal.php';
header('Content-Type: application/json');

$datos = json_decode(file_get_contents("php://input"), true);

if (!empty($datos['semana']) && !empty($datos['ordenIDs'])) {
    try {
        $semana = $datos['semana'];
        $ordenIDs = $datos['ordenIDs'];
        
        
        $pdo_warepro->beginTransaction();
        $stmt = $pdo_warepro->prepare("UPDATE planeacion_semanal SET orden = :orden WHERE identificador = :id AND semana = :semana AND operacion_id = :operacion_id");

        foreach ($ordenIDs as $index => $id) {
            $stmt->execute([
                'orden' => $index,
                'id' => $id,
                'semana' => $semana,
                'operacion_id' => getOperacionActiva()
            ]);
        }
        
        $pdo_warepro->commit();
        echo json_encode(['exito' => true]);
    } catch (Exception $e) {
        $pdo_warepro->rollBack();
        echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
    }
}
?>