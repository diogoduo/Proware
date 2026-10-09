# Imagem da Proware para publicar em qualquer hospedagem com Docker (Render, Railway, Fly.io...).
# Teste local:  docker build -t proware .  &&  docker run -p 8080:80 -e PROWARE_DEMO=1 proware
FROM php:8.3-apache

# O PHP oficial já vem com SQLite (pdo_sqlite), mbstring e openssl.
# Aqui: módulos do Apache usados pelo .htaccess e configuração de produção do PHP.
RUN a2enmod rewrite headers expires \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '<Directory /var/www/html>\n    AllowOverride All\n</Directory>\nServerName localhost\nServerTokens Prod\nServerSignature Off\n' \
        > /etc/apache2/conf-enabled/proware.conf

COPY docker/php.ini "$PHP_INI_DIR/conf.d/proware.ini"
COPY docker/entrypoint.sh /usr/local/bin/proware-entrypoint
COPY . /var/www/html/

RUN chmod +x /usr/local/bin/proware-entrypoint \
    && mkdir -p /var/www/html/app/storage \
    && chown -R www-data:www-data /var/www/html/app/storage

EXPOSE 80
ENTRYPOINT ["proware-entrypoint"]
CMD ["apache2-foreground"]
