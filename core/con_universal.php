<?php
require_once __DIR__ . '/config.php';

/** @var PDO $pdo */
$pdo_warepro = $pdo;

$cabHost = getenv('WAREPRO_CAB_HOST');
$cabUser = getenv('WAREPRO_CAB_USER');
$cabPass = getenv('WAREPRO_CAB_PASS');
$cabName = getenv('WAREPRO_CAB_NAME');

if ($cabHost !== false && $cabUser !== false && $cabPass !== false && $cabName !== false) {
    $pdo_cab = new PDO(
        'mysql:host=' . $cabHost . ';dbname=' . $cabName . ';charset=utf8mb4',
        $cabUser,
        $cabPass,
        [PDO::ATTR_EMULATE_PREPARES => false]
    );
    $pdo_cab->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_cab->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} else {
    /** @var PDO $pdo_cab */
    $pdo_cab = $pdo_warepro;
}
?>
