<?php
require_once '../../core/config.php';
verificarLogin();

$esAdmin = in_array($_SESSION['cargo'], ['admin', 'super_admin'], true);
$esSuperAdmin = $_SESSION['cargo'] === 'super_admin';
$operacionActiva = getOperacionActiva();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    
    if ($action == 'update_profile') {
        $nombre = limpiarDatos($_POST['nombre']);
        $cedula = limpiarDatos($_POST['cedula']);
        $password_actual = $_POST['password_actual'];
        $password_nueva = $_POST['password_nueva'];
        $confirmar_password = $_POST['confirmar_password'];
        
        
        if (!empty($password_nueva)) {
            if (empty($password_actual)) {
                echo json_encode(['success' => false, 'message' => 'Debes ingresar tu contraseña actual']);
                exit();
            }
            
            if ($password_nueva !== $confirmar_password) {
                echo json_encode(['success' => false, 'message' => 'Las contraseñas nuevas no coinciden']);
                exit();
            }
            
            
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch();
            
            if (md5($password_actual) !== $usuario['password']) {
                echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
                exit();
            }
        }
        
        
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ? AND id != ?");
        $stmt->execute([$cedula, $_SESSION['usuario_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada por otro usuario']);
            exit();
        }
        
        try {
            
            if (!empty($password_nueva)) {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, cedula = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $cedula, md5($password_nueva), $_SESSION['usuario_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, cedula = ? WHERE id = ?");
                $stmt->execute([$nombre, $cedula, $_SESSION['usuario_id']]);
            }
            
            
            $_SESSION['nombre'] = $nombre;
            $_SESSION['cedula'] = $cedula;
            
            echo json_encode(['success' => true, 'message' => 'Perfil actualizado exitosamente']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil']);
        }
        exit();
    }
    
    
    if ($esAdmin) {


        if ($action == 'edit_user') {
            $user_id = $_POST['user_id'];
            $nombre = limpiarDatos($_POST['nombre']);
            $cedula = limpiarDatos($_POST['cedula']);
            $cargo = $_POST['cargo'];
            $activo = isset($_POST['activo']) ? 1 : 0;
            $password_nueva = $_POST['password_nueva'];

            if ($cargo === 'super_admin' && !$esSuperAdmin) {
                echo json_encode(['success' => false, 'message' => 'Solo un super_admin puede asignar ese cargo']);
                exit();
            }

            $stmtObjetivo = $pdo->prepare("SELECT operacion_id FROM usuarios WHERE id = ?");
            $stmtObjetivo->execute([$user_id]);
            $usuarioObjetivo = $stmtObjetivo->fetch();
            if (!$usuarioObjetivo || (!$esSuperAdmin && (int)$usuarioObjetivo['operacion_id'] !== (int)$operacionActiva)) {
                echo json_encode(['success' => false, 'message' => 'No tienes permiso sobre ese usuario']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ? AND id != ?");
            $stmt->execute([$cedula, $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada por otro usuario']);
                exit();
            }

            try {
                if (!empty($password_nueva)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, cedula = ?, cargo = ?, activo = ?, password = ? WHERE id = ?");
                    $stmt->execute([$nombre, $cedula, $cargo, $activo, md5($password_nueva), $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, cedula = ?, cargo = ?, activo = ? WHERE id = ?");
                    $stmt->execute([$nombre, $cedula, $cargo, $activo, $user_id]);
                }

                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario']);
            }
            exit();
        }


        if ($action == 'delete_user') {
            $user_id = $_POST['user_id'];


            if ($user_id == $_SESSION['usuario_id']) {
                echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
                exit();
            }

            $stmtObjetivo = $pdo->prepare("SELECT operacion_id FROM usuarios WHERE id = ?");
            $stmtObjetivo->execute([$user_id]);
            $usuarioObjetivo = $stmtObjetivo->fetch();
            if (!$usuarioObjetivo || (!$esSuperAdmin && (int)$usuarioObjetivo['operacion_id'] !== (int)$operacionActiva)) {
                echo json_encode(['success' => false, 'message' => 'No tienes permiso sobre ese usuario']);
                exit();
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);

                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario']);
            }
            exit();
        }


        if ($action == 'create_user') {
            $nombre = limpiarDatos($_POST['nombre']);
            $cedula = limpiarDatos($_POST['cedula']);
            $cargo = $_POST['cargo'];
            $password = $_POST['password'];

            if ($cargo === 'super_admin' && !$esSuperAdmin) {
                echo json_encode(['success' => false, 'message' => 'Solo un super_admin puede crear ese cargo']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ?");
            $stmt->execute([$cedula]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
                exit();
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, cedula, cargo, password, operacion_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $cedula, $cargo, md5($password), $operacionActiva]);

                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al crear el usuario']);
            }
            exit();
        }

        if ($action == 'crear_operacion') {
            if (!$esSuperAdmin) {
                echo json_encode(['success' => false, 'message' => 'Solo un super_admin puede crear operaciones']);
                exit();
            }
            $nombreOperacion = limpiarDatos($_POST['nombre_operacion'] ?? '');
            if ($nombreOperacion === '') {
                echo json_encode(['success' => false, 'message' => 'El nombre de la operación es obligatorio']);
                exit();
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO operaciones (nombre) VALUES (?)");
                $stmt->execute([$nombreOperacion]);
                echo json_encode(['success' => true, 'message' => "Operación '$nombreOperacion' creada exitosamente", 'id' => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    echo json_encode(['success' => false, 'message' => 'Ya existe una operación con ese nombre']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al crear la operación']);
                }
            }
            exit();
        }

        if ($action == 'toggle_operacion') {
            if (!$esSuperAdmin) {
                echo json_encode(['success' => false, 'message' => 'Solo un super_admin puede hacer esto']);
                exit();
            }
            $operacionIdToggle = (int)($_POST['operacion_id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE operaciones SET activo = NOT activo WHERE id = ?");
                $stmt->execute([$operacionIdToggle]);
                echo json_encode(['success' => true, 'message' => 'Estado de la operación actualizado']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la operación']);
            }
            exit();
        }
    }
}


$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario_actual = $stmt->fetch();


$todos_usuarios = [];
$nombreOperacionActiva = '';
if (in_array($_SESSION['cargo'], ['admin', 'super_admin'], true)) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE operacion_id = ? ORDER BY fecha_registro DESC");
    $stmt->execute([getOperacionActiva()]);
    $todos_usuarios = $stmt->fetchAll();

    $stmtOp = $pdo->prepare("SELECT nombre FROM operaciones WHERE id = ?");
    $stmtOp->execute([getOperacionActiva()]);
    $nombreOperacionActiva = $stmtOp->fetchColumn() ?: 'Desconocida';
}

$todasOperaciones = [];
if ($esSuperAdmin) {
    $stmtTodasOp = $pdo->query("
        SELECT o.*, (SELECT COUNT(*) FROM usuarios u WHERE u.operacion_id = o.id) as total_usuarios
        FROM operaciones o
        ORDER BY o.nombre ASC
    ");
    $todasOperaciones = $stmtTodasOp->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .main-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #FFD700;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-title i {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-size: 1.5rem;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .page-subtitle {
            color: #666;
            font-size: 1rem;
            margin-left: 65px;
        }

        
        .admin-tabs {
            display: flex;
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            gap: 1rem;
        }

        .tab-btn {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .tab-btn:hover:not(.active) {
            background: #e9ecef;
            color: #333;
        }

        
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .profile-sidebar {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .profile-avatar {
            text-align: center;
            margin-bottom: 2rem;
        }

        .avatar-container {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .avatar-container i {
            font-size: 3rem;
            color: #1a1a1a;
        }

        .profile-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .profile-role {
            display: inline-block;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-info {
            margin-top: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-icon {
            width: 35px;
            height: 35px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFD700;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        .profile-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-header i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
        }

        .form-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #FFD700;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #333;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control:disabled {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
        }

        .form-control.password-input {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
        }

        .password-toggle:hover {
            color: #FFD700;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.8rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #856404;
        }

        
        .users-table-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .table-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .table-title i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
        }

        .table-title h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .users-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .role-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            text-align: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #FFD700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #333;
            font-weight: 500;
        }

        
        .custom-checkbox {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 25px;
        }

        .custom-checkbox input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .checkbox-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 25px;
        }

        .checkbox-slider:before {
            position: absolute;
            content: "";
            height: 19px;
            width: 19px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .checkbox-slider {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }

        input:checked + .checkbox-slider:before {
            transform: translateX(25px);
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 1rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .admin-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }

            .users-table {
                font-size: 0.8rem;
            }

            .users-table th,
            .users-table td {
                padding: 10px 5px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 2px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../core/header.php'; ?>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-text">Procesando...</div>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-user-circle"></i>
                <h1><?php echo in_array($_SESSION['cargo'], ['admin', 'super_admin'], true) ? 'Panel de Administración' : 'Mi Perfil'; ?></h1>
            </div>
            <p class="page-subtitle">
                <?php echo in_array($_SESSION['cargo'], ['admin', 'super_admin'], true) ? 'Gestiona usuarios y tu información personal' : 'Gestiona tu información personal y configuración de cuenta'; ?>
            </p>
            <?php if ($esAdmin): ?>
            <p class="page-subtitle" style="margin-top: 6px; font-weight: 700; color: #FFA500;">
                <i class="fas fa-building"></i>
                Operación : <?php echo htmlspecialchars($nombreOperacionActiva); ?>
                <?php if ($esSuperAdmin): ?>
                    <span style="font-weight: 400; color: #666;">— usa el selector del encabezado para cambiarla antes de crear o gestionar usuarios de otra sede</span>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>

        <?php if (in_array($_SESSION['cargo'], ['admin', 'super_admin'], true)): ?>
        
        <div class="admin-tabs">
            <button class="tab-btn active" onclick="showTab('profile')">
                <i class="fas fa-user-edit"></i>
                Mi Perfil
            </button>
            <button class="tab-btn" onclick="showTab('users')">
                <i class="fas fa-users"></i>
                Gestionar Usuarios
            </button>
            <button class="tab-btn" onclick="showTab('create')">
                <i class="fas fa-user-plus"></i>
                Crear Usuario
            </button>
            <?php if ($esSuperAdmin): ?>
            <button class="tab-btn" onclick="showTab('operaciones')">
                <i class="fas fa-building"></i>
                Operaciones
            </button>
            <?php endif; ?>
        </div>

        
        <div id="profile-tab" class="tab-content active">
        <?php endif; ?>

            <div class="profile-container">
                
                <div class="profile-sidebar">
                    <div class="profile-avatar">
                        <div class="avatar-container">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($usuario_actual['nombre']); ?></div>
                        <div class="profile-role"><?php echo ucfirst($usuario_actual['cargo']); ?></div>
                    </div>

                    <div class="profile-info">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Cédula</div>
                                <div class="info-value"><?php echo htmlspecialchars($usuario_actual['cedula']); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Cargo</div>
                                <div class="info-value"><?php echo ucfirst($usuario_actual['cargo']); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Fecha de Registro</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($usuario_actual['fecha_registro'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Estado</div>
                                <div class="info-value"><?php echo $usuario_actual['activo'] ? 'Activo' : 'Inactivo'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="profile-form">
                    <div class="form-header">
                        <i class="fas fa-edit"></i>
                        <h2>Editar Información</h2>
                    </div>

                    <form id="profileForm">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Información Personal
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($usuario_actual['nombre']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Número de Cédula</label>
                                    <input type="text" class="form-control" name="cedula" value="<?php echo htmlspecialchars($usuario_actual['cedula']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Cargo</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($usuario_actual['cargo']); ?>" disabled>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-lock"></i>
                                Cambiar Contraseña (Opcional)
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Deja estos campos vacíos si no deseas cambiar tu contraseña
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contraseña Actual</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control password-input" name="password_actual" id="passwordActual">
                                    <button type="button" class="password-toggle" onclick="togglePassword('passwordActual')">
                                        <i class="fas fa-eye" id="passwordActual-icon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Nueva Contraseña</label>
                                    <div style="position: relative;">
                                        <input type="password" class="form-control password-input" name="password_nueva" id="passwordNueva">
                                        <button type="button" class="password-toggle" onclick="togglePassword('passwordNueva')">
                                            <i class="fas fa-eye" id="passwordNueva-icon"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Confirmar Nueva Contraseña</label>
                                    <div style="position: relative;">
                                        <input type="password" class="form-control password-input" name="confirmar_password" id="confirmarPassword">
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirmarPassword')">
                                            <i class="fas fa-eye" id="confirmarPassword-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php if (in_array($_SESSION['cargo'], ['admin', 'super_admin'], true)): ?>
        </div>

        
        <div id="users-tab" class="tab-content">
            <div class="users-table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-users"></i>
                        <h2>Usuarios Registrados</h2>
                    </div>
                    <div>
                        <span class="info-value"><?php echo count($todos_usuarios); ?> usuarios totales</span>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Cargo</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos_usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['cedula']); ?></td>
                                <td>
                                    <span class="role-badge"><?php echo ucfirst($usuario['cargo']); ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="editUser(<?php echo $usuario['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div id="create-tab" class="tab-content">
            <div class="profile-form">
                <div class="form-header">
                    <i class="fas fa-user-plus"></i>
                    <h2>Crear Nuevo Usuario</h2>
                </div>
                <p style="background:#fff8e1;border-left:4px solid #FFA500;padding:10px 14px;border-radius:6px;margin-bottom:16px;font-weight:600;">
                    <i class="fas fa-building"></i>
                    Este usuario quedará creado en: <?php echo htmlspecialchars($nombreOperacionActiva); ?>
                    <?php if ($esSuperAdmin): ?>
                        <br><span style="font-weight:400;font-size:0.9em;">¿Necesitas crearlo en otra operación? Cambia la operación activa en el selector del encabezado y vuelve a esta pestaña.</span>
                    <?php endif; ?>
                </p>

                <form id="createUserForm">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            Información del Usuario
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Número de Cédula</label>
                                <input type="text" class="form-control" name="cedula" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Cargo</label>
                                <select class="form-control" name="cargo" required>
                                    <option value="">Seleccionar cargo</option>
                                    <?php if ($esSuperAdmin): ?>
                                    <option value="super_admin">Super Administrador</option>
                                    <?php endif; ?>
                                    <option value="admin">Administrador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="verificador">Verificador</option>
                                    <option value="auxiliar">Auxiliar</option>
                                    <option value="coplas">Coplas</option>
                                    <option value="lider">Lider</option>
                                    <option value="operador">Operador</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contraseña</label>
                                <div style="position: relative;">
                                    <input type="password" class="form-control password-input" name="password" id="createPassword" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('createPassword')">
                                        <i class="fas fa-eye" id="createPassword-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetCreateForm()">
                            <i class="fas fa-undo"></i>
                            Limpiar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i>
                            Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($esSuperAdmin): ?>
        <div id="operaciones-tab" class="tab-content">
            <div class="profile-form">
                <div class="form-header">
                    <i class="fas fa-building"></i>
                    <h2>Operaciones (sedes)</h2>
                </div>

                <form id="crearOperacionForm" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:24px;">
                    <div class="form-group" style="flex:1; min-width:220px;">
                        <label class="form-label">Nombre de la nueva operación</label>
                        <input type="text" class="form-control" name="nombre_operacion" placeholder="Ej: Medellín" required>
                    </div>
                    <button type="submit" class="btn btn-success" style="white-space:nowrap;">
                        <i class="fas fa-plus"></i>
                        Crear Operación
                    </button>
                </form>

                <div class="users-table-container" style="overflow-x:auto; padding:0;">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuarios</th>
                            <th>Estado</th>
                            <th>Creada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todasOperaciones as $op): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($op['nombre']); ?><?php if ((int)$op['id'] === (int)$operacionActiva) echo ' <span style="color:#FFA500;font-weight:700;">(activa)</span>'; ?></td>
                            <td><?php echo (int)$op['total_usuarios']; ?></td>
                            <td><?php echo $op['activo'] ? 'Activa' : 'Inactiva'; ?></td>
                            <td><?php echo htmlspecialchars($op['fecha_creacion']); ?></td>
                            <td style="white-space:nowrap;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleOperacion(<?php echo (int)$op['id']; ?>)">
                                    <?php echo $op['activo'] ? 'Desactivar' : 'Activar'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <p style="margin-top:16px;color:#666;font-size:0.9em;">
                    Una operación nueva empieza sin zonas, metas ni indicadores configurados — desde "Gestionar Usuarios" crea primero un administrador para esa operación, y que él (o tú, cambiando la operación activa en el encabezado) configure sus zonas y metas.
                </p>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>


    <div id="editUserModal" style="display: none;">

    </div>

    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function resetForm() {
            Swal.fire({
                title: '¿Cancelar cambios?',
                text: 'Se perderán todos los cambios no guardados',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FFD700',
                cancelButtonColor: '#666',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No, continuar editando'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }

        function resetCreateForm() {
            document.getElementById('createUserForm').reset();
        }

        
        function showTab(tabName) {
            
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            
            document.getElementById(tabName + '-tab').classList.add('active');
            
            
            event.target.classList.add('active');
        }

        
        document.querySelectorAll('input[name="cedula"]').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
        });

        
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            showLoading();
            
            const formData = new FormData(this);
            formData.append('action', 'update_profile');
            
            try {
                const response = await fetch('perfil.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Perfil Actualizado!',
                        text: result.message,
                        confirmButtonColor: '#FFD700'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#FFD700'
                    });
                }
            } catch (error) {
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Intenta nuevamente.',
                    confirmButtonColor: '#FFD700'
                });
            }
        });

        
        <?php if (in_array($_SESSION['cargo'], ['admin', 'super_admin'], true)): ?>
        document.getElementById('createUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            showLoading();
            
            const formData = new FormData(this);
            formData.append('action', 'create_user');
            
            try {
                const response = await fetch('perfil.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Usuario Creado!',
                        text: result.message,
                        confirmButtonColor: '#FFD700'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#FFD700'
                    });
                }
            } catch (error) {
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Intenta nuevamente.',
                    confirmButtonColor: '#FFD700'
                });
            }
        });


        const crearOperacionFormEl = document.getElementById('crearOperacionForm');
        if (crearOperacionFormEl) {
            crearOperacionFormEl.addEventListener('submit', async function(e) {
                e.preventDefault();
                showLoading();

                const formData = new FormData(this);
                formData.append('action', 'crear_operacion');

                try {
                    const response = await fetch('perfil.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    hideLoading();

                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Operación Creada!',
                            text: result.message,
                            confirmButtonColor: '#FFD700'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message,
                            confirmButtonColor: '#FFD700'
                        });
                    }
                } catch (error) {
                    hideLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión. Intenta nuevamente.',
                        confirmButtonColor: '#FFD700'
                    });
                }
            });
        }

        async function toggleOperacion(id) {
            const confirm = await Swal.fire({
                title: '¿Cambiar estado de la operación?',
                text: 'Esto activará o desactivará la operación seleccionada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FFD700',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar'
            });

            if (!confirm.isConfirmed) return;

            showLoading();
            const formData = new FormData();
            formData.append('action', 'toggle_operacion');
            formData.append('operacion_id', id);

            try {
                const response = await fetch('perfil.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                hideLoading();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Listo',
                        text: result.message,
                        confirmButtonColor: '#FFD700'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#FFD700'
                    });
                }
            } catch (error) {
                hideLoading();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Intenta nuevamente.',
                    confirmButtonColor: '#FFD700'
                });
            }
        }


        async function editUser(userId) {
            const { value: formValues } = await Swal.fire({
                title: 'Editar Usuario',
                html: `
                    <div style="text-align: left; margin: 20px 0;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nombre:</label>
                            <input id="edit-nombre" class="swal2-input" style="margin: 0; width: 100%;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Cédula:</label>
                            <input id="edit-cedula" class="swal2-input" style="margin: 0; width: 100%;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Cargo:</label>
                            <select id="edit-cargo" class="swal2-input" style="margin: 0; width: 100%;">
                                <?php if ($esSuperAdmin): ?>
                                <option value="super_admin">Super Administrador</option>
                                <?php endif; ?>
                                <option value="admin">Administrador</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="verificador">Verificador</option>
                                <option value="auxiliar">Auxiliar</option>
                                <option value="coplas">Copla</option>
                                <option value="lider">Lider</option>
                                <option value="operador">Operador</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nueva Contraseña (opcional):</label>
                            <input id="edit-password" type="password" class="swal2-input" style="margin: 0; width: 100%;" placeholder="Dejar vacío para no cambiar">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
                                <input id="edit-activo" type="checkbox" style="transform: scale(1.2);">
                                Usuario Activo
                            </label>
                        </div>
                    </div>
                `,
                focusConfirm: false,
                confirmButtonColor: '#FFD700',
                confirmButtonText: 'Actualizar Usuario',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    
                    fetch(`../../api/personal/get_user.php?id=${userId}`)
                        .then(response => response.json())
                        .then(user => {
                            document.getElementById('edit-nombre').value = user.nombre;
                            document.getElementById('edit-cedula').value = user.cedula;
                            document.getElementById('edit-cargo').value = user.cargo;
                            document.getElementById('edit-activo').checked = user.activo == 1;
                        });
                },
                preConfirm: () => {
                    return {
                        nombre: document.getElementById('edit-nombre').value,
                        cedula: document.getElementById('edit-cedula').value,
                        cargo: document.getElementById('edit-cargo').value,
                        password_nueva: document.getElementById('edit-password').value,
                        activo: document.getElementById('edit-activo').checked
                    }
                }
            });

            if (formValues) {
                showLoading();
                
                const formData = new FormData();
                formData.append('action', 'edit_user');
                formData.append('user_id', userId);
                formData.append('nombre', formValues.nombre);
                formData.append('cedula', formValues.cedula);
                formData.append('cargo', formValues.cargo);
                formData.append('password_nueva', formValues.password_nueva);
                if (formValues.activo) {
                    formData.append('activo', '1');
                }
                
                try {
                    const response = await fetch('perfil.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    hideLoading();
                    
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Usuario Actualizado!',
                            text: result.message,
                            confirmButtonColor: '#FFD700'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message,
                            confirmButtonColor: '#FFD700'
                        });
                    }
                } catch (error) {
                    hideLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión. Intenta nuevamente.',
                        confirmButtonColor: '#FFD700'
                    });
                }
            }
        }

        
        function deleteUser(userId, userName) {
            Swal.fire({
                title: '¿Eliminar Usuario?',
                text: `¿Estás seguro de que deseas eliminar a "${userName}"? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#666',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    showLoading();
                    
                    const formData = new FormData();
                    formData.append('action', 'delete_user');
                    formData.append('user_id', userId);
                    
                    try {
                        const response = await fetch('perfil.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        hideLoading();
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Usuario Eliminado!',
                                text: result.message,
                                confirmButtonColor: '#FFD700'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: result.message,
                                confirmButtonColor: '#FFD700'
                            });
                        }
                    } catch (error) {
                        hideLoading();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error de conexión. Intenta nuevamente.',
                            confirmButtonColor: '#FFD700'
                        });
                    }
                }
            });
        }
        <?php endif; ?>

        
        document.querySelector('input[name="confirmar_password"]').addEventListener('input', function() {
            const nuevaPassword = document.querySelector('input[name="password_nueva"]').value;
            const confirmarPassword = this.value;
            
            if (nuevaPassword && confirmarPassword && nuevaPassword !== confirmarPassword) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e8ed';
            }
        });
    </script>
</body>
</html>
