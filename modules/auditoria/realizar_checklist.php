<?php

require_once __DIR__ . '/../../core/config.php';
require_once '../../core/header.php';

$errores = [];
$exito = null;


$modulo_id = $_GET['modulo_id'] ?? null;
$checklist_id = $_GET['checklist_id'] ?? null;
$zona_id = $_GET['zona_id'] ?? null; 

if (!$checklist_id) {
    die("<div style='padding: 20px; font-family: sans-serif; text-align:center;'><h2>Error: Checklist no especificado.</h2><a href='auditoria.php'>Volver</a></div>");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_ejecucion') {
    $respuestas = $_POST['respuestas'] ?? [];
    
    
    $usuario_actual = $_SESSION['nombre'] ?? $_SESSION['cedula'] ?? 'Usuario Desconocido'; 

    try {
        $pdo->beginTransaction();

        
        $stmtEjecucion = $pdo->prepare("INSERT INTO auditoria_ejecuciones (checklist_id, zona_id, usuario, operacion_id) VALUES (?, ?, ?, ?)");

        $zona_db_val = $zona_id ? $zona_id : null;
        $stmtEjecucion->execute([$checklist_id, $zona_db_val, $usuario_actual, getOperacionActiva()]);
        $ejecucion_id = $pdo->lastInsertId();


        $stmtRespuesta = $pdo->prepare("INSERT INTO auditoria_ejecucion_respuestas (ejecucion_id, pregunta_id, respuesta, operacion_id) VALUES (?, ?, ?, ?)");

        foreach ($respuestas as $pregunta_id => $respuesta_valor) {
            $stmtRespuesta->execute([$ejecucion_id, $pregunta_id, trim($respuesta_valor), getOperacionActiva()]);
        }

        $pdo->commit();
        $exito = "Auditoría completada y registrada exitosamente.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errores[] = "Error al guardar la auditoría: " . $e->getMessage();
    }
}


