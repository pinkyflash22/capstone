FROM php:8.2-apache

WORKDIR /var/www/html

ENV PYTHONUNBUFFERED=1
ENV AI_PYTHON_BINARY=python3

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    nodejs \
    npm \
    python3 \
    python3-pip \
    default-mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer config -g github-protocols https \
    && composer clear-cache \
    && composer install --no-dev --optimize-autoloader --no-interaction --prefer-source

RUN if [ -f package.json ]; then npm install && npm run build; fi

RUN if [ -f requirements.txt ]; then pip3 install --break-system-packages --no-cache-dir -r requirements.txt; fi

RUN if [ -f python/requirements.txt ]; then pip3 install --break-system-packages --no-cache-dir -r python/requirements.txt; fi

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/render-start.sh /usr/local/bin/render-start.sh

RUN chmod +x /usr/local/bin/render-start.sh

EXPOSE 80

CMD ["/usr/local/bin/render-start.sh"]
