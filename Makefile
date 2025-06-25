.PHONY: help build up down logs shell clean dev prod

# Default target
help: ## Show this help message
	@echo "🏪 Toko Beras Docker Commands"
	@echo "=============================="
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Development Commands
dev: ## Start development environment
	@echo "🚀 Starting development environment..."
	docker-compose -f docker-compose.dev.yml up -d
	@echo "✅ Development server running at http://localhost:8000"
	@echo "✅ Vite dev server running at http://localhost:5173"

dev-build: ## Build and start development environment
	@echo "🔨 Building development environment..."
	docker-compose -f docker-compose.dev.yml up -d --build

dev-down: ## Stop development environment
	@echo "🛑 Stopping development environment..."
	docker-compose -f docker-compose.dev.yml down

dev-logs: ## Show development logs
	docker-compose -f docker-compose.dev.yml logs -f

##@ Production Commands
build: ## Build production image
	@echo "🔨 Building production image..."
	docker build -t toko-beras:latest .

prod: ## Start production environment
	@echo "🚀 Starting production environment..."
	docker-compose up -d
	@echo "✅ Production server running at http://localhost:8080"

prod-build: ## Build and start production environment
	@echo "🔨 Building and starting production environment..."
	docker-compose up -d --build

prod-down: ## Stop production environment
	@echo "🛑 Stopping production environment..."
	docker-compose down

prod-logs: ## Show production logs
	docker-compose logs -f

##@ Utility Commands
shell: ## Access application shell
	docker-compose exec app sh

shell-dev: ## Access development application shell
	docker-compose -f docker-compose.dev.yml exec app sh

logs: ## Show application logs
	docker-compose logs -f app

clean: ## Clean up containers and images
	@echo "🧹 Cleaning up..."
	docker-compose down -v
	docker-compose -f docker-compose.dev.yml down -v
	docker system prune -f
	@echo "✅ Cleanup completed"

reset: ## Reset everything (clean + rebuild)
	@echo "🔄 Resetting environment..."
	make clean
	make build
	@echo "✅ Reset completed"

##@ Database Commands
migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Seed database
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migration with seed
	docker-compose exec app php artisan migrate:fresh --seed

##@ Cache Commands
cache-clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

cache-optimize: ## Optimize for production
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

##@ Monitoring Commands
status: ## Show container status
	docker-compose ps

health: ## Check application health
	curl -f http://localhost:8080/health || echo "❌ Application not healthy"

monitor: ## Monitor resource usage
	docker stats
