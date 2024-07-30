# Используем официальный PHP образ с Apache
FROM php:8.2-apache

# Устанавливаем необходимые инструменты
RUN apt-get update && \
    apt-get install -y \
    poppler-utils \
    tesseract-ocr \
    tesseract-ocr-rus \
    tesseract-ocr-eng \
    imagemagick \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем проект в контейнер
COPY . /var/www/html/

# Устанавливаем рабочую директорию
WORKDIR /var/www/html/

# Устанавливаем зависимости Composer
RUN composer install

# Настройка Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Открываем порт 80
EXPOSE 80

# Запускаем Apache
CMD ["apache2-foreground"]
