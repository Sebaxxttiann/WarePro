<?php
require_once '../../core/config.php';

verificarLogin();
$token = $_GET['token'] ?? '';
$inventario = [];


$stmt = $pdo->prepare("SELECT * FROM tableros WHERE codigo_qr = ?");
$stmt->execute([$token]);
$tablero = $stmt->fetch();
$tablero_valido = $tablero !== false;


if (!$tablero_valido) {
    die("<div style='background:#1a1a1a; color:#FFD700; padding:20px; text-align:center; font-family:sans-serif; height:100vh; display:flex; align-items:center; justify-content:center;'><h2>QR Inválido o Tablero no encontrado</h2></div>");
}




$stmt_inv = $pdo->prepare("
    SELECT i.cantidad, p.material, p.id_material 
    FROM tablero_inventario i
    JOIN productos p ON i.id_material COLLATE utf8mb4_unicode_ci = p.id_material COLLATE utf8mb4_unicode_ci
    WHERE i.tablero_id = ? AND i.operacion_id = ?
    ORDER BY p.material ASC
");
$stmt_inv->execute([$tablero['id'], $tablero['operacion_id']]);
$inventario = $stmt_inv->fetchAll();




?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>WarePro 3D Visor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: #000; font-family: 'Poppins', sans-serif; }
        
        
        #camera-bg { position: absolute; top: 0; left: 0; width: 100vw; height: 100vh; object-fit: cover; z-index: 1; }
        
        
        .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.8) 100%); z-index: 2; pointer-events: none; }

        
        .scene {
            perspective: 1000px;
            width: 100vw; height: 100vh;
            display: flex; align-items: center; justify-content: center;
            position: absolute; z-index: 3;
        }

        
        .board-3d {
            width: 90%; max-width: 450px;
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-top: 4px solid #FFD700;
            border-bottom: 4px solid #FFD700;
            border-radius: 20px;
            padding: 30px 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5), inset 0 0 20px rgba(255, 215, 0, 0.05);
            
            
            transform-style: preserve-3d;
            transform: rotateX(0deg) rotateY(0deg);
            transition: transform 0.1s ease-out;
        }

        
        .pop-out { transform: translateZ(40px); }
        .pop-out-high { transform: translateZ(70px); }

        
        .header { text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 15px; margin-bottom: 20px; }
        .title { color: #fff; font-size: 22px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .subtitle { color: #FFD700; font-family: 'Share Tech Mono', monospace; font-size: 12px; margin-top: 5px; }

        .list-container { max-height: 50vh; overflow-y: auto; padding-right: 10px; }
        .list-container::-webkit-scrollbar { width: 4px; }
        .list-container::-webkit-scrollbar-thumb { background: #FFD700; border-radius: 4px; }

        .item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 12px 0; border-bottom: 1px dashed rgba(255, 215, 0, 0.15);
        }
        .item-qty { 
            background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3);
            color: #FFD700; font-weight: 700; font-size: 18px; padding: 4px 12px; border-radius: 8px;
        }
        .item-name { color: #e2e8f0; font-size: 14px; font-weight: 600; text-align: right; width: 65%; line-height: 1.2; }

        .btn-refresh {
            width: 100%; margin-top: 25px; background: transparent; border: 1px solid #FFD700;
            color: #FFD700; padding: 15px; border-radius: 12px; font-weight: bold; font-size: 14px;
            text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s;
        }
        .btn-refresh:active { background: #FFD700; color: #000; transform: translateZ(30px) scale(0.95); }

    </style>
</head>
<body>

    <video id="camera-bg" autoplay playsinline></video>
    <div class="overlay"></div>

    <div class="scene" id="scene">
        <div class="board-3d" id="board">
            
            <div class="header pop-out-high">
                <h2 class="title"><?php echo htmlspecialchars($tablero['nombre']); ?></h2>
                <div class="subtitle">● SYNC EN VIVO</div>
            </div>

            <div class="list-container pop-out">
                <?php if (empty($inventario)): ?>
                    <div style="text-align: center; color: #ef4444; padding: 30px 0; font-weight: bold; font-size: 14px;">TABLERO VACÍO</div>
                <?php else: ?>
                    <?php foreach ($inventario as $item): ?>
                        <div class="item">
                            <div class="item-qty"><?php echo str_pad($item['cantidad'], 3, "0", STR_PAD_LEFT); ?></div>
                            <div class="item-name"><?php echo htmlspecialchars($item['material']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button class="btn-refresh pop-out" onclick="window.location.reload()">Sincronizar</button>
        </div>
    </div>

    <script>
        
        const video = document.getElementById('camera-bg');
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
            .then(stream => { video.srcObject = stream; })
            .catch(err => { alert("No se pudo iniciar la cámara."); });
        }

        
        const scene = document.getElementById('scene');
        const board = document.getElementById('board');

        function handleMove(clientX, clientY) {
            const x = (clientX / window.innerWidth - 0.5) * 2; 
            const y = (clientY / window.innerHeight - 0.5) * 2;
            
            
            const rotateX = y * -20; 
            const rotateY = x * 20;

            board.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        }

        
        scene.addEventListener('mousemove', (e) => {
            handleMove(e.clientX, e.clientY);
        });

        
        scene.addEventListener('touchmove', (e) => {
            handleMove(e.touches[0].clientX, e.touches[0].clientY);
        });

        
        scene.addEventListener('mouseleave', () => { board.style.transform = `rotateX(0deg) rotateY(0deg)`; });
        scene.addEventListener('touchend', () => { board.style.transform = `rotateX(0deg) rotateY(0deg)`; });
    </script>
</body>
</html>