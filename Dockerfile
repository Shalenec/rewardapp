FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Fix MPM conflict — disable mpm_event, enable mpm_prefork
RUN a2dismod mpm_event && a2enmod mpm_prefork

# Enable mod_rewrite for .htaccess
RUN a2enmod rewrite

# Copy project files to Apache web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Allow .htaccess overrides
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Railway uses a dynamic $PORT — update Apache to listen on it at startup
RUN printf '#!/bin/bash\nset -e\nsed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf\nsed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/g" /etc/apache2/sites-enabled/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]
