<?php
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
session_start();




$projectRootFs = str_replace('\\', '/', dirname(__DIR__));
$documentRootFs = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
if ($documentRootFs !== '' && strpos($projectRootFs, $documentRootFs) === 0) {
    define('BASE_URL', substr($projectRootFs, strlen($documentRootFs)));
} else {
    define('BASE_URL', '');
}

$dbHost = getenv('WAREPRO_DB_HOST') ?: 'localhost';
$dbUser = getenv('WAREPRO_DB_USER');
$dbPass = getenv('WAREPRO_DB_PASS');
$dbName = getenv('WAREPRO_DB_NAME') ?: 'u806400645_warepro';

// Las credenciales locales (gitignored) tienen prioridad sobre el vhost: así
// se puede rotar la contraseña de MySQL sin editar la configuración de Apache.
$dbCredentialsFile = __DIR__ . '/db_credentials.php';
if (is_readable($dbCredentialsFile)) {
    $dbCredentials = @include $dbCredentialsFile;
    if (is_array($dbCredentials)) {
        $dbHost = $dbCredentials['host'] ?? $dbHost;
        $dbUser = $dbCredentials['user'] ?? $dbUser;
        $dbPass = $dbCredentials['pass'] ?? $dbPass;
        $dbName = $dbCredentials['name'] ?? $dbName;
    }
}

if (!is_string($dbUser) || $dbUser === '' || !is_string($dbPass) || $dbPass === '') {
    error_log('WarePro database credentials are not configured.');
    http_response_code(500);
    die('Conexión de base de datos no configurada.');
}

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log('WarePro database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Error interno de conexión.');
}

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'], $_SESSION['nombre'], $_SESSION['cargo'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }

    $stmt = $GLOBALS['pdo']->prepare('SELECT session_token FROM usuarios WHERE id = ? AND activo = 1');
    $stmt->execute([$_SESSION['usuario_id']]);
    $sessionToken = $stmt->fetchColumn();
    if (!$sessionToken || !hash_equals((string)$sessionToken, (string)($_SESSION['session_token'] ?? ''))) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?sesion_expirada=1');
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

function verificarPassword($password, $hash) {
    if (password_verify($password, $hash)) {
        return true;
    }

    // Compatibilidad temporal con las contraseñas antiguas almacenadas como MD5.
    return hash_equals(strtolower((string)$hash), md5($password));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarCsrf($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$token)) {
        http_response_code(403);
        die('Solicitud no válida.');
    }
}

function jsonError($message = 'Error interno del servidor', $status = 500) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function guardarImagenSegura(array $archivo, $directorio, $prefijo = 'archivo') {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Carga de archivo inválida.');
    }
    if (($archivo['size'] ?? 0) > 5 * 1024 * 1024 || !is_uploaded_file($archivo['tmp_name'])) {
        throw new RuntimeException('La imagen supera el límite permitido.');
    }

    $tipos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($tipos[$mime])) {
        throw new RuntimeException('Solo se permiten imágenes válidas.');
    }

    if (!is_dir($directorio) && !mkdir($directorio, 0750, true)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento.');
    }
    $nombre = $prefijo . '_' . bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
    $ruta = rtrim($directorio, '/\\') . DIRECTORY_SEPARATOR . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $ruta)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return $ruta;
}

function guardarMediaSegura(array $archivo, $directorio, $prefijo = 'archivo') {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new RuntimeException('Carga de archivo inválida.');
    }
    if (($archivo['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('El archivo supera el límite permitido.');
    }

    $tipos = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset($tipos[$mime])) {
        throw new RuntimeException('Tipo de archivo no permitido.');
    }
    if (!is_dir($directorio) && !mkdir($directorio, 0750, true)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento.');
    }
    $nombre = $prefijo . '_' . bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
    $ruta = rtrim($directorio, '/\\') . DIRECTORY_SEPARATOR . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $ruta)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }
    return $ruta;
}

function getOperacionActiva() {
    return $_SESSION['operacion_activa_id'] ?? $_SESSION['operacion_id'] ?? null;
}

function limpiarDatos($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
