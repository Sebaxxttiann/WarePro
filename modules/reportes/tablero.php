<?php
ob_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../core/header.php'; 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save') {
    ob_clean(); 
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    
    $tab_id = $data['tablero_id'] ?? null;
    $items = $data['items'] ?? [];

    if (!$tab_id || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        foreach ($items as $item) {
            $sku = $item['sku'];
            $qty = (int)$item['cantidad'];
            $tipo = $item['tipo']; 

            $stmtCheck = $pdo->prepare("SELECT id, cantidad FROM tablero_inventario WHERE tablero_id = ? AND id_material = ? AND operacion_id = ?");
            $stmtCheck->execute([$tab_id, $sku, getOperacionActiva()]);
            $row = $stmtCheck->fetch();

            if ($row) {

                $nuevaCantidad = $tipo === 'ingreso' ? ($row['cantidad'] + $qty) : ($row['cantidad'] - $qty);

                if ($nuevaCantidad <= 0) {
                    $pdo->prepare("DELETE FROM tablero_inventario WHERE id = ? AND operacion_id = ?")->execute([$row['id'], getOperacionActiva()]);
                } else {
                    $pdo->prepare("UPDATE tablero_inventario SET cantidad = ? WHERE id = ? AND operacion_id = ?")->execute([$nuevaCantidad, $row['id'], getOperacionActiva()]);
                }
            } else {

                if ($tipo === 'ingreso') {
                    $pdo->prepare("INSERT INTO tablero_inventario (tablero_id, id_material, cantidad, operacion_id) VALUES (?, ?, ?, ?)")->execute([$tab_id, $sku, $qty, getOperacionActiva()]);
                }
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit; 
}


try {
    $stmt = $pdo->prepare("SELECT * FROM tableros WHERE operacion_id = ? ORDER BY nombre ASC");
    $stmt->execute([getOperacionActiva()]);
    $tableros = $stmt->fetchAll();
    
    $stmtProd = $pdo->query("SELECT id_material, material FROM productos LIMIT 1500");
    $todos_los_productos = $stmtProd->fetchAll();
} catch (PDOException $e) {
    echo "<div style='margin: 20px; padding: 20px; background-color: #fee2e2; border: 2px solid #ef4444; border-radius: 10px; color: #b91c1c; font-family: sans-serif;'>";
    echo "<strong>Error de Base de Datos:</strong> " . $e->getMessage();
    echo "</div>";
    die();
}

$tablero_id = $_GET['tablero_id'] ?? null;
$tablero_actual = null;
$inventario = [];

if ($tablero_id) {
    foreach ($tableros as $t) { if ($t['id'] == $tablero_id) { $tablero_actual = $t; break; } }
    
    if ($tablero_actual) {
        $stmt_inv = $pdo->prepare("
            SELECT i.cantidad, p.material, p.id_material 
            FROM tablero_inventario i 
            JOIN productos p ON i.id_material COLLATE utf8mb4_unicode_ci = p.id_material COLLATE utf8mb4_unicode_ci
            WHERE i.tablero_id = ?
            ORDER BY p.material ASC
        ");
        $stmt_inv->execute([$tablero_id]);
        $inventario = $stmt_inv->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

        
        .table-custom { width: 100%; border-collapse: collapse; }
        .table-custom th { background-color: #000; color: #FFD700; padding: 15px; text-align: left; }
        .table-custom th:first-child { border-top-left-radius: 12px; }
        .table-custom th:last-child { border-top-right-radius: 12px; }
        .table-custom td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
    </style>
</head>
<body class="bg-gray-50 pb-20">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 border-l-4 border-gold pl-3">Punto de Control Digital</h1>
            <p class="mt-2 text-sm text-gray-600">Seleccione un área para gestionar su inventario.</p>
        </div>
        <div class="mt-4 md:mt-0 w-full md:w-1/3">
            <form method="GET" id="selectorForm">
                <select name="tablero_id" onchange="document.getElementById('selectorForm').submit()" 
                    class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-yellow-500 focus:border-yellow-500 py-3 px-4 border bg-white font-medium text-gray-700 cursor-pointer">
                    <option value="">-- Seleccionar Área --</option>
                    <?php foreach($tableros as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($tablero_id == $t['id'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if($tablero_actual): ?>
        
        <div class="flex space-x-2 mb-6">
            <button id="btn-ver" onclick="cambiarVista('ver')" class="px-6 py-3 rounded-t-xl font-bold text-sm bg-black text-gold border-b-2 border-gold transition-colors">
                <i class="fas fa-boxes mr-2"></i> Inventario & Retiros
            </button>
            <button id="btn-movs" onclick="cambiarVista('movs')" class="px-6 py-3 rounded-t-xl font-bold text-sm bg-white text-gray-600 hover:bg-gray-100 transition-colors">
                <i class="fas fa-plus-circle mr-2"></i> Ingresar Productos
            </button>
        </div>

        <div id="vista-ver" class="bg-white shadow-xl rounded-b-2xl rounded-tr-2xl overflow-hidden border border-gray-100 block">
            
            <div class="p-6 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900"><i class="fas fa-warehouse text-gray-400 mr-2"></i><?= htmlspecialchars($tablero_actual['nombre']) ?></h2>
                <span class="bg-black text-gold px-4 py-2 rounded-lg text-sm font-bold shadow-sm">
                    Total SKU: <?= count($inventario) ?>
                </span>
            </div>
            
            <div class="overflow-x-auto p-6">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th class="w-1/2 uppercase text-xs font-bold tracking-wider">Material / Descripción</th>
                            <th class="w-1/4 uppercase text-xs font-bold tracking-wider text-center">Existencias</th>
                            <th class="w-1/4 uppercase text-xs font-bold tracking-wider text-center">Cant. a Retirar</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if(empty($inventario)): ?>
                            <tr>
                                <td colspan="3" class="py-12 text-center text-gray-500">
                                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300 block"></i>
                                    No hay productos en esta área. Use la pestaña "Ingresar Productos".
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($inventario as $i): ?>
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <strong class="text-gray-900 block"><?= htmlspecialchars($i['material']) ?></strong>
                                        <span class="text-xs text-gray-500 font-mono">SKU: <?= $i['id_material'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="inline-block bg-gray-100 text-black border border-gray-300 font-bold px-4 py-1.5 rounded-lg">
                                            <?= $i['cantidad'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <input type="number" min="0" max="<?= $i['cantidad'] ?>" value="0" 
                                            class="retiro-input w-24 border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500 py-2 px-3 text-center font-bold text-red-600"
                                            data-sku="<?= $i['id_material'] ?>" data-name="<?= htmlspecialchars($i['material']) ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if(!empty($inventario)): ?>
                <div class="mt-6 flex justify-end">
                    <button onclick="procesarRetirosMasivos()" class="bg-black text-gold border border-transparent shadow-sm px-6 py-3 rounded-xl font-bold hover:bg-gray-800 transition-all">
                        <i class="fas fa-dolly mr-2"></i> Confirmar Retiros
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="p-6 bg-gray-50 border-t border-gray-100">
                <label for="fotoAuditoria" class="cursor-pointer flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-gold hover:bg-white transition-all">
                    <i class="fas fa-camera text-3xl text-gray-400 mb-2"></i>
                    <span class="font-bold text-gray-900">Tomar Foto de Auditoría</span>
                    <span class="text-xs text-gray-500">Verifique el orden físico del área</span>
                    <input type="file" id="fotoAuditoria" capture="environment" accept="image/*" class="hidden" onchange="notify('Foto adjuntada correctamente', 'success')">
                </label>
            </div>
        </div>

        <div id="vista-movs" class="bg-white shadow-xl rounded-b-2xl rounded-tr-2xl border border-gray-100 hidden p-6">
            
            <h2 class="text-lg font-bold text-gray-900 mb-4 border-l-4 border-gold pl-3">Ingreso de Nuevo Stock</h2>
            
            <div class="relative mb-6">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="buscadorSku" onkeyup="buscarProducto(this.value)" autocomplete="off"
                    class="block w-full pl-12 pr-4 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 focus:ring-yellow-500 focus:border-yellow-500 shadow-sm" 
                    placeholder="Buscar producto por SKU o nombre para ingresarlo...">
                
                <div id="resultadosBusqueda" class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-2xl border border-gray-200 max-h-60 overflow-y-auto hidden"></div>
            </div>

            <div class="mb-6">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Lista de Ingresos</h3>
                <div id="estadoVacio" class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-8 text-center block">
                    <p class="text-gray-500 text-sm">Utilice el buscador para agregar productos al tablero.</p>
                </div>
                <div id="listaIngresos" class="space-y-3"></div>
            </div>

            <button id="btnProcesarIngresos" style="display: none;" onclick="procesarIngresos()" 
                class="w-full bg-gold text-black font-bold uppercase py-4 rounded-xl shadow-sm hover:bg-yellow-500 transition-all">
                <i class="fas fa-save mr-2"></i> Confirmar Ingresos
            </button>
            
        </div>

    <?php else: ?>
        <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-16 text-center">
            <i class="fas fa-arrow-up text-4xl text-gray-300 mb-4 animate-bounce block"></i>
            <h2 class="text-xl font-bold text-gray-600">Seleccione un Tablero</h2>
            <p class="text-gray-500 mt-2">Debe seleccionar un punto de control en la parte superior para iniciar.</p>
        </div>
    <?php endif; ?>
</div>

<div id="toast-container" class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-3"></div>

<script>
    const productosBase = <?= json_encode($todos_los_productos) ?>;
    let listaIngresos = [];

    
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
        }, 3500);
    }

    
    function cambiarVista(vista) {
        const divVer = document.getElementById('vista-ver');
        const divMovs = document.getElementById('vista-movs');
        const btnVer = document.getElementById('btn-ver');
        const btnMovs = document.getElementById('btn-movs');

        const claseActiva = "px-6 py-3 rounded-t-xl font-bold text-sm bg-black text-gold border-b-2 border-gold transition-colors";
        const claseInactiva = "px-6 py-3 rounded-t-xl font-bold text-sm bg-white text-gray-600 hover:bg-gray-100 transition-colors";

        if(vista === 'ver') {
            divVer.classList.remove('hidden'); divVer.classList.add('block');
            divMovs.classList.add('hidden'); divMovs.classList.remove('block');
            btnVer.className = claseActiva; btnMovs.className = claseInactiva;
        } else {
            divMovs.classList.remove('hidden'); divMovs.classList.add('block');
            divVer.classList.add('hidden'); divVer.classList.remove('block');
            btnMovs.className = claseActiva; btnVer.className = claseInactiva;
        }
    }

    
    
    
    function procesarRetirosMasivos() {
        const inputs = document.querySelectorAll('.retiro-input');
        let retirosProcesar = [];

        inputs.forEach(input => {
            const cantidad = parseInt(input.value);
            if (cantidad > 0) {
                retirosProcesar.push({
                    sku: input.dataset.sku,
                    nombre: input.dataset.name,
                    cantidad: cantidad,
                    tipo: 'retiro'
                });
            }
        });

        if(retirosProcesar.length === 0) {
            notify('No ha indicado ninguna cantidad a retirar en la tabla.', 'error');
            return;
        }

        if(confirm(`¿Está seguro de procesar el retiro de ${retirosProcesar.length} producto(s)?`)) {
            enviarAlServidor(retirosProcesar);
        }
    }

    
    
    
    function buscarProducto(query) {
        const caja = document.getElementById('resultadosBusqueda');
        if(query.length < 2) { caja.classList.add('hidden'); return; }

        const filtrados = productosBase.filter(p => 
            p.material.toLowerCase().includes(query.toLowerCase()) || p.id_material.includes(query)
        ).slice(0, 10);

        let html = '';
        filtrados.forEach(p => {
            html += `
                <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer flex justify-between items-center" 
                     onclick="agregarIngreso('${p.id_material}', '${p.material.replace(/'/g, "")}')">
                    <div>
                        <strong class="text-sm text-gray-900 block">${p.material}</strong>
                        <span class="text-xs text-gray-500 font-mono">SKU: ${p.id_material}</span>
                    </div>
                    <i class="fas fa-plus text-gray-400"></i>
                </div>
            `;
        });

        caja.innerHTML = html || '<div class="p-4 text-center text-gray-500 text-sm">No se encontraron productos</div>';
        caja.classList.remove('hidden');
    }

    document.addEventListener('click', (e) => {
        const caja = document.getElementById('resultadosBusqueda');
        if(caja && !e.target.closest('.relative')) caja.classList.add('hidden');
    });

    function agregarIngreso(sku, nombre) {
        if(listaIngresos.find(item => item.sku === sku)) {
            notify('El producto ya está en la lista de ingresos.', 'error');
            return;
        }
        listaIngresos.unshift({ sku, nombre, cantidad: 1, tipo: 'ingreso' });
        document.getElementById('buscadorSku').value = '';
        document.getElementById('resultadosBusqueda').classList.add('hidden');
        renderizarIngresos();
    }

    function renderizarIngresos() {
        const contenedor = document.getElementById('listaIngresos');
        const vacio = document.getElementById('estadoVacio');
        const btn = document.getElementById('btnProcesarIngresos');

        if(listaIngresos.length === 0) {
            contenedor.innerHTML = '';
            vacio.style.display = 'block';
            btn.style.display = 'none';
            return;
        }

        vacio.style.display = 'none';
        btn.style.display = 'block';
        
        let html = '';
        listaIngresos.forEach((item, index) => {
            html += `
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex-1 w-full">
                        <strong class="text-gray-900 block text-sm">${item.nombre}</strong>
                        <span class="text-xs text-gray-500 font-mono">SKU: ${item.sku}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-gray-500 uppercase">Cant:</span>
                        <input type="number" min="1" value="${item.cantidad}" onchange="listaIngresos[${index}].cantidad=parseInt(this.value)||1" 
                            class="w-20 p-2 border border-gray-300 rounded-lg font-bold text-center focus:ring-yellow-500 focus:border-yellow-500">
                        <button onclick="listaIngresos.splice(${index}, 1); renderizarIngresos()" class="text-gray-400 hover:text-red-500 px-2 py-1">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        contenedor.innerHTML = html;
    }

    function procesarIngresos() {
        if(listaIngresos.length === 0) return;
        enviarAlServidor(listaIngresos);
    }

    
    
    
    function enviarAlServidor(itemsAProcesar) {
        notify('Sincronizando...', 'info');
        
        const payload = {
            tablero_id: <?= $tablero_id ? 'parseInt('.$tablero_id.')' : 'null' ?>,
            items: itemsAProcesar
        };

        
        const fetchUrl = new URL(window.location.href);
        fetchUrl.searchParams.set('action', 'save');

        fetch(fetchUrl.toString(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) throw new Error("Error en la respuesta del servidor");
            return response.json();
        })
        .then(data => {
            if (data.success) {
                notify('Tablero actualizado exitosamente.');
                setTimeout(() => location.reload(), 1500); 
            } else {
                notify('Error al guardar: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error(error); 
            notify('Error de conexión con el servidor.', 'error');
        });
    }
</script>

</body>
</html>