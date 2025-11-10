.PHONY: help install build up down restart logs shell test migrate seed fresh refresh clear-cache

# Default target
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m # No Color

##@ General

help: ## Display this help message
	@echo "$(BLUE)Available commands:$(NC)"
	@awk 'BEGIN {FS = ":.*##"; printf "\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(YELLOW)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Docker Commands

build: ## Build Docker containers
	@echo "$(BLUE)Building Docker containers...$(NC)"
	docker-compose build

build-no-cache: ## Build Docker containers without cache
	@echo "$(BLUE)Building Docker containers (no cache)...$(NC)"
	docker-compose build --no-cache

up: ## Start all Docker containers
	@echo "$(BLUE)Starting Docker containers...$(NC)"
	docker-compose up -d

down: ## Stop all Docker containers
	@echo "$(BLUE)Stopping Docker containers...$(NC)"
	docker-compose down

restart: ## Restart all Docker containers
	@echo "$(BLUE)Restarting Docker containers...$(NC)"
	docker-compose restart

restart-app: ## Restart app container only
	@echo "$(BLUE)Restarting app container...$(NC)"
	docker-compose restart app

ps: ## Show running containers
	docker-compose ps

logs: ## Show logs from all containers
	docker-compose logs -f

logs-app: ## Show logs from app container
	docker-compose logs -f app

logs-worker: ## Show logs from worker container
	docker-compose logs -f worker

logs-supervisor: ## Show logs from supervisor container
	docker-compose logs -f supervisor

##@ Laravel Commands

shell: ## Open shell in app container
	docker-compose exec app bash

tinker: ## Open Laravel Tinker
	docker-compose exec app php artisan tinker

install: ## Install PHP dependencies
	@echo "$(BLUE)Installing PHP dependencies...$(NC)"
	docker-compose exec app composer install

update: ## Update PHP dependencies
	@echo "$(BLUE)Updating PHP dependencies...$(NC)"
	docker-compose exec app composer update

##@ Database Commands

migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	docker-compose exec app php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations
	@echo "$(YELLOW)WARNING: This will drop all tables!$(NC)"
	docker-compose exec app php artisan migrate:fresh

migrate-rollback: ## Rollback the last migration batch
	docker-compose exec app php artisan migrate:rollback

migrate-status: ## Show migration status
	docker-compose exec app php artisan migrate:status

seed: ## Run database seeders
	@echo "$(BLUE)Running database seeders...$(NC)"
	docker-compose exec app php artisan db:seed

seed-campaign: ## Run CampaignSeeder only
	@echo "$(BLUE)Running CampaignSeeder...$(NC)"
	docker-compose exec app php artisan db:seed --class=CampaignSeeder

seed-news-source: ## Run NewsSourceSeeder only
	@echo "$(BLUE)Running NewsSourceSeeder...$(NC)"
	docker-compose exec app php artisan db:seed --class=NewsSourceSeeder

seed-crawl-job: ## Run CrawlJobSeeder only
	@echo "$(BLUE)Running CrawlJobSeeder...$(NC)"
	docker-compose exec app php artisan db:seed --class=CrawlJobSeeder

seed-campaign-source: ## Run CampaignSourceSeeder only
	@echo "$(BLUE)Running CampaignSourceSeeder...$(NC)"
	docker-compose exec app php artisan db:seed --class=CampaignSourceSeeder

fresh: ## Fresh migration and seed
	@echo "$(YELLOW)WARNING: This will drop all tables!$(NC)"
	docker-compose exec app php artisan migrate:fresh --seed

refresh: ## Refresh migrations and seed
	@echo "$(YELLOW)WARNING: This will rollback all migrations!$(NC)"
	docker-compose exec app php artisan migrate:refresh --seed

##@ Cache Commands

clear-cache: ## Clear all Laravel caches
	@echo "$(BLUE)Clearing all caches...$(NC)"
	docker-compose exec app php artisan optimize:clear

clear-config: ## Clear config cache
	docker-compose exec app php artisan config:clear

clear-route: ## Clear route cache
	docker-compose exec app php artisan route:clear

clear-view: ## Clear view cache
	docker-compose exec app php artisan view:clear

cache-config: ## Cache configuration
	docker-compose exec app php artisan config:cache

cache-route: ## Cache routes
	docker-compose exec app php artisan route:cache

##@ Testing

test: ## Run PHPUnit tests
	@echo "$(BLUE)Running tests...$(NC)"
	docker-compose exec app php artisan test

test-unit: ## Run unit tests only
	docker-compose exec app php artisan test --testsuite=Unit

test-feature: ## Run feature tests only
	docker-compose exec app php artisan test --testsuite=Feature

