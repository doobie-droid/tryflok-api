FROM php:8.0.5-fpm
ARG user
ARG uid
RUN apt-get update 
RUN apt-get install -y default-mysql-client
RUN apt-get install -y zlib1g-dev libsqlite3-dev
RUN apt-get install -y libpng-dev libjpeg62-turbo-dev 
RUN apt-get install -y libmagickwand-dev
RUN apt-get install -y libjpeg-dev libfreetype6-dev 
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip
RUN apt-get install -y ffmpeg

RUN pecl install redis
RUN docker-php-ext-enable redis

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN apt-get update
RUN apt install libsodium-dev



RUN docker-php-ext-configure sodium
RUN docker-php-ext-install sodium
RUN pecl install -f libsodium
RUN docker-php-ext-enable sodium

RUN pecl install imagick
RUN docker-php-ext-enable imagick

RUN docker-php-ext-install pdo_mysql 
RUN docker-php-ext-install pcntl
RUN docker-php-ext-enable pcntl
RUN docker-php-ext-install exif
RUN docker-php-ext-install bcmath
RUN docker-php-ext-configure gd --with-jpeg && docker-php-ext-install gd
RUN docker-php-ext-enable gd
RUN docker-php-ext-install zip
RUN docker-php-ext-install opcache

# Install SUPERVISOR
RUN apt-get update \
    && apt-get install -y supervisor


# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

USER $user
