FROM php:8.2-cli

# Dépendances système + extensions PHP nécessaires à Laravel et aux packages
# utilisés (dompdf et maatwebsite/excel ont besoin de gd, intl, bcmath)
RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libsqlite3-dev libonig-dev \
        libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip gd intl bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY start.sh /start.sh
RUN chmod +x /start.sh

# Render fournit le port via la variable $PORT au démarrage (pas au build),
# donc le port réel est décidé dans start.sh, pas ici.
EXPOSE 10000

CMD ["/start.sh"]
