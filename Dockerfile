FROM php:8.2-apache

WORKDIR /var/www/html

ENV PYTHONUNBUFFERED=1
ENV AI_PYTHON_BINARY=python3

# System dependencies + PostgreSQL support
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    nodejs \
    npm \
    python3 \
    python3-pip \
    postgresql-client \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql zip gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Report downloads depend on immutable Word templates bundled with the release.
# Fail the image build instead of deploying an application that returns an HTML
# error page when SIR, PIR, or FIR is downloaded.
RUN test -f storage/app/private/templates/sir-template-follow.docx \
    && test -f storage/app/private/templates/progress-investigation-template-edited.docx \
    && test -f storage/app/private/templates/final-investigation-template-edited.docx \
    && test -f storage/app/private/templates/after-activity-report-template.docx

# Install PHP dependencies
RUN composer config -g github-protocols https \
    && composer clear-cache \
    && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Build frontend
RUN if [ -f package.json ]; then npm install && npm run build; fi

# Python dependencies
RUN if [ -f requirements.txt ]; then \
        pip3 install --break-system-packages --no-cache-dir -r requirements.txt; \
    fi

RUN if [ -f python/requirements.txt ]; then \
        pip3 install --break-system-packages --no-cache-dir -r python/requirements.txt; \
    fi

# Laravel permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/capstone-ai

# Apache + Render startup
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/render-start.sh /usr/local/bin/render-start.sh
COPY docker/render-worker.sh /usr/local/bin/render-worker.sh

RUN chmod +x /usr/local/bin/render-start.sh /usr/local/bin/render-worker.sh

EXPOSE 80

CMD ["/usr/local/bin/render-start.sh"]
