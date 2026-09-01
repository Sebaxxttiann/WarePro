<?php
require_once '../../core/config.php';
verificarLogin();
require '../../core/conexion_gps.php';

$stmt = $pdo_gps->prepare("SELECT usuario_id, latitud, longitud, ultima_actualizacion FROM ubicaciones WHERE operacion_id = ?");
$stmt->execute([getOperacionActiva()]);
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($ubicaciones);
?>