FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite
RUN sed -ri -e 's!/var/www/html!<Directory /var/www/html>\n\tAllowOverride All\n</Directory>\n\n&!' /etc/apache2/apache2.conf
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader || composer update

RUN echo "\n# ==== Rewrite rules for API ====" >> /var/www/html/.htaccess \
 && echo "RewriteEngine On" >> /var/www/html/.htaccess \
 && echo "RewriteCond %{REQUEST_URI} ^/api" >> /var/www/html/.htaccess \
 && echo "RewriteRule ^api/(.*)$ index.php [QSA,L]" >> /var/www/html/.htaccess

RUN composer require --dev zircote/swagger-php:^4.0 symfony/console doctrine/annotations
RUN vendor/bin/openapi app --output swagger.json

EXPOSE 80
CMD ["apache2-foreground"]
