# ==============================================================================
# Base Stage: PHP 8.5 + Node.js 24 + Nginx + Supervisor
# ==============================================================================
FROM php:8.5-fpm AS base

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    ca-certificates \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        pcntl \
        bcmath \
        zip \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 24
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Nginx: forward logs to stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

WORKDIR /var/www/html

# ==============================================================================
# Development Stage
# ==============================================================================
FROM base AS development

# Development tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    vim \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP config
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx config
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint for development
COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint-dev.sh
RUN chmod +x /usr/local/bin/entrypoint-dev.sh

# Application code is volume-mounted in development
# See docker-compose.yml

EXPOSE 80 5173

CMD ["/usr/local/bin/entrypoint-dev.sh"]

# ==============================================================================
# Production Build Stage: Install dependencies & build assets
# ==============================================================================
FROM base AS production-build

WORKDIR /var/www/html

# Composer dependencies (cache layer)
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Node dependencies & build (cache layer)
COPY src/package.json src/package-lock.json ./
RUN npm ci

# Copy application code
COPY src/ .

# Finalize Composer
RUN composer dump-autoload --optimize --no-dev

# Build frontend assets
RUN npm run build && rm -rf node_modules

# ==============================================================================
# Production Stage
# ==============================================================================
FROM base AS production

# Remove dev packages not needed at runtime
RUN apt-get purge -y curl unzip && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /usr/bin/composer

# PHP config
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx config
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy built application from production-build stage
COPY --from=production-build /var/www/html /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php -r "echo file_get_contents('http://localhost/');" > /dev/null 2>&1 || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
