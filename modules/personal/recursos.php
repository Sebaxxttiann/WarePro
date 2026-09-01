<?php
require_once '../../core/config.php';
verificarLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Recursos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 3px solid #e8eef5;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1a1a1a 0%, #4a4a4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            letter-spacing: -1px;
        }

        .header p {
            font-size: 1.2rem;
            color: #666;
            font-weight: 400;
        }

        .filter-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 50px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 12px 30px;
            border: 2px solid #e0e0e0;
            background: #ffffff;
            color: #666;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            border-color: #2196F3;
            color: #2196F3;
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: #2196F3;
            color: #ffffff;
            border-color: #2196F3;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 30px;
            padding-left: 15px;
            border-left: 4px solid #2196F3;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .card {
            background: #ffffff;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 35px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .card.hidden {
            display: none;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #2196F3, #21CBF3);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .card.pdf-card::before {
            background: linear-gradient(90deg, #f44336, #ff7961);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
            border-color: #2196F3;
        }

        .card.pdf-card:hover {
            border-color: #f44336;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .card-description {
            font-size: 1rem;
            color: #666;
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-type {
            display: inline-block;
            padding: 8px 18px;
            background: #E3F2FD;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1976D2;
            letter-spacing: 0.3px;
        }

        .card-type.pdf {
            background: #FFEBEE;
            color: #C62828;
        }

        .card-pilar {
            display: inline-block;
            padding: 6px 14px;
            background: #f5f5f5;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #555;
        }

        .card-pilar.almacen {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .card-pilar.seguridad {
            background: #FFF3E0;
            color: #E65100;
        }

        .card-pilar.people {
            background: #F3E5F5;
            color: #6A1B9A;
        }

        .card-pilar.flota {
            background: #E1F5FE;
            color: #0277BD;
        }

        @media (max-width: 1024px) {
            .grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 25px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 15px;
            }

            .header {
                margin-bottom: 30px;
                padding-bottom: 30px;
            }

            .header h1 {
                font-size: 2.2rem;
            }

            .header p {
                font-size: 1.1rem;
            }

            .filter-container {
                gap: 10px;
                margin-bottom: 40px;
            }

            .filter-btn {
                padding: 10px 20px;
                font-size: 0.95rem;
            }

            .section-title {
                font-size: 1.5rem;
                margin-bottom: 25px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
                margin-bottom: 40px;
            }

            .card {
                padding: 28px;
            }

            .card-title {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.9rem;
            }

            .header p {
                font-size: 1rem;
            }

            .filter-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .card {
                padding: 24px;
            }

            .card-title {
                font-size: 1.15rem;
            }

            .card-description {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Portal de Recursos Corporativos</h1>
            <p>Accede a todos los documentos y enlaces importantes de la organización</p>
        </div>

        <div class="filter-container">
            <button class="filter-btn active" onclick="filterPilar('todos')">Todos</button>
            <button class="filter-btn" onclick="filterPilar('almacen')">Almacén</button>
            <button class="filter-btn" onclick="filterPilar('seguridad')">Seguridad</button>
            <button class="filter-btn" onclick="filterPilar('people')">People</button>
            <button class="filter-btn" onclick="filterPilar('flota')">Flota</button>
        </div>

        <div class="section-title">Enlaces y Recursos Web</div>
        <div class="grid">
            <div class="card" data-pilar="almacen" onclick="openLink('https://warepro.logisticos.com.co/')">
    <div class="card-title">Ware Pro</div>
    <div class="card-description">Control de flujo y gestión de productividades en almacén</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card" data-pilar="people" onclick="openLink('https://peoplesistem.logisticos.com.co')">
    <div class="card-title">People Sistem</div>
    <div class="card-description">Sistema de gestión de personal y recursos humanos</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar people">People</span>
    </div>
</div>

<div class="card" data-pilar="almacen" onclick="openLink('https://lookerstudio.google.com/u/0/reporting/2282a61f-b220-43f9-b030-70382157b556/page/p_1mher982qd')">
    <div class="card-title">Tablero 5S 2026 operativo almacén</div>
    <div class="card-description">Dashboard de seguimiento y cumplimiento de metodología 5S</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card" data-pilar="almacen" onclick="openLink('https://docs.google.com/forms/d/e/1FAIpQLSccZPhtaRRmiBvgC2dAjs8xA8TW_TxsjcyhT8BmSQz7KELLWw/viewform')">
    <div class="card-title">Programa Buenas Prácticas - Cúcuta 2025</div>
    <div class="card-description">Formulario de registro y seguimiento de buenas prácticas operativas</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card" data-pilar="almacen" onclick="openLink('https://app.powerbi.com/view?r=eyJrIjoiOTM5MzUzYmEtMDczYi00MDZkLTkzMWEtOWU3ZTYwZTFmYzg5IiwidCI6Ijk3NmI0MDI4LWJhNjYtNDIxOC1hN2IwLWRmNjI5YmIwZjcxMSIsImMiOjR9')">
    <div class="card-title">Comparativo y Seguimiento Regional</div>
    <div class="card-description">Análisis comparativo de indicadores y métricas por región</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card" data-pilar="almacen" onclick="openLink('https://lookerstudio.google.com/u/0/reporting/fd2e9e32-a6ce-4d94-b0d3-886efc9b025f/page/ShIUE')">
    <div class="card-title">Listado SKU</div>
    <div class="card-description">Consulta y gestión de referencias de productos en almacén</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card" data-pilar="people" onclick="openLink('https://cdcucuta.logisticos.com.co/')">
    <div class="card-title">Página CD Cúcuta</div>
    <div class="card-description">Portal informativo del Centro de Distribución Cúcuta</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar people">People</span>
    </div>
</div>
<div class="card" data-pilar="seguridad" onclick="openLink('https://forms.gle/RCEAjPxC4nDQPfBm6')">
    <div class="card-title">Check list de Herramienta de vertimiento</div>
    <div class="card-description">Verificación de herramientas y equipos para operaciones de vertimiento</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar seguridad">Seguridad</span>
    </div>
</div>

<div class="card" data-pilar="seguridad" onclick="openLink('https://forms.gle/2mNQkB2t53i6V3YWA')">
    <div class="card-title">Check list de Herramientas de reempaque</div>
    <div class="card-description">Control de herramientas y equipos utilizados en procesos de reempaque</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar seguridad">Seguridad</span>
    </div>
</div>

<div class="card" data-pilar="seguridad" onclick="openLink('https://forms.gle/yL3A6SBJYZzgzKp17')">
    <div class="card-title">Check list de Herramienta estibadores</div>
    <div class="card-description">Inspección de herramientas y equipos para operaciones de estiba</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar seguridad">Seguridad</span>
    </div>
</div>

<div class="card" data-pilar="seguridad" onclick="openLink('https://forms.gle/xn9okMctX81Tp3Bb9')">
    <div class="card-title">Check list de Herramienta de lavado</div>
    <div class="card-description">Revisión de herramientas y equipos para operaciones de lavado</div>
    <div class="card-footer">
        <span class="card-type link">Enlace Externo</span>
        <span class="card-pilar seguridad">Seguridad</span>
    </div>
</div>

        </div>











        <div class="section-title">Documentos y Manuales PDF</div>
        <div class="grid">
            <div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_ MONITOREO DE TEMPERATURA_V10.pdf')">
    <div class="card-title">Monitoreo de Temperatura V10</div>
    <div class="card-description">Procedimiento para control y registro de temperaturas en almacén</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_ INV_05_V2025.pdf')">
    <div class="card-title">Inventario 05 V2025</div>
    <div class="card-description">Procedimiento actualizado de gestión de inventarios</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_1.2.1_ ADHERENCIA AL ABC_ LAYOUT V17.pdf')">
    <div class="card-title">Adherencia al ABC Layout V17</div>
    <div class="card-description">Gestión de ubicaciones según clasificación ABC en almacén</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_1.2.1_ CAPACIDAD DE ALMACENAMIENTO V05.pdf')">
    <div class="card-title">Capacidad de Almacenamiento V05</div>
    <div class="card-description">Procedimiento para cálculo y optimización de capacidad de almacén</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_2.2.4 CHECK-IN_DEVOLUCIONES_V11.pdf')">
    <div class="card-title">Check-In Devoluciones V11</div>
    <div class="card-description">Proceso de recepción y verificación de productos devueltos</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_2.2.5_VERTIMIENTO_V9.pdf')">
    <div class="card-title">Vertimiento V9</div>
    <div class="card-description">Procedimiento de descarga y vertimiento de producto líquido</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_2.2.6_REEMPAQUE V13.pdf')">
    <div class="card-title">Reempaque V13</div>
    <div class="card-description">Instrucciones para reempaque de producto dañado o deteriorado</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_3.3.3_SORTING_V6.pdf')">
    <div class="card-title">Sorting V6</div>
    <div class="card-description">Proceso de clasificación y ordenamiento de productos en almacén</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_4.1.3_PICKING_V8.pdf')">
    <div class="card-title">Picking V8</div>
    <div class="card-description">Procedimiento de preparación de pedidos y extracción de productos</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_4.2.3_REABASTECIMIENTO DE PICKING_V9.pdf')">
    <div class="card-title">Reabastecimiento de Picking V9</div>
    <div class="card-description">Proceso de reposición de productos en zona de picking</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_5.1.1_CARGUE T2 V15.pdf')">
    <div class="card-title">Cargue T2 V15</div>
    <div class="card-description">Procedimiento de carga de vehículos tipo T2 en muelle</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_5.1.1_DESCARGUE T2 V14.pdf')">
    <div class="card-title">Descargue T2 V14</div>
    <div class="card-description">Procedimiento de descarga de vehículos tipo T2 en muelle</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_CO_CUC_WH_MANEJO DE RESIDUOS SOLIDOS_V7.pdf')">
    <div class="card-title">Manejo de Residuos Sólidos V7</div>
    <div class="card-description">Procedimiento de clasificación, almacenamiento y disposición de residuos</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

<div class="card pdf-card" data-pilar="almacen" onclick="openPDF('../../pdfs/SOP_WH_2.2.3_ BLOQUEO DE PRODUCTO_V15.pdf')">
    <div class="card-title">Bloqueo de Producto V15</div>
    <div class="card-description">Protocolo de retención y bloqueo de productos no conformes</div>
    <div class="card-footer">
        <span class="card-type pdf">Documento PDF</span>
        <span class="card-pilar almacen">Almacén</span>
    </div>
</div>

        </div>
    </div>

    <script>
        function openLink(url) {
            window.open(url, '_blank');
        }

        function openPDF(pdfPath) {
            window.open(pdfPath, '_blank');
        }

        function filterPilar(pilar) {
            const cards = document.querySelectorAll('.card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if (pilar === 'todos') {
                    card.classList.remove('hidden');
                } else {
                    if (card.getAttribute('data-pilar') === pilar) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
        }
    </script>
</body>
</html>