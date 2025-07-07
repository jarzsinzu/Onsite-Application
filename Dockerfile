FROM php:8.2.12-apache

RUN docker-php-ext-install mysqli

RUN apt-get update \
  && apt-get install -y libldap2-dev \
  && docker-php-ext-configure ldap \
  && docker-php-ext-install ldap \
  && docker-php-ext-install mysqli

COPY . /var/www/html/

RUN a2enmod rewrite

RUN echo '<Directory /var/www/html/>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom-permission.conf \
&& a2enconf custom-permission

RUN chown -R www-data:www-data /var/www/html/uploads

