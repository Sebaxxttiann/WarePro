<?php
require_once 'core/config.php';

if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare('UPDATE usuarios SET session_token = NULL WHERE id = ? AND session_token = ?');
    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['session_token'] ?? '']);
}


$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


session_destroy();


header('Location: index.php');
exit();
?>
