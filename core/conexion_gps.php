<?php
$host = 'localhost'; 
$dbname = 'u806400645_warepro';
$user = 'u806400645_warepro';
$pass = 'Warepro2107';

try {
    $pdo_gps = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo_gps->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    $pdo_gps->exec("SET time_zone = '-05:00';");
    
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "mensaje" => "Error de BD: " . $e->getMessage()]));
}
?>

