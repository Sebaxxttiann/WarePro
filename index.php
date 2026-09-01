<?php
require_once 'core/config.php';

if (isset($_POST['action']) && $_POST['action'] == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = limpiarDatos($_POST['cedula']);
    $password = md5($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE cedula = ? AND password = ? AND activo = 1");
    $stmt->execute([$cedula, $password]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['cedula'] = $usuario['cedula'];
        $_SESSION['cargo'] = $usuario['cargo'];
        $_SESSION['operacion_id'] = $usuario['operacion_id'];
        $_SESSION['operacion_activa_id'] = $usuario['operacion_id'];
        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ware Pro - Sistema de Gestión</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: #f5f5f5;
        }

        
        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 100;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 65px; 
            width: auto;
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.05);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #666;
            font-size: 0.9rem;
        }

        .header-actions i {
            margin-right: 5px;
        }

        
        .main-container {
            display: flex;
            min-height: calc(100vh - 95px);
        }

        
        .carousel-section {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
            min-height: 600px; 
        }

        .carousel-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .carousel-content {
            text-align: left;
            color: white;
            padding: 60px;
            z-index: 2;
            max-width: 500px;
        }

        .carousel-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .carousel-subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            font-weight: 400;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        
        .carousel-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(45, 45, 45, 0.7), rgba(26, 26, 26, 0.5));
            z-index: 1;
        }

        
        .carousel-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-dot.active {
            background: #FFD700;
            transform: scale(1.2);
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            color: #333;
            transition: all 0.3s ease;
            z-index: 3;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .carousel-arrow:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-arrow.prev {
            left: 20px;
        }

        .carousel-arrow.next {
            right: 20px;
        }

        
        .auth-section {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 80px;
            position: relative;
        }

        
        .auth-header {
            width: 100%;
            max-width: 400px;
            margin-bottom: 40px;
            text-align: center;
        }

        
        .auth-logo {
            width: 120px;
            height: auto;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }

        .auth-logo:hover {
            transform: scale(1.05);
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 8px;
            text-align: left;
        }

        .security-badge {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            font-size: 0.9rem;
            color: #16a085;
        }

        .security-badge i {
            margin-right: 8px;
            font-size: 1rem;
        }

        
        .auth-forms {
            width: 100%;
            max-width: 400px;
        }

        .form-switch {
            display: none;
        }

        .form-switch.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
            color: #374151;
            font-family: 'Poppins', sans-serif;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #2d2d2d;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .trouble-link {
            text-align: center;
            margin-bottom: 20px;
        }

        .trouble-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .trouble-link a:hover {
            color: #FFD700;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .toggle-text {
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .toggle-link {
            color: #FFD700;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-link:hover {
            color: #FFA500;
        }

        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            text-align: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #FFD700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        .loading-text {
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        
        .notyf__toast--success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }

        .notyf__toast--error {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }

        .notyf__toast {
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
            font-family: 'Poppins', sans-serif !important;
        }

        .notyf__message {
            font-weight: 500 !important;
        }

        
        @media (max-width: 1024px) {
            .auth-section {
                padding: 40px 50px;
            }
            
            .carousel-content {
                padding: 40px;
            }
            
            .carousel-title {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 10px 15px;
            }

            .logo-container img {
                height: 50px;
            }
            
            .main-container {
                flex-direction: column;
            }
            
            .carousel-section {
                min-height: 450px !important; 
                flex: none;
                order: 1;
                width: 100% !important;
            }
            
            .carousel-content {
                padding: 30px;
                text-align: center;
            }
            
            .carousel-title {
                font-size: 2.2rem;
            }
            
            .carousel-subtitle {
                font-size: 1.1rem;
            }
            
            .auth-section {
                padding: 30px 20px;
                order: 2;
                min-height: auto;
            }
            
            .auth-header,
            .auth-forms {
                max-width: 350px;
            }

            .auth-logo {
                width: 100px;
            }

            .carousel-arrow {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .carousel-section {
                min-height: 400px !important; 
            }
            
            .carousel-content {
                padding: 20px;
            }
            
            .carousel-title {
                font-size: 1.8rem;
            }
            
            .auth-section {
                padding: 25px 15px;
            }
        }
        
        .btn-secondary-outline {
    width: 100%;
    padding: 12px;
    background: transparent;
    border: 2px solid #2d2d2d;
    color: #2d2d2d;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-secondary-outline:hover {
    background: #2d2d2d;
    color: #FFD700;
}
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-text">Procesando...</div>
        </div>
    </div>

    
    <header class="header">
        <div class="header-content">
            <div class="logo-container">
                <img src="public/img/logo-2.png" alt="Ware Pro" class="logo1">
            </div>
            <div class="header-actions">
            </div>
        </div>
    </header>

    <div class="main-container">
        
        <div class="carousel-section">
            <div class="carousel-container">
                
                <div class="carousel-slide active" style="background-image: url('public/img/carru1.jpg');">
                    <div class="carousel-content">
                        <h1 class="carousel-title">Gestión<br>Profesional</h1>
                        <p class="carousel-subtitle">Sistema completo para tu almacén</p>
                    </div>
                </div>
                
                
                <div class="carousel-slide" style="background-image: url('public/img/carru2.jpg');">
                    <div class="carousel-content">
                        <h1 class="carousel-title">Control<br>Total</h1>
                        <p class="carousel-subtitle">Monitorea tu inventario en tiempo real</p>
                    </div>
                </div>
                
                
                <div class="carousel-slide" style="background-image: url('public/img/carru3.jpg');">
                    <div class="carousel-content">
                        <h1 class="carousel-title">Eficiencia<br>Máxima</h1>
                        <p class="carousel-subtitle">Optimiza todas tus operaciones</p>
                    </div>
                </div>
                
                
                <button class="carousel-arrow prev" onclick="prevSlide()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="carousel-arrow next" onclick="nextSlide()">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <div class="carousel-controls">
                    <div class="carousel-dot active" onclick="goToSlide(0)"></div>
                    <div class="carousel-dot" onclick="goToSlide(1)"></div>
                    <div class="carousel-dot" onclick="goToSlide(2)"></div>
                </div>
            </div>
        </div>

        
        <div class="auth-section">
            
            <div class="auth-header">
                
                <img src="public/img/logotipo.png" alt="Ware Pro" class="auth-logo">
                <h1 class="auth-title">Iniciar Sesión</h1>
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    Tu información está protegida
                </div>
            </div>


<div class="auth-forms">
<form id="loginForm" class="form-switch active">
<div class="form-group">
<input type="text" class="form-control" name="cedula" placeholder="Número de cédula" required>
</div>
<button type="submit" class="btn btn-primary">Continuar</button>
                </form>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    
    <script>
        
        const notyf = new Notyf({
            duration: 4000,
            position: {
                x: 'right',
                y: 'top'
            },
            types: [
                {
                    type: 'success',
                    background: 'linear-gradient(135deg, #10b981, #059669)',
                    icon: {
                        className: 'fas fa-check',
                        tagName: 'i'
                    }
                },
                {
                    type: 'error',
                    background: 'linear-gradient(135deg, #ef4444, #dc2626)',
                    icon: {
                        className: 'fas fa-times',
                        tagName: 'i'
                    }
                }
            ]
        });

        
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        let slideInterval;

        
        function initCarousel() {
            startAutoSlide();
        }

        
        function goToSlide(n) {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');
            
            currentSlide = n;
            
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
            
            resetAutoSlide();
        }

        
        function nextSlide() {
            const next = (currentSlide + 1) % slides.length;
            goToSlide(next);
        }

        
        function prevSlide() {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            goToSlide(prev);
        }

        
        function startAutoSlide() {
            slideInterval = setInterval(nextSlide, 4000);
        }

        
        function resetAutoSlide() {
            clearInterval(slideInterval);
            startAutoSlide();
        }

        
        document.querySelector('.carousel-section').addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });

        document.querySelector('.carousel-section').addEventListener('mouseleave', () => {
            startAutoSlide();
        });

        
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            
            const cedulaInput = this.querySelector('input[name="cedula"]');
            const passwordInput = this.querySelector('input[name="password"]');
            
            if (!passwordInput) {
                
                const formGroup = document.createElement('div');
                formGroup.className = 'form-group';
                formGroup.innerHTML = '<input type="password" class="form-control" name="password" placeholder="Contraseña" required>';
                
                cedulaInput.parentNode.insertAdjacentElement('afterend', formGroup);
                formGroup.querySelector('input').focus();
                return;
            }
            
            showLoading();
            
            const formData = new FormData(this);
            formData.append('action', 'login');
            
            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                hideLoading();
                
                if (result.success) {
                    notyf.success('¡Bienvenido! Inicio de sesión exitoso. Redirigiendo...');
                    setTimeout(() => {
                        window.location.href = 'modules/reportes/dashboard.php';
                    }, 2000);
                } else {
                    notyf.error(result.message || 'Credenciales incorrectas');
                }
            } catch (error) {
                hideLoading();
                notyf.error('Error de conexión. No se pudo conectar con el servidor.');
            }
        });


        
        document.querySelectorAll('input[name="cedula"]').forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
        });

        
        document.addEventListener('DOMContentLoaded', initCarousel);
    </script>
</body>
</html>