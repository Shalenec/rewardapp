FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Install Apache, PHP and required extensions cleanly
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    libapache2-mod-php8.1 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Remove default Apache index page
RUN rm -f /var/www/html/index.html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache virtual host
RUN echo '<VirtualHost *:${PORT}>\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex index.php index.html\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Startup script — sets Railway's dynamic PORT then starts Apache
RUN printf '#!/bin/bash\n\
export PORT=${PORT:-80}\n\
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf\n\
sed -i "s/\\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf\n\
source /etc/apache2/envvars\n\
exec apache2 -D FOREGROUND\n' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]
