<?php
require_once '../../core/config.php';
verificarLogin();
require '../../core/conexion_gps.php';


$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['usuario_id']) && isset($data['latitud']) && isset($data['longitud'])) {
    $usuario_id = $data['usuario_id'];
    $lat = $data['latitud'];
    $lng = $data['longitud'];
    $operacionActiva = getOperacionActiva();


    $stmt = $pdo_gps->prepare("INSERT INTO ubicaciones (usuario_id, latitud, longitud, operacion_id)
                           VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE latitud = ?, longitud = ?");
    $stmt->execute([$usuario_id, $lat, $lng, $operacionActiva, $lat, $lng]);

    echo json_encode(["status" => "success", "mensaje" => "Ubicación guardada"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos"]);
}
?>