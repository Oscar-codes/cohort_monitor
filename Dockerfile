FROM php:8.2-cli

# Instalar dependencias del sistema para extensiones PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP una por una para aislar errores
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mysqli mbstring gd zip intl bcmath xml fileinfo

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar código
WORKDIR /var/www/html
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos para storage
RUN chmod -R 775 storage 2>/dev/null || true
RUN mkdir -p storage/logs storage/cache

# Exponer puerto (Railway inyecta $PORT automáticamente)
EXPOSE ${PORT:-8000}

# Usar router PHP para manejar todas las URLs correctamente
CMD php -S 0.0.0.0:${PORT:-8000} -t public public/index.php
