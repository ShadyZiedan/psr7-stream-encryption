FROM php:8.2-cli

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем PHP расширения
RUN docker-php-ext-install \
    zip \
    mbstring

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /app

# Копируем исходный код
COPY . .

# Устанавливаем зависимости
RUN composer install

# Команда по умолчанию
CMD ["php", "samples/example.php"]
