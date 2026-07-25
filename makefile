.DEFAULT_GOAL := help

# Inventory Assessment - Docker Helper

.PHONY: help build up up-build down restart ps \
        logs logs-app logs-nginx logs-mysql logs-worker \
        sh sh-nginx sh-mysql \
        install \
        key migrate migrate-fresh seed \
        cache-clear permission \
        queue-restart wait-db \
         clean nuke reset install-all

help: ## Tampilkan daftar perintah yang tersedia
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

#  Lifecycle Dasar 
build: ## Build ulang image (tanpa cache)
	docker compose build --no-cache

up: ## Jalankan semua container (background)
	docker compose up -d

up-build: ## Build lalu jalankan semua container
	docker compose up -d --build

down: ## Hentikan dan hapus semua container (volume tetap ada)
	docker compose down

restart: ## Restart semua container
	docker compose restart

ps: ## Lihat status container
	docker compose ps

#  Logs 
logs: ## Tail log semua service
	docker compose logs -f --tail=100

logs-app: ## Tail log service app (PHP-FPM)
	docker compose logs -f --tail=100 app

logs-nginx: ## Tail log service nginx
	docker compose logs -f --tail=100 nginx

logs-mysql: ## Tail log service mysql
	docker compose logs -f --tail=100 mysql

logs-worker: ## Tail log service worker (Queue)
	docker compose logs -f --tail=100 worker

#  Shell Access
sh: ## Masuk ke shell container app (bash)
	docker compose exec app bash

sh-nginx: ## Masuk ke shell container nginx
	docker compose exec nginx sh

sh-mysql: ## Masuk ke shell container mysql
	docker compose exec mysql bash

#  Laravel & Composer 
install: ## Jalankan composer install di dalam container
	docker compose exec app composer install

key: ## Generate application key
	docker compose exec app php artisan key:generate

migrate: ## Jalankan migration
	docker compose exec app php artisan migrate

migrate-fresh: ## Reset database lalu migrate ulang (data hilang!)
	docker compose exec app php artisan migrate:fresh

make-migration: ## Buat file migration baru: make make-migration name=create_xxx_table
	docker compose exec app php artisan make:migration $(name)

seed: ## Jalankan database seeder (Demo data)
	docker compose exec app php artisan db:seed

cache-clear: ## Bersihkan semua cache Laravel
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

permission: ## Perbaiki permission storage & bootstrap/cache
	docker compose exec app chmod -R 775 storage bootstrap/cache




#  Queue & Worker
queue-restart: ## Restart queue worker (berguna setelah deploy code baru)
	docker compose restart worker

#  Database Helpers
wait-db: ## Tunggu sampai MySQL benar-benar siap menerima koneksi
	@echo " Menunggu MySQL siap..."
	@until docker compose exec -T mysql mysqladmin ping -h localhost -uroot -proot --silent; do \
		sleep 2; \
		echo "   masih menunggu MySQL..."; \
	done
	@echo " MySQL siap."


clean: ## Hentikan container & hapus volume (database ikut terhapus)
	docker compose down -v

nuke: ## Bersih total: container, volume, image lokal project ini, dan build cache
	docker compose down -v --rmi local
	docker builder prune -f

#  Shortcut Kombinasi 
reset: clean up-build key permission wait-db migrate ## Reset total lalu setup ulang dari awal
	@echo " Reset selesai. Akses di http://localhost:8000"