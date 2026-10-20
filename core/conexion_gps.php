<?php
// La BD GPS es la misma que la principal salvo que se configure aparte.
// Se cae a las credenciales principales (WAREPRO_DB_*) para no dejar el
// módulo caído cuando no existen las variables WAREPRO_GPS_*.
$host = getenv('WAREPRO_GPS_HOST') ?: (getenv('WAREPRO_DB_HOST') ?: 'localhost');
$dbname = getenv('WAREPRO_GPS_NAME') ?: (getenv('WAREPRO_DB_NAME') ?: 'u806400645_warepro');
$user = getenv('WAREPRO_GPS_USER') ?: getenv('WAREPRO_DB_USER');
$pass = getenv('WAREPRO_GPS_PASS') ?: getenv('WAREPRO_DB_PASS');

if ($user === false || $user === null || $user === '' || $pass === false || $pass === null || $pass === '') {
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