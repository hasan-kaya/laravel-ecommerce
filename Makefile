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

	@echo "Elasticsearch index oluşturuluyor..."
	docker-compose exec app php artisan elasticsearch:index --recreate

	@echo "✅ Laravel setup tamamlandı. http://localhost:8080"

migrate:
	docker-compose exec app php artisan migrate

refresh:
	@echo "Veritabanı migrate ediliyor ve veriler seed ediliyor."
	docker-compose exec app php artisan migrate:fresh --seed --seeder="App\\Infrastructure\\Database\\Seeders\\DatabaseSeeder"

	@echo "Laravel passport install ediliyor."
	docker-compose exec app php artisan passport:client --personal --no-interaction

	@echo "Elasticsearch index oluşturuluyor..."
	docker-compose exec app php artisan elasticsearch:index --recreate

	@echo "✅ DB refresh ve Elasticsearch indexing tamamlandı."

elasticsearch-index:
	@echo "Elasticsearch index oluşturuluyor ve ürünler indeksleniyor..."
	docker-compose exec app php artisan elasticsearch:index --recreate
	@echo "✅ Elasticsearch indexing tamamlandı."

elasticsearch-reindex:
	@echo "Ürünler yeniden indeksleniyor..."
	docker-compose exec app php artisan elasticsearch:index
	@echo "✅ Elasticsearch reindexing tamamlandı."

start:
	docker-compose up -d
	@echo "✅ Container'lar başlatıldı."

optimize:
	@echo "Laravel cache optimize ediliyor..."
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app php artisan event:cache
	@echo "✅ Cache optimize edildi!"

cache-clear:
	@echo "Tüm cache'ler temizleniyor..."
	docker-compose exec app php artisan optimize:clear
	@echo "✅ Cache temizlendi!"
