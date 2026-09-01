<?php
require_once '../../core/config.php'; 

verificarLogin();
date_default_timezone_set('America/Bogota');

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    try {
        $pdo->exec("SET time_zone = '-05:00';");

        
        $stmt_diario = $pdo->prepare("
            SELECT HOUR(hora_final_cargue) as hora_dia, ROUND(AVG(TIME_TO_SEC(tiempo)/60), 1) as promedio_tiempo 
            FROM recargue_t2 
            WHERE DATE(fecha) = CURDATE() AND estatus = 'Completado'
            GROUP BY HOUR(hora_final_cargue) 
            ORDER BY hora_dia ASC
        ");
        $stmt_diario->execute();
        $sic_diario = $stmt_diario->fetchAll();

        
        $stmt_mes = $pdo->prepare("
            SELECT DAY(fecha) as dia, ROUND(AVG(TIME_TO_SEC(tiempo)/60), 1) as promedio_tiempo
            FROM recargue_t2 
            WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE()) AND estatus = 'Completado'
            GROUP BY DATE(fecha) 
            ORDER BY fecha ASC
        ");
        $stmt_mes->execute();
        $tendencia_mes = $stmt_mes->fetchAll();
        
        
        $stmt_total = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM recargue_t2 
            WHERE DATE(fecha) = CURDATE() AND estatus = 'Completado'
        ");
        $stmt_total->execute();
        $total_atendidos = $stmt_total->fetch()['total'];

        
        $stmt_recientes = $pdo->prepare("
            SELECT placa, opm1, estatus, tiempo 
            FROM recargue_t2 
            WHERE DATE(fecha) = CURDATE() AND estatus = 'Completado'
            ORDER BY hora_final_cargue DESC LIMIT 3
        ");
        $stmt_recientes->execute();
        $recientes = $stmt_recientes->fetchAll();

        
        $stmt_ultimo = $pdo->prepare("
            SELECT id, placa, opm1, tiempo 
            FROM recargue_t2 
            WHERE estatus = 'Completado'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_ultimo->execute();
        $ultimo_registro = $stmt_ultimo->fetch();

        echo json_encode([
            'sic_diario' => $sic_diario,
            'tendencia_mes' => $tendencia_mes,
            'total_atendidos' => $total_atendidos,
            'recientes' => $recientes,
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
    <title>Workstation - Recargue CD Cúcuta</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/highcharts-more.js"></script>
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
        
        .row-top { display: grid; grid-template-columns: 2.2fr 4fr 4fr; gap: 15px; }
        
        .panel-izquierdo { display: flex; flex-direction: column; gap: 10px; }
        
        .mini-tabla-container { border: 2px dashed #ffcc00; border-radius: 8px; overflow: hidden; padding: 5px; background: #fff;}
        .mini-tabla { width: 100%; border-collapse: collapse; text-align: center; font-size: 11px; }
        .mini-tabla th { background: #000; color: #ffcc00; padding: 6px; font-weight: 700; text-transform: uppercase; }
        .mini-tabla td { padding: 6px; border-bottom: 1px solid #eee; font-weight: 600; color: #333; }
        .mini-tabla tr:last-child td { border-bottom: none; }
        
        .atendidos-box { display: flex; flex-direction: column; align-items: center; margin-top: 5px; }
        .atendidos-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #555; margin-bottom: 5px; }
        .atendidos-circle { background: #ffcc00; width: 100px; height: 50px; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: #000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        .riesgos-area { margin-top: 10px; font-weight: 700; font-size: 14px; text-align: center; }
        .iconos-riesgos { display: flex; justify-content: center; gap: 12px; margin-top: 5px; }
        .iconos-riesgos div { width: 45px; height: 45px; background: #fff; border-radius: 8px; border: 2px solid #ffcc00; display:flex; align-items:center; justify-content:center; box-shadow: 2px 2px 0px #000; padding: 2px; overflow: hidden; }
        .iconos-riesgos img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .texto-riesgos { display: flex; justify-content: center; gap: 5px; font-size: 9px; margin-top: 4px; font-weight: 600; color: #555; }
        .texto-riesgos span { width: 45px; text-align: center; word-wrap: break-word; }

        .chart-container { height: 320px; width: 100%; border-radius: 8px; overflow: hidden; }

        .row-bottom { display: grid; grid-template-columns: 3.5fr 4fr 2.5fr; gap: 15px; align-items: stretch; }
        
        .action-log { background: #111; color: #fff; border-radius: 12px; }
        .action-log .card-header { border-color: #333; }
        .log-item { margin-bottom: 12px; }
        .log-item span { font-weight: 800; font-size: 14px; display: block; margin-bottom: 4px; }
        .log-item p { font-size: 12px; font-weight: 300; line-height: 1.5; color: #ddd; }
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
        <h1>WORKSTATION - RECARGUE CD CÚCUTA</h1>
        <div style="display: flex; align-items: center;">
            <div id="estado-sistema" class="estado-sistema" onclick="activarSistemaSilencioso()">
                <i data-lucide="mic-off" style="width: 18px; height: 18px;"></i> INICIANDO...
            </div>
            <img src="https://dpo.arvolution.com/assets/img-Cv_l3L5U.png" alt="DPO" style="height: 40px; object-fit: contain;">
        </div>
    </div>

    <div class="dashboard-wrapper">
        <div class="row-top">
            <div class="card panel-izquierdo">
                <div class="mini-tabla-container">
                    <table class="mini-tabla" id="tabla-opm">
                        <thead>
                            <tr><th>OPM1</th><th>Estatus</th><th>Tiempo</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3">No hay datos</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="mini-tabla-container">
                    <table class="mini-tabla" id="tabla-placa">
                        <thead>
                            <tr><th>PLACA</th><th>Estatus</th><th>Tiempo</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3">No hay datos</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="atendidos-box">
                    <div class="atendidos-title">TOTAL ATENDIDOS</div>
                    <div class="atendidos-circle" id="val-atendidos">0</div>
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
                <div class="card-header">SIC DIARIO (TIEMPO)</div>
                <div id="containerDiario" class="chart-container"></div>
            </div>

            <div class="card">
                <div class="card-header">TENDENCIA MES (TIEMPO)</div>
                <div id="containerMes" class="chart-container"></div>
            </div>
        </div>

        <div class="row-bottom">
            <div class="card action-log">
                <div class="card-header" style="color: #ffcc00;">ACTION LOG</div>
                <div class="log-item">
                    <span class="text-preventiva">Zona Preventiva:</span>
                    <p>Garantizar las 5s de las Bahias. Evaluar y priorizar con el UC el orden de las atenciones de recargue.<br>
                    <b style="color:#ffcc00;">(WAREPRO):</b> Hacer uso de la app warepro para el registro de tiempos.<br>
                    <b style="color:#ffcc00;">(OPM):</b> Coordinar asignación de OPM según su habilidad para la operación.<br>
                    <b style="color:#ffcc00;">(Torre de control):</b> Coordinar con Torre de control el aviso de arribo de segundos viajes.</p>
                </div>
                <div class="log-item">
                    <span class="text-reactiva">Zona Reactiva:</span>
                    <p>Redefinir flujo de recargue con el UC y el flujo de T4 con equipo ABI. Solicitar OPM en hora pico para solventar TAT de recargue.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">LAYOUT</div>
                <div class="layout-container">
                    <img src="../../public/img/recargue.png" alt="Layout Recargue" class="layout-placeholder">
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

        function getChartOptions() {
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
                    title: { text: 'TIEMPO PROMEDIO (MIN)', style: { fontWeight: 'bold', fontFamily: 'Poppins', fontSize: '10px' } },
                    plotBands: [
    
    { from: 0, to: 26, color: '#32cd32' },      
    { from: 26, to: 33, color: '#ffeb3b' },     
    { from: 33, to: 9999, color: '#ff4d4d' }    
],
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
            chartDiario = Highcharts.chart('containerDiario', getChartOptions());
            chartMes = Highcharts.chart('containerMes', getChartOptions());
        }

        async function fetchData() {
            try {
                const res = await fetch('workstation_recargue.php?ajax=1');
                const data = await res.json();

                if (data.error) return;

                
                document.getElementById('val-atendidos').innerText = data.total_atendidos || 0;

                let tbodyOpm = document.querySelector('#tabla-opm tbody');
                let tbodyPlaca = document.querySelector('#tabla-placa tbody');
                
                tbodyOpm.innerHTML = '';
                tbodyPlaca.innerHTML = '';

                if(data.recientes && data.recientes.length > 0) {
                    data.recientes.forEach(r => {
                        let opmCorto = r.opm1.split(" ")[0]; 
                        tbodyOpm.innerHTML += `<tr>
                            <td>${opmCorto}</td>
                            <td style="color:#32cd32;">${r.estatus}</td>
                            <td>${r.tiempo}</td>
                        </tr>`;

                        tbodyPlaca.innerHTML += `<tr>
                            <td>${r.placa}</td>
                            <td style="color:#32cd32;">${r.estatus}</td>
                            <td>${r.tiempo}</td>
                        </tr>`;
                    });
                } else {
                    tbodyOpm.innerHTML = '<tr><td colspan="3">No hay datos</td></tr>';
                    tbodyPlaca.innerHTML = '<tr><td colspan="3">No hay datos</td></tr>';
                }

                
                let labelsDiario = data.sic_diario.map(d => d.hora_dia + "h");
                let datosDiario = data.sic_diario.map(d => Number(d.promedio_tiempo));
                let maxDiario = datosDiario.length > 0 ? Math.max(...datosDiario) : 0;
                
                chartDiario.update({ 
                    yAxis: { max: maxDiario > 90 ? null : 90 }, 
                    xAxis: { categories: labelsDiario }, 
                    series: [{ name: 'Minutos', data: datosDiario }] 
                }, true, true);

                
                let labelsMes = data.tendencia_mes.map(d => d.dia);
                let datosMes = data.tendencia_mes.map(d => Number(d.promedio_tiempo));
                let maxMes = datosMes.length > 0 ? Math.max(...datosMes) : 0;
                
                chartMes.update({ 
                    yAxis: { max: maxMes > 90 ? null : 90 }, 
                    xAxis: { categories: labelsMes }, 
                    series: [{ name: 'Minutos', data: datosMes }] 
                }, true, true);

                
                if (data.ultimo_registro && data.ultimo_registro.id > ultimoIdRegistro) {
                    let placa = data.ultimo_registro.placa;
                    let opm = data.ultimo_registro.opm1.split(" ")[0]; 
                    let tiempo_str = data.ultimo_registro.tiempo; 
                    
                    
                    let partes = tiempo_str.split(':');
                    let minutos = parseInt(partes[0]) * 60 + parseInt(partes[1]);

                    if (ultimoIdRegistro !== 0) { 
                        if (minutos <= 45) {
                            hablar(`¡Excelente equipo de recargue! Vehículo placa ${placa} finalizado por ${opm}. Tiempo registrado: ${minutos} minutos, cumpliendo la meta de los 45 minutos. Sigan así.`);
                        } else {
                            let exceso = minutos - 45;
                            hablar(`Atención equipo de recargue. Vehículo placa ${placa} finalizado por ${opm}. Tiempo registrado: ${minutos} minutos. Nos excedimos de la meta por ${exceso} minutos. Revisemos los procesos para optimizar el tiempo.`);
                        }
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
                hablar("Hola a todo el equipo de recargue. Faltan 15 minutos para completar la hora, evaluemos y prioricemos con la torre de control las próximas atenciones.");
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