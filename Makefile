.PHONY: setup

setup:
	@echo "Laravel bağımlılıkları kuruluyor."
	docker-compose run --rm app composer install

	@echo "Container’lar başlatılıyor."
	docker-compose up -d

	@echo "Veritabanı migrate ediliyor."
	docker-compose exec app php artisan migrate

	@echo "Laravel passport install ediliyor."
	docker-compose exec app php artisan passport:client --personal --no-interaction

	@echo "Veriler seed ediliyor."
	docker-compose exec app php artisan db:seed --class="App\\Infrastructure\\Database\\Seeders\\DatabaseSeeder"

	@echo "Laravel setup tamamlandı. http://localhost:8080"

migrate:
	docker-compose exec app php artisan migrate

refresh:
	@echo "Veritabanı migrate ediliyor ve veriler seed ediliyor."
	docker-compose exec app php artisan migrate:fresh --seed --seeder="App\\Infrastructure\\Database\\Seeders\\DatabaseSeeder"

	@echo "Laravel passport install ediliyor."
	docker-compose exec app php artisan passport:client --personal --no-interaction

	@echo "DB refresh tamamlandı."
