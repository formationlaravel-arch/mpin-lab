FROM php:8.2-cli-alpine
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/html
COPY index.php .
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]
