#!/bin/sh
# Inicia o Apache na porta informada pela hospedagem em $PORT (o Render usa 10000).
# Sem a variável, usa a porta 80.
set -e

PORTA="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORTA}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORTA}>/" /etc/apache2/sites-available/000-default.conf

exec docker-php-entrypoint "$@"