try {
    
    $stmt = $pdo->prepare("
        SELECT c.titulo, c.id as checklist_id, m.nombre as modulo_nombre 
        FROM auditoria_checklists c
        JOIN auditorias m ON c.auditoria_id = m.id
        WHERE c.id = ?
    ");
    $stmt->execute([$checklist_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        die("<div style='padding: 20px; font-family: sans-serif; text-align:center;'><h2>Error: El checklist no existe.</h2><a href='auditoria.php'>Volver</a></div>");
    }

    
    $zona_nombre = "Zona no especificada";
    if ($zona_id) {
        $stmtZona = $pdo->prepare("SELECT nombre FROM auditoria_zonas WHERE id = ? AND operacion_id = ?");
        $stmtZona->execute([$zona_id, getOperacionActiva()]);
        $zonaInfo = $stmtZona->fetch(PDO::FETCH_ASSOC);
        if ($zonaInfo) {
            $zona_nombre = $zonaInfo['nombre'];
        }
    }

    
    $stmtPreguntas = $pdo->prepare("SELECT * FROM auditoria_checklist_preguntas WHERE checklist_id = ? ORDER BY orden ASC");
    $stmtPreguntas->execute([$checklist_id]);
    $preguntas = $stmtPreguntas->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Auditoría - WARE PRO</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], },
                    colors: {
                        ware: { black: '#0F0F10', yellow: '#FFC107', yellowDark: '#E0A800', gray: '#f8f9fa' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-ware-gray text-gray-800 antialiased min-h-screen">

    <main class="w-full max-w-3xl mx-auto px-4 py-10">
        
        <a href="auditoria.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-ware-black transition-colors mb-6 bg-white px-4 py-2 rounded-full shadow-sm border border-gray-100">
            <i class="fa-solid fa-arrow-left"></i> Volver a Módulos
        </a>

        <div class="bg-ware-black rounded-[2rem] p-8 shadow-xl relative overflow-hidden mb-8">
            <div class="absolute -top-20 -right-20 w-64 h-64 bg-ware-yellow/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="inline-flex items-center bg-white/10 text-ware-yellow text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-white/10">
                        <i class="fa-solid fa-cube mr-1.5"></i> <?php echo htmlspecialchars($info['modulo_nombre']); ?>
                    </span>
                    <span class="inline-flex items-center bg-blue-500/20 text-blue-300 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-blue-500/20">
                        <i class="fa-solid fa-location-dot mr-1.5 text-blue-400"></i> <?php echo htmlspecialchars($zona_nombre); ?>
                    </span>
                </div>

                <h1 class="text-3xl md:text-4xl font-black text-white mb-2 leading-tight">
                    <?php echo htmlspecialchars($info['titulo']); ?>
                </h1>
                <p class="text-gray-400 font-medium">Por favor, responde todas las preguntas con honestidad para completar la auditoría.</p>
            </div>
        </div>

        <?php if (empty($preguntas)): ?>
            <div class="bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-100 border-dashed border-2">
                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                    <i class="fa-solid fa-clipboard-question text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-ware-black">El checklist está vacío</h3>
                <p class="text-gray-500 mt-2">Un administrador debe configurar las preguntas primero.</p>
            </div>
        <?php else: ?>
            
            <form method="POST" id="formChecklist" class="space-y-6">
                <input type="hidden" name="accion" value="guardar_ejecucion">
                
                <?php foreach ($preguntas as $index => $p): ?>
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-ware-black mb-4 flex gap-3">
                            <span class="text-gray-300 select-none"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>.</span>
                            <?php echo htmlspecialchars($p['pregunta']); ?>
                        </h3>

                        <?php if ($p['tipo_respuesta'] === 'si_no'): ?>
                            <div class="flex gap-4">
                                <label class="cursor-pointer flex-1 group">
                                    <input type="radio" name="respuestas[<?php echo $p['id']; ?>]" value="Si" class="peer hidden" required>
                                    <div class="rounded-2xl border-2 border-gray-100 py-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 font-bold text-gray-400 group-hover:bg-gray-50 transition-all">
                                        <i class="fa-solid fa-check text-xl mb-1 block"></i> Sí
                                    </div>
                                </label>
                                <label class="cursor-pointer flex-1 group">
                                    <input type="radio" name="respuestas[<?php echo $p['id']; ?>]" value="No" class="peer hidden">
                                    <div class="rounded-2xl border-2 border-gray-100 py-4 text-center peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 font-bold text-gray-400 group-hover:bg-gray-50 transition-all">
                                        <i class="fa-solid fa-xmark text-xl mb-1 block"></i> No
                                    </div>
                                </label>
                            </div>
                        <?php else: ?>
                            <textarea name="respuestas[<?php echo $p['id']; ?>]" 
                                      class="w-full border-2 border-gray-100 bg-gray-50 rounded-2xl p-4 text-gray-700 outline-none focus:border-ware-yellow focus:bg-white focus:ring-4 focus:ring-ware-yellow/10 transition-all resize-y" 
                                      rows="3" 
                                      placeholder="Escribe tu observación detallada aquí..." 
                                      required></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="sticky bottom-6 mt-10">
                    <button type="submit" class="w-full bg-ware-yellow hover:bg-ware-yellowDark text-ware-black text-lg font-black py-4 px-6 rounded-2xl shadow-xl shadow-ware-yellow/30 transform hover:-translate-y-1 transition-all flex justify-center items-center gap-3">
                        <i class="fa-solid fa-paper-plane"></i> Finalizar Auditoría
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($exito): ?>
                Swal.fire({
                    icon: 'success',
                    title: '¡Auditoría Completada!',
                    text: '<?php echo $exito; ?>',
                    confirmButtonColor: '#0F0F10',
                    confirmButtonText: 'Volver al Inicio',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'auditoria.php';
                    }
                });
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                let errorHtml = '<ul style="text-align: left;">';
                <?php foreach($errores as $e): ?>
                    errorHtml += '<li><?php echo addslashes($e); ?></li>';
                <?php endforeach; ?>
                errorHtml += '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Ocurrió un problema',
                    html: errorHtml,
                    confirmButtonColor: '#dc2626'
                });
            <?php endif; ?>
        });

        
        const form = document.getElementById('formChecklist');
        if(form) {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando respuestas...';
            });
        }
    </script>
</body>
</html>