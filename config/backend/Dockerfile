FROM php:8.3-apache

# Cài thêm extension cần thiết
RUN apt-get update \
    && apt-get install -y \
        curl \
        zip \
        unzip \
        git \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        libexif-dev \
        tzdata \
    && docker-php-ext-install \
        bcmath \
        gd \
        opcache \
        sockets \
        zip \
        exif \
        pdo_mysql \
        mysqli \
    && rm -rf /var/lib/apt/lists/*

# Cài Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Cài Composer
ENV COMPOSER_VERSION=2.7.7
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION

# Bật rewrite module
RUN a2enmod rewrite

# Thêm config cho phép .htaccess override
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Default command
CMD ["apache2-foreground"]
