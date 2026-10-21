# WarePro — Checkpoint de trabajo

**Fecha:** 2026-10-21 11:13
**Autor:** Jefe Sistemas `<jefesistemas@lis.com.co>`
**Rama principal:** `main` (sincronizada con `origin/main`)

> Documento de continuidad. Resume lo realizado, cómo está desplegado el sistema
> y qué queda pendiente. Si retomas el trabajo, empieza leyendo la sección
> "Estado actual" y "Pendientes".

---

## 1. Contexto del sistema

- **Aplicación:** WarePro — sistema PHP de gestión de almacén (turnos, insumos,
  calidad, personal, workstations, metas/indicadores).
- **Repositorio:** https://github.com/Sebaxxttiann/WarePro.git (**público**).
- **Producción:** servidor local + Cloudflare Tunnel.
  - URL pública: `https://warepro.lis.com.co`
  - `DocumentRoot`: `/var/www/warepro` (copia del árbol de trabajo; propiedad de
    `lis-cucuta`, sin sudo necesario para desplegar).
  - vhost: `/etc/apache2/sites-available/warepro.conf` (propiedad de **root**;
    editarlo o recargar Apache requiere sudo con contraseña).
  - Log de errores: `/var/log/apache2/warepro-error.log`
- **Base de datos:** MySQL en `127.0.0.1`, esquema `u806400645_warepro`,
  usuario `u806400645_warepro`. No está expuesta a internet.
- **Despliegue:** manual. No hay CI/CD. Se copia el árbol de trabajo a
  `/var/www/warepro` (por ejemplo con `rsync`).

### Estructura relevante

- `/api/` — endpoints AJAX/fetch.
- `/core/` — configuración, conexión BD, header, helpers.
- `/modules/` — vistas y lógica por área.
- `/public/`, `/assets/` — estáticos.
- `/vendor/` — dependencias Composer (PhpSpreadsheet). **No versionado**; en
  producción existe como `root:www-data` y no es legible por `lis-cucuta`.

---

## 2. Estado actual (2026-10-21)

- `main` local == `origin/main` == `24e130a`. Árbol de trabajo limpio.
- Rama `fix/bugs-criticos` publicada en `ac85b91`.
- Producción refleja el árbol fusionado y verificado.

### Commits destacados (historial reescrito; hashes actuales)

```
24e130a chore: normalizar .gitignore (salto de línea final)
ac85b91 feat(db): permitir credenciales locales en core/db_credentials.php
682f5a4 security: bloquear acceso web directo a archivos internos de core/
02caf45 fix(proxy): silenciar warning si la key deja de ser legible
b12d91c fix: reportes de productividad vía proxy server-side (API key fuera del cliente)
977cac2 Merge remote-tracking branch 'origin/main' into fix/bugs-criticos
69a9db4 fix: módulo GPS caído, huecos CSRF y binario en el repo
af031f7 new_warepro
```

---

## 3. Trabajo realizado en esta sesión

### 3.1 Merge con `origin/main`

Se integró el PR #1 (workstations, indicadores por turno, metas, compensación
variable) **sin conflictos manuales**. Los bloques CSRF sobrevivieron en los 7
módulos y en `core/header.php`.

### 3.2 Bug crítico: reportes de productividad rotos

El merge **borró silenciosamente las constantes `GOOGLE_SHEETS_URL`**, dejando
`modules/reportes/productividad2.php` y `productividades.php` sin definirla (los
`fetch` fallaban).

**Solución:** proxy server-side `api/reportes/get_sheet_data.php`:
- Requiere sesión (`verificarLogin()`).
- Whitelist de 2 reportes (`productividad2`, `productividades`); el cliente solo
  nombra el reporte, nunca la URL ni el `spreadsheet_id`.
- Consulta Google Sheets desde el servidor y reenvía el JSON.
- Los dos reportes ahora apuntan a
  `../../api/reportes/get_sheet_data.php?reporte=...`.
- Verificado en producción con sesión real: 2476 y 3823 filas.

### 3.3 API key de Google fuera del cliente

- Vive en `core/google_api_key.php` (**gitignored**), con fallback a la env var
  `WAREPRO_GOOGLE_API_KEY`.
- Nuevo `core/.htaccess` bloquea el acceso web directo a archivos internos:
  antes `con_universal.php`, `config.php`, etc. eran servibles por HTTP
  (fuga potencial). Ahora responden **403**.
