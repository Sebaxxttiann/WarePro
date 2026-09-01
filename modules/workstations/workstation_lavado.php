<?php
require_once '../../core/config.php'; 

verificarLogin();
date_default_timezone_set('America/Bogota');

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    try {
        $pdo->exec("SET time_zone = '-05:00';");

        $stmt_diario = $pdo->prepare("
            SELECT HOUR(hora) as hora_dia, SUM(unidades) as total_unidades 
            FROM ows_reempaque 
            WHERE actividad = 'LAVADO' AND DATE(fecha) = CURDATE() 
            GROUP BY HOUR(hora) 
            ORDER BY hora_dia ASC
        ");
        $stmt_diario->execute();
        $sic_diario = $stmt_diario->fetchAll();

        $stmt_mes = $pdo->prepare("
            SELECT DAY(fecha) as dia, ROUND(SUM(unidades) / COUNT(DISTINCT HOUR(hora)), 2) as promedio_diario
            FROM ows_reempaque 
            WHERE actividad = 'LAVADO' AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
            GROUP BY DATE(fecha) 
            ORDER BY fecha ASC
        ");
        $stmt_mes->execute();
        $tendencia_mes = $stmt_mes->fetchAll();

        $stmt_ranking = $pdo->prepare("
            SELECT u.nombre as colaborador, SUM(o.unidades) as total_unidades
            FROM ows_reempaque o
            JOIN usuarios u ON o.usuario_id = u.id
            WHERE o.actividad = 'LAVADO' AND MONTH(o.fecha) = MONTH(CURDATE()) AND YEAR(o.fecha) = YEAR(CURDATE())
            GROUP BY u.id
            ORDER BY total_unidades DESC
            LIMIT 5
        ");
        $stmt_ranking->execute();
        $ranking = $stmt_ranking->fetchAll();

        $stmt_ultimo = $pdo->prepare("
            SELECT o.id, u.nombre as colaborador, o.unidades 
            FROM ows_reempaque o
            JOIN usuarios u ON o.usuario_id = u.id
            WHERE o.actividad = 'LAVADO'
            ORDER BY o.id DESC LIMIT 1
        ");
        $stmt_ultimo->execute();
        $ultimo_registro = $stmt_ultimo->fetch();

        echo json_encode([
            'sic_diario' => $sic_diario,
            'tendencia_mes' => $tendencia_mes,
            'ranking' => $ranking,
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
    <title>Workstation - Lavado CD Cúcuta</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
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
        
        
        .header-container { background: linear-gradient(90deg, #000000 0%, #1a1a1a 100%); color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 5px solid #ffcc00; box-shadow: 0 4px 10px rgba(0,0,0,0.2); position: sticky; top: 0; z-index: 1000; }
        .header-container h1 { font-size: 30px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin: 0; text-align: center; flex-grow: 1; }
        .logo-bavaria { font-weight: 800; font-size: 26px; color: #ff2a2a; }
        .logo-dpo { font-weight: 800; font-size: 26px; color: #d4af37; }
        
        .estado-sistema { font-size: 13px; font-weight: 700; color: #ff4d4d; margin-right: 20px; display: flex; align-items: center; gap: 5px; cursor: pointer; }

        
        .dashboard-wrapper { padding: 15px; display: flex; flex-direction: column; gap: 15px; }
        
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 15px; border: 1px solid #eaeaea; }
        .card-header { font-size: 18px; font-weight: 800; color: #d4af37; text-align: center; text-transform: uppercase; margin-bottom: 10px; border-bottom: 2px dashed #eee; padding-bottom: 8px; }
        
        .row-top { display: grid; grid-template-columns: 2fr 4fr 4fr; gap: 15px; }
        
        .meta-content { text-align: center; margin-top: 10px; }
        .meta-content .highlight { font-size: 20px; font-weight: 800; color: #000; background: #ffcc00; padding: 5px 10px; border-radius: 6px; display: inline-block; margin-bottom: 5px; }
        .meta-content .sub-text { font-size: 15px; font-weight: 600; color: #555; }
        .no-data { font-size: 24px; font-weight: 300; color: #aaa; margin: 25px 0; }
        
       .riesgos-area { margin-top: 20px; font-weight: 700; font-size: 16px; text-align: center; }
.iconos-riesgos { display: flex; justify-content: center; gap: 12px; margin-top: 5px; }


.iconos-riesgos div { 
    width: 55px; 
    height: 55px; 
    background: #fff; 
    border-radius: 8px; 
    border: 2px solid #ffcc00; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    box-shadow: 2px 2px 0px #000; 
    padding: 2px; 
    overflow: hidden; 
}


.iconos-riesgos img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
    border-radius: 4px; 
}


.texto-riesgos { display: flex; justify-content: center; gap: 12px; font-size: 10px; margin-top: 4px; font-weight: 600; color: #555; }
.texto-riesgos span { width: 55px; text-align: center; word-wrap: break-word; line-height: 1.2; }

        
        .chart-container { height: 320px; width: 100%; border-radius: 8px; overflow: hidden; }

        .row-bottom { display: grid; grid-template-columns: 3.5fr 4fr 2.5fr; gap: 15px; align-items: stretch; }
        
        .action-log { background: #111; color: #fff; border-radius: 12px; }
        .action-log .card-header { border-color: #333; }
        .log-item { margin-bottom: 15px; }
        .log-item span { font-weight: 800; font-size: 15px; display: block; margin-bottom: 4px; }
        .log-item p { font-size: 13px; font-weight: 300; line-height: 1.5; color: #ddd; font-style: italic; }
        .text-preventiva { color: #ffcc00; }
        .text-reactiva { color: #ff4c4c; }

        .table-responsive { overflow-x: auto; }
        .ranking-table { width: 100%; border-collapse: collapse; }
        .ranking-table th { background: #ffcc00; color: #000; padding: 10px; font-weight: 800; font-size: 14px; text-align: left; }
        .ranking-table td { padding: 10px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; color: #333; vertical-align: middle; }
        .ranking-table tr:hover { background-color: #fcf8e3; }
        .ranking-table tr:nth-child(1) td { background: linear-gradient(90deg, rgba(255,204,0,0.2) 0%, rgba(255,204,0,0.05) 100%); border-left: 4px solid #ffcc00; font-weight: 800; }
        .icon-rank { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; margin-right: 6px; vertical-align: middle;}

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
        <h1>WORKSTATION - LAVADO CD CÚCUTA</h1>
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
                <div class="card-header">META</div>
                <div class="meta-content">
                    <div class="highlight">17 CAJAS / HORA</div>
                    <div class="sub-text">408 UNIDADES / HORA</div>
                    <div class="no-data" id="estado-meta">Esperando datos...</div>
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
                    <p>Revisar si el flujo de agua y aire en el compresor es el apropiado para el ejercicio y organizar por referencia producto a lavar, garantizar 5s.</p>
                </div>
                <div class="log-item">
                    <span class="text-reactiva">Zona Reactiva:</span>
                    <p>Notificar al supervisor si la desviación persiste para asignar un auxiliar de apoyo y recuperar el ritmo de productividad en el área.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">RANKING POR PERSONA</div>
                <div class="table-responsive">
                    <table class="ranking-table" id="tabla-ranking">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>COLABORADOR</th>
                                <th>UNIDADES</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
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

        const yAxisConfig = {
            min: 200, 
            max: 800, 
            showEmpty: true, 
            title: { text: 'UNIDADES', style: { fontWeight: 'bold', fontFamily: 'Poppins' } },
            plotBands: [
                { from: 0, to: 275, color: '#ff4d4d' },     
                { from: 275, to: 400, color: '#ffeb3b' },   
                { from: 400, to: 10000, color: '#32cd32' }  
            ],
            gridLineWidth: 0,
            labels: { style: { fontWeight: 'bold', color: '#000', fontFamily: 'Poppins' } }
        };

        const chartOptions = {
            chart: { type: 'line', style: { fontFamily: 'Poppins' }, backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            legend: { enabled: false },
            xAxis: { 
                gridLineWidth: 1, gridLineDashStyle: 'Dash', 
                labels: { style: { fontWeight: 'bold', color: '#000' } },
                showEmpty: true 
            },
            yAxis: yAxisConfig,
            plotOptions: {
                line: {
                    color: '#000000',
                    lineWidth: 3,
                    marker: { enabled: true, fillColor: '#000000', radius: 5, symbol: 'circle' },
                    dataLabels: { enabled: true, style: { fontWeight: 'bold', fontSize: '11px' } }
                }
            }
        };

        let chartDiario, chartMes;
        let ultimoIdRegistro = 0;
        let sistemaActivo = false;
        let speechSynth = window.speechSynthesis;
        let horaAvisoNotificado = -1; 
        let wakeLock = null;

        function inicializarGraficas() {
            chartDiario = Highcharts.chart('containerDiario', { ...chartOptions, xAxis: { ...chartOptions.xAxis, categories: [] }, series: [{ name: 'Unidades', data: [] }] });
            chartMes = Highcharts.chart('containerMes', { ...chartOptions, xAxis: { ...chartOptions.xAxis, categories: [] }, series: [{ name: 'Unidades', data: [] }] });
        }

        async function fetchData() {
            try {
                const res = await fetch('workstation_lavado.php?ajax=1');
                const data = await res.json();

                if (data.error) return;

                let labelsDiario = data.sic_diario.map(d => d.hora_dia + "h");
                let datosDiario = data.sic_diario.map(d => Number(d.total_unidades));
                
                let maxDiario = datosDiario.length > 0 ? Math.max(...datosDiario) : 0;
                chartDiario.yAxis[0].update({ max: maxDiario > 800 ? null : 800 });
                chartDiario.update({ xAxis: { categories: labelsDiario }, series: [{ data: datosDiario }] }, true, true);

                let labelsMes = data.tendencia_mes.map(d => d.dia);
                let datosMes = data.tendencia_mes.map(d => Number(d.promedio_diario));
                
                let maxMes = datosMes.length > 0 ? Math.max(...datosMes) : 0;
                chartMes.yAxis[0].update({ max: maxMes > 800 ? null : 800 });
                chartMes.update({ xAxis: { categories: labelsMes }, series: [{ data: datosMes }] }, true, true);

                let tbody = document.querySelector('#tabla-ranking tbody');
                tbody.innerHTML = '';
                if(data.ranking.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Sin datos aún</td></tr>';
                } else {
                    data.ranking.forEach((row, index) => {
                        let icono = '';
                        if(index === 0) icono = `<i data-lucide="award" class="icon-rank" style="color:#d4af37;"></i>`; 
                        else if(index === 1) icono = `<i data-lucide="award" class="icon-rank" style="color:#C0C0C0;"></i>`; 
                        else if(index === 2) icono = `<i data-lucide="award" class="icon-rank" style="color:#cd7f32;"></i>`; 
                        else icono = `<span class="icon-rank"></span>`;

                        tbody.innerHTML += `<tr>
                            <td>${index + 1}</td>
                            <td>${icono} ${row.colaborador.toUpperCase()}</td>
                            <td>${row.total_unidades}</td>
                        </tr>`;
                    });
                    lucide.createIcons();
                }

                if (data.ultimo_registro && data.ultimo_registro.id > ultimoIdRegistro) {
                    let nombre = data.ultimo_registro.colaborador || "Compañero";
                    let unidades = parseInt(data.ultimo_registro.unidades);
                    let divMeta = document.getElementById('estado-meta');
                    
                    divMeta.innerHTML = `<span style="color:#000;">Último reg: <b>${unidades}</b></span>`;

                    if (ultimoIdRegistro !== 0) { 
                        if (unidades >= 408) {
                            hablar(`¡Excelente! Felicitaciones ${nombre}, cumpliste con la productividad, registrando ${unidades} unidades lavadas. Sigue así.`);
                        } else {
                            let faltantes = 408 - unidades;
                            hablar(`Atención ${nombre}, no alcanzaste la meta. Registraste ${unidades} unidades y te faltaron ${faltantes} unidades. Recuerda tomar las acciones correctivas que se encuentran en la esquina inferior izquierda para mejorar tu productividad. ¡Vamos que sí se puede recuperar el ritmo!`);
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
                hablar("Hola a todo el equipo de lavado. Faltan 15 minutos para completar la hora, no se les olvide subir la productividad a nuestra aplicación de warepro.");
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

        window.onload = () => {
            activarSistemaSilencioso();
            inicializarGraficas();
            fetchData();
            
            setInterval(fetchData, 5000); 
            setInterval(checkAvisosHora, 1000); 
            setInterval(moverPantallaMicro, 30000); 
        };
    </script>
</body>
</html>