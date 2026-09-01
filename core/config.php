<?php
session_start();




$projectRootFs = str_replace('\\', '/', dirname(__DIR__));
$documentRootFs = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
if ($documentRootFs !== '' && strpos($projectRootFs, $documentRootFs) === 0) {
    define('BASE_URL', substr($projectRootFs, strlen($documentRootFs)));
} else {
    define('BASE_URL', '');
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'u806400645_warepro');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'], $_SESSION['nombre'], $_SESSION['cargo'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

function requireRole(array $roles) {
    verificarLogin();
    if (!in_array($_SESSION['cargo'] ?? '', $roles, true)) {
        http_response_code(403);
        die('Acceso denegado.');
    }
}

function getOperacionActiva() {
    return $_SESSION['operacion_activa_id'] ?? $_SESSION['operacion_id'] ?? null;
}

function limpiarDatos($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
