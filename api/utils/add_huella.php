<?php
require_once '../../core/config.php';

verificarLogin();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'vincular') {
    $cedula = limpiarDatos($_POST['cedula']);
    $password = md5($_POST['password']); 
    $huella_data = $_POST['huella_binaria']; 

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ? AND password = ? AND activo = 1");
    $stmt->execute([$cedula, $password]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        
        $update = $pdo->prepare("UPDATE usuarios SET huella = ? WHERE id = ?");
        if ($update->execute([$huella_data, $usuario['id']])) {
            echo json_encode(['success' => true, 'message' => '¡Biometría vinculada correctamente!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar en base de datos']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Cédula o contraseña incorrectas']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ware Pro - Vincular Huella</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f5f5f5; min-height: 100vh; display: flex; flex-direction: column; }
        .header { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo-container img { height: 55px; }
        .main-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; text-align: center; }
        .sensor-area { width: 100px; height: 100px; background: #f9fafb; border: 3px dashed #e5e7eb; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 2.5rem; transition: all 0.3s ease; }
        .sensor-area.active { border-color: #FFD700; color: #FFD700; background: rgba(255, 215, 0, 0.05); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        .form-group { margin-bottom: 15px; text-align: left; }
        label { font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 5px; display: block; }
        .form-control { width: 100%; padding: 14px; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; font-size: 0.9rem; }
        .form-control:focus { outline: none; border-color: #FFD700; background: white; }
        .btn-primary { width: 100%; padding: 16px; border: none; border-radius: 8px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #2d2d2d; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-back { display: block; margin-top: 20px; color: #9ca3af; text-decoration: none; font-size: 0.85rem; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-content">
            <div class="logo-container"><img src="../../public/img/logo-2.png" alt="Ware Pro"></div>
            <a href="index.php" style="color: #666; text-decoration: none;"><i class="fas fa-times"></i></a>
        </div>
    </header>

    <div class="main-container">
        <div class="card">
            <div id="sensorIcon" class="sensor-area"><i class="fas fa-fingerprint"></i></div>
            <h2 style="font-size: 1.4rem; margin-bottom: 5px;">Vincular Biometría</h2>
            <p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 25px;">Confirma tu identidad para activar el sensor</p>

            <form id="huellaForm">
                <div class="form-group">
                    <label>NÚMERO DE CÉDULA</label>
                    <input type="text" class="form-control" id="cedula" placeholder="Ingresa tu cédula" required>
                </div>
                <div class="form-group">
                    <label>CONTRASEÑA</label>
                    <input type="password" class="form-control" id="password" placeholder="Ingresa tu contraseña" required>
                </div>
                <button type="button" id="btnCapturar" class="btn-primary">Verificar y Vincular</button>
            </form>
            <a href="index.php" class="btn-back">Cancelar</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script>
        const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } });

        document.getElementById('btnCapturar').addEventListener('click', async () => {
            const cedula = document.getElementById('cedula').value;
            const pass = document.getElementById('password').value;

            if (!cedula || !pass) return notyf.error('Completa todos los campos');
            if (!window.PublicKeyCredential) return notyf.error('Navegador no compatible');

            const sensorIcon = document.getElementById('sensorIcon');
            sensorIcon.classList.add('active');

            
            const challenge = new Uint8Array(32);
            window.crypto.getRandomValues(challenge);

            const options = {
                publicKey: {
                    
                    authenticatorSelection: { 
            authenticatorAttachment: "platform",
            residentKey: "required", 
            requireResidentKey: true,
            userVerification: "required"
        },
                    challenge: challenge,
                    rp: { name: "Ware Pro" },
                    user: {
                        id: Uint8Array.from(cedula, c => c.charCodeAt(0)),
                        name: cedula,
                        displayName: cedula
                    },
                    pubKeyCredParams: [{alg: -7, type: "public-key"}],
                    authenticatorSelection: { authenticatorAttachment: "platform" },
                    timeout: 60000
                    
                    
                }
                
                
            };

            try {
                
                const credential = await navigator.credentials.create(options);
                
                if (credential) {
                    const formData = new FormData();
                    formData.append('action', 'vincular');
                    formData.append('cedula', cedula);
                    formData.append('password', pass);
                    formData.append('huella_binaria', btoa(String.fromCharCode(...new Uint8Array(credential.rawId))));

                    const response = await fetch('add_huella.php', { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.success) {
                        notyf.success(result.message);
                        setTimeout(() => window.location.href='index.php', 2000);
                    } else {
                        notyf.error(result.message);
                        sensorIcon.classList.remove('active');
                    }
                }
            } catch (err) {
                notyf.error('Validación biométrica cancelada');
                sensorIcon.classList.remove('active');
            }
        });
    </script>
</body>
</html>