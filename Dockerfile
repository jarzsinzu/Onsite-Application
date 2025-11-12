FROM php:8.2.12-apache

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

# Pastikan folder uploads bisa diakses
RUN mkdir -p /var/www/html/uploads \
  && chown -R www-data:www-data /var/www/html/uploads
