<?php
require_once '../../core/config.php';
verificarLogin();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    
    if ($_POST['action'] === 'verificar_cedula') {
        $cedula = $_POST['cedula'];
        $stmt = $pdo->prepare("SELECT id, nombre, huella FROM usuarios WHERE cedula = ? AND operacion_id = ?");
        $stmt->execute([$cedula, getOperacionActiva()]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt2 = $pdo->prepare("SELECT * FROM descansos WHERE usuario_id = ? AND estado = 'activo' AND operacion_id = ?");
            $stmt2->execute([$user['id'], getOperacionActiva()]);
            $descanso = $stmt2->fetch();

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'tiene_huella' => !empty($user['huella'])
                ],
                'descanso_activo' => $descanso ? $descanso : null
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cédula no encontrada en el sistema.']);
        }
        exit;
    }

    
    if ($_POST['action'] === 'guardar_huella') {
        $user_id = $_POST['user_id'];
        $huella = $_POST['huella'];
        $stmt = $pdo->prepare("UPDATE usuarios SET huella = ? WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$huella, $user_id, getOperacionActiva()]);
        echo json_encode(['success' => true]);
        exit;
    }

    
    if ($_POST['action'] === 'borrar_huella') {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE usuarios SET huella = NULL WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$user_id, getOperacionActiva()]);
        echo json_encode(['success' => true]);
        exit;
    }

    
    if ($_POST['action'] === 'iniciar') {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("SELECT id FROM descansos WHERE usuario_id = ? AND estado = 'activo' AND operacion_id = ?");
        $stmt->execute([$user_id, getOperacionActiva()]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'El empleado ya tiene un descanso activo.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO descansos (usuario_id, hora_inicio, estado, operacion_id) VALUES (?, NOW(), 'activo', ?)");
        if ($stmt->execute([$user_id, getOperacionActiva()])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar el descanso.']);
        }
        exit;
    }

    
    if ($_POST['action'] === 'finalizar') {
        $descanso_id = $_POST['descanso_id'];
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE descansos SET hora_fin = NOW(), estado = 'finalizado', duracion_minutos = TIMESTAMPDIFF(MINUTE, hora_inicio, NOW()) WHERE id = ? AND usuario_id = ? AND estado = 'activo' AND operacion_id = ?");
        if ($stmt->execute([$descanso_id, $user_id, getOperacionActiva()])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al finalizar el descanso.']);
        }
        exit;
    }
}

