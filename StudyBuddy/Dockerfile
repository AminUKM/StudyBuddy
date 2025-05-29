FROM php:8.2-apache

WORKDIR /var/www/html

COPY templates/ /var/www/html/
COPY static/ /var/www/html/static/

# Install PDO MySQL extension for PHP
RUN docker-php-ext-install pdo pdo_mysql

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Allow Apache to serve content
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/allow-access.conf && \
    a2enconf allow-access

# Set ServerName to suppress warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