- El archivo con la key se autoprotege si se accede directamente (`404`).
- Cero referencias a `AIzaSy` en archivos versionados.

### 3.4 Migración aplicada en producción

Faltaban objetos de `database/migrations/2026_10_13_metas_indicadores_compensacion.sql`:
- Tablas creadas: `ind_turno_indicadores`, `ind_turno_valores`,
  `ind_turno_responsables`, `compensacion_config`.
- Columnas nuevas en `metas` (`sentido`, `modulo`, `descripcion`,
  `es_sistema`, `configurada`) y en `config_opm_global` (`operacion_id`).
- **Ya aplicadas** en producción.

### 3.5 Endurecimiento CSRF y otros (commit `69a9db4`)

- `verificarCsrf()` añadido a `modules/turnos/turnob.php` y `modules/rotura.php`.
- `api/planeacion/get_cargar_informativo.php`: valida token también en DELETE.
- `core/conexion_gps.php`: credenciales GPS caen a `WAREPRO_DB_*` (el módulo
  devolvía 500 porque el vhost solo define las principales).
- `core/con_universal.php`: registra en `error_log` el respaldo a la BD principal.
- `.gitignore`: excluye el binario `cloudflared-linux-amd64.deb` (19 MB).

### 3.6 Limpieza del historial de git

- Se eliminó `cloudflared-linux-amd64.deb` de **toda** la historia local con
  `git filter-branch`, luego `reflog expire` + `gc --prune=now`.
- Repo reducido a ~1.9 MB. Ningún archivo >1 MB en el historial.
- Respaldo del `.git` original en `/tmp/opencode/git-backup-*` (temporal).
- Tag remoto de salvaguarda del `main` anterior: **`backup-origin-main-original`**
  → `742c9bf`.

### 3.7 Push a GitHub

- `main` subido con `--force-with-lease` (fue necesario por la reescritura).
- `fix/bugs-criticos` publicado y configurado para rastrear el remoto.

### 3.8 Credenciales de BD locales (mecanismo listo)

- `core/config.php` ahora lee `core/db_credentials.php` (**gitignored**) con
  **prioridad sobre las variables `WAREPRO_DB_*` del vhost**.
- Esto permite rotar la contraseña de MySQL **sin editar el vhost de Apache**
  (que es de root). El archivo de credenciales existe en producción y funciona.
- **La contraseña de MySQL NO se pudo rotar** (ver Pendientes).

---

## 4. Verificación de producción (última corrida)

| Ruta | Resultado esperado | Estado |
|---|---|---|
| `/` y `/index.php` (login) | 200 | OK |
| `/logout.php` sin sesión | 302 | OK |
| `/modules/workstations/index.php` sin sesión | 302 | OK |
| `/modules/reportes/productividad2.php` sin sesión | 302 | OK |
| `/api/reportes/get_sheet_data.php` sin sesión | 302 | OK |
| `/api/reportes/get_sheet_data.php?reporte=<inválido>` | 400 | OK |
| `/core/google_api_key.php` | 403 | OK |
| `/core/db_credentials.php` | 403 | OK |
| `/core/config.php`, `/core/con_universal.php` | 403 | OK |
| `/cloudflared-linux-amd64.deb` | 404 | OK |
| Proxy con sesión válida | JSON con datos | OK |

Los errores `AH01630 client denied` en el log son el bloqueo del `.htaccess`
funcionando correctamente, no incidencias.

---

## 5. Archivos clave

| Archivo | Descripción |
|---|---|
| `api/reportes/get_sheet_data.php` | Proxy de Google Sheets (whitelist, server-side). |
| `modules/reportes/productividad2.php` | Usa el proxy (`?reporte=productividad2`). |
| `modules/reportes/productividades.php` | Usa el proxy (`?reporte=productividades`). |
| `core/config.php` | Credenciales por env var + `db_credentials.php`; sesión endurecida. |
| `core/db_credentials.php` | **Gitignored.** Credenciales locales de BD (prioridad). |
| `core/google_api_key.php` | **Gitignored.** API key de Google. |
| `core/.htaccess` | Bloquea acceso web directo a `core/`. |
| `core/header.php` | Meta CSRF + interceptor de `fetch`/`submit`. |
| `database/migrations/2026_10_13_metas_indicadores_compensacion.sql` | Migración aplicada. |
| `/etc/apache2/sites-available/warepro.conf` | vhost con `SetEnv WAREPRO_DB_*` (root). |

