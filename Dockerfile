# Utiliser PHP 8.2 avec Apache
FROM php:8.2-apache

# Installer les dépendances système et Node.js
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires pour Symfony
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    mbstring

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer mod_rewrite pour Apache
RUN a2enmod rewrite

COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    DirectoryIndex index.php

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>

    <FilesMatch "\.(js|css|png|jpg|jpeg|gif|ico|svg)$">
        LogLevel alert
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

COPY . .

# Installer les dépendances (garder les dépendances dev pour le développement)
RUN composer install --optimize-autoloader --no-scripts

RUN npm ci

# Copier et configurer le script d'entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/var

RUN composer dump-autoload --optimize

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"] 