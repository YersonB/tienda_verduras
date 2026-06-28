# 🧺 El mercadito de Julia

Sistema web para el servicio **"Nosotros hacemos tu mercado"**: una landing pública donde el
cliente arma su lista y la envía por WhatsApp, más un panel interno de gestión (inventario,
ventas, solicitudes, usuarios) para el personal.

- **Stack:** PHP 8 + MySQL/MariaDB (PDO) + Bootstrap 5. Sin dependencias que compilar.
- **Frente público:** `index.php` (landing), `solicitar.php` (pedido detallado).
- **Panel interno:** `login.php` → `modulos/` (dashboard, productos, ventas, solicitudes, usuarios, perfil).

---

## 🖥️ Ejecutar en local (XAMPP)

1. Copia el proyecto en `htdocs/tienda_verduras`.
2. Inicia **Apache** y **MySQL** en XAMPP.
3. En phpMyAdmin crea la base `tienda_verduras` e importa, en este orden:
   - `sql/esquema_completo.sql`
   - `sql/datos_iniciales.sql`
4. Entra a `http://localhost/tienda_verduras/`.
   - Panel: `http://localhost/tienda_verduras/login.php`
   - Usuario: `admin@mercadito.com` · Contraseña: `Julia2026!`

> En local no necesitas archivo `.env`: los valores por defecto ya apuntan a XAMPP
> (`localhost`, usuario `root`, sin contraseña, `BASE_URL=/tienda_verduras`).

---

## ☁️ Desplegar en la nube

La configuración se hace con **variables de entorno** (o un archivo `.env`). El código no
cambia entre local y nube.

### 1. Variables que debes definir

| Variable | Para qué | Ejemplo en la nube |
|---|---|---|
| `APP_ENV` | Entorno | `production` |
| `APP_DEBUG` | Mostrar errores (0 en producción) | `0` |
| `BASE_URL` | Prefijo de rutas. **Vacío** si la app vive en la raíz del dominio | *(vacío)* |
| `DB_HOST` | Host de la base de datos | `localhost` o el que te den |
| `DB_PORT` | Puerto MySQL | `3306` |
| `DB_NAME` | Nombre de la base | `el_mercadito` |
| `DB_USER` | Usuario de la base | `u123_julia` |
| `DB_PASS` | Contraseña de la base | *(la tuya)* |

Copia `.env.example` como `.env` y complétalo (o usa el panel de variables del hosting).

### 2. Crea la base de datos e impórtala

En el panel de tu hosting (phpMyAdmin / consola):
1. Crea una base de datos vacía y un usuario con permisos sobre ella.
2. Importa **`sql/esquema_completo.sql`** (estructura).
3. Importa **`sql/datos_iniciales.sql`** (admin + canastas).

### 3. Sube los archivos

- **Con Git:** conecta el repo de GitHub al hosting/servicio.
- **Con FTP / Administrador de archivos:** sube todo *excepto* `.git/`, `logs/*.log`,
  `logs/*.json` y `.env` (ese se crea en el servidor).

### 4. Ajustes finales

- Asegúrate de que la carpeta **`logs/` tenga permisos de escritura** (chmod 775).
- Verifica que el sitio cargue por **HTTPS** (casi todos los hosts lo activan gratis con Let's Encrypt).
- Entra al panel, ve a **Mi Perfil → Cambiar contraseña** y cambia la del admin.

---

## ✅ Lista de verificación (lo que haces TÚ, sin programar)

- [ ] Elegir y contratar un **hosting con PHP 8 + MySQL** (o un servicio tipo Railway/Render).
- [ ] (Opcional) Comprar un **dominio**.
- [ ] Crear la **base de datos** y anotar host, nombre, usuario y contraseña.
- [ ] Crear el archivo **`.env`** con esos datos (basándote en `.env.example`).
- [ ] Importar **`esquema_completo.sql`** y luego **`datos_iniciales.sql`**.
- [ ] Subir el código (Git o FTP).
- [ ] Confirmar **HTTPS** activo.
- [ ] Iniciar sesión y **cambiar la contraseña del admin**.
- [ ] Confirmar que el **número de WhatsApp** es el correcto
      (`includes/config_sitio.php` → `WHATSAPP_NUMERO`).

---

## 🔐 Notas de seguridad

- El `.env` y las carpetas `config/`, `includes/`, `logs/`, `sql/` están protegidas del acceso
  web directo mediante `.htaccess` (Apache).
- Contraseñas con **bcrypt**, protección **CSRF**, control de **roles**, límite de intentos de
  login y cabeceras HTTP defensivas ya vienen incluidos.
- Cambia la contraseña del admin apenas entres y usa una `DB_PASS` fuerte.
