<?php
require_once '../../core/config.php'; 

verificarLogin();
date_default_timezone_set('America/Bogota');

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    try {
        $pdo->exec("SET time_zone = '-05:00';");

        $stmt_diario = $pdo->prepare("
            SELECT HOUR(hora) as hora_dia, SUM(vehiculos_cargados) as total_vehiculos 
            FROM ows_cargue 
            WHERE DATE(fecha) = CURDATE()
            GROUP BY HOUR(hora) 
            ORDER BY hora_dia ASC
        ");
        $stmt_diario->execute();
        $sic_diario = $stmt_diario->fetchAll();

        $stmt_mes = $pdo->prepare("
            SELECT DAY(fecha) as dia, SUM(vehiculos_cargados) as total_diario
            FROM ows_cargue 
            WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
            GROUP BY DATE(fecha) 
            ORDER BY fecha ASC
        ");
        $stmt_mes->execute();
        $tendencia_mes = $stmt_mes->fetchAll();
        $stmt_cargados_hoy = $pdo->prepare("
            SELECT SUM(vehiculos_cargados) 
            FROM ows_cargue 
            WHERE DATE(fecha) = CURDATE()
        ");
        $stmt_cargados_hoy->execute();
        $cargados_hoy = $stmt_cargados_hoy->fetchColumn() ?: 0;

        $stmt_planeados_hoy = $pdo->prepare("
            SELECT vehiculos_planeados 
            FROM ows_cargue 
            WHERE DATE(fecha) = CURDATE() AND vehiculos_planeados > 0
            ORDER BY id ASC LIMIT 1
        ");
        $stmt_planeados_hoy->execute();
        $planeados_hoy = $stmt_planeados_hoy->fetchColumn() ?: 0;

        $stmt_ultimo = $pdo->prepare("
            SELECT id, vehiculos_cargados 
            FROM ows_cargue 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_ultimo->execute();
        $ultimo_registro = $stmt_ultimo->fetch();

        echo json_encode([
            'sic_diario' => $sic_diario,
            'tendencia_mes' => $tendencia_mes,
            'resumen_hoy' => [
                'cargados' => $cargados_hoy,
                'planeados' => $planeados_hoy
            ],
            'ultimo_registro' => $ultimo_registro
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workstation - Cargue CD Cúcuta</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/highcharts-more.js"></script>
    <script src="https://code.highcharts.com/modules/solid-gauge.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: #f4f7f6; 
            color: #333; 
            min-height: 100vh;
            -ms-overflow-style: none; 
            scrollbar-width: none; 
            transition: transform 3s ease-in-out; 
        }
        body::-webkit-scrollbar { display: none; }
        
        
        .header-container { background: linear-gradient(90deg, #000000 0%, #1a1a1a 100%); color: #fff; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 5px solid #ffcc00; box-shadow: 0 4px 10px rgba(0,0,0,0.2); position: sticky; top: 0; z-index: 1000; }
        .header-container h1 { font-size: 30px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin: 0; text-align: center; flex-grow: 1; }
        
        .estado-sistema { font-size: 13px; font-weight: 700; color: #ff4d4d; margin-right: 20px; display: flex; align-items: center; gap: 5px; cursor: pointer; }

        .dashboard-wrapper { padding: 15px; display: flex; flex-direction: column; gap: 15px; }
        
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 15px; border: 1px solid #eaeaea; }
        .card-header { font-size: 18px; font-weight: 800; color: #d4af37; text-align: center; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px dashed #eee; padding-bottom: 8px; }
        
        .row-top { display: grid; grid-template-columns: 2fr 4fr 4fr; gap: 15px; }
        
        
        .meta-content { text-align: center; margin-top: 5px; }
        .filtro-mes { border: 1px solid #ccc; border-radius: 5px; padding: 5px 10px; font-size: 14px; margin-bottom: 10px; display: inline-block; background: #fff;}
        .dato-box { background: #f0f0f0; padding: 8px; margin-bottom: 10px; border-radius: 5px; }
        .dato-box.red { background: #ff0000; color: white; font-weight: bold;}
        .dato-label { font-size: 12px; color: #555; text-transform: uppercase; margin-bottom: 2px;}
        .dato-box.red .dato-label { color: #fff;}
        .dato-value { font-size: 24px; font-weight: bold; color: #000; }
        .dato-box.red .dato-value { color: #fff;}

        .riesgos-area { margin-top: 10px; font-weight: 700; font-size: 16px; text-align: center; }
        .iconos-riesgos { display: flex; justify-content: center; gap: 12px; margin-top: 5px; }
        .iconos-riesgos div { width: 55px; height: 55px; background: #fff; border-radius: 8px; border: 2px solid #ffcc00; display:flex; align-items:center; justify-content:center; box-shadow: 2px 2px 0px #000; padding: 2px; overflow: hidden; }
        .iconos-riesgos img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .texto-riesgos { display: flex; justify-content: center; gap: 5px; font-size: 10px; margin-top: 4px; font-weight: 600; color: #555; }
        .texto-riesgos span { width: 55px; text-align: center; word-wrap: break-word; }

        .chart-container { height: 320px; width: 100%; border-radius: 8px; overflow: hidden; }

        
        .row-bottom { display: grid; grid-template-columns: 3.5fr 4fr 2.5fr; gap: 15px; align-items: stretch; }
        
        .action-log { background: #111; color: #fff; border-radius: 12px; }
        .action-log .card-header { border-color: #333; }
        .log-item { margin-bottom: 12px; }
        .log-item span { font-weight: 800; font-size: 14px; display: block; margin-bottom: 4px; }
        .log-item p { font-size: 12px; font-weight: 300; line-height: 1.5; color: #ddd; font-style: italic; }
        .text-preventiva { color: #ffcc00; }
        .text-reactiva { color: #ff4c4c; }

        .layout-container { display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; padding: 10px;}
        .layout-placeholder { max-width: 100%; max-height: 160px; object-fit: contain; border-radius: 8px; }

        .qr-section { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .qr-box { border: 3px dashed #ffcc00; border-radius: 10px; padding: 8px; background: #fff; margin-bottom: 12px; }
        .qr-box img { width: 100px; height: 100px; object-fit: contain; }
        .qr-list { text-align: left; width: 100%; padding-left: 15px; }
        .qr-list p { font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #555; display: flex; align-items: center; }
        .qr-list p svg { color: #32cd32; margin-right: 6px; width: 16px; height: 16px; }
    </style>
</head>
<body>

    <div class="header-container">
        <img src="https://www.bavaria.co/sites/g/files/seuoyk1666/files/inline-images/Logo_Tipo_Horizontal.png" alt="Bavaria" style="height: 40px; object-fit: contain;">
        <h1>WORKSTATION - CARGUE CD CÚCUTA</h1>
        <div style="display: flex; align-items: center;">
            <div id="estado-sistema" class="estado-sistema" onclick="activarSistemaSilencioso()">
                <i data-lucide="mic-off" style="width: 18px; height: 18px;"></i> INICIANDO...
            </div>
            <img src="https://dpo.arvolution.com/assets/img-Cv_l3L5U.png" alt="DPO" style="height: 40px; object-fit: contain;">
        </div>
    </div>

    <div class="dashboard-wrapper">
        <div class="row-top">
            
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div class="meta-content">
                    <div class="filtro-mes">MES ▼</div>
                    
                    <div class="dato-box">
                        <div class="dato-label">PLANEADOS</div>
                        <div class="dato-value" id="val-planeados">0</div>
                    </div>
                    
                    <div class="dato-box">
                        <div class="dato-label">VEHICULOS CARGADOS</div>
                        <div class="dato-value" id="val-cargados">0</div>
                    </div>
                    
                    <div class="dato-box red">
                        <div class="dato-label">VEH / HORA</div>
                        <div class="dato-value" id="val-veh-hora">0.0</div>
                    </div>
                </div>
                
                <div class="riesgos-area">
                    Riesgos del área
                    <div class="iconos-riesgos">
                        <div><img src="https://ecuador.unir.net/wp-content/uploads/sites/8/2024/01/La-ergonomia-en-el-trabajo-importancia-y-factores-de-riesgo.jpg" alt="Ergonómico"></div>
                        <div><img src="https://st.depositphotos.com/1751231/4962/v/450/depositphotos_49628071-stock-illustration-warning-sign-beware-forklift.jpg" alt="Atropellamiento"></div>
                        <div><img src="https://i.pinimg.com/474x/44/c2/4e/44c24e42f65b2127de69f97c82fc9582.jpg" alt="Locativo"></div>
                    </div>
                    <div class="texto-riesgos">
                        <span>ERGONÓMICO</span>
                        <span>ATROPELLAMIENTO</span>
                        <span>LOCATIVO</span>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">SIC DIARIO</div>
                <div id="containerDiario" class="chart-container"></div>
            </div>

            
            <div class="card">
                <div class="card-header">TENDENCIA MES</div>
                <div id="containerMes" class="chart-container"></div>
            </div>
        </div>

        <div class="row-bottom">
            
            <div class="card action-log">
                <div class="card-header" style="color: #ffcc00;">ACTION LOG</div>
                <div class="log-item">
                    <span class="text-preventiva">Zona Preventiva:</span>
                    <p><b>(FLT):</b> Garantizar las 5s de las Bahías. Verificar, reestructurar y garantizar el cronograma de prioridades de cargue. Evaluar el recurso y realizar solicitud según operación.</p>
                    <p><b>(Picking):</b> Supervisor reemplaza a verificador 360 y este baja a apoyar armado de Picking.</p>
                </div>
                <div class="log-item">
                    <span class="text-reactiva">Zona Reactiva:</span>
                    <p><b>(FLT):</b> Solicitar recurso a turno A, Realizar extension del jornada laboral del personal.</p>
                    <p><b>(Movilizador):</b> Hacer Mix entre OPM y Movilizador.</p>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">LAYOUT</div>
                <div class="layout-container">
                    <img src="../../public/img/recargue.png" alt="Layout Cargue" class="layout-placeholder">
                </div>
            </div>

            
            <div class="card qr-section">
                <div class="card-header">RINCÓN DE GESTIÓN</div>
                <div class="qr-box">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://warepro.logisticos.com.co/recursos.php" alt="QR Code">
                </div>
                <div class="qr-list">
                    <p><i data-lucide="check-circle-2"></i> Documentos y estándares</p>
                    <p><i data-lucide="check-circle-2"></i> Checklist de herramientas</p>
                    <p><i data-lucide="check-circle-2"></i> Página del CD Cúcuta</p>
                    <p><i data-lucide="check-circle-2"></i> Programa Buenas Prácticas</p>
                </div>
            </div>
        </div>
    </div>

<script>
        lucide.createIcons();

        function getChartOptions(maxLimit, bands) {
            return {
                chart: { type: 'line', style: { fontFamily: 'Poppins' }, backgroundColor: 'transparent' },
                title: { text: null },
                credits: { enabled: false },
                legend: { enabled: false },
                xAxis: { 
                    gridLineWidth: 1, gridLineDashStyle: 'Dash', 
                    labels: { style: { fontWeight: 'bold', color: '#000' } },
                    showEmpty: true 
                },
                yAxis: {
                    min: 0, 
                    showEmpty: true, 
                    title: { text: 'VEHÍCULOS CARGADOS', style: { fontWeight: 'bold', fontFamily: 'Poppins', fontSize: '10px' } },
                    plotBands: bands,
                    gridLineWidth: 0,
                    labels: { style: { fontWeight: 'bold', color: '#000', fontFamily: 'Poppins' } }
                },
                plotOptions: {
                    line: {
                        color: '#000000',
                        lineWidth: 3,
                        marker: { enabled: true, fillColor: '#000000', radius: 5, symbol: 'circle' },
                        dataLabels: { enabled: true, style: { fontWeight: 'bold', fontSize: '12px' } }
                    }
                }
            };
        }

        let chartDiario, chartMes;
        let ultimoIdRegistro = 0;
        let sistemaActivo = false;
        let speechSynth = window.speechSynthesis;
        let horaAvisoNotificado = -1; 
        let wakeLock = null;

        function inicializarGraficas() {
            
            let bandasDiario = [
                { from: 0, to: 2, color: '#ff4d4d' },      
                { from: 2, to: 4, color: '#ffeb3b' },    
                { from: 4, to: 999, color: '#32cd32' } 
            ];
            
            
            let bandasMes = [
                { from: 0, to: 25, color: '#ff4d4d' },      
                { from: 25, to: 45, color: '#ffeb3b' },    
                { from: 45, to: 9999, color: '#32cd32' } 
            ];

            chartDiario = Highcharts.chart('containerDiario', getChartOptions(8, bandasDiario));
            chartMes = Highcharts.chart('containerMes', getChartOptions(80, bandasMes));
        }

        async function fetchData() {
            try {
                const res = await fetch('workstation_cargue.php?ajax=1');
                const data = await res.json();

                if (data.error) return;

                
                
                let p_hoy = data.resumen_hoy ? data.resumen_hoy.planeados : 0;
                let c_hoy = data.resumen_hoy ? data.resumen_hoy.cargados : 0;
                
                document.getElementById('val-planeados').innerText = p_hoy;
                document.getElementById('val-cargados').innerText = c_hoy;
                
                
                let horaActual = new Date().getHours();
                let horas_transcurridas = horaActual > 0 ? horaActual : 1; 
                let veh_hora_estimado = (c_hoy / horas_transcurridas).toFixed(1);
                
                document.getElementById('val-veh-hora').innerText = veh_hora_estimado;

                
                let labelsDiario = data.sic_diario.map(d => d.hora_dia + "h");
                let datosDiario = data.sic_diario.map(d => Number(d.total_vehiculos));
                let maxDiario = datosDiario.length > 0 ? Math.max(...datosDiario) : 0;
                
                chartDiario.update({ 
                    yAxis: { max: maxDiario > 8 ? null : 8 }, 
                    xAxis: { categories: labelsDiario }, 
                    series: [{ name: 'Vehículos', data: datosDiario }] 
                }, true, true);

                
                let labelsMes = data.tendencia_mes.map(d => d.dia);
                let datosMes = data.tendencia_mes.map(d => Number(d.total_diario));
                let maxMes = datosMes.length > 0 ? Math.max(...datosMes) : 0;
                
                chartMes.update({ 
                    yAxis: { max: maxMes > 80 ? null : 80 }, 
                    xAxis: { categories: labelsMes }, 
                    series: [{ name: 'Vehículos', data: datosMes }] 
                }, true, true);

                if (data.ultimo_registro && data.ultimo_registro.id > ultimoIdRegistro) {
                    let vehiculos_hoy = datosDiario.length > 0 ? datosDiario[datosDiario.length - 1] : 0;

                    if (ultimoIdRegistro !== 0) { 
                        hablar(`Atención equipo. Se ha registrado un nuevo despacho. Llevamos un acumulado de ${vehiculos_hoy} vehículos en esta hora. Mantengamos las zonas seguras y prioricemos el cargue antes de 6, !Vamos Equipo¡.`);
                    }
                    ultimoIdRegistro = data.ultimo_registro.id;
                }

            } catch (e) {
                console.error("Error fetching:", e);
            }
        }

        function hablar(texto) {
            if (!sistemaActivo) return; 
            speechSynth.cancel(); 
            let utterance = new SpeechSynthesisUtterance(texto);
            utterance.lang = 'es-CO'; 
            utterance.rate = 1.0;
            utterance.volume = 1.0;
            speechSynth.speak(utterance);
        }

        function checkAvisosHora() {
            let ahora = new Date();
            if (ahora.getMinutes() === 45 && horaAvisoNotificado !== ahora.getHours()) {
                hablar("Hola a todo el equipo de cargue. Faltan 15 minutos para completar la hora, garanticemos las 5 eses de las bahías y el cronograma de prioridades de cargue.");
                horaAvisoNotificado = ahora.getHours(); 
            }
        }

        async function mantenerPantallaDespierta() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (err) {}
        }

        document.addEventListener('visibilitychange', async () => {
            if (wakeLock !== null && document.visibilityState === 'visible') {
                await mantenerPantallaDespierta();
            }
        });

        function activarSistemaSilencioso() {
            if (!sistemaActivo) {
                sistemaActivo = true;
                let estadoDiv = document.getElementById('estado-sistema');
                estadoDiv.innerHTML = '<i data-lucide="mic" style="width:18px; height:18px;"></i> AUDIO ON';
                estadoDiv.style.color = '#32cd32';
                lucide.createIcons();
                mantenerPantallaDespierta();
            }
        }

        document.body.addEventListener('mousemove', activarSistemaSilencioso, { once: true });
        document.body.addEventListener('click', activarSistemaSilencioso, { once: true });
        document.body.addEventListener('touchstart', activarSistemaSilencioso, { once: true });

        let desplazado = false;
        function moverPantallaMicro() {
            if (desplazado) {
                document.body.style.transform = "translateY(0px)";
            } else {
                document.body.style.transform = "translateY(-5px)";
            }
            desplazado = !desplazado;
        }

        document.addEventListener("DOMContentLoaded", () => {
            activarSistemaSilencioso();
            inicializarGraficas();
            fetchData();
            
            setInterval(fetchData, 5000); 
            setInterval(checkAvisosHora, 1000); 
            setInterval(moverPantallaMicro, 30000); 
        });
    </script>
</body>
</html>