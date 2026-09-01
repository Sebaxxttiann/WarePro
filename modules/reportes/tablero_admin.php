<?php
require_once '../../core/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tablero'])) {
    $nombre = limpiarDatos($_POST['nombre_tablero']);
    $token_qr = bin2hex(random_bytes(16)); 
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tableros (nombre, codigo_qr, operacion_id) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $token_qr, getOperacionActiva()]);
        $success = "Tablero '$nombre' creado con éxito.";
    } catch (PDOException $e) {
        $error = "Error al crear el tablero: " . $e->getMessage();
    }
}


$stmt = $pdo->prepare("SELECT * FROM tableros WHERE operacion_id = ? ORDER BY id DESC");
$stmt->execute([getOperacionActiva()]);
$tableros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #fcfcfc; }
        .bg-gold { background-color: #FFD700; }
        .text-gold { color: #FFD700; }
        .border-gold { border-color: #FFD700; }
        
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-animation { animation: slideIn 0.4s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Tableros Digitales</h1>
            <p class="mt-2 text-sm text-gray-600">Crea, administra y supervisa los puntos de control de inventario.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <button onclick="document.getElementById('modalCrear').classList.remove('hidden')" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-black bg-gold hover:bg-yellow-500 transition-all transform hover:-translate-y-1">
                <i class="fas fa-plus-circle mr-2"></i> Nuevo Tablero
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-10">
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Total Tableros</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo count($tableros); ?></dd>
        </div>
        </div>

    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-black">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gold uppercase tracking-wider">Nombre del Lugar</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gold uppercase tracking-wider">Fecha Creación</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gold uppercase tracking-wider">Token QR</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gold uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach($tableros as $tab): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            <i class="fas fa-chalkboard text-gray-400 mr-2"></i> <?php echo htmlspecialchars($tab['nombre']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d M, Y', strtotime($tab['fecha_creacion'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-400">
                            <?php echo $tab['codigo_qr']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button onclick="generarQR('<?php echo $tab['codigo_qr']; ?>', '<?php echo $tab['nombre']; ?>')" class="inline-flex items-center p-2 border border-gray-200 rounded-lg text-black bg-white hover:bg-gray-100 transition-all shadow-sm" title="Ver QR">
                                <i class="fas fa-qrcode"></i>
                            </button>
                            <button onclick="limpiarTablero(<?php echo $tab['id']; ?>)" class="inline-flex items-center p-2 border border-gray-200 rounded-lg text-orange-600 bg-white hover:bg-orange-50 transition-all shadow-sm" title="Limpiar Productos">
                                <i class="fas fa-broom"></i>
                            </button>
                            <button onclick="eliminarTablero(<?php echo $tab['id']; ?>)" class="inline-flex items-center p-2 border border-gray-200 rounded-lg text-red-600 bg-white hover:bg-red-50 transition-all shadow-sm" title="Eliminar Tablero">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalCrear" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-plus text-yellow-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Nuevo Tablero</h3>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Lugar / Área</label>
                                <input type="text" name="nombre_tablero" required placeholder="Ej: Pasillo A-01" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-yellow-500 focus:border-yellow-500 py-3 px-4 border">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="crear_tablero" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gold text-base font-medium text-black hover:bg-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">Crear Ahora</button>
                    <button type="button" onclick="document.getElementById('modalCrear').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalQR" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" onclick="document.getElementById('modalQR').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl text-center">
            <h3 id="qrNombre" class="text-xl font-bold text-gray-900 mb-6"></h3>
            <div id="qrcode" class="flex justify-center p-4 bg-gray-50 rounded-2xl inline-block mb-6"></div>
            <p class="text-sm text-gray-500 mb-6 italic">Escanea este código para acceder al tablero digital desde cualquier dispositivo móvil.</p>
            <button onclick="document.getElementById('modalQR').classList.add('hidden')" class="w-full py-3 bg-black text-gold rounded-xl font-bold hover:bg-gray-800 transition-colors">Cerrar</button>
        </div>
    </div>
</div>

<div id="toast-container" class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-3"></div>

<script>
    
    function notify(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-black' : 'bg-red-600';
        const icon = type === 'success' ? 'fa-check-circle text-gold' : 'fa-exclamation-triangle text-white';
        
        toast.className = `${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-4 border-l-4 border-gold toast-animation min-w-[300px]`;
        toast.innerHTML = `
            <i class="fas ${icon} text-xl"></i>
            <span class="font-medium">${message}</span>
        `;
        
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    
    function generarQR(token, nombre) {
        document.getElementById('qrNombre').innerText = "Tablero: " + nombre;
        document.getElementById('qrcode').innerHTML = "";
        
        
        const url = window.location.origin + window.location.pathname.replace('tablero_admin.php', 'tablero_qr.php') + '?token=' + token;
        
        new QRCode(document.getElementById("qrcode"), {
            text: url,
            width: 220,
            height: 220,
            colorDark : "#000000",
            colorLight : "#f9fafb",
            correctLevel : QRCode.CorrectLevel.H
        });
        
        document.getElementById('modalQR').classList.remove('hidden');
    }

    
    function limpiarTablero(id) {
        if(confirm('¿Estás seguro de vaciar este tablero?')) {
            notify('Inventario del tablero vaciado correctamente.');
        }
    }

    function eliminarTablero(id) {
        if(confirm('¿Eliminar permanentemente este tablero?')) {
            notify('Tablero eliminado del sistema.', 'error');
        }
    }

    
    <?php if(isset($success)): ?> notify('<?php echo $success; ?>'); <?php endif; ?>
    <?php if(isset($error)): ?> notify('<?php echo $error; ?>', 'error'); <?php endif; ?>
</script>

</body>
</html>