include '../../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosco de Descansos - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; min-height: 100vh; color: #333; }
        .page-container { max-width: 800px; margin: 0 auto; padding: 40px 20px; text-align: center; }
        
        .back-button { 
            display: inline-flex; align-items: center; gap: 8px; 
            background: #ffffff; color: #11998e; 
            padding: 10px 20px; border-radius: 50px; 
            text-decoration: none; font-weight: 600; font-size: 0.95rem;
            border: 2px solid #11998e; transition: all 0.3s ease; 
            margin-bottom: 30px; 
        }
        .back-button:hover { background: #11998e; color: white; transform: translateX(-5px); }

        .descanso-card {
            background: #ffffff; border-radius: 24px; padding: 50px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: 20px;
        }

        .icon-container {
            width: 100px; height: 100px; margin: 0 auto 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; color: white;
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            box-shadow: 0 10px 25px rgba(132, 250, 176, 0.4);
        }

        .title { font-size: 2rem; font-weight: 700; color: #2d3748; margin-bottom: 10px; }
        .subtitle { color: #64748b; font-size: 1.1rem; margin-bottom: 40px; }

        .btn-action {
            background: #11998e; color: white; border: none;
            padding: 15px 30px; border-radius: 50px; font-size: 1.1rem; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 10px;
            transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(17, 153, 142, 0.3);
            margin: 5px;
        }
        .btn-action:hover { transform: translateY(-3px); filter: brightness(1.1); }
        
        .btn-cedula { background: #f59e0b; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3); }
        .btn-cancel { background: #64748b; box-shadow: 0 10px 20px rgba(100, 116, 139, 0.3); }
        .btn-danger { background: #ef4444; box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); }

        .timer-display {
            font-size: 4rem; font-weight: 700; color: #11998e; margin-bottom: 20px; font-variant-numeric: tabular-nums;
        }
        
        .pulse { animation: pulse-animation 2s infinite; }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(132, 250, 176, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(132, 250, 176, 0); }
            100% { box-shadow: 0 0 0 0 rgba(132, 250, 176, 0); }
        }
    </style>
</head>
<body>

<div class="page-container">
    <a href="people.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver a People
    </a>

    <div class="descanso-card" id="card-content">
        <div class="icon-container">
            <i class="fas fa-users"></i>
        </div>
        <h2 class="title">Estación de Descansos</h2>
        <p class="subtitle">Identifícate para iniciar o finalizar tu tiempo de descanso.</p>
        
        <button class="btn-action" onclick="pedirCedula()">
            <i class="fas fa-id-card"></i> Ingresar Cédula
        </button>
    </div>
</div>

<script>
    let timerInterval;

    
    function bufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let str = ''; for (let charCode of bytes) str += String.fromCharCode(charCode);
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    function base64urlToBuffer(base64url) {
        const base64 = base64url.replace(/-/g, '+').replace(/_/, '/');
        const padLen = (4 - base64.length % 4) % 4;
        const padded = base64 + '='.repeat(padLen);
        const binary = atob(padded);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes.buffer;
    }

    function formatTime(dateStr) {
        const d = new Date(dateStr.replace(/-/g, '/'));
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    
    function pedirCedula() {
        Swal.fire({
            title: 'Ingrese Cédula del Empleado',
            input: 'text',
            inputAttributes: { autocapitalize: 'off' },
            showCancelButton: true,
            confirmButtonText: 'Buscar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#11998e',
            showLoaderOnConfirm: true,
            preConfirm: (cedula) => {
                const fd = new FormData();
                fd.append('action', 'verificar_cedula');
                fd.append('cedula', cedula);
                return fetch('descanso.php', { method: 'POST', body: fd })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) throw new Error(data.message);
                        return data;
                    })
                    .catch(error => { Swal.showValidationMessage(error.message); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                renderOpcionesUsuario(result.value.user, result.value.descanso_activo);
            }
        });
    }

    
    function renderOpcionesUsuario(user, descanso) {
        const container = document.getElementById('card-content');
        
        if (descanso) {
            
            container.innerHTML = `
                <div class="icon-container pulse">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="title">Descanso en Curso</h2>
                <p class="subtitle">Empleado: <strong>${user.nombre}</strong><br>Iniciado a las: <strong>${formatTime(descanso.hora_inicio)}</strong></p>
                <div class="timer-display" id="timer">00:00</div>
                
                <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:10px;">
                    <button class="btn-action btn-danger" onclick="finalizarDescanso(${descanso.id}, ${user.id})">
                        <i class="fas fa-stop-circle"></i> Finalizar Descanso
                    </button>
                    <button class="btn-action btn-cancel" onclick="location.reload()">
                        <i class="fas fa-times"></i> Salir
                    </button>
                </div>
            `;
            iniciarCronometro(descanso.hora_inicio);
        } else {
            
            container.innerHTML = `
                <div class="icon-container">
                    <i class="fas fa-mug-hot"></i>
                </div>
                <h2 class="title">Hola, ${user.nombre}</h2>
                <p class="subtitle">Elige cómo deseas registrar tu descanso.</p>
                
                <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:10px;">
                    <button class="btn-action" onclick="procesarHuella(${user.id}, ${user.tiene_huella})">
                        <i class="fas fa-fingerprint"></i> Iniciar con Huella
                    </button>
                    <button class="btn-action btn-cedula" onclick="registrarInicio(${user.id})">
                        <i class="fas fa-id-badge"></i> Iniciar solo Cédula
                    </button>
                    <button class="btn-action btn-cancel" onclick="location.reload()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            `;
        }
    }

    
    async function procesarHuella(userId, tieneHuella) {
        if (!window.PublicKeyCredential) {
            Swal.fire('Aviso', 'Tu dispositivo no soporta huella web. Iniciando con cédula...', 'info');
            registrarInicio(userId);
            return;
        }

        const storageKey = 'huella_user_' + userId;
        const rpId = window.location.hostname;

        try {
            if (!tieneHuella) {
                
                Swal.fire({
                    title: 'Configurando Huella',
                    text: 'Toca el lector de huella o usa el PIN de tu equipo para registrarte.',
                    icon: 'info',
                    showConfirmButton: false
                });

                const challenge = new Uint8Array(32); window.crypto.getRandomValues(challenge);
                const userBuffer = new Uint8Array(16); window.crypto.getRandomValues(userBuffer);

                const cred = await navigator.credentials.create({
                    publicKey: {
                        challenge: challenge,
                        rp: { name: "Ware Pro", id: rpId },
                        user: { id: userBuffer, name: "Emp_" + userId, displayName: "Empleado" },
                        pubKeyCredParams: [{ alg: -7, type: "public-key" }, { alg: -257, type: "public-key" }],
                        authenticatorSelection: { authenticatorAttachment: "platform", userVerification: "preferred" },
                        timeout: 60000
                    }
                });

                if (cred) {
                    const rawIdBase64 = bufferToBase64url(cred.rawId);
                    localStorage.setItem(storageKey, rawIdBase64);
                    
                    
                    const fd = new FormData();
                    fd.append('action', 'guardar_huella');
                    fd.append('user_id', userId);
                    fd.append('huella', rawIdBase64);
                    await fetch('descanso.php', { method: 'POST', body: fd });

                    registrarInicio(userId);
                }
            } else {
                
                const storedCredId = localStorage.getItem(storageKey);
                if (!storedCredId) throw new Error("Llave local no encontrada");

                const challenge = new Uint8Array(32); window.crypto.getRandomValues(challenge);
                const cred = await navigator.credentials.get({
                    publicKey: {
                        challenge: challenge,
                        allowCredentials: [{ type: "public-key", id: base64urlToBuffer(storedCredId) }],
                        userVerification: "preferred",
                        timeout: 60000
                    }
                });

                if (cred) {
                    registrarInicio(userId);
                }
            }
        } catch (error) {
            console.error("Fallo Huella:", error);
            
            
            localStorage.removeItem(storageKey);
            const fdReset = new FormData();
            fdReset.append('action', 'borrar_huella');
            fdReset.append('user_id', userId);
            fetch('descanso.php', { method: 'POST', body: fdReset });

            
            Swal.fire({
                icon: 'warning',
                title: 'Huella no reconocida',
                text: 'Se ha restablecido. Puedes iniciar con cédula o volver a registrar la huella en el próximo intento.',
                timer: 4000
            });
        }
    }

    
    function registrarInicio(userId) {
        const fd = new FormData();
        fd.append('action', 'iniciar');
        fd.append('user_id', userId);

        fetch('descanso.php', { method: 'POST', body: fd })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success', title: '¡Buen descanso!',
                    showConfirmButton: false, timer: 1500
                }).then(() => location.reload()); 
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    
    function finalizarDescanso(descansoId, userId) {
        Swal.fire({
            title: '¿Terminar descanso?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#11998e',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, finalizar'
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'finalizar');
                fd.append('descanso_id', descansoId);
                fd.append('user_id', userId);

                fetch('descanso.php', { method: 'POST', body: fd })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success', title: '¡De vuelta al trabajo!',
                            showConfirmButton: false, timer: 1500
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        });
    }

    
    function iniciarCronometro(horaInicioStr) {
        clearInterval(timerInterval);
        const startTime = new Date(horaInicioStr.replace(/-/g, '/')).getTime();
        
        function updateTimer() {
            const now = new Date().getTime();
            const diff = now - startTime;
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            const el = document.getElementById('timer');
            if(el) {
                el.innerText = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                if(minutes >= 30) el.style.color = '#ef4444';
            }
        }
        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
    }
</script>

</body>
</html>