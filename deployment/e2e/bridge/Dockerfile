# Bridge stub — serves ONLY the alsernetbridge endpoints over a PS stub.
# PHP 7.4 matches the module's declared compatibility floor.
FROM php:7.4-apache

RUN docker-php-ext-install pdo_mysql

# The module and the stub config are bind-mounted by docker-compose:
#   /var/www/html/config/config.inc.php   <- ps-stub bootstrap
#   /var/www/html/modules/alsernetbridge  <- real module (read-only)
