<?php
$host = getenv('WAREPRO_GPS_HOST') ?: 'localhost';
$dbname = getenv('WAREPRO_GPS_NAME') ?: 'u806400645_warepro';
$user = getenv('WAREPRO_GPS_USER');
$pass = getenv('WAREPRO_GPS_PASS');

if ($user === false || $pass === false) {
    error_log('WarePro GPS database credentials are not configured.');
    http_response_code(500);
    die(json_encode(['status' => 'error', 'mensaje' => 'Conexión GPS no configurada.']));
}

try {
    $pdo_gps = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo_gps->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    $pdo_gps->exec("SET time_zone = '-05:00';");
    
} catch (PDOException $e) {
    error_log('WarePro GPS database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode(['status' => 'error', 'mensaje' => 'Error interno de conexión GPS.']));
}
?>