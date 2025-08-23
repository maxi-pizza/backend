COLOR_GREEN=\033[1;32m
COLOR_YELLOW=\033[1;33m
COLOR_DEFAULT=\033[0m

init: docker-up install-packages backend-migrations post-scripts
post-scripts: success info

docker-up:
	docker compose up -d

backend-migrations:
	docker compose run --rm php php artisan migrate:fresh --seed

install-packages:
	docker compose run --rm composer install

dump-autoload:
	docker compose run --rm composer dump-autoload

success:
	@echo "\n$(COLOR_GREEN)Docker Compose Stack successfully started$(COLOR_DEFAULT)\n"

info:
	@echo "STACK URLs:"
	@echo " - Backend: \t\t http://localhost:8080"
	@echo " - PhpMyAdmin: \t\t http://localhost:8082"
	@echo " - Ngrok: \t\t http://localhost:4040"
	@echo " "

php:
	docker compose run --rm php bash

php-debug:
	docker compose run --rm -e XDEBUG_CONFIG="idekey=PHPSTORM" -e PHP_IDE_CONFIG="serverName=salesbox" php bash

