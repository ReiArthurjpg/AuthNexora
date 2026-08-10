FROM php:8.2-apache

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Extensões PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ativar módulos necessários do Apache
RUN a2enmod rewrite headers

# Diretório da aplicação
WORKDIR /var/www

# Copiar aplicação
COPY . /var/www

# DocumentRoot -> public
ENV APACHE_DOCUMENT_ROOT=/var/www/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Instalar dependências PHP
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev

# Permissões
RUN chown -R www-data:www-data /var/www

# Render usa HTTP
EXPOSE 10000

# Apache deve escutar na porta do Render
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf && \
    sed -i 's/:80>/:10000>/g' /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]