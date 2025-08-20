FROM php:8.4-fpm-alpine AS php

RUN docker-php-ext-install pdo_mysql

RUN install -o www-data -g www-data -d /var/www/upload/image/

# PHPのアップロードサイズ制限を直接iniで渡す
RUN echo "upload_max_filesize=5M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=6M" >> /usr/local/etc/php/conf.d/uploads.ini
