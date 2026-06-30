FROM php:8.4-fpm

# Instalar dependencias del sistema (incluyendo herramientas de red)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    wget \
    libaio1t64 \
    libaio-dev \
    net-tools \
    iputils-ping \
    dnsutils \
    telnet \
    jpegoptim \
    optipng \
    pngquant \
    webp

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Crear symlink para libaio (compatible con x86_64 y arm64)
RUN find /usr/lib -name "libaio.so.1t64" -exec sh -c 'ln -sf "$1" "${1%t64}"' _ {} \; || true

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar extensión Redis (phpredis)
RUN pecl install redis && docker-php-ext-enable redis

# Copiar archivos de Oracle Instant Client 11.2 desde directorio local
COPY docker/oracle/instantclient-basic-linux.x64-11.2.0.4.0.zip /tmp/
COPY docker/oracle/instantclient-sdk-linux.x64-11.2.0.4.0.zip /tmp/

# Instalar Oracle Instant Client 11.2 (compatible con Oracle 9i)
RUN mkdir /opt/oracle && \
    cd /opt/oracle && \
    unzip /tmp/instantclient-basic-linux.x64-11.2.0.4.0.zip && \
    unzip /tmp/instantclient-sdk-linux.x64-11.2.0.4.0.zip && \
    rm -f /tmp/instantclient-basic-linux.x64-11.2.0.4.0.zip /tmp/instantclient-sdk-linux.x64-11.2.0.4.0.zip && \
    cd /opt/oracle/instantclient_11_2 && \
    ln -s libclntsh.so.11.1 libclntsh.so && \
    echo /opt/oracle/instantclient_11_2 > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

# Instalar OCI8 via PECL (versión compatible con PHP 8.4)
RUN echo "instantclient,/opt/oracle/instantclient_11_2" | pecl install oci8-3.4.0 && \
    docker-php-ext-enable oci8

# Configurar php.ini para mejor conectividad con Oracle
# connection_class vacío y ping_interval -1: evita timeout TCP al iniciar workers
# cuando Oracle no es alcanzable (zz-custom.ini mounted by docker-compose overrides these)
RUN echo "oci8.max_persistent = 20" >> /usr/local/etc/php/conf.d/oci8.ini && \
    echo "oci8.persistent_timeout = 3600" >> /usr/local/etc/php/conf.d/oci8.ini && \
    echo "oci8.ping_interval = -1" >> /usr/local/etc/php/conf.d/oci8.ini && \
    echo "oci8.connection_class =" >> /usr/local/etc/php/conf.d/oci8.ini && \
    echo "default_socket_timeout = 30" >> /usr/local/etc/php/conf.d/docker-php-ext-oci8.ini

# Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario para la aplicación
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Copiar archivos de la aplicación
COPY --chown=www:www src /var/www

# Cambiar directorio de trabajo
WORKDIR /var/www

# Cambiar al usuario www
USER www

# Exponer puerto 9000
EXPOSE 9000

CMD ["php-fpm"]
