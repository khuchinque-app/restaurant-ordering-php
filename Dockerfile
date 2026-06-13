FROM php:8.2-apache

# Install SQLite3 extension
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite alias

# Replace default port config (remove port 80, add our four ports)
RUN printf 'Listen 7500\nListen 7501\nListen 7504\nListen 7505\n' > /etc/apache2/ports.conf

# Install our site config, disable the default one
COPY .docker/apache.conf /etc/apache2/sites-available/restaurant.conf
RUN a2dissite 000-default && a2ensite restaurant

# Copy application source
COPY . /var/www/html/

# Create persistent data directory for the SQLite database
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/data

# Copy and enable the entrypoint (auto-init DB on first run)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 7500 7501 7504 7505

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
