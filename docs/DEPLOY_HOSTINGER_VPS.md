# Guía de Despliegue — Hostinger VPS

> Guía paso a paso para montar **Cohort Monitor** (PHP + MySQL) en un VPS de Hostinger.

### Leyenda de ubicación

| Etiqueta | Significado |
|---|---|
| 🖥️ **LOCAL** | Ejecutar en **tu PC Windows** (PowerShell / Terminal) |
| 🌐 **SERVIDOR** | Ejecutar en el **VPS** conectado por SSH |
| 🌍 **NAVEGADOR** | Hacer en el **navegador web** (panel Hostinger, etc.) |

---

## 1. Requisitos previos

| Componente | Versión mínima |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 8.0 / 10.6+ |
| Composer | 2.x |
| Nginx o Apache | Última estable |
| Git | 2.x |
| Certbot (SSL) | Última estable |

---

## 2. Contratar y acceder al VPS

### 2.1 Contratar el VPS en Hostinger — 🌍 NAVEGADOR

1. Ve a [hostinger.com](https://www.hostinger.com) → **VPS Hosting**.
2. Escoge un plan **KVM** (mínimo recomendado: **1 vCPU / 2 GB RAM / 20 GB SSD**).  
   El plan **KVM 1** suele ser suficiente para arrancar.
3. Completa el pago y accede a tu panel **hPanel**.

### 2.2 Configurar el sistema operativo — 🌍 NAVEGADOR

1. En hPanel ve a **VPS** → selecciona tu servidor → **Sistema Operativo**.
2. Selecciona **Ubuntu 24.04 LTS** (recomendado) como sistema operativo.

> **¿Por qué Ubuntu 24.04 LTS?**
>
> | Versión | Tipo | Soporte hasta | Recomendado para servidor |
> |---|---|---|---|
> | Ubuntu 22.04 LTS | LTS | Abr 2027 (ESM 2032) | ✅ Sí — también válida |
> | **Ubuntu 24.04 LTS** | **LTS** | **Abr 2029 (ESM 2034)** | **✅ Recomendada** |
> | Ubuntu 25.04 | Intermedia | Ene 2026 (expirada) | ❌ No — sin soporte LTS |
>
> - **Siempre elige una versión LTS** para producción (5 años de soporte + ESM).
> - Ubuntu 24.04 ya tiene ~2 años de madurez, trae PHP 8.3 nativamente y soporte hasta 2029.
> - Ubuntu 25.04 (y cualquier .04 non-LTS o .10) solo tiene 9 meses de soporte — **no apta para servidores**.
> - Ubuntu 22.04 sigue siendo válida si ya la tienes; los comandos de esta guía funcionan igual.

3. Establece una **contraseña de root** segura (la necesitarás para el primer acceso SSH).
4. Haz clic en **Instalar** y espera ~2 minutos a que el VPS se aprovisione.

### 2.3 Obtener datos de acceso — 🌍 NAVEGADOR

1. En hPanel → **VPS** → **Información del servidor**, copia:
   - **IP pública** del VPS (ej. `154.12.xxx.xxx`)
   - **Puerto SSH** (generalmente `22`)
   - **Usuario:** `root`
2. (Opcional pero recomendado) En **VPS → Llaves SSH**, agrega tu llave pública:
   - 🖥️ **LOCAL** — En tu PC genera una si no la tienes:
     ```bash
     ssh-keygen -t ed25519 -C "tu_email@ejemplo.com"
     ```
   - 🌍 **NAVEGADOR** — Copia el contenido de `~/.ssh/id_ed25519.pub` y pégalo en el panel de Hostinger.

### 2.4 Conectar por SSH — 🖥️ LOCAL

```bash
ssh root@TU_IP_PUBLICA
```

Si configuraste llave SSH, entrará sin pedir contraseña. Si no, usa la contraseña de root que estableciste.

> **Tip:** Si usas Windows, puedes conectar desde **PowerShell**, **Windows Terminal** o **VS Code Remote SSH**.

---

## 3. Configuración inicial del servidor — 🌐 SERVIDOR

```bash
# Actualizar paquetes
apt update && apt upgrade -y

# Instalar utilidades
apt install -y curl wget unzip git software-properties-common ufw

# Crear un usuario de aplicación (no trabajar como root)
adduser deployer
usermod -aG sudo deployer
su - deployer
```

### 3.1 Firewall (UFW)

> **Nota:** No uses `sudo ufw allow 'Nginx Full'` aquí — ese perfil solo existe después de instalar Nginx/Apache.  
> Usa los números de puerto directamente:

```bash
sudo ufw allow OpenSSH        # Puerto 22 (SSH)
sudo ufw allow 80/tcp         # HTTP
sudo ufw allow 443/tcp        # HTTPS
sudo ufw enable
sudo ufw status
```

> Después de instalar Nginx (sección 8) podrás verificar con `sudo ufw app list` que aparece el perfil `Nginx Full`, pero ya no es necesario agregarlo porque los puertos 80 y 443 ya están abiertos.

---

## 4. Instalar PHP 8.2+ — 🌐 SERVIDOR

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath
```

Verifica:

```bash
php -v
```

---

## 5. Instalar MySQL / MariaDB — 🌐 SERVIDOR

### Opción A: MySQL 8

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

### Opción B: MariaDB 10.11

```bash
sudo apt install -y mariadb-server
sudo mysql_secure_installation
```

### 5.1 Crear la base de datos y el usuario

```sql
sudo mysql -u root -p

CREATE DATABASE cohort_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cohort_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURA';
GRANT ALL PRIVILEGES ON cohort_monitor.* TO 'cohort_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> **Nota:** La importación del esquema se hace en la sección 7, después de subir el código al servidor.

---

## 6. Instalar Composer — 🌐 SERVIDOR

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 7. Subir el código al servidor

Tienes **dos opciones** para llevar tu código local al VPS:

### Opción A: Clonar desde GitHub (recomendada) — 🌐 SERVIDOR

Si tu código ya está en GitHub:

```bash
sudo mkdir -p /var/www/cohort_monitor
sudo chown deployer:deployer /var/www/cohort_monitor
cd /var/www
git clone https://github.com/Oscar-codes/cohort_monitor.git cohort_monitor
cd cohort_monitor
```

### Opción B: Subir desde tu PC con SCP (si NO usas GitHub)

🖥️ **LOCAL** — Desde PowerShell en tu PC Windows:

```powershell
# Sube toda la carpeta del proyecto al VPS (excepto vendor/)
scp -r E:\SOFTWARE_PROJECTS\cohort_monitor root@TU_IP_VPS:/var/www/cohort_monitor
```

> **Tip:** Si la carpeta `vendor/` es muy pesada, exclúyela y luego instala con Composer en el servidor.
> Puedes comprimir primero para que sea más rápido:
>
> 🖥️ **LOCAL:**
> ```powershell
> # Comprimir el proyecto
> cd E:\SOFTWARE_PROJECTS
> tar -czf cohort_monitor.tar.gz --exclude='vendor' --exclude='.git' cohort_monitor
> 
> # Subir el archivo comprimido
> scp cohort_monitor.tar.gz root@TU_IP_VPS:/var/www/
> ```
>
> 🌐 **SERVIDOR:**
> ```bash
> cd /var/www
> tar -xzf cohort_monitor.tar.gz
> rm cohort_monitor.tar.gz
> ```

### 7.1 Instalar dependencias — 🌐 SERVIDOR

```bash
cd /var/www/cohort_monitor
composer install --no-dev --optimize-autoloader
```

### 7.2 Configurar el archivo `.env` — 🌐 SERVIDOR

```bash
nano .env
```

Contenido mínimo del `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cohort_monitor
DB_USERNAME=cohort_user
DB_PASSWORD=TU_PASSWORD_SEGURA

DB_CHARSET=utf8mb4
```

> **Importante:** Cambia `TU_PASSWORD_SEGURA` por la contraseña que pusiste en la sección 5.1.

### 7.3 Permisos — 🌐 SERVIDOR

```bash
sudo chown -R deployer:www-data /var/www/cohort_monitor
sudo chmod -R 755 /var/www/cohort_monitor
sudo chmod -R 775 /var/www/cohort_monitor/storage
```

---

## 8. Importar la base de datos

Ahora que el código ya está en el servidor, importa la BD. Tienes **dos caminos**:

### Camino A: Importar el dump completo (TU BD local con todos los datos)

Esto sube toda tu base de datos tal como la tienes en tu PC (tablas + datos reales).

**Paso 1 — Exportar la BD local** — 🖥️ **LOCAL:**

```powershell
# En tu PC, exporta la BD local completa
# Ajusta la ruta de mysqldump según tu XAMPP
E:\FILES_PROGRAMS\DEVS_PROGRAMS\xampp\mysql\bin\mysqldump.exe -u root cohort_monitor > E:\SOFTWARE_PROJECTS\cohort_monitor\database\mi_dump_completo.sql
```

**Paso 2 — Subir el dump al VPS** — 🖥️ **LOCAL:**

```powershell
scp E:\SOFTWARE_PROJECTS\cohort_monitor\database\mi_dump_completo.sql root@TU_IP_VPS:/tmp/
```

**Paso 3 — Importar en el VPS** — 🌐 **SERVIDOR:**

```bash
mysql -u cohort_user -p cohort_monitor < /tmp/mi_dump_completo.sql
rm /tmp/mi_dump_completo.sql
```

### Camino B: Crear la BD desde cero (schema + migraciones)

Si prefieres empezar con una base limpia:

**Paso 1 — Importar el esquema base** — 🌐 **SERVIDOR:**

```bash
cd /var/www/cohort_monitor
mysql -u cohort_user -p cohort_monitor < database/schema.sql
```

> Esto crea las tablas `cohorts` y `students` con datos de ejemplo.

**Paso 2 — Ejecutar TODAS las migraciones (en orden)** — 🌐 **SERVIDOR:**

```bash
mysql -u cohort_user -p cohort_monitor < database/migrations/002_refactor_cohorts_table.sql
mysql -u cohort_user -p cohort_monitor < database/migrations/003_auth_marketing_alerts.sql
mysql -u cohort_user -p cohort_monitor < database/migrations/004_add_b2b_admissions.sql
mysql -u cohort_user -p cohort_monitor < database/migrations/005_add_area_to_cohorts.sql
mysql -u cohort_user -p cohort_monitor < database/migrations/006_seed_cohorts_feb2026.sql
```

> **El orden importa.** Cada migración depende de la anterior.

**Paso 3 — Verificar que todo se creó bien** — 🌐 **SERVIDOR:**

```bash
mysql -u cohort_user -p cohort_monitor -e "SHOW TABLES;"
```

Deberías ver tablas como: `cohorts`, `students`, `users`, `comments`, `marketing_stages`, `audit_log`, `alerts`.

### ¿Cuál camino elegir?

| Situación | Camino |
|---|---|
| Quieres tus datos reales (cohortes, usuarios, etc.) | **A** — dump completo |
| Quieres empezar limpio en producción | **B** — schema + migraciones |

---

## 9. Configurar Apache (ya lo tienes instalado) — 🌐 SERVIDOR

> Ya que instalaste Apache, sigue esta configuración. Si prefieres Nginx, ve a la sección alternativa al final.

```bash
# Habilitar módulos necesarios
sudo a2enmod rewrite headers
```

Crea el archivo de configuración:

```bash
sudo nano /etc/apache2/sites-available/cohort_monitor.conf
```

```apache
<VirtualHost *:80>
    ServerName tudominio.com
    ServerAlias www.tudominio.com
    DocumentRoot /var/www/cohort_monitor/public

    <Directory /var/www/cohort_monitor/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Denegar acceso a directorios sensibles
    <DirectoryMatch "^/var/www/cohort_monitor/(app|bootstrap|config|database|docs|routes|storage|vendor)">
        Require all denied
    </DirectoryMatch>

    ErrorLog ${APACHE_LOG_DIR}/cohort_monitor_error.log
    CustomLog ${APACHE_LOG_DIR}/cohort_monitor_access.log combined
</VirtualHost>
```

Activa el sitio:

```bash
sudo a2ensite cohort_monitor.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

🌍 **NAVEGADOR** — Verifica que funciona visitando `http://TU_IP_VPS` en tu navegador.

---

## 10. SSL con Let's Encrypt (HTTPS) — 🌐 SERVIDOR

> **Requisito:** Necesitas un dominio apuntando a tu VPS antes de este paso (sección 12).
> Si aún no tienes dominio, puedes acceder por IP temporalmente y hacer esto después.

```bash
sudo apt install -y certbot python3-certbot-apache
# Si usas Apache:
sudo certbot --apache -d tudominio.com -d www.tudominio.com

# Si usas Nginx:
# sudo apt install -y certbot python3-certbot-nginx
# sudo certbot --nginx -d tudominio.com -d www.tudominio.com
```

Certbot configurará la redirección HTTP → HTTPS automáticamente.  
La renovación es automática vía timer de systemd; puedes verificar con:

```bash
sudo certbot renew --dry-run
```

---

## 11. Configurar PHP para producción — 🌐 SERVIDOR

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Ajustes sugeridos:

```ini
pm = dynamic
pm.max_children = 15
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500
```

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

```ini
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 60
date.timezone = America/El_Salvador
```

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
sudo systemctl restart php8.2-fpm
```

---

## 12. Apuntar el dominio (DNS) — 🌍 NAVEGADOR

En tu proveedor de dominio (o en Hostinger si compraste allí):

| Tipo | Nombre | Valor | TTL |
|------|--------|-------|-----|
| A    | @      | TU_IP_VPS | 3600 |
| A    | www    | TU_IP_VPS | 3600 |

Espera propagación DNS (~5-30 min).

---

## 13. Seguridad adicional — 🌐 SERVIDOR

### 13.1 Deshabilitar login root por SSH

```bash
sudo nano /etc/ssh/sshd_config
# Cambiar:
# PermitRootLogin yes  →  PermitRootLogin no
# PasswordAuthentication yes  →  PasswordAuthentication no  (si usas llaves SSH)
sudo systemctl restart sshd
```

### 13.2 Fail2Ban (protección contra fuerza bruta)

```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 13.3 Actualizaciones automáticas

```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 14. Script de despliegue (deploy.sh) — 🌐 SERVIDOR

Crea `/var/www/cohort_monitor/deploy.sh`:

```bash
#!/bin/bash
set -e

PROJECT_DIR="/var/www/cohort_monitor"
cd "$PROJECT_DIR"

echo "🔄 Pulling latest changes..."
git pull origin main

echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🔒 Setting permissions..."
sudo chown -R deployer:www-data "$PROJECT_DIR"
sudo chmod -R 775 "$PROJECT_DIR/storage"

echo "♻️ Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm

echo "✅ Deploy completed at $(date)"
```

```bash
chmod +x /var/www/cohort_monitor/deploy.sh
```

Para desplegar actualizaciones: `./deploy.sh`

---

## 15. Backups automáticos de la BD — 🌐 SERVIDOR

Crea un cron job para respaldos diarios:

```bash
sudo mkdir -p /var/backups/cohort_monitor
sudo nano /etc/cron.d/cohort_backup
```

```cron
# Backup diario a las 2:00 AM
0 2 * * * deployer mysqldump -u cohort_user -pTU_PASSWORD_SEGURA cohort_monitor | gzip > /var/backups/cohort_monitor/db_$(date +\%Y\%m\%d).sql.gz
# Limpiar backups mayores a 30 días
0 3 * * * deployer find /var/backups/cohort_monitor -name "*.sql.gz" -mtime +30 -delete
```

---

## 16. Monitoreo básico — 🌐 SERVIDOR

### Logs de la aplicación

```bash
# Nginx
tail -f /var/log/nginx/cohort_monitor_error.log

# PHP
tail -f /var/log/php/error.log

# App logs
tail -f /var/www/cohort_monitor/storage/logs/*.log
```

### Verificar servicios

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql    # o mariadb
```

---

## 17. Checklist final

- [ ] VPS contratado y accesible por SSH
- [ ] Firewall (UFW) configurado — puertos 22, 80, 443
- [ ] PHP 8.2+ instalado con extensiones necesarias
- [ ] MySQL/MariaDB instalado y securizado
- [ ] Base de datos `cohort_monitor` creada con usuario dedicado
- [ ] Esquema y migraciones importados
- [ ] Código clonado en `/var/www/cohort_monitor`
- [ ] `composer install --no-dev` ejecutado
- [ ] `.env` configurado con credenciales de producción
- [ ] Permisos correctos (`storage/` escritura para www-data)
- [ ] Nginx configurado con document root en `public/`
- [ ] SSL/HTTPS activo con Let's Encrypt
- [ ] DNS apuntando a la IP del VPS
- [ ] Backup automático de BD configurado
- [ ] Login root SSH deshabilitado
- [ ] Fail2Ban activo

---

## Configuración alternativa: Nginx — 🌐 SERVIDOR

Si prefieres Nginx en vez de Apache:

```bash
sudo apt install -y nginx
```

```bash
sudo nano /etc/nginx/sites-available/cohort_monitor
```

```nginx
server {
    listen 80;
    server_name tudominio.com www.tudominio.com;

    root /var/www/cohort_monitor/public;
    index index.php;

    charset utf-8;

    access_log /var/log/nginx/cohort_monitor_access.log;
    error_log  /var/log/nginx/cohort_monitor_error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\. {
        deny all;
    }

    location ~* ^/(app|bootstrap|config|database|docs|routes|storage|vendor)/ {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/cohort_monitor /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
# Para SSL:
sudo certbot --nginx -d tudominio.com -d www.tudominio.com
```

---

## Solución de problemas comunes

| Problema | Causa probable | Solución |
|----------|---------------|----------|
| 502 Bad Gateway | PHP-FPM no corre | `sudo systemctl restart php8.2-fpm` |
| 403 Forbidden | Permisos incorrectos | `sudo chown -R deployer:www-data /var/www/cohort_monitor` |
| Página en blanco | Error PHP oculto | Revisa `/var/log/php/error.log` |
| No conecta a BD | Credenciales `.env` | Verifica DB_HOST, DB_USERNAME, DB_PASSWORD |
| CSS/JS no carga | Document root mal | Asegúrate que Nginx apunta a `public/` |
| Sesión no funciona | Permisos storage | `chmod -R 775 storage/` |

---

*Última actualización: Marzo 2026*
