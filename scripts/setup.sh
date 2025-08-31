#!/bin/bash

# Скрипт для быстрой настройки проекта WhatsApp PSR-7 Stream Encryption

set -e

echo "🚀 Настройка проекта WhatsApp PSR-7 Stream Encryption"
echo "=================================================="

# Проверяем наличие Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker не установлен. Пожалуйста, установите Docker и Docker Compose."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose не установлен. Пожалуйста, установите Docker Compose."
    exit 1
fi

echo "✅ Docker и Docker Compose найдены"

# Проверяем наличие Make
if ! command -v make &> /dev/null; then
    echo "⚠️  Make не найден. Установите make для использования удобных команд."
    echo "   Или используйте docker-compose команды напрямую."
fi

# Собираем Docker образ
echo "🔨 Сборка Docker образа..."
docker-compose build

# Устанавливаем зависимости
echo "📦 Установка зависимостей..."
docker-compose run --rm shell composer install

# Проверяем требования
echo "🔍 Проверка требований PHP..."
docker-compose run --rm requirements

# Запускаем тесты
echo "🧪 Запуск тестов..."
docker-compose run --rm test

# Запускаем пример
echo "🎯 Запуск примера..."
docker-compose run --rm app

echo ""
echo "🎉 Настройка завершена успешно!"
echo ""
echo "Доступные команды:"
echo "  make run          - Запустить приложение"
echo "  make test         - Запустить тесты"
echo "  make shell        - Открыть shell в контейнере"
echo "  make requirements - Проверить требования"
echo "  make help         - Показать все команды"
echo ""
echo "Или используйте docker-compose напрямую:"
echo "  docker-compose run --rm app"
echo "  docker-compose run --rm test"
echo "  docker-compose run --rm shell"


