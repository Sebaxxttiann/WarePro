<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';

$stmt = $pdo->prepare("SELECT * FROM pi_despachados WHERE operacion_id = ? ORDER BY fecha DESC, id DESC");
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI Desechado - WARE PRO</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 3px solid #FFD700;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1rem;
        }

        .page-title i {
            font-size: 2rem;
            color: #FFD700;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        .page-subtitle {
            color: #ccc;
            font-size: 1rem;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #FFD700;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }

        .search-box {
            position: relative;
            max-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .table-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 60px repeat(6, 1fr) 120px;
            gap: 1rem;
            align-items: center;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 60px repeat(6, 1fr) 120px;
            gap: 1rem;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .table-row:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .row-number {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .fecha-badge {
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #e0e0e0;
        }

        .distribuidor-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .distribuidor-surti {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
        }

        .distribuidor-surtilicores {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
        }

        .distribuidor-logisticos {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
        }

        .distribuidor-zomax {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            color: #7b1fa2;
        }

        .distribuidor-bavarianbeer {
            background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
            color: #e65100;
        }

        .distribuidor-ms3j {
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
            color: #c2185b;
        }

        .placa-badge {
            background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
            color: #e65100;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cd-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cd-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .verificador-info {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .actions-cell {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-view:hover {
            background: #1976d2;
            color: white;
            transform: scale(1.1);
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
        }

        .btn-edit:hover {
            background: #f57c00;
            color: white;
            transform: scale(1.1);
        }

        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }

        .btn-delete:hover {
            background: #d32f2f;
            color: white;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
        }

        .modal-content {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            margin: 2% auto;
            padding: 0;
            border-radius: 25px;
            width: 95%;
            max-width: 1100px;
            max-height: 90vh;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            z-index: 10000;
            overflow: hidden;
            border: 3px solid #FFD700;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-80px) scale(0.8) rotateX(15deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1) rotateX(0deg);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: white;
            padding: 2.5rem 3rem;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(180deg); }
        }

        .modal-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .modal-header i {
            color: #FFD700;
            font-size: 2rem;
            padding: 0.5rem;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 12px;
        }

        .close {
            position: absolute;
            right: 25px;
            top: 25px;
            color: #ccc;
            font-size: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10001;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
        }

        .close:hover {
            color: #FFD700;
            transform: rotate(90deg) scale(1.1);
            background: rgba(255, 215, 0, 0.2);
        }

        .modal-body {
            padding: 2.5rem 3rem;
            max-height: 65vh;
            overflow-y: auto;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
        }

        .form-section {
            margin-bottom: 2.5rem;
            padding: 2rem;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #FFD700;
        }

        .section-title i {
            color: #FFD700;
            font-size: 1.5rem;
            padding: 0.5rem;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #FFD700;
            width: 18px;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            font-family: inherit;
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
            transform: translateY(-2px);
            background: #ffffff;
        }

        .form-control:valid:not(:placeholder-shown) {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }

        select.form-control {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
            appearance: none;
        }

        select.form-control:hover {
            border-color: #FFD700;
            background-color: rgba(255, 215, 0, 0.02);
        }

        select.form-control option {
            padding: 10px;
            background: #ffffff;
            color: #333;
            font-weight: 500;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            padding: 2rem 3rem;
            border-top: 2px solid #e0e0e0;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            background: linear-gradient(145deg, #f8f9fa 0%, #ffffff 100%);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        }

        .swal2-container {
            z-index: 10002 !important;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: center;
            }

            .actions-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .search-box {
                max-width: 100%;
            }

            .modal-content {
                margin: 5% auto;
                width: 98%;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-truck-loading"></i>
                <div>
                    <h1>PI Desechados</h1>
                    <p class="page-subtitle">Control y registro de productos despachados</p>
                </div>
            </div>
        </div>

        
        <div class="actions-bar">
            <button class="btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus"></i>
                Nuevo PI Desechado
            </button>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar registros..." onkeyup="filtrarTabla()">
                <i class="fas fa-search"></i>
            </div>
        </div>

        
        <div class="table-container">
            <div class="table-header">
                <div>#</div>
                <div>Fecha</div>
                <div>CD</div>
                <div>Verificador</div>
                <div>Distribuidor</div>
                <div>Placa</div>
                <div>Cajas/Envases</div>
                <div>Acciones</div>
            </div>

            <div id="tableBody">
                <?php if (empty($registros)): ?>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <h3>No hay registros</h3>
                        <p>Comienza agregando tu primer PI despachado</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($registros as $index => $registro): ?>
                        <div class="table-row" data-id="<?= $registro['id'] ?>">
                            <div class="row-number"><?= $index + 1 ?></div>
                            <div>
                                <span class="fecha-badge">
                                    <?= date('d/m/Y', strtotime($registro['fecha'])) ?>
                                </span>
                            </div>
                            <div class="cd-info">
                                <div class="cd-avatar">
                                    <?= strtoupper(substr($registro['cd'], 0, 2)) ?>
                                </div>
                                <span><?= htmlspecialchars($registro['cd']) ?></span>
                            </div>
                            <div class="verificador-info">
                                <?= htmlspecialchars($registro['verificador']) ?>
                            </div>
                            <div>
                                <span class="distribuidor-badge distribuidor-<?= strtolower(str_replace([' ', '&'], '', $registro['distribuidor'])) ?>">
                                    <?= htmlspecialchars($registro['distribuidor']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="placa-badge">
                                    <?= htmlspecialchars($registro['placa']) ?>
                                </span>
                            </div>
                            <div>
                                <small>Rec: <?= $registro['cajas_recibidas'] ?>/<?= $registro['envases_recibidos'] ?></small><br>
                                <small>Res: <?= $registro['cajas_resividas'] ?>/<?= $registro['envases_resividos'] ?></small>
                            </div>
                            <div class="actions-cell">
                                <button class="btn-action btn-view" onclick="verRegistro(<?= $registro['id'] ?>)" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" onclick="editarRegistro(<?= $registro['id'] ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div id="piModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-truck-loading"></i> <span id="modalTitle">Nuevo PI Despachado</span></h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="piForm">
                    <input type="hidden" id="registroId">

                    
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Información General
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha"><i class="fas fa-calendar-alt"></i> Fecha *</label>
                                <input type="date" id="fecha" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="cd"><i class="fas fa-building"></i> CD *</label>
                                <input type="text" id="cd" class="form-control" placeholder="Centro de distribución" required>
                            </div>
                            <div class="form-group">
                                <label for="verificador"><i class="fas fa-user-check"></i> Verificador *</label>
                                <select id="verificador" class="form-control" required>
                                    <option value="">Seleccionar verificador</option>
                                    <option value="BREHITER JOSE JAIMES CAMPOS">BREHITER JOSE JAIMES CAMPOS</option>
                                    <option value="EGLY JOSE GONZALEZ ROLON">EGLY JOSE GONZALEZ ROLON</option>
                                    <option value="JAIDER ALBERTO TARAZONA BALAGUERA">JAIDER ALBERTO TARAZONA BALAGUERA</option>
                                    <option value="JESUS DAVID CARVAJAL NAVAS">JESUS DAVID CARVAJAL NAVAS</option>
                                    <option value="JESUS STEVENSON LEON SIERRA">JESUS STEVENSON LEON SIERRA</option>
                                    <option value="JOSE ALBERTO RIVERO CONTRERAS">JOSE ALBERTO RIVERO CONTRERAS</option>
                                    <option value="JUAN FRAN PEREZ QUINTERO">JUAN FRAN PEREZ QUINTERO</option>
                                    <option value="MARCOS ALEJANDRO ESPAÑA LARA">MARCOS ALEJANDRO ESPAÑA LARA</option>
                                    <option value="ROVIN ENRIQUE JIMENEZ OROZCO">ROVIN ENRIQUE JIMENEZ OROZCO</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="distribuidor"><i class="fas fa-truck"></i> Distribuidor *</label>
                                <select id="distribuidor" class="form-control" required>
                                    <option value="">Seleccionar distribuidor</option>
                                    <option value="Surti">Surti</option>
                                    <option value="Logisticos">Logisticos</option>
                                    <option value="Surtilicores">Surtilicores</option>
                                    <option value="Zomax">Zomax</option>
                                    <option value="Bavarian Beer">Bavarian Beer</option>
                                    <option value="M&S3J">M&S3J</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="placa"><i class="fas fa-id-card"></i> Placa *</label>
                                <input type="text" id="placa" class="form-control" placeholder="ABC123" style="text-transform: uppercase;" required>
                            </div>
                        </div>
                    </div>

                    
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-boxes"></i>
                            Cantidades
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cajas_recibidas"><i class="fas fa-box"></i> Cajas Recibidas</label>
                                <input type="number" id="cajas_recibidas" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="envases_recibidos"><i class="fas fa-wine-bottle"></i> Envases Recibidos</label>
                                <input type="number" id="envases_recibidos" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="cajas_resividas"><i class="fas fa-box-open"></i> Cajas Resividas</label>
                                <input type="number" id="cajas_resividas" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="envases_resividos"><i class="fas fa-recycle"></i> Envases Resividos</label>
                                <input type="number" id="envases_resividos" class="form-control" min="0" value="0">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="descripcion_envase"><i class="fas fa-edit"></i> Descripción de Envase</label>
                                <textarea id="descripcion_envase" class="form-control" rows="3" placeholder="Descripción detallada del envase"></textarea>
                            </div>
                        </div>
                    </div>

                    
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Unidades con Problemas
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="unidades_rotas"><i class="fas fa-broken-image"></i> Unidades Rotas</label>
                                <input type="text" id="unidades_rotas" class="form-control" placeholder="Descripción de unidades rotas">
                            </div>
                            <div class="form-group">
                                <label for="unidades_faltantes"><i class="fas fa-minus-circle"></i> Unidades Faltantes</label>
                                <input type="text" id="unidades_faltantes" class="form-control" placeholder="Descripción de unidades faltantes">
                            </div>
                            <div class="form-group">
                                <label for="unidades_otras_companias"><i class="fas fa-exchange-alt"></i> Unidades de Otras Compañías</label>
                                <input type="number" id="unidades_otras_companias" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="unidades_antiguo_formato"><i class="fas fa-history"></i> Unidades con Antiguo Formato</label>
                                <input type="number" id="unidades_antiguo_formato" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-sort"></i>
                            Clasificación de Unidades
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="unidades_nr"><i class="fas fa-ban"></i> Unidades No Retornables (NR)</label>
                                <input type="number" id="unidades_nr" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="unidades_mal_estado"><i class="fas fa-exclamation-circle"></i> Unidades en Mal Estado</label>
                                <input type="number" id="unidades_mal_estado" class="form-control" min="0" value="0">
                                <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">Impregnadas de cemento, pintura, dobladas u otros químicos</small>
                            </div>
                            <div class="form-group">
                                <label for="unidades_mal_clasificadas"><i class="fas fa-random"></i> Unidades Mal Clasificadas</label>
                                <input type="number" id="unidades_mal_clasificadas" class="form-control" min="0" value="0">
                                <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">Trocadas</small>
                            </div>
                            <div class="form-group">
                                <label for="plasticos_mal_estado"><i class="fas fa-trash-alt"></i> Plásticos en Mal Estado</label>
                                <input type="number" id="plasticos_mal_estado" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-tools"></i>
                            Condiciones Especiales
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="unidades_cuerpo_extrano"><i class="fas fa-search"></i> Unidades con Cuerpo Extraño</label>
                                <input type="number" id="unidades_cuerpo_extrano" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="envases_sucios_recuperables"><i class="fas fa-broom"></i> Envases Sucios Recuperables</label>
                                <input type="number" id="envases_sucios_recuperables" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="estibas_mal_estado"><i class="fas fa-pallet"></i> # Estibas en Mal Estado</label>
                                <input type="number" id="estibas_mal_estado" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="estibas_buen_estado"><i class="fas fa-check-circle"></i> # Estibas en Buen Estado</label>
                                <input type="number" id="estibas_buen_estado" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-primary" onclick="guardarRegistro()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <script>
        let modoEdicion = false;

        function abrirModal(id = null) {
            const modal = document.getElementById('piModal');
            const modalTitle = document.getElementById('modalTitle');

            if (id) {
                modoEdicion = true;
                modalTitle.textContent = 'Editar PI Despachado';
                cargarDatos(id);
            } else {
                modoEdicion = false;
                modalTitle.textContent = 'Nuevo PI Despachado';
                document.getElementById('piForm').reset();
                document.getElementById('registroId').value = '';
                document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
            }

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal() {
            const modal = document.getElementById('piModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('piForm').reset();
        }

        function cargarDatos(id) {
            const formData = new FormData();
            formData.append('accion', 'obtener');
            formData.append('id', id);

            fetch('../../api/picking/get_pi_despachados.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const registro = data.data;
                    document.getElementById('registroId').value = registro.id;
                    document.getElementById('fecha').value = registro.fecha;
                    document.getElementById('cd').value = registro.cd;
                    document.getElementById('verificador').value = registro.verificador;
                    document.getElementById('distribuidor').value = registro.distribuidor;
                    document.getElementById('placa').value = registro.placa;
                    document.getElementById('cajas_recibidas').value = registro.cajas_recibidas;
                    document.getElementById('envases_recibidos').value = registro.envases_recibidos;
                    document.getElementById('cajas_resividas').value = registro.cajas_resividas;
                    document.getElementById('envases_resividos').value = registro.envases_resividos;
                    document.getElementById('descripcion_envase').value = registro.descripcion_envase || '';
                    document.getElementById('unidades_rotas').value = registro.unidades_rotas || '';
                    document.getElementById('unidades_faltantes').value = registro.unidades_faltantes || '';
                    document.getElementById('unidades_otras_companias').value = registro.unidades_otras_companias;
                    document.getElementById('unidades_antiguo_formato').value = registro.unidades_antiguo_formato;
                    document.getElementById('unidades_nr').value = registro.unidades_nr;
                    document.getElementById('unidades_mal_estado').value = registro.unidades_mal_estado;
                    document.getElementById('unidades_mal_clasificadas').value = registro.unidades_mal_clasificadas;
                    document.getElementById('plasticos_mal_estado').value = registro.plasticos_mal_estado;
                    document.getElementById('unidades_cuerpo_extrano').value = registro.unidades_cuerpo_extrano;
                    document.getElementById('envases_sucios_recuperables').value = registro.envases_sucios_recuperables;
                    document.getElementById('estibas_mal_estado').value = registro.estibas_mal_estado;
                    document.getElementById('estibas_buen_estado').value = registro.estibas_buen_estado;
                } else {
                    mostrarError('Error al cargar los datos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos del registro');
            });
        }

        function guardarRegistro() {
            const formData = new FormData();
            const camposRequeridos = ['fecha', 'cd', 'verificador', 'distribuidor', 'placa'];
            let camposFaltantes = [];

            camposRequeridos.forEach(campo => {
                const elemento = document.getElementById(campo);
                if (!elemento.value.trim()) {
                    camposFaltantes.push(elemento.previousElementSibling.textContent.replace(/\*|\s*\*/g, '').replace(/^\s*[\w\s]*\s/, ''));
                    elemento.style.borderColor = '#d32f2f';
                } else {
                    elemento.style.borderColor = '#e0e0e0';
                }
            });

            if (camposFaltantes.length > 0) {
                mostrarError('Por favor complete los siguientes campos: ' + camposFaltantes.join(', '));
                return;
            }

            formData.append('accion', modoEdicion ? 'editar' : 'crear');
            if (modoEdicion) {
                formData.append('id', document.getElementById('registroId').value);
            }

            const campos = [
                'fecha', 'cd', 'verificador', 'distribuidor', 'placa',
                'cajas_recibidas', 'envases_recibidos', 'cajas_resividas', 'envases_resividos',
                'descripcion_envase', 'unidades_rotas', 'unidades_faltantes',
                'unidades_otras_companias', 'unidades_antiguo_formato', 'unidades_nr',
                'unidades_mal_estado', 'unidades_mal_clasificadas', 'plasticos_mal_estado',
                'unidades_cuerpo_extrano', 'envases_sucios_recuperables',
                'estibas_mal_estado', 'estibas_buen_estado'
            ];

            campos.forEach(campo => {
                formData.append(campo, document.getElementById(campo).value);
            });

            Swal.fire({
                title: modoEdicion ? 'Actualizando...' : 'Guardando...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../../api/picking/get_pi_despachados.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        confirmButtonColor: '#FFD700'
                    }).then(() => {
                        cerrarModal();
                        location.reload();
                    });
                } else {
                    mostrarError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al procesar la solicitud');
            });
        }

        function verRegistro(id) {
            const formData = new FormData();
            formData.append('accion', 'obtener');
            formData.append('id', id);

            fetch('../../api/picking/get_pi_despachados.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const registro = data.data;
                    Swal.fire({
                        title: '<div style="color: #1a1a1a; display: flex; align-items: center; gap: 15px; justify-content: center;"><i class="fas fa-truck-loading" style="color: #FFD700; font-size: 2rem;"></i>Detalles del PI Despachado</div>',
                        html: `
                        <div style="text-align: left; padding: 0; max-height: 70vh; overflow-y: auto;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem; border-radius: 15px; border: 2px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                                    <h4 style="color: #1a1a1a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFD700; font-weight: 700; display: flex; align-items: center; gap: 8px;"><i class="fas fa-info-circle"></i> Información General</h4>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Fecha:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${new Date(registro.fecha).toLocaleDateString('es-ES', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>CD:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.cd}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Verificador:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.verificador}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Distribuidor:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.distribuidor}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Placa:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.placa}</span></p>
                                </div>
                                <div style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem; border-radius: 15px; border: 2px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                                    <h4 style="color: #1a1a1a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFD700; font-weight: 700; display: flex; align-items: center; gap: 8px;"><i class="fas fa-boxes"></i> Cantidades</h4>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Cajas Recibidas:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.cajas_recibidas}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Envases Recibidos:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.envases_recibidos}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Cajas Resividas:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.cajas_resividas}</span></p>
                                    <p style="margin: 0.4rem 0; display: flex; justify-content: space-between; align-items: center;"><strong>Envases Resividos:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600; color: #1a1a1a;">${registro.envases_resividos}</span></p>
                                </div>
                            </div>
                            
                            ${registro.descripcion_envase ? `
                            <div style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem; border-radius: 15px; border: 2px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 1.5rem;">
                                <h4 style="color: #1a1a1a; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;"><i class="fas fa-edit"></i> Descripción de Envase</h4>
                                <div style="background: linear-gradient(145deg, #f8f9fa 0%, #ffffff 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid #FFD700; font-style: italic; color: #555;">
                                    ${registro.descripcion_envase}
                                </div>
                            </div>
                            ` : ''}
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem; border-radius: 15px; border: 2px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                                    <h4 style="color: #1a1a1a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFD700; font-weight: 700; display: flex; align-items: center; gap: 8px;"><i class="fas fa-exclamation-triangle"></i> Unidades con Problemas</h4>
                                    ${registro.unidades_rotas ? `<p style="margin: 0.3rem 0;"><strong>Rotas:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_rotas}</span></p>` : ''}
                                    ${registro.unidades_faltantes ? `<p style="margin: 0.3rem 0;"><strong>Faltantes:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_faltantes}</span></p>` : ''}
                                    <p style="margin: 0.3rem 0;"><strong>Otras Compañías:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_otras_companias}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>Antiguo Formato:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_antiguo_formato}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>No Retornables:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_nr}</span></p>
                                </div>
                                <div style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem; border-radius: 15px; border: 2px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                                    <h4 style="color: #1a1a1a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #FFD700; font-weight: 700; display: flex; align-items: center; gap: 8px;"><i class="fas fa-sort"></i> Estados y Clasificación</h4>
                                    <p style="margin: 0.3rem 0;"><strong>Mal Estado:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_mal_estado}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>Mal Clasificadas:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_mal_clasificadas}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>Plásticos Mal Estado:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.plasticos_mal_estado}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>Cuerpo Extraño:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.unidades_cuerpo_extrano}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>Sucios Recuperables:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.envases_sucios_recuperables}</span></p>
                                </div>
                            </div>
                            
                            <div style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem; border-radius: 15px; border: 2px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-top: 1.5rem;">
                                <h4 style="color: #1a1a1a; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px;"><i class="fas fa-pallet"></i> Estibas</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <p style="margin: 0.3rem 0;"><strong>Mal Estado:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.estibas_mal_estado}</span></p>
                                    <p style="margin: 0.3rem 0;"><strong>Buen Estado:</strong> <span style="background: rgba(255, 215, 0, 0.1); padding: 0.3rem 0.8rem; border-radius: 8px; font-weight: 600;">${registro.estibas_buen_estado}</span></p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 165, 0, 0.1) 100%); border-radius: 15px; text-align: center; border: 2px solid rgba(255, 215, 0, 0.3);">
                                <p style="margin: 0; font-size: 0.95rem; color: #666; font-weight: 500;">
                                    <i class="fas fa-clock" style="color: #FFD700; margin-right: 8px;"></i>
                                    Registrado: ${new Date(registro.fecha_creacion).toLocaleString('es-ES')}
                                </p>
                            </div>
                        </div>
                        `,
                        confirmButtonColor: '#FFD700',
                        confirmButtonText: '<i class="fas fa-check"></i> Cerrar',
                        width: '900px'
                    });
                } else {
                    mostrarError('Error al cargar los datos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos del registro');
            });
        }

        function editarRegistro(id) {
            abrirModal(id);
        }

        function mostrarError(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#FFD700'
            });
        }

        function filtrarTabla() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.table-row');

            rows.forEach(row => {
                if (row.classList.contains('empty-state')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('piModal');
            if (event.target === modal) {
                cerrarModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });

        document.getElementById('placa').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const numericos = document.querySelectorAll('input[type="number"]');
            numericos.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            });

            const rows = document.querySelectorAll('.table-row');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });

            const selectores = document.querySelectorAll('select.form-control');
            selectores.forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.style.borderColor = '#28a745';
                        this.style.background = 'rgba(40, 167, 69, 0.05)';
                    } else {
                        this.style.borderColor = '#e0e0e0';
                        this.style.background = '#ffffff';
                    }
                });
            });
        });
    </script>
</body>
</html>