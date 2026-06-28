# 🚀 Guía de despliegue en InfinityFree — El mercadito de Julia

Guía paso a paso para publicar el sitio **gratis** en InfinityFree.
Tiempo aproximado: 30–45 min (la activación de la cuenta y el SSL tardan un rato).

---

## PASO 1 · Crear la cuenta

1. Entra a **https://infinityfree.com** y haz clic en **Sign Up**.
2. Regístrate con tu correo y confírmalo (revisa tu bandeja / spam).

## PASO 2 · Crear el alojamiento (hosting)

1. En el panel, clic en **Create Account**.
2. Elige **subdominio gratis**, por ejemplo:
   `elmercaditodejulia.infinityfreeapp.com`
   *(o conecta tu dominio propio si ya tienes uno).*
3. Ponle una etiqueta y crea el sitio. **Espera ~5 minutos** a que se active.

## PASO 3 · Crear la base de datos MySQL

1. Entra al **Control Panel** del sitio → sección **MySQL Databases**.
2. Crea una base nueva (ej. `mercadito`). El sistema le pondrá un prefijo.
3. **Anota estos 4 datos** (los muestra esa misma página):

   | Dato | Se ve así |
   |---|---|
   | **DB_HOST** | `sqlXXX.infinityfree.com` *(NO es localhost)* |
   | **DB_NAME** | `epiz_XXXXXXX_mercadito` |
   | **DB_USER** | `epiz_XXXXXXX` |
   | **DB_PASS** | *(la contraseña de tu cuenta InfinityFree)* |

## PASO 4 · Importar la base de datos

1. En **MySQL Databases**, clic en **Admin** (abre phpMyAdmin) de tu base.
2. Pestaña **Importar** → **Seleccionar archivo** → sube `sql/esquema_completo.sql` → **Continuar**.
3. Repite **Importar** con `sql/datos_iniciales.sql`.
4. Verifica que aparezcan las tablas (`usuarios`, `productos`, `canastas`, etc.).

## PASO 5 · Elegir PHP 8

1. En el Control Panel → **PHP Config** (o **Select PHP Version**).
2. Selecciona **PHP 8.x** y guarda.

## PASO 6 · Subir los archivos del proyecto

Usa **FileZilla** (recomendado) o el **Online File Manager** del panel.

**Datos FTP** (Control Panel → **FTP Accounts**):
- Host FTP: `ftpupload.net` (o el que indique tu panel)
- Usuario: `epiz_XXXXXXX`
- Contraseña: la de tu cuenta

Pasos:
1. Conéctate por FTP.
2. Entra a la carpeta **`htdocs`** (ESA es la raíz web; todo va ahí dentro).
3. Sube **todo el contenido del proyecto** EXCEPTO:
   - la carpeta `.git/`
   - los archivos dentro de `logs/` (solo deja la carpeta vacía con su `.htaccess`)
4. Que la estructura quede así: `htdocs/index.php`, `htdocs/login.php`, `htdocs/config/`, `htdocs/modulos/`, etc.

## PASO 7 · Crear el archivo `.env` en el servidor

1. En el File Manager (dentro de `htdocs`), crea un archivo nuevo llamado **`.env`**.
2. Pega esto y reemplaza con TUS datos del Paso 3:

   ```
   APP_ENV=production
   APP_DEBUG=0
   BASE_URL=
   DB_HOST=sqlXXX.infinityfree.com
   DB_PORT=3306
   DB_NAME=epiz_XXXXXXX_mercadito
   DB_USER=epiz_XXXXXXX
   DB_PASS=tu_contraseña
   ```

   > **`BASE_URL=` va VACÍO** porque la app vive en la raíz del dominio.

## PASO 8 · Activar HTTPS (SSL gratis)

1. En el panel busca **Free SSL Certificates** (o desde el dashboard de InfinityFree).
2. Emite el certificado para tu subdominio (puede tardar de minutos a un par de horas).
3. Cuando esté activo, prueba `https://tusitio...`.

## PASO 9 · ¡Probar!

1. Abre tu sitio: `https://elmercaditodejulia.infinityfreeapp.com`
2. Entra al panel: `…/login.php`
   - Usuario: `admin@mercadito.com`
   - Contraseña: `Julia2026!`
3. Ve a **Mi Perfil → Cambiar contraseña** y cámbiala de inmediato.
4. Haz una prueba: arma una lista en la web y envíala por WhatsApp.

---

## 🆘 Si algo falla

| Síntoma | Causa probable | Solución |
|---|---|---|
| "El servicio no está disponible" | Datos del `.env` mal | Revisa `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` |
| Páginas sin estilo o enlaces rotos | `BASE_URL` con valor | Debe estar **vacío** (`BASE_URL=`) |
| Error 500 al entrar | PHP < 8 | Cambia a PHP 8 en el panel (Paso 5) |
| Tablas no existen | Faltó importar | Repite el Paso 4 con ambos `.sql` |
| No guarda logs | Carpeta sin permisos | Permisos de `logs/` a 755/775 |

> Para diagnosticar, puedes poner temporalmente `APP_DEBUG=1` en el `.env`
> (verás el error real). **Vuélvelo a `0`** cuando termines.
