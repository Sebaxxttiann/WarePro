# Ware Pro - Sistema de Gestión de Almacén

**Ware Pro** es un aplicativo web diseñado para gestionar y medir la productividad de las operaciones de un almacén. Centraliza el control de turnos, manejo de insumos, procesos de calidad (temperaturas, roturas), auditorías, y la logística de personal a través de un panel de control interactivo (Dashboard).

---

## 👥 Roles y Permisos

El sistema cuenta con un control de acceso basado en roles (RBAC). Dependiendo del cargo del usuario en su sesión, el sistema habilita o deshabilita (bloquea) las tarjetas y rutas del dashboard.

Los roles principales son:

1. **Admin / Super Admin / Líder**
   - **Acceso:** Total.
   - **Descripción:** Tienen visibilidad y permisos sobre todos los módulos del aplicativo, incluyendo la administración de personal y configuraciones globales.

2. **Supervisor**
   - **Acceso:** Módulo de Supervisores.
   - **Módulos permitidos:** Gestión de Turnos (A, B, C), Insumos, Temperatura, Rotura, Sider Certificado, Errores de Verificación, OWs Cargue y Tableros Digitales.
   - **Descripción:** Encargados de la gestión completa de supervisión de turnos, control de calidad y temperatura.

3. **Verificador**
   - **Acceso:** Módulo de Verificador.
   - **Módulos permitidos:** Pasaje de T1, Error de Armado, Control de Devoluciones, Recargue T2, Check-List del WIP, Sorting (general y portería) y Tableros Digitales.
   - **Descripción:** Operan el control de verificación, armado, devoluciones y recargue de productos.

4. **Auxiliar**
   - **Acceso:** Módulo Auxiliar.
   - **Módulos permitidos:** Maquila (Reempaque), Vertimiento, Revisión, Check-List del WIP, Sorting, Temperatura Auxiliar, PI Despachados y PI Reabastecimiento. También acceden a sus respectivas vistas de OWS (Maquila, Vertimiento, Revisión).
   - **Descripción:** Encargados de operaciones de clasificación, lavado, reempaque, vertimiento y sorting (operación 24/7).

5. **Operador**
   - **Acceso:** Módulo de Operadores.
   - **Módulos permitidos:** Control de rotación de botellas y latas, y módulos de montacargas.
   - **Descripción:** Responsables de la movilización de carga y operación de maquinaria (montacargas).

---

## 📁 Estructura del Proyecto

De acuerdo a la estructura física del proyecto, los directorios principales son:

* `/api/`: Contiene los endpoints y servicios para peticiones asíncronas (AJAX/Fetch).
* `/assets/`: Archivos estáticos como hojas de estilo (CSS), scripts (JS) y fuentes.
* `/backups/`: Directorio destinado a las copias de seguridad del sistema o base de datos.
* `/core/`: Archivos de configuración principal, conexión a BD (`config.php`) y plantillas base (`header.php`).
* `/migrations/`: Scripts de migración o archivos SQL para la estructura de la base de datos.
* `/modules/`: Lógica y vistas de los diferentes módulos (turnos, insumos, personal, auditoría, etc.).
* `/public/`: Recursos públicos, incluyendo imágenes como el logotipo (`img/logotipo.png`).
* `/uploads/`: Directorio donde se almacenan los archivos cargados por los usuarios.
* `/vendor/`: Dependencias de terceros instaladas mediante Composer.
* `composer.json` / `composer.lock`: Archivos de configuración para el gestor de dependencias de PHP.
* `index.php`: Punto de entrada de la aplicación.

---

## ⚙️ Instalación y Configuración

Sigue estos pasos para desplegar el aplicativo en tu entorno local o servidor (ej. XAMPP, WAMP, o servidor Linux con Apache/Nginx).

### 1. Preparar el Entorno y Archivos
1. Clona o descarga el repositorio del proyecto en tu servidor web (por ejemplo, dentro de `htdocs` o `/var/www/html/`).
2. Asegúrate de tener **PHP 7.4 o superior** instalado.

### 2. Configuración de la Base de Datos
El proyecto requiere una base de datos MySQL/MariaDB.
1. Abre tu gestor de base de datos preferido (phpMyAdmin, DBeaver, MySQL Workbench).
2. Crea una nueva base de datos con el nombre: **`u806400645_warepro`** (tal como se indica en los recursos).
3. Selecciona la base de datos recién creada e **importa** el archivo SQL de la base de datos. Si el proyecto cuenta con el archivo en la carpeta `/migrations/` o en `/backups/`, impórtalo desde allí.
4. Edita el archivo `/core/config.php` y actualiza las credenciales de conexión:
   ```php
   // Ejemplo de configuración en config.php
   $host = 'localhost';
   $dbname = 'u806400645_warepro';
   $user = 'tu_usuario_db';
   $pass = 'tu_contraseña';
   ```

### 3. Instalación de Dependencias (PhpSpreadsheet)
El sistema requiere la librería **PhpSpreadsheet** (para exportar/importar reportes en Excel) y otras posibles dependencias manejadas por Composer.
1. Asegúrate de tener [Composer](https://getcomposer.org/) instalado en tu equipo.
2. Abre una terminal (o consola de comandos) y navega hasta la carpeta raíz del proyecto.
3. Ejecuta el siguiente comando para instalar todas las dependencias listadas en el `composer.json` (esto creará la carpeta `/vendor/`):
   ```bash
   composer install
   ```
   *Nota:* Si PhpSpreadsheet no estuviera en el `composer.json`, puedes instalarlo manualmente con:
   ```bash
   composer require phpoffice/phpspreadsheet
   ```

---

## 🚀 Uso del Aplicativo

1. **Iniciar Sesión:** Accede a la URL principal del proyecto (ej. `http://localhost/warepro/`). Serás redirigido al login. Ingresa tus credenciales asignadas.
2. **Dashboard Principal:** Una vez autenticado, verás el panel de control. 
3. **Buscador Inteligente:** En la parte superior, hay una barra de búsqueda (`#globalSearch`). Puedes escribir allí "turnos", "insumos", o cualquier módulo y el sistema filtrará automáticamente las opciones a las que tienes acceso.
4. **Navegación por Tarjetas:** El panel muestra tarjetas (Supervisores, Verificador, Auxiliar, Operadores).
   - Si tu rol te lo permite, al hacer clic en una tarjeta ingresarás a su respectivo sub-menú o módulo principal.
   - Si no tienes permisos, la tarjeta se verá opaca y, al intentar hacer clic, aparecerá una alerta de **"Acceso Denegado"** gestionada por SweetAlert2.
5. **Transiciones:** El sistema cuenta con un `loading-overlay` (pantalla de carga animada) que se activa entre las navegaciones para brindar retroalimentación visual al usuario durante tiempos de carga.

---
*Documentación generada para Ware Pro v1.0*
