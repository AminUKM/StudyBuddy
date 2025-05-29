# Use official PHP with Apache
FROM php:8.2-apache

# Set working directory to Apache's root
WORKDIR /var/www/html

# Copy your project files
COPY templates/ /var/www/html/
COPY static/ /var/www/html/static/

# Fix permissions so Apache can access files
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Allow Apache to serve the content
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/allow-access.conf && \
    a2enconf allow-access

# Set ServerName to suppress Apache warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80