test-campaign: ## Run Campaign tests only
	docker-compose exec app php artisan test --filter Campaign

##@ Swagger/API Documentation

swagger-generate: ## Generate Swagger documentation
	@echo "$(BLUE)Generating Swagger documentation...$(NC)"
	docker-compose exec app php artisan l5-swagger:generate

swagger-clear: ## Clear Swagger cache and regenerate
	docker-compose exec app php artisan config:clear && \
	docker-compose exec app php artisan l5-swagger:generate

##@ Log Viewer

log-viewer-publish: ## Publish Log Viewer assets
	@echo "$(BLUE)Publishing Log Viewer assets...$(NC)"
	docker-compose exec app php artisan vendor:publish --tag=log-viewer-assets --force
	@echo "$(GREEN)Log Viewer assets published!$(NC)"

log-viewer-config: ## Publish Log Viewer config
	@echo "$(BLUE)Publishing Log Viewer config...$(NC)"
	docker-compose exec app php artisan vendor:publish --tag=log-viewer-config --force
	@echo "$(GREEN)Log Viewer config published!$(NC)"

##@ Queue Commands

queue-work: ## Start queue worker
	docker-compose exec app php artisan queue:work

queue-failed: ## Show failed jobs
	docker-compose exec app php artisan queue:failed

queue-retry: ## Retry failed jobs
	docker-compose exec app php artisan queue:retry all

horizon: ## Access Horizon dashboard (info only)
	@echo "$(BLUE)Horizon dashboard: http://localhost:8080/horizon$(NC)"

log-viewer: ## Access Log Viewer (info only)
	@echo "$(BLUE)Log Viewer: http://localhost:8080/log-viewer$(NC)"

scheduler-status: ## Check scheduler status and list scheduled tasks
	@echo "$(BLUE)Checking scheduler status...$(NC)"
	@docker-compose ps scheduler
	@echo ""
	@echo "$(BLUE)Scheduled tasks:$(NC)"
	@docker-compose exec scheduler php artisan schedule:list

scheduler-logs: ## Show scheduler logs
	@echo "$(BLUE)Showing scheduler logs...$(NC)"
	@docker-compose logs -f scheduler

logs-laravel: ## Show Laravel application logs
	@echo "$(BLUE)Showing Laravel logs...$(NC)"
	@docker-compose exec app tail -f storage/logs/laravel.log

logs-horizon: ## Show Horizon logs
	@echo "$(BLUE)Showing Horizon logs...$(NC)"
	@docker-compose exec app tail -f storage/logs/horizon.log

logs-worker: ## Show worker logs
	@echo "$(BLUE)Showing worker logs...$(NC)"
	@docker-compose exec app tail -f storage/logs/worker.log

##@ Development

setup: build up migrate seed ## Complete setup: build, start, migrate, and seed
	@echo "$(GREEN)Setup complete!$(NC)"
	@echo "$(BLUE)Application: http://localhost:8080$(NC)"
	@echo "$(BLUE)Swagger UI: http://localhost:8080/api/documentation$(NC)"
	@echo "$(BLUE)Horizon: http://localhost:8080/horizon$(NC)"
	@echo "$(BLUE)Log Viewer: http://localhost:8080/log-viewer$(NC)"

dev: up ## Start development environment
	@echo "$(GREEN)Development environment started!$(NC)"
	@echo "$(BLUE)Application: http://localhost:8080$(NC)"

clean: down ## Stop containers and clean up
	@echo "$(BLUE)Cleaning up...$(NC)"
	docker-compose down -v

##@ Utilities

routes: ## List all routes
	docker-compose exec app php artisan route:list

routes-api: ## List API routes only
	docker-compose exec app php artisan route:list --path=api

key-generate: ## Generate application key
	docker-compose exec app php artisan key:generate

optimize: ## Optimize Laravel for production
	docker-compose exec app php artisan optimize

storage-link: ## Create storage link
	docker-compose exec app php artisan storage:link

permissions: ## Fix storage permissions
	docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
	docker-compose exec app chmod -R 775 storage bootstrap/cache

##@ Database Access

db-connect: ## Connect to PostgreSQL database
	docker-compose exec postgres psql -U laravel -d laravel

db-dump: ## Dump database to file
	@echo "$(BLUE)Creating database dump...$(NC)"
	docker-compose exec postgres pg_dump -U laravel laravel > dump_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Database dumped!$(NC)"

##@ Quick Actions

quick-reload: clear-cache swagger-generate ## Quick reload: clear cache and regenerate Swagger
	@echo "$(GREEN)Quick reload complete!$(NC)"

rebuild-app: build-no-cache restart-app ## Rebuild and restart app container
	@echo "$(GREEN)App container rebuilt and restarted!$(NC)"

