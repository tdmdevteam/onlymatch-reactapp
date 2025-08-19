# Stage 1: Build frontend
FROM node:20 AS frontend-builder
WORKDIR /app

# Copy frontend files
COPY frontend/package*.json ./frontend/
WORKDIR /app/frontend
RUN npm ci --legacy-peer-deps
COPY frontend/ ./
RUN npm run build

# Stage 2: Setup PHP backend and nginx
FROM php:8.2-fpm-alpine

# Install nginx and required PHP extensions
RUN apk add --no-cache nginx supervisor sqlite-dev && \
    docker-php-ext-install pdo pdo_mysql pdo_sqlite

# Create necessary directories
RUN mkdir -p /var/www/html/backend/public/uploads && \
    mkdir -p /var/www/html/frontend && \
    mkdir -p /run/nginx && \
    mkdir -p /var/log/supervisor

# Copy backend files
COPY backend/ /var/www/html/backend/
# Use production bootstrap in container
RUN if [ -f /var/www/html/backend/bootstrap-production.php ]; then \
        mv /var/www/html/backend/bootstrap-production.php /var/www/html/backend/bootstrap.php; \
    fi && \
    chown -R www-data:www-data /var/www/html/backend && \
    chmod -R 755 /var/www/html/backend/public/uploads

# Copy frontend build
COPY --from=frontend-builder /app/frontend/dist /var/www/html/frontend

# Copy nginx configuration for production (Alpine nginx loads from http.d)
COPY nginx.prod.conf /etc/nginx/http.d/default.conf

# Copy supervisor configuration
COPY supervisord.conf /etc/supervisord.conf

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]