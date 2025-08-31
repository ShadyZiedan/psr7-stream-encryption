.PHONY: help build run test shell requirements clean install

# Default target
help:
	@echo "Available commands:"
	@echo "  build        - Build Docker image"
	@echo "  run          - Run the application"
	@echo "  test         - Run tests"
	@echo "  shell        - Open shell in container"
	@echo "  requirements - Check PHP requirements"
	@echo "  install      - Install dependencies in container and copy to local"
	@echo "  update       - Update dependencies in container and copy to local"
	@echo "  clean        - Clean up containers and images"

# Build Docker image
build:
	docker-compose build

# Run the application
run: build
	docker-compose run --rm app

# Run tests
test: build
	docker-compose run --rm test

# Open shell in container
shell: build
	docker-compose run --rm shell

# Check PHP requirements
requirements: build
	docker-compose run --rm requirements

# Install dependencies
install: build
	docker-compose run --rm shell composer install
	@echo "Dependencies installed in container. Copying to local project..."
	@docker create --name temp-container i2crm-test-shell
	@docker cp temp-container:/app/vendor ./vendor
	@docker cp temp-container:/app/composer.lock ./composer.lock
	@docker rm temp-container
	@echo "✓ Dependencies copied to local project"

# Clean up
clean:
	docker-compose down --rmi all --volumes --remove-orphans
	docker system prune -f

# Development mode with live reload
dev: build
	docker-compose run --rm --service-ports app

# Run specific test file
test-file:
	@read -p "Enter test file path: " file; \
	docker-compose run --rm shell vendor/bin/phpunit $$file

# Generate coverage report
coverage: build
	docker-compose run --rm shell composer test-coverage

# Run PHPStan analysis
phpstan: build
	docker-compose run --rm shell vendor/bin/phpstan analyse src

# Run all checks
check: test phpstan
	@echo "All checks completed!"

# Update dependencies
update: build
	docker-compose run --rm shell composer update
	@echo "Dependencies updated in container. Copying to local project..."
	@docker create --name temp-container i2crm-test-shell
	@docker cp temp-container:/app/vendor ./vendor
	@docker cp temp-container:/app/composer.lock ./composer.lock
	@docker rm temp-container
	@echo "✓ Dependencies updated and copied to local project"


