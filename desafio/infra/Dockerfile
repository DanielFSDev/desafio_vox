FROM ubuntu:24.04

RUN apt update && apt install -y \
    software-properties-common \
    curl \
    git \
    zip \
    unzip \
    nginx \
    supervisor

RUN apt install -y \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-mysql \
    php8.3-curl \
    php8.3-zip \
    php8.3-intl \
    php8.3-gd \
    php8.3-bcmath \
    php8.3-soap \
    php8.3-opcache

RUN apt-get update && apt-get install -y \
    netcat-openbsd \
    default-mysql-client \
    libreoffice \
    vim \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN usermod -s /bin/bash www-data

RUN sed -i 's/listen = .*/listen = 9000/' /etc/php/8.3/fpm/pool.d/www.conf
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 30M/' /etc/php/8.3/fpm/php.ini
RUN sed -i 's/post_max_size = 8M/post_max_size = 30M/' /etc/php/8.3/fpm/php.ini

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 80

COPY nginx.conf /etc/nginx/sites-available/default

CMD ["sh", "-c", "service php8.3-fpm start && nginx -g 'daemon off;'"]
