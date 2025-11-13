FROM php:8.1-apache

# Ganti port Apache ke 8080 biar bisa jalan non-root
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Install LDAP + MySQLi
RUN apt-get update \
  && apt-get install -y libldap2-dev \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
  && docker-php-ext-install ldap \
  && docker-php-ext-install mysqli

# Copy project ke web root
COPY . /var/www/html/

# Enable mod_rewrite + permission
RUN a2enmod rewrite

RUN echo '<Directory /var/www/html/>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom-permission.conf \
&& a2enconf custom-permission

# Siapkan folder uploads untuk OpenShift (random UID friendly)
RUN mkdir -p /var/www/html/uploads \
    && mkdir -p /var/www/html/uploads/csv \
    && mkdir -p /var/www/html/uploads/dokumentasi \
    && chgrp -R 0 /var/www/html/uploads \
    && chmod -R g+rwX /var/www/html/uploads

  # Expose port baru
EXPOSE 8080

RUN mkdir -p /var/www/html/uploads/dokumentasi \
    && mkdir -p /var/www/html/uploads/csv \
    && chmod -R 777 /var/www/html/uploads

# Jalankan Apache di foreground
CMD ["apache2-foreground"]