---

## 6. Pendientes

### 6.1 Rotar la API key de Google  🔴

La key sigue expuesta en el **historial público** del repo (commits anteriores a
la limpieza). Acción:

1. En Google Cloud Console, **rotar/restriccionar** la key actual.
2. Actualizar la nueva key en `/var/www/warepro/core/google_api_key.php`
   (y en el árbol de trabajo) — o bien definir
   `SetEnv WAREPRO_GOOGLE_API_KEY <key>` en el vhost.
3. Verificar el proxy con una sesión válida.

### 6.2 Rotar la contraseña de MySQL  🔴

El usuario `u806400645_warepro` **no tiene privilegio `ALTER USER`** y no hay
acceso root de MySQL, así que no se pudo cambiar. Pasos con un DBA:

1. `ALTER USER 'u806400645_warepro'@'localhost' IDENTIFIED BY '<nueva_fuerte>';`
2. Editar `pass` en `/var/www/warepro/core/db_credentials.php`.
3. Copiar el mismo cambio a `~/Documentos/WarePro/core/db_credentials.php`.
4. Verificar: login 200 y proxy de reportes OK.

> Nota: la contraseña antigua (la que aparece en el historial público del repo,
> en `core/conexion_gps.php`) **ya no funciona** (verificado con intento de
> conexión real). No hace falta rotarla de nuevo, solo asegurarse de que no se
> reutilice.
> La contraseña vigente es el literal `UNA_NUEVA_CONTRASEÑA` definido en el
> vhost (BD solo escucha en `127.0.0.1`).

### 6.3 Limpieza del tag de respaldo

Cuando se confirme que todo está correcto:

```bash
git push origin --delete backup-origin-main-original
git tag -d backup-origin-main-original
```

### 6.4 Vendor en producción

`/var/www/warepro/vendor` es `root:www-data` y no legible por `lis-cucuta`
(por eso el `rsync` de despliegue lo excluye). Si hay que actualizar
dependencias, requiere permiso elevado.

---

## 7. Comandos útiles

### Desplegar cambios a producción

```bash
cd ~/Documentos/WarePro
rsync -rc --delete \
  --exclude='.git' --exclude='vendor' \
  --exclude='cloudflared-linux-amd64.deb' \
  --exclude='core/google_api_key.php' \
  --exclude='core/db_credentials.php' \
  --exclude='.gitignore' \
  ./ /var/www/warepro/
```

> Tras el despliegue, ajustar permisos de los secretos a `644` para que
> `www-data` pueda leerlos:
> `chmod 644 /var/www/warepro/core/google_api_key.php /var/www/warepro/core/db_credentials.php`

### Comprobar producción

```bash
curl -s -o /dev/null -w "login=%{http_code}\n" https://warepro.lis.com.co/
curl -s -o /dev/null -w "key=%{http_code}\n"   https://warepro.lis.com.co/core/google_api_key.php
```

### Validar sintaxis PHP

```bash
find . -name "*.php" -not -path "./.git/*" -not -path "./vendor/*" -print0 \
  | xargs -0 -n1 php -l 2>&1 | grep -v "No syntax errors"
```

### Probar el proxy con sesión (crear usuario temporal)

```sql
INSERT INTO usuarios (cedula,nombre,cargo,password,activo,operacion_id)
VALUES ('1000000001','ZZ Temp','super_admin','<hash>',1,1);
-- ... probar ...
DELETE FROM usuarios WHERE cedula='1000000001';
```

Generar hash: `php -r "echo password_hash('ClaveTemp!', PASSWORD_DEFAULT);"`

---

## 8. Notas de seguridad

- `core/google_api_key.php` y `core/db_credentials.php` están **gitignored**
  (ver `.gitignore`) y **nunca** deben versionarse.
- El `.htaccess` de `core/` solo aplica porque el vhost declara
  `AllowOverride All`. La config global de Apache tiene `AllowOverride None`.
- Ningún código llama a `core/*.php` por HTTP (son solo includes), por lo que el
  bloqueo no rompe funcionalidad.
- El repositorio es **público**: cualquier secreto publicado debe considerarse
  comprometido y **rotarse**, no solo eliminarse.
